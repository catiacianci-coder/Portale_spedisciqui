<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_descrizionis', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['credito', 'debito']);
            $table->string('codice', 64)->unique();
            $table->string('descrizione', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_descrizionis');
    }
};
