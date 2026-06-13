<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Struttura tabella da storage/app/tabella_ordinis.csv (+ tabella stato_ordinis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('stato_ordinis', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 32)->unique();
            $table->string('denominazione', 64);
            $table->timestamps();
        });

        DB::table('stato_ordinis')->insert([
            ['id' => 1, 'codice' => 'non_pagato', 'denominazione' => 'Non pagato', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'codice' => 'pagato', 'denominazione' => 'Pagato', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'codice' => 'annullato', 'denominazione' => 'Annullato', 'created_at' => now(), 'updated_at' => now()],
        ]);

        if (Schema::hasTable('spedizionis')) {
            Schema::table('spedizionis', function (Blueprint $table) {
                $table->dropForeign(['ordine_id']);
            });
        }

        if (Schema::hasTable('wallet_movimentis')) {
            Schema::table('wallet_movimentis', function (Blueprint $table) {
                $table->dropForeign(['ordine_id']);
            });
        }

        Schema::dropIfExists('ordinis');

        Schema::create('ordinis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stato_ordine_id')->default(1)->constrained('stato_ordinis')->restrictOnDelete();
            $table->string('metodo_pagamento', 120)->nullable();
            $table->foreignId('metodo_pagamento_ordinis_id')
                ->nullable()
                ->constrained('metodo_pagamento_ordinis')
                ->nullOnDelete();
            $table->decimal('costo_servizo', 12, 2)->default(0);
            $table->decimal('commissioni', 8, 4)->nullable();
            $table->decimal('total_pagamento', 12, 2)->nullable();
            $table->timestamp('data_pagamento')->nullable();
            $table->timestamp('annullato_in')->nullable();
            $table->string('cr', 64)->nullable();
            $table->string('payment_id', 128)->nullable();
            $table->string('token', 255)->nullable();
            $table->string('token_2', 255)->nullable();
            $table->string('stripe_checkout_session_id', 255)->nullable();
            $table->string('stripe_payment_intent_id', 255)->nullable();
            $table->string('stripe_refund_id', 255)->nullable();
            $table->decimal('stripe_refund_amount', 14, 2)->nullable();
            $table->timestamp('stripe_refunded_at')->nullable();
            $table->json('dettaglio_json');
            $table->string('varie4', 255)->nullable();
            $table->string('varie5', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'stato_ordine_id']);
            $table->index('data_pagamento');
        });

        if (Schema::hasTable('spedizionis')) {
            Schema::table('spedizionis', function (Blueprint $table) {
                $table->foreign('ordine_id')->references('id')->on('ordinis')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('wallet_movimentis')) {
            Schema::table('wallet_movimentis', function (Blueprint $table) {
                $table->foreign('ordine_id')->references('id')->on('ordinis')->nullOnDelete();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        //
    }
};
