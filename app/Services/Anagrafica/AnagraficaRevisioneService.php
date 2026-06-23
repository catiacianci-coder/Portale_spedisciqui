<?php

namespace App\Services\Anagrafica;

use App\Models\Anagrafica;
use App\Models\comune;
use App\Models\User;
use App\Services\UserMittenzeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnagraficaRevisioneService
{
    /**
     * @return array<string, array<int, string>>
     */
    public function validationRules(string $tipo): array
    {
        $rules = [
            'nome' => ['required', 'string', 'max:255'],
            'cognome' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20'],
            'cap' => ['required', 'string', 'max:10'],
            'citta' => ['required', 'string', 'max:255'],
            'provincia' => ['required', 'string', 'size:2'],
            'indirizzo' => ['required', 'string', 'max:255'],
            'civico' => ['required', 'string', 'max:10'],
            'id_comune' => ['required', 'integer', 'exists:comuni,id'],
        ];

        if ($tipo !== 'privato') {
            $rules['denominazione_ragione_sociale'] = ['required', 'string', 'max:255'];
            $rules['partita_iva'] = ['required', 'string', 'size:11'];
            $rules['pec'] = ['nullable', 'email', 'max:255'];
            $rules['codice_sdi'] = ['nullable', 'string', 'max:7'];
        }

        return $rules;
    }

    public function normalizzaCap(string $raw): string
    {
        return str_pad(preg_replace('/\D/', '', $raw), 5, '0', STR_PAD_LEFT);
    }

    public function risolviIdComuneDaAnagrafica(Anagrafica $a): ?int
    {
        $capP = $this->normalizzaCap((string) $a->cap);
        if (strlen($capP) !== 5 || trim((string) $a->citta) === '' || trim((string) $a->provincia) === '') {
            return null;
        }
        $cittaL = mb_strtolower(trim((string) $a->citta));
        $provL = strtoupper(substr(trim((string) $a->provincia), 0, 2));
        $id = comune::query()
            ->where('cap', $capP)
            ->whereRaw('LOWER(TRIM(comune)) = ?', [$cittaL])
            ->whereRaw('UPPER(LEFT(TRIM(provincia), 2)) = ?', [$provL])
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return Anagrafica|null  null se nessuna modifica rispetto all’ultima revisione attiva
     */
    public function creaRevisioneSeModificato(User $user, Anagrafica $latest, array $validated): ?Anagrafica
    {
        $tipo = (string) ($user->tipo_utente ?? 'privato');
        $cap = $this->normalizzaCap($validated['cap']);
        if (strlen($cap) !== 5) {
            throw ValidationException::withMessages(['cap' => 'Il CAP deve essere di 5 cifre.']);
        }

        $comune = comune::query()->findOrFail((int) $validated['id_comune']);
        $this->assertComuneAllineato($comune, $cap, $validated['citta'], $validated['provincia']);

        if (! $this->haModificheRispettoAUltima($latest, $validated, $cap, $tipo)) {
            return null;
        }

        return DB::transaction(function () use ($user, $latest, $validated, $cap, $tipo) {
            $prov = strtoupper(substr(trim($validated['provincia']), 0, 2));

            $row = [
                'user_id' => $user->id,
                'codice_fiscale' => $latest->codice_fiscale,
                'nome' => trim($validated['nome']),
                'cognome' => trim($validated['cognome']),
                'telefono' => trim($validated['telefono']),
                'indirizzo' => trim($validated['indirizzo']),
                'civico' => trim($validated['civico']),
                'cap' => $cap,
                'citta' => trim($validated['citta']),
                'provincia' => $prov,
                'sede_liccardi' => array_key_exists('sede_liccardi', $validated)
                    ? filter_var($validated['sede_liccardi'], FILTER_VALIDATE_BOOLEAN)
                    : (bool) $latest->sede_liccardi,
                'varie_1' => $latest->varie_1,
                'varie_2' => $latest->varie_2,
            ];

            if ($tipo === 'privato') {
                $row['denominazione_ragione_sociale'] = $latest->denominazione_ragione_sociale;
                $row['partita_iva'] = $latest->partita_iva;
                $row['pec'] = $latest->pec;
                $row['codice_sdi'] = $latest->codice_sdi;
            } else {
                $row['denominazione_ragione_sociale'] = trim($validated['denominazione_ragione_sociale']);
                $row['partita_iva'] = trim($validated['partita_iva']);
                $row['pec'] = isset($validated['pec']) ? trim((string) $validated['pec']) : null;
                $row['codice_sdi'] = isset($validated['codice_sdi']) ? trim((string) $validated['codice_sdi']) : null;
                if ($row['pec'] === '') {
                    $row['pec'] = null;
                }
                if ($row['codice_sdi'] === '') {
                    $row['codice_sdi'] = null;
                }
            }

            $nuova = Anagrafica::creaRevisioneAttiva($row);
            app(UserMittenzeService::class)->syncFatturazioneRow(
                $user,
                $nuova,
                (int) $validated['id_comune']
            );

            return $nuova;
        });
    }

    private function assertComuneAllineato(comune $comune, string $capNorm, string $cittaInput, string $provInput): void
    {
        $capDb = str_pad((string) $comune->cap, 5, '0', STR_PAD_LEFT);
        $prov = strtoupper(substr(trim($provInput), 0, 2));
        $provDb = strtoupper(substr(trim((string) $comune->provincia), 0, 2));
        $cittaNorm = mb_strtolower(trim($cittaInput));
        $comuneNorm = mb_strtolower(trim((string) $comune->comune));

        if ($capDb !== $capNorm || $provDb !== $prov || $comuneNorm !== $cittaNorm) {
            throw ValidationException::withMessages([
                'id_comune' => 'CAP, città e provincia devono coincidere con il comune scelto dall’autocomplete.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $v
     */
    private function haModificheRispettoAUltima(Anagrafica $latest, array $v, string $capNorm, string $tipo): bool
    {
        $cmp = function (?string $a, string $b): bool {
            return mb_strtolower(trim((string) $a)) !== mb_strtolower(trim($b));
        };

        if ($cmp($latest->nome, $v['nome'])) {
            return true;
        }
        if ($cmp($latest->cognome, $v['cognome'])) {
            return true;
        }
        if ($cmp($latest->telefono, $v['telefono'])) {
            return true;
        }
        if ($this->normalizzaCap((string) $latest->cap) !== $capNorm) {
            return true;
        }
        if ($cmp($latest->citta, $v['citta'])) {
            return true;
        }
        if (strtoupper(substr(trim((string) $latest->provincia), 0, 2)) !== strtoupper(substr(trim($v['provincia']), 0, 2))) {
            return true;
        }
        if ($cmp($latest->indirizzo, $v['indirizzo'])) {
            return true;
        }
        if ($cmp($latest->civico, $v['civico'])) {
            return true;
        }

        $sedeNuova = array_key_exists('sede_liccardi', $v)
            ? filter_var($v['sede_liccardi'], FILTER_VALIDATE_BOOLEAN)
            : null;
        if ($sedeNuova !== null && (bool) $latest->sede_liccardi !== $sedeNuova) {
            return true;
        }

        if ($tipo !== 'privato') {
            if ($cmp($latest->denominazione_ragione_sociale, $v['denominazione_ragione_sociale'])) {
                return true;
            }
            if (trim((string) $latest->partita_iva) !== trim($v['partita_iva'])) {
                return true;
            }
            if (mb_strtolower($this->normStr($latest->pec)) !== mb_strtolower($this->normStr($v['pec'] ?? ''))) {
                return true;
            }
            if (strtoupper($this->normStr($latest->codice_sdi)) !== strtoupper($this->normStr($v['codice_sdi'] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    private function normStr(mixed $v): string
    {
        return trim((string) ($v ?? ''));
    }
}
