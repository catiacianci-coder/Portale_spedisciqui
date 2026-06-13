<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $denom = 'iban_cc_r_b';
        $now = now();

        $exists = DB::table('parametri_globalis')->where('denominazione', $denom)->exists();
        if ($exists) {
            DB::table('parametri_globalis')->where('denominazione', $denom)->update([
                'valore_testo' => '123456789009876543211234567',
                'varie' => 'Conto corrente (IBAN) per ricevere i bonifici bancari.',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('parametri_globalis')->insert([
            'denominazione' => $denom,
            'valore_assoluto' => null,
            'valore_percentuale' => null,
            'data_inizio' => null,
            'data_fine' => null,
            'id_metodo_pagamentos' => null,
            'varie' => 'Conto corrente (IBAN) per ricevere i bonifici bancari.',
            'valore_testo' => '123456789009876543211234567',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('parametri_globalis')->where('denominazione', 'iban_cc_r_b')->delete();
    }
};
