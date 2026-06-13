<?php

namespace App\Services;

use App\Models\disagiato;
use App\Models\regola;

class RegolePricingService
{
    public function calcolaSovrattassaDisagiato(
        int $idCorriere,
        int $idComuneOrigine,
        int $idComuneDestino,
        float $pesoKg
    ): float {
        $regolaDisagiato = disagiato::query()
            ->where('corriere_id', $idCorriere)
            ->whereIn('comune_id', [$idComuneOrigine, $idComuneDestino])
            ->first(['id_regola', 'varie_1']);

        if ($regolaDisagiato && $regolaDisagiato->id_regola) {
            $cfg = regola::query()->where('attiva', true)->find((int) $regolaDisagiato->id_regola);
            if ($cfg) {
                if ($cfg->tipo_formula === 'per_blocchi_peso') {
                    $blocco = (float) ($cfg->blocco_peso_kg ?? 0);
                    $fisso = (float) ($cfg->valore_fisso ?? 0);
                    if ($blocco > 0 && $fisso > 0) {
                        return ceil(max(0.0, $pesoKg) / $blocco) * $fisso;
                    }
                }

                if ($cfg->tipo_formula === 'fisso') {
                    $fisso = (float) ($cfg->valore_fisso ?? 0);
                    if ($fisso > 0) {
                        return $fisso;
                    }
                }
            }
        }

        if ($regolaDisagiato && $regolaDisagiato->varie_1 === 'BRT_CAMPIONE_70_X_100KG' && $idCorriere === 1) {
            return ceil(max(0.0, $pesoKg) / 100) * 70.0;
        }

        return 0.0;
    }
}
