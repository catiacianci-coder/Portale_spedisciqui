<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stato_interno_spedizionis') && ! Schema::hasTable('stato_spedizionis')) {
            $this->createStatoSpedizionisTable();
        } elseif (Schema::hasTable('stato_interno_spedizionis')) {
            if (Schema::hasTable('spedizionis')) {
                Schema::table('spedizionis', function (Blueprint $table): void {
                    $table->dropForeign(['spedizione_stato_id']);
                });
            }

            Schema::rename('stato_interno_spedizionis', 'stato_spedizionis');
        }

        $this->seedStati();

        if (Schema::hasTable('spedizionis') && Schema::hasTable('stato_spedizionis')) {
            $fkExists = collect(DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'spedizionis'
                 AND CONSTRAINT_NAME = 'spedizionis_spedizione_stato_id_foreign'"
            ))->isNotEmpty();

            if (! $fkExists) {
                Schema::table('spedizionis', function (Blueprint $table): void {
                    $table->foreign('spedizione_stato_id')
                        ->references('id')
                        ->on('stato_spedizionis')
                        ->nullOnDelete();
                });
            }

            $this->backfillSpedizioni();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('stato_spedizionis')) {
            return;
        }

        if (Schema::hasTable('spedizionis')) {
            Schema::table('spedizionis', function (Blueprint $table): void {
                $table->dropForeign(['spedizione_stato_id']);
            });
        }

        Schema::rename('stato_spedizionis', 'stato_interno_spedizionis');

        if (Schema::hasTable('spedizionis')) {
            Schema::table('spedizionis', function (Blueprint $table): void {
                $table->foreign('spedizione_stato_id')
                    ->references('id')
                    ->on('stato_interno_spedizionis')
                    ->nullOnDelete();
            });
        }
    }

    private function createStatoSpedizionisTable(): void
    {
        Schema::create('stato_spedizionis', function (Blueprint $table): void {
            $table->id();
            $table->string('denominazione_stato', 120);
            $table->timestamps();
        });
    }

    private function seedStati(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('stato_spedizionis')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $now = now();
        foreach ([
            1 => 'non pagata',
            2 => 'pagata',
            3 => 'generata',
            4 => 'annullata',
            5 => 'rimborsata',
        ] as $id => $denominazione) {
            DB::table('stato_spedizionis')->insert([
                'id' => $id,
                'denominazione_stato' => $denominazione,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillSpedizioni(): void
    {
        DB::statement("
            UPDATE spedizionis s
            INNER JOIN ordinis o ON o.id = s.ordine_id
            INNER JOIN stato_ordinis so ON so.id = o.stato_ordine_id
            LEFT JOIN rimborsi r ON r.spedizione_id = s.id
            SET s.spedizione_stato_id = CASE
                WHEN r.id IS NOT NULL THEN 5
                WHEN s.esiste_integrazione = 1 OR s.ldv_emessa_il IS NOT NULL THEN 3
                WHEN so.codice = 'annullato' THEN 4
                WHEN so.codice = 'pagato' THEN 2
                ELSE 1
            END
        ");
    }
};
