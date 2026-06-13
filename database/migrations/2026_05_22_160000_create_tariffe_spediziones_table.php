<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Struttura da storage/app/tabella_tariffe_spedizioni.csv — breakdown economico per spedizione.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffe_spediziones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spedizione_id')->unique()->constrained('spedizionis')->cascadeOnDelete();
            $table->string('codice_interno', 40)->nullable();
            $table->decimal('costo_trasporto', 12, 2)->default(0);
            $table->decimal('costo_fuel', 12, 2)->default(0);
            $table->decimal('ricarico_trasporto', 12, 2)->default(0);
            $table->decimal('totale_cliente', 12, 2)->default(0);
            $table->decimal('costo_servizi_aggiuntivi', 12, 2)->default(0);
            $table->decimal('cliente_servizi_aggiuntivi', 12, 2)->default(0);
            $table->decimal('totale_spedizione', 12, 2)->default(0);
            $table->decimal('margine_lordo', 12, 2)->default(0);
            $table->timestamps();

            $table->index('codice_interno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffe_spediziones');
    }
};
