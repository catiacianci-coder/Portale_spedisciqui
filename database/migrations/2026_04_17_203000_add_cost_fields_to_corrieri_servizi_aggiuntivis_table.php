<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
            $table->decimal('costo_percentuale', 8, 4)->nullable()->after('id_servizi_aggiuntivi');
            $table->decimal('costo_valore_assoluto', 10, 2)->nullable()->after('costo_percentuale');
            $table->text('varie1')->nullable()->after('costo_valore_assoluto');
            $table->text('varie2')->nullable()->after('varie1');
        });
    }

    public function down(): void
    {
        Schema::table('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
            $table->dropColumn([
                'costo_percentuale',
                'costo_valore_assoluto',
                'varie1',
                'varie2',
            ]);
        });
    }
};
