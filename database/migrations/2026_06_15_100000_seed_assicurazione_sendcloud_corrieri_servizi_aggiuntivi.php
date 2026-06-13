<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Assicurazione via API Sendcloud per corrieri Poste/InPost (piattaforma sendcloud).
 * Costo fornitore da insurance_price; ricarico cliente 20% (ricarico_k91), fisso nostro 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $corrieriIds = DB::table('corrieres')
            ->where('piattaforma', 'sendcloud')
            ->where('attivo', true)
            ->where('tariffa_interna', false)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($corrieriIds === []) {
            return;
        }

        foreach ($corrieriIds as $corriereId) {
            DB::table('corrieri_servizi_aggiuntivis')->updateOrInsert(
                [
                    'id_corriere' => $corriereId,
                    'testo_servizio' => 'Assicurazione',
                    'id_tipo' => 1,
                    'min_fascia' => 2,
                    'max_fascia' => 5000,
                ],
                [
                    'fonte_servizio' => 'sendcloud',
                    'codice_servizio_corriere' => null,
                    'visualizzato' => true,
                    'percentuale_cor' => 0,
                    'ricarico_k91' => 0.2,
                    'valore_fisso_cor' => 0,
                    'valore_fisso_k91' => 0,
                    'valore_percentuale' => 0,
                    'valore_minimo' => null,
                    'valore_massimo' => null,
                    'rimessa_tra' => 10,
                    'rimessa_clli' => 20,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $corrieriIds = DB::table('corrieres')
            ->where('piattaforma', 'sendcloud')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($corrieriIds === []) {
            return;
        }

        DB::table('corrieri_servizi_aggiuntivis')
            ->whereIn('id_corriere', $corrieriIds)
            ->where('testo_servizio', 'Assicurazione')
            ->where('fonte_servizio', 'sendcloud')
            ->where('id_tipo', 1)
            ->where('min_fascia', 2)
            ->where('max_fascia', 5000)
            ->delete();
    }
};
