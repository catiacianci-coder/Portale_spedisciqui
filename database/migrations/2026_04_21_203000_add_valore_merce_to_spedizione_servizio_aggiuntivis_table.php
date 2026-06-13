<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
            $table->decimal('valore_merce', 12, 2)->nullable()->after('id_servizi_aggiuntivi');
        });
    }

    public function down(): void
    {
        Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
            $table->dropColumn('valore_merce');
        });
    }
};

