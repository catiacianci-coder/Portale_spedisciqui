<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
            // L'FK su id_corriere usa l'indice leftmost: senza un indice dedicato MySQL blocca il drop dell'unique.
            $table->index('id_corriere', 'corrieri_servizi_aggiuntivis_id_corriere_index');
            $table->dropUnique('corriere_servizio_aggiuntivo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
            $table->unique(['id_corriere', 'id_servizi_aggiuntivi'], 'corriere_servizio_aggiuntivo_unique');
            $table->dropIndex('corrieri_servizi_aggiuntivis_id_corriere_index');
        });
    }
};
