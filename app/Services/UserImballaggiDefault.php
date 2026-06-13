<?php

namespace App\Services;

use App\Models\tipo_spedizone;
use App\Models\User;
use App\Models\UserImballaggio;
use Illuminate\Support\Str;

class UserImballaggiDefault
{
    /**
     * Per ogni tipo (Pacco, Documento, Pallet) crea tre imballaggi standard se l'utente non ne ha ancora per quel tipo.
     */
    public function ensureDefaults(User $user): void
    {
        $tipoIds = $this->resolveTipoIdsByName();
        if ($tipoIds === []) {
            return;
        }

        $sets = [
            'Pacco' => [
                ['nome' => 'Scatola piccola', 'altezza' => 20, 'larghezza' => 15, 'spessore' => 10, 'peso' => 0.5],
                ['nome' => 'Scatola media', 'altezza' => 35, 'larghezza' => 25, 'spessore' => 18, 'peso' => 2],
                ['nome' => 'Scatola grande', 'altezza' => 55, 'larghezza' => 40, 'spessore' => 35, 'peso' => 5],
            ],
            'Documento' => [
                ['nome' => 'Busta documenti A4', 'altezza' => 32, 'larghezza' => 23, 'spessore' => 1, 'peso' => 0.15],
                ['nome' => 'Busta imbottita documenti', 'altezza' => 35, 'larghezza' => 27, 'spessore' => 3, 'peso' => 0.45],
                ['nome' => 'Cartella portadocumenti', 'altezza' => 38, 'larghezza' => 29, 'spessore' => 6, 'peso' => 1.1],
            ],
            'Pallet' => [
                ['nome' => 'Pallet EUR carico basso', 'altezza' => 120, 'larghezza' => 80, 'spessore' => 80, 'peso' => 380],
                ['nome' => 'Pallet EUR carico medio', 'altezza' => 120, 'larghezza' => 80, 'spessore' => 130, 'peso' => 620],
                ['nome' => 'Pallet EUR carico alto', 'altezza' => 120, 'larghezza' => 80, 'spessore' => 180, 'peso' => 880],
            ],
        ];

        foreach ($sets as $tipoNome => $righe) {
            $tipoId = $tipoIds[Str::lower($tipoNome)] ?? null;
            if (! $tipoId) {
                continue;
            }

            $haGia = $user->imballaggi()->where('id_tipo_spediziones', $tipoId)->exists();
            if ($haGia) {
                continue;
            }

            foreach ($righe as $row) {
                UserImballaggio::query()->create([
                    'user_id' => $user->id,
                    'id_tipo_spediziones' => $tipoId,
                    'nome' => $row['nome'],
                    'altezza' => $row['altezza'],
                    'larghezza' => $row['larghezza'],
                    'spessore' => $row['spessore'],
                    'peso' => $row['peso'],
                ]);
            }
        }
    }

    /**
     * @return array<string, int> chiave = nome tipo normalizzato (lowercase), valore = id
     */
    private function resolveTipoIdsByName(): array
    {
        $rows = tipo_spedizone::query()->get(['id', 'tipo_spedizione']);
        $map = [];
        foreach ($rows as $row) {
            $map[Str::lower(trim((string) $row->tipo_spedizione))] = (int) $row->id;
        }

        return $map;
    }
}
