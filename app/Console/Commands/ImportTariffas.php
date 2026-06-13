<?php

namespace App\Console\Commands;

use App\Models\tariffa;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ImportTariffas extends Command
{
    protected $signature = 'import:tariffas
                            {file? : File in storage/app (es. tariffas_Poste.csv); default tariffas_template.csv}
                            {--truncate : Svuota tariffas prima dell\'import}
                            {--append : Aggiunge righe senza svuotare la tabella}
                            {--replace-corriere= : Elimina solo le tariffe di questo id_corrieres prima dell\'import}';

    protected $description = 'Importa tariffe da CSV in storage/app (default tariffas_template.csv)';

    public function handle(): int
    {
        $relative = $this->argument('file') ?? 'tariffas_template.csv';
        $path = str_contains($relative, DIRECTORY_SEPARATOR)
            ? $relative
            : storage_path('app/'.$relative);

        if (! file_exists($path)) {
            $this->error("File non trovato: {$path}");

            return self::FAILURE;
        }

        if ($this->option('truncate') && ! $this->option('append')) {
            tariffa::query()->delete();
            $this->info('Tabella tariffas svuotata.');
        }

        $replaceCorriere = $this->option('replace-corriere');
        if ($replaceCorriere !== null && $replaceCorriere !== '') {
            $cid = (int) $replaceCorriere;
            $deleted = tariffa::query()->where('id_corrieres', $cid)->delete();
            $this->info("Rimosse {$deleted} tariffe del corriere {$cid} (sostituzione mirata).");
        }

        $imported = $this->importFile($path);

        $this->info("Import completato da ".basename($path).". Righe importate: {$imported}");

        return self::SUCCESS;
    }

    private function importFile(string $path): int
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines || count($lines) < 2) {
            $this->warn('Nessuna riga dati trovata nel CSV.');

            return 0;
        }

        $headers = array_map(function (string $h): string {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', $h) ?? $h);
        }, explode(';', $lines[0]));
        $imported = 0;

        foreach (array_slice($lines, 1) as $index => $line) {
            $values = array_map('trim', explode(';', $line));
            if (count($values) !== count($headers)) {
                $this->warn('Riga '.($index + 2).' saltata: colonne non coerenti.');

                continue;
            }

            $row = array_combine($headers, $values);

            $idCorriere = $this->toInt($row['id_corrieres'] ?? null);
            if ($idCorriere === null || $idCorriere < 1) {
                continue;
            }

            $idRicarico = $this->toInt($row['id_ricarico'] ?? null);
            if ($idRicarico === null) {
                $idRicarico = $this->toInt($row['ricarico'] ?? null);
            }

            tariffa::create([
                'data_modifica' => $this->parseDate($row['data_modifica'] ?? null),
                'data_sospensione' => $this->parseDate($row['data_sospensione'] ?? null),
                'id_corrieres' => $this->toInt($row['id_corrieres'] ?? null),
                'servizio' => $row['servizio'] ?? null,
                'id_tipo_spediziones' => $this->toInt($row['id_tipo_spediziones'] ?? null),
                'peso_da' => $this->toDecimal($row['peso_da'] ?? null),
                'peso_a' => $this->toDecimal($row['peso_a'] ?? null),
                'livello' => $row['livello'] ?? null,
                'tariffa' => $this->toDecimal($row['tariffa'] ?? null),
                'lato_max' => $this->toDecimal($row['lato_max'] ?? null),
                'lato_med' => $this->toDecimal($row['lato_med'] ?? null),
                'lato_min' => $this->toDecimal($row['lato_min'] ?? null),
                'max' => $this->toDecimal($row['max'] ?? null),
                'peso_max_collo' => $this->toDecimal($row['peso_max_collo'] ?? null),
                'id_ricarico' => $idRicarico,
                'nazione_partenza' => $row['nazione_partenza'] ?? null,
                'nazione_arrivo' => $row['nazione_arrivo'] ?? null,
                'sicilia' => $this->toDecimal($this->cell($row, ['Sicilia', 'sicilia'])),
                'calabria' => $this->toDecimal($this->cell($row, ['Calabria', 'calabria'])),
                'sardegna' => $this->toDecimal($this->cell($row, ['Sardegna', 'sardegna'])),
                'varie1' => $this->cell($row, ['varie1', 'Varie1']) ?: null,
                'varie2' => $this->cell($row, ['varie2', 'Varie2']) ?: null,
                'varie3' => $this->cell($row, ['varie3', 'Varie3']) ?: null,
            ]);

            $imported++;
        }

        return $imported;
    }

    private function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function toDecimal(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = str_replace(',', '.', trim($value));
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function toInt(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<string>  $keys
     */
    private function cell(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return null;
    }
}
