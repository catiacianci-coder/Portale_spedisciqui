<?php

namespace App\Services;

use App\Models\nc_pratica;
use App\Models\nc_pratica_riga;
use App\Models\ordine;
use App\Models\spedizione;
use App\Support\SpedizioneCampiPersistenza;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NcCsvImportService
{
    public function __construct(
        private NcImportoCalcoloService $calcolo,
        private NcPraticaPdfService $pdf,
    ) {}

    /**
     * @return array{pratiche: int, righe: int, errori: array<int, string>}
     */
    public function importa(UploadedFile $file, int $creatoDaUserId): array
    {
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false || $raw === '') {
            return ['pratiche' => 0, 'righe' => 0, 'errori' => ['File vuoto o non leggibile.']];
        }
        $raw = str_replace("\xEF\xBB\xBF", '', $raw);
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
        if (count($lines) < 2) {
            return ['pratiche' => 0, 'righe' => 0, 'errori' => ['Il CSV deve contenere intestazione e almeno una riga dati.']];
        }

        $firstLine = (string) $lines[0];
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
        $headerRaw = str_getcsv($firstLine, $delimiter);
        if (count($headerRaw) < 2) {
            $alt = $delimiter === ';' ? ',' : ';';
            $try = str_getcsv($firstLine, $alt);
            if (count($try) > count($headerRaw)) {
                $delimiter = $alt;
                $headerRaw = $try;
            }
        }

        [$headerRaw, $delimiter] = self::espandiSeUnicaCellaConSeparatoreInterno($headerRaw, $delimiter);

        $keys = array_map(function ($h) {
            $t = trim((string) $h);
            $t = str_replace([' ', '-', '/'], '_', $t);
            $t = preg_replace('/_+/', '_', $t) ?? $t;

            return Str::snake(Str::lower($t));
        }, $headerRaw);

        $keys = self::mappaAliasCodiceInterno($keys);

        if (! in_array('codice_interno', $keys, true)) {
            $rilevate = array_values(array_filter($keys, static fn ($k) => $k !== ''));
            $lista = $rilevate === [] ? '(nessuna colonna: controlla separatore ; o , e la prima riga di intestazione)' : implode(', ', $rilevate);

            return ['pratiche' => 0, 'righe' => 0, 'errori' => [
                'Manca una colonna con il codice spedizione (es. COD-123). Nomi accettati in intestazione: codice_interno, codice_spedizione, cod_spedizione, riferimento_interno, ecc.',
                'Intestazioni effettivamente lette ('.count($rilevate).' colonne, separatore «'.$delimiter.'»): '.$lista.'.',
                'Nota: email e prezzo_pagato nel file non sono obbligatori: se assenti il sistema usa utente e importo della spedizione.',
                'Se in Excel vedi tutto nella sola colonna A con i punti e virgola nel testo, salva comunque come CSV: il formato «monocolonna» è ora supportato.',
            ]];
        }

        /** @var array<int, array{row: array<string, mixed>, sped: spedizione, prezzo_pagato: float}> */
        $validByUserId = [];

        $errori = [];

        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line, $delimiter);
            [$cols] = self::espandiSeUnicaCellaConSeparatoreInterno($cols, $delimiter);
            $row = [];
            foreach ($keys as $idx => $key) {
                $row[$key] = $cols[$idx] ?? null;
            }
            if (! isset($row['email_cliente']) && isset($row['email'])) {
                $row['email_cliente'] = $row['email'];
            }

            $codice = trim((string) ($row['codice_interno'] ?? ''));
            if ($codice === '') {
                $errori[] = 'Riga '.($i + 1).': codice_interno vuoto.';

                continue;
            }

            $sped = spedizione::query()
                ->where('codice_interno', $codice)
                ->with(['user', 'ordine', 'corriereRecord'])
                ->first();

            if (! $sped || ! $sped->user) {
                $errori[] = 'Riga '.($i + 1).': spedizione non trovata per codice_interno '.$codice.'.';

                continue;
            }

            $emailDb = Str::lower(trim((string) $sped->user->email));
            if ($emailDb === '' || ! filter_var($emailDb, FILTER_VALIDATE_EMAIL)) {
                $errori[] = 'Riga '.($i + 1).': utente della spedizione senza email valida.';

                continue;
            }

            $emailCsv = Str::lower(trim((string) ($row['email_cliente'] ?? '')));
            if ($emailCsv !== '' && filter_var($emailCsv, FILTER_VALIDATE_EMAIL) && $emailCsv !== $emailDb) {
                $errori[] = 'Riga '.($i + 1).': email_cliente nel file non coincide con l\'utente titolare della spedizione '.$codice.'.';

                continue;
            }

            $prezzoRaw = isset($row['prezzo_pagato']) ? trim((string) $row['prezzo_pagato']) : '';
            if ($prezzoRaw !== '') {
                $prezzoPagato = round((float) str_replace(',', '.', $prezzoRaw), 2);
            } else {
                $prezzoPagato = round((float) (SpedizioneCampiPersistenza::prezzoNettoDaOrdine($sped) ?? 0), 2);
            }

            $userId = (int) $sped->user_id;
            if (! isset($validByUserId[$userId])) {
                $validByUserId[$userId] = [];
            }
            $validByUserId[$userId][] = ['row' => $row, 'sped' => $sped, 'prezzo_pagato' => $prezzoPagato];
        }

        if ($validByUserId === []) {
            return ['pratiche' => 0, 'righe' => 0, 'errori' => array_merge(['Nessuna riga dati valida.'], $errori)];
        }

        $pratiche = 0;
        $righeTot = 0;

        DB::transaction(function () use ($validByUserId, $creatoDaUserId, &$pratiche, &$righeTot, &$errori): void {
            foreach ($validByUserId as $userId => $gruppo) {
                $user = User::query()->find($userId);
                if (! $user) {
                    $errori[] = 'Utente non trovato per id: '.$userId;

                    continue;
                }

                $pratica = nc_pratica::query()->create([
                    'user_id' => $user->id,
                    'stato' => nc_pratica::STATO_APERTO,
                    'creato_da_user_id' => $creatoDaUserId,
                ]);

                foreach ($gruppo as $item) {
                    $row = $item['row'];
                    $sped = $item['sped'];
                    $prezzoPagato = $item['prezzo_pagato'];

                    $codice = trim((string) ($row['codice_interno'] ?? ''));
                    if ($codice === '') {
                        $errori[] = 'Pratica #'.$pratica->id.': codice_interno vuoto ignorato.';

                        continue;
                    }

                    $dataPagamento = null;
                    $corriereNome = trim((string) ($sped->corriere ?? ''));
                    if ($corriereNome === '') {
                        $corriereNome = $sped->corriereRecord?->nome_visualizzato
                            ?? $sped->corriereRecord?->nome_corriere;
                    }
                    if ($sped->ordine && $sped->ordine->stato === ordine::STATO_PAGATO) {
                        $dataPagamento = $sped->ordine->updated_at;
                    }

                    [$dh, $dl, $ds, $dp] = $this->dichiaratiDaSpedizione($sped);

                    $hD = $this->num($row, 'altezza_dich', 'altezza_dichiarata', 'altezza_dichiarata_cm') ?? $dh;
                    $lD = $this->num($row, 'larghezza_dich', 'larghezza_dichiarata', 'larghezza_dichiarata_cm') ?? $dl;
                    $sD = $this->num($row, 'spessore_dich', 'spessore_dichiarata', 'spessore_dichiarato', 'spessore_dichiarata_cm') ?? $ds;
                    $pD = $this->num($row, 'peso_dich', 'peso_dichiarato', 'peso_dichiarato_kg') ?? $dp;
                    $hC = $this->num($row, 'altezza_corriere', 'altezza_corriere_cm');
                    $lC = $this->num($row, 'larghezza_corriere', 'larghezza_corriere_cm');
                    $sC = $this->num($row, 'spessore_corriere', 'spessore_corriere_cm');
                    $pC = $this->num($row, 'peso_corriere', 'peso_corriere_kg');

                    $importoCsv = isset($row['importo_dovuto']) && trim((string) $row['importo_dovuto']) !== ''
                        ? round((float) str_replace(',', '.', (string) $row['importo_dovuto']), 2)
                        : null;

                    $importoDovuto = $this->calcolo->importoDovuto(
                        $prezzoPagato,
                        $hD,
                        $lD,
                        $sD,
                        $pD,
                        $hC,
                        $lC,
                        $sC,
                        $pC,
                        $importoCsv,
                    );
                    $delta = round($importoDovuto - $prezzoPagato, 2);

                    nc_pratica_riga::query()->create([
                        'nc_pratica_id' => $pratica->id,
                        'spedizione_id' => $sped->id,
                        'codice_interno' => $codice,
                        'altezza_dich' => $hD,
                        'larghezza_dich' => $lD,
                        'spessore_dich' => $sD,
                        'peso_dich' => $pD,
                        'altezza_corriere' => $hC,
                        'larghezza_corriere' => $lC,
                        'spessore_corriere' => $sC,
                        'peso_corriere' => $pC,
                        'prezzo_pagato' => $prezzoPagato,
                        'importo_dovuto' => $importoDovuto,
                        'delta' => $delta,
                        'stato_riga' => nc_pratica_riga::STATO_NON_PAGATO,
                        'data_pagamento_ordine' => $dataPagamento,
                        'corriere_nome_visualizzato' => $corriereNome ? (string) $corriereNome : null,
                    ]);
                    $righeTot++;
                }

                $pratica->refresh();
                if ($pratica->righe()->count() === 0) {
                    $pratica->delete();
                    $errori[] = 'Nessuna riga valida per utente #'.$userId.', pratica eliminata.';

                    continue;
                }

                $this->pdf->genera($pratica->fresh(['righe', 'user']));
                $pratiche++;
            }
        });

        return ['pratiche' => $pratiche, 'righe' => $righeTot, 'errori' => $errori];
    }

    /**
     * Se manca codice_interno, riconosce intestazioni equivalenti (es. codice_spedizione).
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    /**
     * Excel spesso esporta una riga come unica cella (es. colonna A) con testo tipo
     * codice_interno;altezza;... (anche tra virgolette): la prima passata CSV produce
     * un solo campo; qui si riapre con il separatore interno ; o ,.
     *
     * @param  array<int, string|null>  $cells
     * @return array{0: array<int, string>, 1: string}
     */
    private static function espandiSeUnicaCellaConSeparatoreInterno(array $cells, string $fallbackDelimiter): array
    {
        if (count($cells) !== 1) {
            return [$cells, $fallbackDelimiter];
        }
        $text = trim((string) ($cells[0] ?? ''));
        if ($text === '') {
            return [$cells, $fallbackDelimiter];
        }

        foreach ([';', ','] as $d) {
            if (! str_contains($text, $d)) {
                continue;
            }
            $split = str_getcsv($text, $d);
            if (count($split) > 1) {
                return [$split, $d];
            }
        }

        return [$cells, $fallbackDelimiter];
    }

    private static function mappaAliasCodiceInterno(array $keys): array
    {
        if (in_array('codice_interno', $keys, true)) {
            return $keys;
        }

        $aliases = [
            'codice_spedizione',
            'cod_spedizione',
            'codice_riferimento',
            'riferimento_interno',
            'riferimento_spedizione',
            'cod_interno',
            'numero_spedizione_interno',
            'n_spedizione',
            'nr_spedizione',
        ];

        foreach ($keys as $i => $k) {
            if (in_array($k, $aliases, true)) {
                $keys[$i] = 'codice_interno';

                return $keys;
            }
        }

        return $keys;
    }

    /**
     * Misure dichiarate già registrate sulla spedizione (colonne scalari o campo pacco_json).
     *
     * @return array{0: ?float, 1: ?float, 2: ?float, 3: ?float}
     */
    private function dichiaratiDaSpedizione(spedizione $sped): array
    {
        return [
            $sped->altezza !== null ? (float) $sped->altezza : null,
            $sped->larghezza !== null ? (float) $sped->larghezza : null,
            $sped->spessore !== null ? (float) $sped->spessore : null,
            $sped->peso !== null ? (float) $sped->peso : null,
        ];
    }

    /** @param  array<string, mixed>  $row */
    private function num(array $row, string ...$keys): ?float
    {
        foreach ($keys as $k) {
            if (! array_key_exists($k, $row)) {
                continue;
            }
            $v = trim((string) $row[$k]);
            if ($v === '') {
                continue;
            }
            $f = (float) str_replace(',', '.', $v);

            return $f;
        }

        return null;
    }
}
