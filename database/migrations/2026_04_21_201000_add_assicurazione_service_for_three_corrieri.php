<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $assicurazioneId = (int) (DB::table('servizi_aggiuntivis')
            ->where('denominazione_servizio', 'Assicurazione')
            ->value('id') ?? 0);

        if ($assicurazioneId < 1) {
            $assicurazioneId = (int) DB::table('servizi_aggiuntivis')->insertGetId([
                'denominazione_servizio' => 'Assicurazione',
                'varie' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $corrieriIds = DB::table('corrieres')
            ->orderBy('id')
            ->limit(3)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($corrieriIds as $corriereId) {
            DB::table('corrieri_servizi_aggiuntivis')->updateOrInsert(
                [
                    'id_corriere' => $corriereId,
                    'id_servizi_aggiuntivi' => $assicurazioneId,
                    'id_tipo_spediziones' => null,
                    'fascia_da' => 0,
                    'fascia_a' => null,
                ],
                [
                    'valore_minimo' => 3,
                    'costo_percentuale' => 8,
                    'costo_valore_assoluto' => 0,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $assicurazioneId = (int) (DB::table('servizi_aggiuntivis')
            ->where('denominazione_servizio', 'Assicurazione')
            ->value('id') ?? 0);

        if ($assicurazioneId < 1) {
            return;
        }

        DB::table('corrieri_servizi_aggiuntivis')
            ->where('id_servizi_aggiuntivi', $assicurazioneId)
            ->whereNull('id_tipo_spediziones')
            ->where('fascia_da', 0)
            ->whereNull('fascia_a')
            ->where('valore_minimo', 3)
            ->where('costo_percentuale', 8)
            ->delete();
    }
};

