<?php

namespace App\Console\Commands;

use App\Models\origine_italia;
use Illuminate\Console\Command;

class ImportOrigineItalias extends Command
{
    protected $signature = 'import:origine-italias {--truncate : Svuota origine_italias prima dell\'import}';
    protected $description = 'Importa origine_italias da storage/app/origine_italias_template.csv';

    public function handle(): int
    {
        $path = storage_path('app/origine_italias_template.csv');

        if (!file_exists($path)) {
            $this->error("File non trovato: $path");
            return self::FAILURE;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines || count($lines) < 2) {
            $this->warn('Nessuna riga dati trovata nel CSV.');
            return self::SUCCESS;
        }

        $headers = array_map('trim', explode(';', $lines[0]));

        if ($this->option('truncate')) {
            origine_italia::query()->delete();
            $this->info('Tabella origine_italias svuotata.');
        }

        $imported = 0;

        foreach (array_slice($lines, 1) as $index => $line) {
            $values = array_map('trim', explode(';', $line));
            if (count($values) !== count($headers)) {
                $this->warn('Riga ' . ($index + 2) . ' saltata: colonne non coerenti.');
                continue;
            }

            $row = array_combine($headers, $values);

            origine_italia::create([
                'id_corriere' => isset($row['id_corriere']) && $row['id_corriere'] !== '' ? (int) $row['id_corriere'] : null,
                'id_comune' => isset($row['id_comune']) && $row['id_comune'] !== '' ? (int) $row['id_comune'] : null,
                'varie' => $row['varie'] ?? null,
            ]);

            $imported++;
        }

        $this->info("Import completato. Righe importate: $imported");
        return self::SUCCESS;
    }
}
