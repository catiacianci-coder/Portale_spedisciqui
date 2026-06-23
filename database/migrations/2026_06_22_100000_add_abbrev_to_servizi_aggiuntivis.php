<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servizi_aggiuntivis', function (Blueprint $table) {
            $table->string('abbrev', 24)->nullable()->after('denominazione_servizio');
        });

        $map = [
            'Contrassegno' => 'COD',
            'Assicurazione' => 'ASS',
            'Consegna al piano' => 'PIANO',
            'Consegna su appuntamento' => 'APP',
            'Consegna di sabato' => 'SAB',
        ];

        foreach ($map as $denominazione => $abbrev) {
            DB::table('servizi_aggiuntivis')
                ->where('denominazione_servizio', $denominazione)
                ->update(['abbrev' => $abbrev, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('servizi_aggiuntivis', function (Blueprint $table) {
            $table->dropColumn('abbrev');
        });
    }
};
