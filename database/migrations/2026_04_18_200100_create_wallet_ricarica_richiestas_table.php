<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ricarica_richiestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            /** Importo richiesto (euro, intero in fase cliente; salvato come decimale). */
            $table->decimal('importo', 14, 2);
            $table->string('stato', 24)->default('in_attesa');
            $table->foreignId('wallet_movimento_id')->nullable()->constrained('wallet_movimentis')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['stato', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ricarica_richiestas');
    }
};
