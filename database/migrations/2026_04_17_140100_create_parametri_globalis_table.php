<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametri_globalis', function (Blueprint $table) {
            $table->id();
            $table->string('denominazione', 160);
            $table->decimal('valore_assoluto', 12, 4)->nullable();
            $table->decimal('valore_percentuale', 10, 4)->nullable();
            $table->date('data_inizio')->nullable();
            $table->date('data_fine')->nullable();
            $table->foreignId('id_metodo_pagamentos')->nullable()->constrained('metodo_pagamentos')->nullOnDelete();
            $table->string('varie')->nullable();
            $table->timestamps();
        });

        $now = now();

        $idBonifico = DB::table('metodo_pagamentos')->insertGetId([
            'metodo_pagamento' => 'Bonifico bancario',
            'abilitato' => true,
            'varie' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $idCarta = DB::table('metodo_pagamentos')->insertGetId([
            'metodo_pagamento' => 'Carta di credito/debito',
            'abilitato' => true,
            'varie' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $idWallet = DB::table('metodo_pagamentos')->insertGetId([
            'metodo_pagamento' => 'Wallet',
            'abilitato' => true,
            'varie' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('parametri_globalis')->insert([
            [
                'denominazione' => 'Aliquota IVA',
                'valore_assoluto' => null,
                'valore_percentuale' => 22,
                'data_inizio' => null,
                'data_fine' => null,
                'id_metodo_pagamentos' => null,
                'varie' => 'Percentuale IVA su imponibile (prezzi esclusi IVA).',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'denominazione' => 'Bonifico bancario',
                'valore_assoluto' => 0,
                'valore_percentuale' => 0,
                'data_inizio' => null,
                'data_fine' => null,
                'id_metodo_pagamentos' => $idBonifico,
                'varie' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'denominazione' => 'Carta di credito/debito',
                'valore_assoluto' => 0,
                'valore_percentuale' => 0,
                'data_inizio' => null,
                'data_fine' => null,
                'id_metodo_pagamentos' => $idCarta,
                'varie' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'denominazione' => 'Wallet',
                'valore_assoluto' => 0,
                'valore_percentuale' => -2,
                'data_inizio' => null,
                'data_fine' => null,
                'id_metodo_pagamentos' => $idWallet,
                'varie' => 'Sconto percentuale sul netto prima dell\'IVA (valore negativo = sconto).',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('parametri_globalis');
    }
};
