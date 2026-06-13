<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('parametri_globalis')
            ->where('denominazione', 'Ricarica wallet minimo (EUR)')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();
        DB::table('parametri_globalis')->insert([
            'denominazione' => 'Ricarica wallet minimo (EUR)',
            'valore_assoluto' => 150,
            'valore_percentuale' => null,
            'data_inizio' => null,
            'data_fine' => null,
            'id_metodo_pagamentos' => null,
            'varie' => 'Importo minimo intero in euro per una ricarica wallet (come da policy commerciale).',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('parametri_globalis')
            ->where('denominazione', 'Ricarica wallet minimo (EUR)')
            ->delete();
    }
};
