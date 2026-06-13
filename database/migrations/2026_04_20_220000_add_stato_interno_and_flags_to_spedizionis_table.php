<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->foreignId('stato_interno_spedizione_id')
                ->nullable()
                ->after('id_metodo_pagamentos')
                ->constrained('stato_interno_spedizionis')
                ->nullOnDelete();

            $table->timestamp('data_ritiro')
                ->nullable()
                ->after('stato_interno_spedizione_id');

            $table->boolean('reso')
                ->default(false)
                ->after('data_ritiro');

            $table->boolean('esiste_integrazione')
                ->default(false)
                ->after('reso');

            $table->index('data_ritiro');
            $table->index(['stato_interno_spedizione_id', 'reso', 'esiste_integrazione'], 'spedizioni_internal_state_flags_idx');
        });
    }

    public function down(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->dropIndex('spedizioni_internal_state_flags_idx');
            $table->dropIndex(['data_ritiro']);
            $table->dropConstrainedForeignId('stato_interno_spedizione_id');
            $table->dropColumn(['data_ritiro', 'reso', 'esiste_integrazione']);
        });
    }
};
