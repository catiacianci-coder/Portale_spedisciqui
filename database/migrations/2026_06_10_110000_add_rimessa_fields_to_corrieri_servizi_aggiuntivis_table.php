<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
            $table->unsignedSmallInteger('rimessa_tra')->nullable()->after('valore_massimo');
            $table->unsignedSmallInteger('rimessa_clli')->nullable()->after('rimessa_tra');
        });

        DB::table('corrieri_servizi_aggiuntivis')
            ->whereIn('testo_servizio', ['Assicurazione', 'Contrassegno'])
            ->update([
                'rimessa_tra' => 10,
                'rimessa_clli' => 20,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
            $table->dropColumn(['rimessa_tra', 'rimessa_clli']);
        });
    }
};
