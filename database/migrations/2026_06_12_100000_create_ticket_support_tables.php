<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_stati', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 32)->unique();
            $table->string('nome', 120);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('ticket_tipo_problemas', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 64)->nullable()->unique();
            $table->string('nome', 200);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ticket_stato_id')->constrained('ticket_stati');
            $table->foreignId('ticket_tipo_problema_id')->nullable()->constrained('ticket_tipo_problemas')->nullOnDelete();
            $table->foreignId('ordine_id')->nullable()->constrained('ordinis')->nullOnDelete();
            $table->foreignId('spedizione_id')->nullable()->constrained('spedizionis')->nullOnDelete();
            $table->string('oggetto', 500);
            $table->timestamp('cliente_ultima_visualizacao_at')->nullable();
            $table->unsignedBigInteger('cliente_ultima_messaggio_id_visto')->nullable();
            $table->string('campo_1', 500)->nullable();
            $table->string('campo_2', 500)->nullable();
            $table->string('campo_3', 500)->nullable();
            $table->string('campo_4', 500)->nullable();
            $table->string('campo_5', 500)->nullable();
            $table->string('campo_6', 500)->nullable();
            $table->string('campo_7', 500)->nullable();
            $table->string('campo_8', 500)->nullable();
            $table->string('campo_9', 500)->nullable();
            $table->timestamps();

            $table->index(['ticket_stato_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ticket_messaggi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_staff')->default(false);
            $table->text('body');
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        $now = now();

        DB::table('ticket_stati')->insert([
            ['codigo' => 'novo', 'nome' => 'Nuovo', 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'aberto', 'nome' => 'Aperto', 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'em_espera', 'nome' => 'In attesa (cliente)', 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'em_tratamento', 'nome' => 'In lavorazione (team)', 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'resolvido', 'nome' => 'Risolto', 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('ticket_tipo_problemas')->insert([
            ['codigo' => 'entrega', 'nome' => 'Consegna', 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'etiqueta_nao_gerada', 'nome' => 'Etichetta non generata', 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messaggi');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_tipo_problemas');
        Schema::dropIfExists('ticket_stati');
    }
};
