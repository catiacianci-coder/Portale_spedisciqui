<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCorrieriServiziAggiuntiviCsvCommand extends Command
{
    protected $signature = 'corrieri-servizi-aggiuntivi:import-csv
                            {path? : Percorso al CSV (default: database/data/corrieri_servizi_aggiuntivis_inizializzazione.csv)}';

    protected $description = 'Importa righe da CSV (schema legacy). Per il formato attuale usare storage/app/tabella_s_a_c.csv (import automatico in migration 2026_05_22_170000).';

    public function handle(): int
    {
        $path = $this->argument('path') ?? database_path('data/corrieri_servizi_aggiuntivis_inizializzazione.csv');

        if (! is_readable($path)) {
            $this->error("File non leggibile: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->error('Impossibile aprire il file.');

            return self::FAILURE;
        }

        $header = fgetcsv($handle, 0, ';');
        if ($header === false) {
            fclose($handle);
            $this->error('CSV vuoto o non valido.');

            return self::FAILURE;
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', $header[0] ?? '') ?? $header[0];

        $norm = static fn (array $a): array => array_map(static fn ($h) => strtolower(trim((string) $h)), $a);
        $expectedSac = ['id', 'fonte_servizio', 'id_tipo', 'id_corriere', 'codice_servizio_corriere', 'testo_servizio', 'visualizzato', 'min_fascia', 'max_fascia', 'percentuale_cor', 'ricarico_k91', 'valore_fisso_cor', 'valore_fisso_k91', 'valore_percentuale', 'valore_minimo', 'valore_massimo', 'varie1', 'varie2', 'varie3', 'varie4'];
        if ($norm($header) === $norm($expectedSac)) {
            $this->error('Formato tabella_s_a_c.csv: reimportare con php artisan migrate (migration 2026_05_22_170000) oppure svuotare la tabella e usare un comando dedicato.');

            return self::FAILURE;
        }
        $expected = ['id', 'id_corriere', 'id_servizi_aggiuntivi', 'costo_percentuale', 'costo_valore_assoluto', 'varie1', 'varie2', 'id_tipo_spediziones', 'valore_minimo', 'fascia_da', 'fascia_a'];
        if ($norm($header) !== $norm($expected)) {
            $this->warn('Intestazioni CSV non coincidono con schema legacy né tabella_s_a_c. Colonne: '.implode(', ', $header));
        }

        $groups = [];
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 11) {
                continue;
            }
            $legacyId = (int) trim($row[0]);
            if ($legacyId < 1) {
                continue;
            }
            if (! isset($groups[$legacyId])) {
                $groups[$legacyId] = [];
            }
            $groups[$legacyId][] = $row;
        }
        fclose($handle);

        if ($groups === []) {
            $this->error('Nessuna riga dati nel CSV.');

            return self::FAILURE;
        }

        $legacyToFirstNew = [];

        DB::transaction(function () use ($groups, &$legacyToFirstNew): void {
            foreach ($groups as $legacyId => $rows) {
                $firstNewId = null;
                foreach ($rows as $row) {
                    $data = $this->mapRow($row);
                    $newId = DB::table('corrieri_servizi_aggiuntivis')->insertGetId(array_merge($data, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    if ($firstNewId === null) {
                        $firstNewId = $newId;
                    }
                }
                $legacyToFirstNew[$legacyId] = $firstNewId;
            }

            foreach ($legacyToFirstNew as $legacyId => $firstNewId) {
                DB::table('spedizione_servizio_aggiuntivis')
                    ->where('id_corrieri_servizi_aggiuntivis', $legacyId)
                    ->update(['id_corrieri_servizi_aggiuntivis' => $firstNewId]);
            }

            DB::table('corrieri_servizi_aggiuntivis')->whereIn('id', array_keys($legacyToFirstNew))->delete();
        });

        $this->info('Import completato. Gruppi legacy: '.count($legacyToFirstNew).'.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        return [
            'id_corriere' => (int) trim($row[1]),
            'id_servizi_aggiuntivi' => (int) trim($row[2]),
            'costo_percentuale' => $this->decOrNull($row[3] ?? null),
            'costo_valore_assoluto' => $this->decOrNull($row[4] ?? null),
            'varie1' => $this->textOrNull($row[5] ?? null),
            'varie2' => $this->textOrNull($row[6] ?? null),
            'id_tipo_spediziones' => $this->intOrNull($row[7] ?? null),
            'valore_minimo' => $this->decOrNull($row[8] ?? null),
            'fascia_da' => $this->decOrNull($row[9] ?? null),
            'fascia_a' => $this->decOrNull($row[10] ?? null),
        ];
    }

    private function textOrNull(?string $v): ?string
    {
        $v = $v === null ? '' : trim($v);

        return $v === '' ? null : $v;
    }

    private function intOrNull(?string $v): ?int
    {
        $v = $v === null ? '' : trim(str_replace(',', '.', $v));
        if ($v === '') {
            return null;
        }

        return (int) $v;
    }

    private function decOrNull(?string $v): ?string
    {
        $v = $v === null ? '' : trim($v);
        if ($v === '') {
            return null;
        }
        $normalized = str_replace(',', '.', $v);

        return is_numeric($normalized) ? $normalized : null;
    }
}
