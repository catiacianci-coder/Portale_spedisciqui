<?php

namespace App\Console\Commands;

use App\Models\spedizione;
use App\Support\SpedizioneCampiScalariFromJson;
use Illuminate\Console\Command;

class BackfillSpedizioniNominativiCommand extends Command
{
    protected $signature = 'spedizioni:backfill-nominativi';

    protected $description = 'Allinea i campi scalari mittente/destinatario sulle spedizioni storiche.';

    public function handle(): int
    {
        $tot = 0;
        $agg = 0;

        spedizione::query()
            ->with(['user.anagrafica'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$tot, &$agg): void {
                foreach ($rows as $s) {
                    $tot++;

                    $m = is_array($s->mittente_json) ? $s->mittente_json : [];
                    $d = is_array($s->destinatario_json) ? $s->destinatario_json : [];
                    $p = is_array($s->pacco_json) ? $s->pacco_json : [];
                    $attrs = SpedizioneCampiScalariFromJson::estrai($m, $d, $p);

                    // Fallback mittente dall'anagrafica utente quando i vecchi JSON non avevano nome/cognome.
                    $anag = $s->user?->anagrafica;
                    if ($anag) {
                        $attrs['mittente_nome'] = $attrs['mittente_nome'] ?: ($anag->nome ?: null);
                        $attrs['mittente_cognome'] = $attrs['mittente_cognome'] ?: ($anag->cognome ?: null);
                        $attrs['mittente_indirizzo'] = $attrs['mittente_indirizzo'] ?: ($anag->indirizzo ?: null);
                        $attrs['mittente_numero'] = $attrs['mittente_numero'] ?: ($anag->civico ?: null);
                        $attrs['mittente_cap'] = $attrs['mittente_cap'] ?: ($anag->cap ?: null);
                        $attrs['mittente_citta'] = $attrs['mittente_citta'] ?: ($anag->citta ?: null);
                        $attrs['mittente_provincia'] = $attrs['mittente_provincia'] ?: ($anag->provincia ?: null);
                    }

                    // Se assenti nei record storici, mettiamo un placeholder neutro per non perdere la simulazione.
                    if (($attrs['destinatario_nome'] ?? null) === null && ($attrs['destinatario_cognome'] ?? null) === null) {
                        $attrs['destinatario_nome'] = 'N/D';
                    }

                    $dirty = false;
                    foreach ($attrs as $k => $v) {
                        if ($s->{$k} !== $v) {
                            $dirty = true;
                            break;
                        }
                    }
                    if (! $dirty) {
                        continue;
                    }

                    $s->forceFill($attrs)->saveQuietly();
                    $agg++;
                }
            });

        $this->info("Spedizioni analizzate: {$tot}");
        $this->info("Spedizioni aggiornate: {$agg}");

        return self::SUCCESS;
    }
}
