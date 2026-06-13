<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_ricarica_richiestas', function (Blueprint $table) {
            $table->foreignId('id_metodo_pagamentos')
                ->nullable()
                ->after('importo')
                ->constrained('metodo_pagamentos')
                ->nullOnDelete();
            /** Riferimento sessione / intent gateway (PSP, ecc.) */
            $table->string('token_pagamento', 512)->nullable()->after('id_metodo_pagamentos');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_ricarica_richiestas', function (Blueprint $table) {
            $table->dropForeign(['id_metodo_pagamentos']);
            $table->dropColumn(['id_metodo_pagamentos', 'token_pagamento']);
        });
    }
};
