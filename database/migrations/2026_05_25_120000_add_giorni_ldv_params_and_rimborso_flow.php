<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'denominazione' => 'giorni_ldv_no',
                'valore_assoluto' => 0,
                'varie' => 'Giorni lavorativi (lun–ven) per rimborso senza etichetta/LDV: esecuzione immediata se 0.',
            ],
            [
                'denominazione' => 'giorni_ldv_si',
                'valore_assoluto' => 15,
                'varie' => 'Giorni lavorativi (lun–ven) per rimborso con etichetta/LDV emessa.',
            ],
        ] as $row) {
            $exists = DB::table('parametri_globalis')
                ->where('denominazione', $row['denominazione'])
                ->exists();

            if (! $exists) {
                DB::table('parametri_globalis')->insert([
                    'denominazione' => $row['denominazione'],
                    'valore_assoluto' => $row['valore_assoluto'],
                    'valore_percentuale' => null,
                    'data_inizio' => null,
                    'data_fine' => null,
                    'id_metodo_pagamentos' => null,
                    'varie' => $row['varie'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('rimborsi') && Schema::hasColumn('rimborsi', 'stripe_payment_intent_id')) {
            DB::statement(
                'ALTER TABLE `rimborsi` MODIFY `stripe_payment_intent_id` VARCHAR(255) NULL'
            );
        }
    }

    public function down(): void
    {
        DB::table('parametri_globalis')
            ->whereIn('denominazione', ['giorni_ldv_no', 'giorni_ldv_si'])
            ->delete();
    }
};
