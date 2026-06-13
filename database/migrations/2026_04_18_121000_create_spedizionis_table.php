<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spedizionis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ordine_id')->constrained('ordinis')->cascadeOnDelete();
            $table->foreignId('id_corrieres')->nullable()->constrained('corrieres')->nullOnDelete();
            $table->foreignId('id_tariffas')->nullable()->constrained('tariffas')->nullOnDelete();
            $table->string('tracking', 128)->nullable();
            $table->json('mittente_json');
            $table->json('destinatario_json');
            /** Netto vendita al cliente per questa spedizione (IVA esc.), coerente con il carrello. */
            $table->decimal('importo_netto_iva_esc', 12, 2);
            $table->decimal('vendita_trasporto_netto_iva_esc', 12, 2);
            $table->decimal('vendita_servizi_netto_iva_esc', 12, 2)->default(0);
            /**
             * Stima costo acquisto (IVA esc.): trasporto da listino tariffa (campo tariffa + eventuale logica futura).
             * I servizi in corrieri_servizi_aggiuntivis oggi alimentano anche le maggiorazioni cliente: verificare in contabilità.
             */
            $table->decimal('nostro_acquisto_trasporto_iva_esc', 12, 2)->nullable();
            $table->decimal('nostro_acquisto_servizi_iva_esc', 12, 2)->nullable();
            $table->decimal('nostro_acquisto_totale_iva_esc', 12, 2)->nullable();
            /** Opzionale: se un giorno si pagano spedizioni singole con metodi diversi; di norma resta null (metodo sull’ordine). */
            $table->foreignId('id_metodo_pagamentos')->nullable()->constrained('metodo_pagamentos')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'ordine_id']);
            $table->index('tracking');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spedizionis');
    }
};
