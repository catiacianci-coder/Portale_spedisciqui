<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('spedizione_servizio_aggiuntivis');

        Schema::create('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_spedizionis');
            $table->unsignedBigInteger('id_corrieri_servizi_aggiuntivis')->nullable();
            $table->unsignedBigInteger('id_servizi_aggiuntivi');
            /** Maggiorazione applicata al cliente sul trasporto (snapshot al momento ordine). */
            $table->decimal('maggiorazione_pct', 8, 4)->default(0);
            $table->decimal('maggiorazione_abs', 10, 2)->default(0);
            /** Quota stimata di costo/acquisto per questo servizio (IVA esc.), se distinta dalla maggiorazione. */
            $table->decimal('nostro_acquisto_stimato_iva_esc', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['id_spedizionis', 'id_servizi_aggiuntivi'], 'idx_spsa_sped_srv');

            $table->foreign('id_spedizionis', 'fk_spsa_sped')
                ->references('id')->on('spedizionis')->cascadeOnDelete();
            $table->foreign('id_corrieri_servizi_aggiuntivis', 'fk_spsa_csrv')
                ->references('id')->on('corrieri_servizi_aggiuntivis')->nullOnDelete();
            $table->foreign('id_servizi_aggiuntivi', 'fk_spsa_srv')
                ->references('id')->on('servizi_aggiuntivis')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spedizione_servizio_aggiuntivis');
    }
};
