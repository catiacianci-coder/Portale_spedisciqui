<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_movimentis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['credito', 'debito']);
            $table->foreignId('wallet_descrizione_id')->constrained('wallet_descrizionis')->restrictOnDelete();
            /** Importo sempre positivo; il segno contabile è dato da `tipo` (credito +, debito −). */
            $table->decimal('importo', 14, 2);
            $table->dateTime('data_movimento');
            $table->string('riferimento', 255)->nullable();
            $table->foreignId('ordine_id')->nullable()->constrained('ordinis')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'data_movimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_movimentis');
    }
};
