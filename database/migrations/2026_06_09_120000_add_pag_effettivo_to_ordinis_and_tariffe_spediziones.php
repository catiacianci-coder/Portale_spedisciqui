<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordinis', function (Blueprint $table): void {
            if (! Schema::hasColumn('ordinis', 'pag_effettivo_or')) {
                $table->decimal('pag_effettivo_or', 12, 2)->nullable()->after('total_pagamento_wallet');
            }
        });

        Schema::table('tariffe_spediziones', function (Blueprint $table): void {
            if (! Schema::hasColumn('tariffe_spediziones', 'pag_effettivo_sp')) {
                $table->decimal('pag_effettivo_sp', 12, 2)->nullable()->after('cliente_ivato_wallet');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordinis', function (Blueprint $table): void {
            if (Schema::hasColumn('ordinis', 'pag_effettivo_or')) {
                $table->dropColumn('pag_effettivo_or');
            }
        });

        Schema::table('tariffe_spediziones', function (Blueprint $table): void {
            if (Schema::hasColumn('tariffe_spediziones', 'pag_effettivo_sp')) {
                $table->dropColumn('pag_effettivo_sp');
            }
        });
    }
};
