<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modalita_pagamento_nconformitas', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 32)->unique();
            $table->string('nome', 120);
            $table->boolean('abilitato')->default(true);
            $table->unsignedSmallInteger('ordine')->default(0);
            $table->timestamps();
        });

        Schema::create('nc_pratiche', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('numero_pratica', 32)->nullable()->unique();
            $table->string('stato', 16)->default('aperto')->comment('aperto|chiuso');
            $table->string('pdf_path', 512)->nullable();
            $table->foreignId('creato_da_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'stato']);
            $table->index('created_at');
        });

        Schema::create('nc_pratica_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nc_pratica_id')->constrained('nc_pratiche')->cascadeOnDelete();
            $table->foreignId('spedizione_id')->nullable()->constrained('spedizionis')->nullOnDelete();
            $table->string('codice_interno', 40);
            $table->decimal('altezza_dich', 10, 3)->nullable();
            $table->decimal('larghezza_dich', 10, 3)->nullable();
            $table->decimal('spessore_dich', 10, 3)->nullable();
            $table->decimal('peso_dich', 10, 3)->nullable();
            $table->decimal('altezza_corriere', 10, 3)->nullable();
            $table->decimal('larghezza_corriere', 10, 3)->nullable();
            $table->decimal('spessore_corriere', 10, 3)->nullable();
            $table->decimal('peso_corriere', 10, 3)->nullable();
            $table->decimal('prezzo_pagato', 14, 2);
            $table->decimal('importo_dovuto', 14, 2);
            $table->decimal('delta', 14, 2);
            $table->string('stato_riga', 16)->default('non_pagato')->comment('non_pagato|pagato');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('data_pagamento_ordine')->nullable();
            $table->string('corriere_nome_visualizzato', 255)->nullable();
            $table->timestamps();

            $table->index(['nc_pratica_id', 'stato_riga']);
            $table->index('codice_interno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nc_pratica_righe');
        Schema::dropIfExists('nc_pratiche');
        Schema::dropIfExists('modalita_pagamento_nconformitas');
    }
};
