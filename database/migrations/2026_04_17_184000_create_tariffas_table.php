<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffas', function (Blueprint $table) {
            $table->id();
            $table->date('data_modifica')->nullable();
            $table->date('data_sospensione')->nullable();
            $table->foreignId('id_corrieres')->constrained('corrieres')->cascadeOnDelete();
            $table->string('servizio')->nullable();
            $table->foreignId('id_tipo_spediziones')->constrained('tipo_spediziones')->cascadeOnDelete();
            $table->decimal('peso_da', 10, 3)->nullable();
            $table->decimal('peso_a', 10, 3)->nullable();
            $table->string('livello')->nullable();
            $table->decimal('tariffa', 10, 2)->nullable();
            $table->decimal('lato_max', 10, 2)->nullable();
            $table->decimal('lato_med', 10, 2)->nullable();
            $table->decimal('lato_min', 10, 2)->nullable();
            $table->decimal('max', 10, 2)->nullable();
            $table->decimal('peso_max_collo', 10, 3)->nullable();
            $table->decimal('ricarico', 10, 2)->nullable();
            $table->string('nazione_partenza')->nullable();
            $table->string('nazione_arrivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffas');
    }
};
