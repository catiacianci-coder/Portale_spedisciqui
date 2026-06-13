<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Struttura tabella da storage/app/tabella_spedizioni.csv (+ id PK).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement('DROP VIEW IF EXISTS spedizionis_debug');

        if (Schema::hasTable('spedizione_servizio_aggiuntivis')) {
            Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
                $table->dropForeign('fk_spsa_sped');
            });
        }

        if (Schema::hasTable('nc_pratica_righe')) {
            Schema::table('nc_pratica_righe', function (Blueprint $table) {
                $table->dropForeign(['spedizione_id']);
            });
        }

        Schema::dropIfExists('spedizionis');

        Schema::create('spedizionis', function (Blueprint $table) {
            $table->id();
            $table->string('codice_interno', 40)->nullable()->unique();
            $table->string('id_shipment', 128)->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ordine_id')->constrained('ordinis')->cascadeOnDelete();
            $table->string('stripe_payment_intent_id', 255)->nullable();
            $table->foreignId('spedizione_stato_id')
                ->nullable()
                ->constrained('stato_interno_spedizionis')
                ->nullOnDelete();
            $table->string('carrello_id', 64)->nullable();
            $table->foreignId('tipo_id')->nullable()->constrained('tipo_spediziones')->nullOnDelete();
            $table->foreignId('id_codice_servizio')->nullable()->constrained('corrieres')->nullOnDelete();
            $table->string('codice_servizio', 64)->nullable();
            $table->text('service_description')->nullable();
            $table->string('corriere', 255)->nullable();

            $table->string('nome_o', 120)->nullable();
            $table->string('cognome_o', 120)->nullable();
            $table->string('ragione_sociale_o', 255)->nullable();
            $table->string('cap_o', 16)->nullable();
            $table->string('citta_o', 160)->nullable();
            $table->string('indirizzo_o', 255)->nullable();
            $table->string('numero_o', 32)->nullable();
            $table->string('frazione_o', 120)->nullable();
            $table->string('stato_o', 8)->nullable();
            $table->string('tel_o', 40)->nullable();
            $table->string('email_o', 255)->nullable();
            $table->text('note_o')->nullable();

            $table->string('nome_d', 120)->nullable();
            $table->string('sobrenome_d', 120)->nullable();
            $table->string('ragione_sociale_d', 255)->nullable();
            $table->string('cap_d', 16)->nullable();
            $table->string('citta_d', 160)->nullable();
            $table->string('indirizzo_d', 255)->nullable();
            $table->string('numero_d', 32)->nullable();
            $table->string('frazione_d', 120)->nullable();
            $table->string('stato_d', 8)->nullable();
            $table->string('tel_d', 40)->nullable();
            $table->string('email_d', 255)->nullable();
            $table->text('note_d')->nullable();

            $table->decimal('altezza', 10, 4)->nullable();
            $table->decimal('larghezza', 10, 4)->nullable();
            $table->decimal('spessore', 10, 4)->nullable();
            $table->decimal('peso', 12, 4)->nullable();

            $table->timestamp('cancellata_il')->nullable();
            $table->boolean('compensata')->default(false);

            $table->foreignId('padre_reso')->nullable()->constrained('spedizionis')->nullOnDelete();
            $table->foreignId('padre_comp')->nullable()->constrained('spedizionis')->nullOnDelete();

            $table->string('tracking', 512)->nullable();
            $table->string('etiqueta_pdf_path', 512)->nullable();
            $table->timestamp('ldv_emessa_il')->nullable();
            $table->boolean('ldverro')->default(false);
            $table->string('tracking_status', 64)->nullable();
            $table->timestamp('traking_evento_em')->nullable();
            $table->timestamp('traking_consultato_il')->nullable();
            $table->timestamp('data_ritiro')->nullable();
            $table->string('codice_reso', 64)->nullable();
            $table->boolean('esiste_integrazione')->default(false);
            $table->boolean('reso')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'ordine_id']);
            $table->index('tracking');
            $table->index('carrello_id');
            $table->index('codice_reso');
        });

        if (Schema::hasTable('spedizione_servizio_aggiuntivis')) {
            Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
                $table->foreign('id_spedizionis', 'fk_spsa_sped')
                    ->references('id')->on('spedizionis')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('nc_pratica_righe')) {
            Schema::table('nc_pratica_righe', function (Blueprint $table) {
                $table->foreign('spedizione_id')->references('id')->on('spedizionis')->nullOnDelete();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Ripristino manuale: eseguire migrate:fresh su ambiente di sviluppo se necessario.
    }
};
