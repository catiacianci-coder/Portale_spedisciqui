<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mittenzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('denominazione_ragione_sociale')->nullable();

            $table->string('indirizzo')->nullable();
            $table->string('civico', 10)->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('citta')->nullable();
            $table->string('provincia', 2)->nullable();
            $table->foreignId('id_comune')->nullable()->constrained('comuni')->nullOnDelete();

            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();

            $table->boolean('is_preferito')->default(false);
            $table->boolean('is_fatturazione')->default(false);

            $table->string('varie1')->nullable();
            $table->string('varie2')->nullable();
            $table->string('varie3')->nullable();
            $table->string('varie4')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_preferito']);
            $table->index(['user_id', 'is_fatturazione']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mittenzes');
    }
};
