<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametri_globalis', function (Blueprint $table) {
            $table->text('valore_testo')->nullable()->after('varie');
        });

        $now = now();
        $righe = [
            [
                'denominazione' => 'nome_impresa',
                'valore_assoluto' => null,
                'valore_percentuale' => null,
                'data_inizio' => null,
                'data_fine' => null,
                'id_metodo_pagamentos' => null,
                'varie' => 'Ragione sociale o nome commerciale mostrato nei documenti / portale.',
                'valore_testo' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'denominazione' => 'indirizzo_impresa',
                'valore_assoluto' => null,
                'valore_percentuale' => null,
                'data_inizio' => null,
                'data_fine' => null,
                'id_metodo_pagamentos' => null,
                'varie' => 'Sede legale o recapito (via, CAP, città).',
                'valore_testo' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'denominazione' => 'p_iva_impresa',
                'valore_assoluto' => null,
                'valore_percentuale' => null,
                'data_inizio' => null,
                'data_fine' => null,
                'id_metodo_pagamentos' => null,
                'varie' => 'Partita IVA (formato IT… o numerico).',
                'valore_testo' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'denominazione' => 'sito_impresa',
                'valore_assoluto' => null,
                'valore_percentuale' => null,
                'data_inizio' => null,
                'data_fine' => null,
                'id_metodo_pagamentos' => null,
                'varie' => 'URL sito web (es. https://…).',
                'valore_testo' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($righe as $r) {
            $exists = DB::table('parametri_globalis')->where('denominazione', $r['denominazione'])->exists();
            if (! $exists) {
                DB::table('parametri_globalis')->insert($r);
            }
        }
    }

    public function down(): void
    {
        DB::table('parametri_globalis')->whereIn('denominazione', [
            'nome_impresa',
            'indirizzo_impresa',
            'p_iva_impresa',
            'sito_impresa',
        ])->delete();

        Schema::table('parametri_globalis', function (Blueprint $table) {
            $table->dropColumn('valore_testo');
        });
    }
};
