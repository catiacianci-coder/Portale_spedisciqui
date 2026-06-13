<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffe_spediziones', function (Blueprint $table): void {
            if (! Schema::hasColumn('tariffe_spediziones', 'totale_cliente_wallet')) {
                $table->decimal('totale_cliente_wallet', 12, 2)->default(0)->after('totale_cliente');
            }
            if (! Schema::hasColumn('tariffe_spediziones', 'totale_spedizione_wallet')) {
                $table->decimal('totale_spedizione_wallet', 12, 2)->default(0)->after('totale_spedizione');
            }
            if (! Schema::hasColumn('tariffe_spediziones', 'cliente_ivato_wallet')) {
                $table->decimal('cliente_ivato_wallet', 12, 2)->default(0)->after('cliente_ivato');
            }
        });

        Schema::table('ordinis', function (Blueprint $table): void {
            if (! Schema::hasColumn('ordinis', 'total_pagamento_wallet')) {
                $table->decimal('total_pagamento_wallet', 12, 2)->nullable()->after('total_pagamento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tariffe_spediziones', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('tariffe_spediziones', 'totale_cliente_wallet')) {
                $columns[] = 'totale_cliente_wallet';
            }
            if (Schema::hasColumn('tariffe_spediziones', 'totale_spedizione_wallet')) {
                $columns[] = 'totale_spedizione_wallet';
            }
            if (Schema::hasColumn('tariffe_spediziones', 'cliente_ivato_wallet')) {
                $columns[] = 'cliente_ivato_wallet';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('ordinis', function (Blueprint $table): void {
            if (Schema::hasColumn('ordinis', 'total_pagamento_wallet')) {
                $table->dropColumn('total_pagamento_wallet');
            }
        });
    }
};
