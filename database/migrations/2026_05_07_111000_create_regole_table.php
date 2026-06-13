<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regole', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descrizione')->nullable();
            $table->decimal('peso_min', 10, 3)->nullable();
            $table->decimal('peso_max', 10, 3)->nullable();
            $table->decimal('percentuale', 10, 2)->nullable();
            $table->string('applica_su')->nullable(); // es: tariffa, peso, altro
            $table->decimal('valore_fisso', 12, 2)->nullable();
            $table->string('cap_origine', 10)->nullable();
            $table->string('cap_destino', 10)->nullable();
            $table->string('tipo_formula')->nullable(); // es: per_blocchi_peso
            $table->decimal('blocco_peso_kg', 10, 3)->nullable();
            $table->string('varie1')->nullable();
            $table->string('varie2')->nullable();
            $table->string('varie3')->nullable();
            $table->string('varie4')->nullable();
            $table->string('varie5')->nullable();
            $table->boolean('attiva')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regole');
    }
};
