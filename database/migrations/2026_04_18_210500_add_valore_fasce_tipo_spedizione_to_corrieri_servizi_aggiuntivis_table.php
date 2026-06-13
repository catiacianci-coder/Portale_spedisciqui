<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
            $table->foreignId('id_tipo_spediziones')
                ->nullable()
                ->after('varie2')
                ->constrained('tipo_spediziones')
                ->nullOnDelete();
            $table->decimal('valore_minimo', 12, 2)->nullable()->after('id_tipo_spediziones');
            $table->decimal('fascia_da', 12, 4)->nullable()->after('valore_minimo');
            $table->decimal('fascia_a', 12, 4)->nullable()->after('fascia_da');
        });
    }

    public function down(): void
    {
        Schema::table('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_spediziones']);
            $table->dropColumn([
                'id_tipo_spediziones',
                'valore_minimo',
                'fascia_da',
                'fascia_a',
            ]);
        });
    }
};
