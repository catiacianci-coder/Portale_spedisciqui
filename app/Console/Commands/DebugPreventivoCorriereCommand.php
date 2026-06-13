<?php

namespace App\Console\Commands;

use App\Models\corriere;
use App\Models\tariffa;
use App\Models\tipo_spedizone;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DebugPreventivoCorriereCommand extends Command
{
    protected $signature = 'debug:preventivo-corriere {corriere_id=4} {--tipo=1} {--peso=2} {--altezza=30} {--larghezza=20} {--spessore=15}';

    protected $description = 'Diagnostica perché un corriere non ottiene tariffa in preventivo';

    public function handle(): int
    {
        $id = (int) $this->argument('corriere_id');
        $corriere = corriere::query()->find($id);
        if (! $corriere) {
            $this->error("Corriere {$id} non trovato.");

            return self::FAILURE;
        }

        $idTipo = (int) $this->option('tipo');
        $peso = (float) $this->option('peso');
        $dims = [(float) $this->option('altezza'), (float) $this->option('larghezza'), (float) $this->option('spessore')];
        rsort($dims);
        [$latoMax, $latoMed, $latoMin] = $dims;
        $sommaLati = $latoMax + $latoMed + $latoMin;

        $this->info("Corriere: {$corriere->nome_corriere} | attivo={$corriere->attivo} | tipo_o_d={$corriere->tipo_o_d}");
        $this->info('Tipi spedizione DB: '.tipo_spedizone::query()->pluck('tipo_spedizione', 'id')->map(fn ($n, $i) => "{$i}={$n}")->implode(', '));
        $this->info("Test: tipo_spedizione_id={$idTipo}, peso={$peso}, lati={$latoMax}/{$latoMed}/{$latoMin}, somma={$sommaLati}");

        $tot = tariffa::query()->where('id_corrieres', $id)->count();
        $perTipo = tariffa::query()->where('id_corrieres', $id)->where('id_tipo_spediziones', $idTipo)->count();
        $this->info("Tariffe totali corriere {$id}: {$tot}, per tipo {$idTipo}: {$perTipo}");

        $oggi = Carbon::today();
        $candidati = tariffa::query()
            ->where('id_corrieres', $id)
            ->where('id_tipo_spediziones', $idTipo)
            ->where(function ($q) use ($peso) {
                $q->whereNull('peso_da')->orWhere('peso_da', '<=', $peso);
            })
            ->where(function ($q) use ($peso) {
                $q->whereNull('peso_a')->orWhere('peso_a', '>=', $peso);
            })
            ->where(function ($q) use ($oggi) {
                $q->whereNull('data_sospensione')->orWhereDate('data_sospensione', '>', $oggi);
            })
            ->orderBy('tariffa')
            ->get();

        $this->info('Candidati peso/data: '.$candidati->count());

        foreach ($candidati as $t) {
            $latoMaxTariffaCm = $this->latoMaxCm($t->lato_max);
            $okDim = true;
            $motivi = [];
            if ($latoMaxTariffaCm !== null && $latoMax > $latoMaxTariffaCm) {
                $okDim = false;
                $motivi[] = "lato_max pacco {$latoMax} > {$latoMaxTariffaCm} cm tariffa";
            }
            if ($t->max !== null && $sommaLati > (float) $t->max) {
                $okDim = false;
                $motivi[] = "somma lati {$sommaLati} > max {$t->max}";
            }
            if ($t->peso_max_collo !== null && $peso > (float) $t->peso_max_collo) {
                $okDim = false;
                $motivi[] = "peso {$peso} > peso_max_collo {$t->peso_max_collo}";
            }
            if ($okDim) {
                $this->info("OK tariffa id={$t->id} prezzo={$t->tariffa}");

                return self::SUCCESS;
            }
            $this->warn("Tariffa id={$t->id} scartata: ".implode('; ', $motivi));
        }

        if ($perTipo === 0) {
            $altri = tariffa::query()->where('id_corrieres', $id)->distinct()->pluck('id_tipo_spediziones')->all();
            $this->error('Nessuna tariffa per questo tipo. Tipi presenti nel CSV/DB: '.implode(', ', $altri));
            $this->line('Probabile fix: imposta id_tipo_spediziones=1 (Pacco) nel CSV Poste se in home scegli Pacco.');
        } else {
            $this->error('Tariffe per tipo ci sono ma nessuna passa vincoli dimensioni/peso.');
        }

        return self::FAILURE;
    }

    private function latoMaxCm(mixed $valore): ?float
    {
        if ($valore === null || $valore === '') {
            return null;
        }
        $v = (float) $valore;

        return $v <= 0 ? null : ($v <= 10 ? $v * 100 : $v);
    }
}
