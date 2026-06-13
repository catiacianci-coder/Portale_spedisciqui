<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('wallet_ricarica_richiestas', 'id_metodo_pagamento_wallet_ricariches')) {
            return;
        }

        Schema::table('wallet_ricarica_richiestas', function (Blueprint $table) {
            $table->foreign('id_metodo_pagamento_wallet_ricariches', 'fk_wrr_metodo_ricarica')
                ->references('id')
                ->on('metodo_pagamento_wallet_ricariches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_ricarica_richiestas', function (Blueprint $table) {
            $table->dropForeign('fk_wrr_metodo_ricarica');
        });
    }
};
