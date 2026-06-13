<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Creiamo la tabella che associa l'utente al suo stato attuale
        Schema::create('user_status', function (Blueprint $table) {
            $table->id();
            // ID dell'utente (collegato alla tabella users)
            $table->foreignId('id_utente')->constrained('users')->onDelete('cascade');
            // ID dello stato (collegato alla tabella status che abbiamo visto prima)
            $table->foreignId('id_status')->constrained('status')->onDelete('cascade');
            
            $table->timestamp('data_definizione')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_status');
    }
};