<?php

namespace App\Console\Commands;

use App\Models\spedizione;
use App\Services\SpedisciOnline\SpedisciOnlineEtichettaPdfService;
use Illuminate\Console\Command;

class SpedisciOnlineBackfillEtichettePdfCommand extends Command
{
    protected $signature = 'spedisci-online:backfill-etichette-pdf {--limit=500 : Max spedizioni da elaborare}';

    protected $description = 'Estrae labelData dai JSON integrazione e salva PDF in storage/app/etichette';

    public function handle(SpedisciOnlineEtichettaPdfService $pdf): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $ok = 0;
        $skip = 0;
        $fail = 0;

        $query = spedizione::query()
            ->where('esiste_integrazione', true)
            ->where(function ($q): void {
                $q->whereNull('etiqueta_pdf_path')->orWhere('etiqueta_pdf_path', '');
            })
            ->orderByDesc('id')
            ->limit($limit);

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        foreach ($query->cursor() as $spedizione) {
            $path = $pdf->salvaDaIntegrazione($spedizione);
            if ($path !== null) {
                $ok++;
            } elseif (trim((string) $spedizione->etiqueta_pdf_path) !== '') {
                $skip++;
            } else {
                $fail++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("PDF salvati: {$ok}; già presenti: {$skip}; senza labelData: {$fail}");

        return self::SUCCESS;
    }
}
