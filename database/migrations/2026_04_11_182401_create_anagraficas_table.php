<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anagrafiche', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Identificativi Fiscali (Fase 2 del flusso)
            $table->string('codice_fiscale', 16)->nullable(); 
            $table->string('partita_iva', 11)->nullable();
            
            // Dati societari (Percorso Ditta e Società)
            $table->string('denominazione_ragione_sociale')->nullable();
            
            // Persona Fisica / Referente / Titolare
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            
            // Indirizzo (Percorso Privato / Sede Legale)
            $table->string('indirizzo')->nullable();
            $table->string('civico', 10)->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('citta')->nullable();
            $table->string('provincia', 2)->nullable();
            
            // Contatti e Fatturazione (Fase 3)
            $table->string('telefono')->nullable();
            $table->string('pec')->nullable();
            $table->string('codice_sdi', 7)->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anagrafiche');
    }
};