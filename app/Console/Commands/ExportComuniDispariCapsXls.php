<?php

namespace App\Console\Commands;

use App\Models\comune;
use Illuminate\Console\Command;

class ExportComuniDispariCapsXls extends Command
{
    protected $signature = 'export:comuni-dispari-caps-xls {--from=1} {--to=299}';
    protected $description = 'Esporta i CAP dei comuni con ID dispari in un file .xls (Excel XML).';

    public function handle(): int
    {
        $from = (int) $this->option('from');
        $to = (int) $this->option('to');

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $oddIds = array_values(array_filter(range($from, $to), fn (int $id) => $id % 2 !== 0));

        $rows = comune::query()
            ->whereIn('id', $oddIds)
            ->orderBy('id')
            ->get(['id', 'cap']);

        $path = storage_path('app/comuni_dispari_1_299_caps.xls');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
            . 'xmlns:o="urn:schemas-microsoft-com:office:office" '
            . 'xmlns:x="urn:schemas-microsoft-com:office:excel" '
            . 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" '
            . 'xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        $xml .= '<Worksheet ss:Name="CAP">' . "\n";
        $xml .= '<Table>' . "\n";

        $xml .= '<Row>';
        $xml .= '<Cell><Data ss:Type="String">id</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">cap</Data></Cell>';
        $xml .= '</Row>' . "\n";

        foreach ($rows as $row) {
            $xml .= '<Row>';
            $xml .= '<Cell><Data ss:Type="Number">' . (int) $row->id . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars((string) $row->cap, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</Data></Cell>';
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>' . "\n";

        if (file_put_contents($path, $xml) === false) {
            $this->error("Impossibile scrivere il file: $path");
            return self::FAILURE;
        }

        $this->info("Export completato: {$rows->count()} righe in $path");
        return self::SUCCESS;
    }
}
