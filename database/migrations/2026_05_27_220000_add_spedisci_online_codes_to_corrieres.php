<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->string('carrier_code', 128)->nullable()->after('piattaforma');
            $table->string('contract_code', 128)->nullable()->after('carrier_code');
        });

        DB::table('corrieres')
            ->where('nome_visualizzato', 'Poste Italiane - Delivery Business')
            ->update([
                'carrier_code' => 'postedeliverybusiness',
                'contract_code' => 'TPEp4Ph7OzIRWtTL',
            ]);
    }

    public function down(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->dropColumn(['carrier_code', 'contract_code']);
        });
    }
};
