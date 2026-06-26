<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_movimentis', function (Blueprint $table) {
            $table->string('nota_interna', 500)->nullable()->after('riferimento');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_movimentis', function (Blueprint $table) {
            $table->dropColumn('nota_interna');
        });
    }
};
