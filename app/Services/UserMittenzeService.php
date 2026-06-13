<?php

namespace App\Services;

use App\Models\Anagrafica;
use App\Models\mittenza;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserMittenzeService
{
    public function ensureForUser(User $user): void
    {
        $a = $user->anagrafica;
        if (! $a) {
            return;
        }

        if ($user->mittenze()->exists()) {
            return;
        }

        DB::transaction(function () use ($user, $a): void {
            mittenza::query()->create($this->payloadFromAnagrafica(
                $user,
                $a,
                null,
                isPreferito: true,
                isFatturazione: true,
            ));
        });
    }

    /**
     * Allinea la riga “sede fatturazione” alla revisione anagrafica indicata (es. ultima appena salvata dal profilo).
     */
    public function syncFatturazioneRow(User $user, Anagrafica $anagrafica, ?int $idComune = null): void
    {
        DB::transaction(function () use ($user, $anagrafica, $idComune): void {
            $row = $user->mittenze()->where('is_fatturazione', true)->first();
            $payload = $this->payloadFromAnagrafica(
                $user,
                $anagrafica,
                $idComune,
                isPreferito: false,
                isFatturazione: true,
            );

            if ($row) {
                $preferito = (bool) $row->is_preferito;
                $row->fill($payload);
                $row->is_preferito = $preferito;
                $row->is_fatturazione = true;
                $row->save();
            } else {
                $hadPreferito = $user->mittenze()->where('is_preferito', true)->exists();
                $payload['is_preferito'] = ! $hadPreferito;
                mittenza::query()->create($payload);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payloadFromAnagrafica(
        User $user,
        Anagrafica $a,
        ?int $idComune,
        bool $isPreferito,
        bool $isFatturazione,
    ): array {
        $cap = $this->normalizzaCap((string) ($a->cap ?? ''));

        return [
            'user_id' => $user->id,
            'nome' => $a->nome !== null ? trim((string) $a->nome) : null,
            'cognome' => $a->cognome !== null ? trim((string) $a->cognome) : null,
            'denominazione_ragione_sociale' => $a->denominazione_ragione_sociale !== null
                ? trim((string) $a->denominazione_ragione_sociale) : null,
            'indirizzo' => $a->indirizzo !== null ? trim((string) $a->indirizzo) : null,
            'civico' => $a->civico !== null ? trim((string) $a->civico) : null,
            'cap' => strlen($cap) === 5 ? $cap : null,
            'citta' => $a->citta !== null ? trim((string) $a->citta) : null,
            'provincia' => $a->provincia !== null
                ? strtoupper(substr(trim((string) $a->provincia), 0, 2)) : null,
            'id_comune' => $idComune,
            'telefono' => $a->telefono !== null ? trim((string) $a->telefono) : null,
            'email' => $user->email,
            'is_preferito' => $isPreferito,
            'is_fatturazione' => $isFatturazione,
        ];
    }

    public function normalizzaCap(string $raw): string
    {
        return str_pad(preg_replace('/\D/', '', $raw), 5, '0', STR_PAD_LEFT);
    }

    public function setPreferito(User $user, mittenza $row): void
    {
        abort_if((int) $row->user_id !== (int) $user->id, 403);

        DB::transaction(function () use ($user, $row): void {
            if ($row->is_preferito) {
                $row->update(['is_preferito' => false]);

                return;
            }

            $user->mittenze()->update(['is_preferito' => false]);
            $row->update(['is_preferito' => true]);
        });
    }
}
