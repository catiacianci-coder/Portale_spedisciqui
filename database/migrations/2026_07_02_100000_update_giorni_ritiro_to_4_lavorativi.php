<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('parametri_globalis')
            ->where('denominazione', 'giorni_ritiro')
            ->update([
                'valore_assoluto' => 4,
                'varie' => 'Numero di giorni lavorativi (lun–ven) selezionabili per la data ritiro a domicilio (a partire dal giorno del pagamento; sabato e domenica esclusi).',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('parametri_globalis')
            ->where('denominazione', 'giorni_ritiro')
            ->update([
                'valore_assoluto' => 5,
                'varie' => 'Numero di giorni di calendario selezionabili per la data ritiro SDA (a partire dal giorno del pagamento).',
                'updated_at' => now(),
            ]);
    }
};
