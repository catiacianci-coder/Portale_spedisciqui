<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('parametri_globalis')
            ->where('denominazione', 'giorni_ritiro')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();
        $row = [
            'denominazione' => 'giorni_ritiro',
            'valore_assoluto' => 4,
            'valore_percentuale' => null,
            'id_metodo_pagamentos' => null,
            'varie' => 'Numero di giorni lavorativi (lun–ven) selezionabili per la data ritiro a domicilio (a partire dal giorno del pagamento; sabato e domenica esclusi).',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('parametri_globalis', 'inizio_validita')) {
            $row['inizio_validita'] = $now->toDateString();
            $row['fine_validita'] = null;
        } else {
            $row['data_inizio'] = $now->toDateString();
            $row['data_fine'] = null;
        }

        DB::table('parametri_globalis')->insert($row);
    }

    public function down(): void
    {
        DB::table('parametri_globalis')
            ->where('denominazione', 'giorni_ritiro')
            ->delete();
    }
};
