<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('origine_destinos', function (Blueprint $table) {
            $table->id();
            
            // 1. Colleghiamo il corriere alla tabella 'corrieres' (o come si chiama la tua tabella corrieri)
            $table->foreignId('id_corriere')->constrained('corrieres')->onDelete('cascade');
            
            // 2. Colleghiamo l'origine alla tabella 'comuni'
            $table->foreignId('id_comune_origine')->constrained('comuni')->onDelete('cascade');
            
            // 3. Colleghiamo il destino alla tabella 'comuni'
            $table->foreignId('id_comune_destino')->constrained('comuni')->onDelete('cascade');
            
            $table->string('varie')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('origine_destinos');
    }
};