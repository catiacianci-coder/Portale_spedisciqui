<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordinis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('codice', 40)->unique();
            $table->string('stato', 32)->default('non_pagato');
            $table->decimal('totale_netto_iva_esc', 12, 2);
            $table->foreignId('id_metodo_pagamentos')->nullable()->constrained('metodo_pagamentos')->nullOnDelete();
            $table->decimal('totale_imponibile', 12, 2)->nullable();
            $table->decimal('totale_iva', 12, 2)->nullable();
            $table->decimal('totale_ivato', 12, 2)->nullable();
            $table->json('dettaglio_json');
            $table->string('varie')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'stato']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordinis');
    }
};
