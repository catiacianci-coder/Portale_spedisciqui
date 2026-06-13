<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\comune;
use Exception;

class ImportComuni extends Command
{
    protected $signature = 'import:comuni';
    protected $description = 'Importa i comuni dal file CSV UTF-8';

    public function handle()
    {
        $filePath = storage_path('app/comuni_italia.csv');

        if (!file_exists($filePath)) {
            $this->error("File non trovato in: $filePath");
            return;
        }

        $file = fopen($filePath, 'r');

        $headerLine = fgets($file);
        if ($headerLine === false) {
            $this->error('File CSV vuoto o non leggibile.');
            fclose($file);
            return;
        }

        $header = $this->parseCsvLine($headerLine);
        if (empty($header)) {
            $this->error('Intestazione CSV non valida.');
            fclose($file);
            return;
        }

        // Normalizziamo le chiavi per essere robusti con maiuscole/spazi
        $header = array_map(
            fn ($value) => strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', (string) $value))),
            $header
        );

        $count = 0;
        $lineNumber = 1;
        while (($rawLine = fgets($file)) !== false) {
            $lineNumber++;

            $data = $this->parseCsvLine($rawLine);
            // Se la riga è vuota, saltala
            if (empty($data)) {
                continue;
            }

            if (count($header) !== count($data)) {
                $this->warn("Riga $lineNumber saltata: numero colonne non valido.");
                continue;
            }

            $row = array_combine($header, $data);
            
            try {
                $rawCap = isset($row['cap']) ? trim((string) $row['cap']) : null;
                $cap = $rawCap !== '' ? str_pad($rawCap, 5, '0', STR_PAD_LEFT) : null;

                comune::create([
                    'cap'       => $cap,
                    'comune'    => $row['nome'] ?? $row['comune'] ?? null,
                    'provincia' => $row['provincia'] ?? null,
                    'regione'   => $row['regione'] ?? null,
                    'paese'     => $row['paese'] ?? 'Italia',
                    'attivo'    => $row['attivo'] ?? 1,
                ]);
                $count++;
            } catch (Exception $e) {
                $this->error("Errore riga $lineNumber: " . $e->getMessage());
            }
        }

        fclose($file);
        $this->info("Operazione conclusa! Importati $count comuni.");
    }

    private function parseCsvLine(string $line): array
    {
        $line = trim($line);
        if ($line === '') {
            return [];
        }

        // Il file sorgente puo' essere CP1252/ISO-8859-1: convertiamo sempre in UTF-8
        $line = mb_convert_encoding($line, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

        // CSV nel progetto: riga intera racchiusa tra doppi apici
        if (str_starts_with($line, '"') && str_ends_with($line, '"')) {
            $line = substr($line, 1, -1);
        }

        $values = array_map('trim', explode(';', $line));

        // Elimina colonne finali vuote ";;;;"
        while (!empty($values) && end($values) === '') {
            array_pop($values);
        }

        return $values;
    }
}