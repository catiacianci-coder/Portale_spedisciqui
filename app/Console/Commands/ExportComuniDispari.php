<?php

namespace App\Console\Commands;

use App\Models\comune;
use Illuminate\Console\Command;

class ExportComuniDispari extends Command
{
    protected $signature = 'export:comuni-dispari {--from=1} {--to=299}';
    protected $description = 'Esporta i comuni con ID dispari in CSV.';

    public function handle(): int
    {
        $from = (int) $this->option('from');
        $to = (int) $this->option('to');

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $ids = range($from, $to);
        $oddIds = array_values(array_filter($ids, fn (int $id) => $id % 2 !== 0));

        $rows = comune::query()
            ->whereIn('id', $oddIds)
            ->orderBy('id')
            ->get(['id', 'cap', 'comune', 'provincia', 'regione', 'paese', 'attivo']);

        $path = storage_path('app/comuni_dispari_1_299_test.csv');
        $file = fopen($path, 'w');

        if ($file === false) {
            $this->error("Impossibile creare il file: $path");
            return self::FAILURE;
        }

        fputcsv($file, ['id', 'cap', 'comune', 'provincia', 'regione', 'paese', 'attivo'], ';');

        foreach ($rows as $row) {
            fputcsv($file, [
                $row->id,
                $row->cap,
                $row->comune,
                $row->provincia,
                $row->regione,
                $row->paese,
                $row->attivo,
            ], ';');
        }

        fclose($file);

        $this->info("Export completato: {$rows->count()} righe in $path");
        return self::SUCCESS;
    }
}
