<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rimborsi', function (Blueprint $table): void {
            if (! Schema::hasColumn('rimborsi', 'data_richiesta_trasferimento_esterno')) {
                $table->dateTime('data_richiesta_trasferimento_esterno')->nullable()->after('data_reale');
            }
            if (! Schema::hasColumn('rimborsi', 'data_trasferimento_esterno')) {
                $table->dateTime('data_trasferimento_esterno')->nullable()->after('data_richiesta_trasferimento_esterno');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rimborsi', function (Blueprint $table): void {
            if (Schema::hasColumn('rimborsi', 'data_trasferimento_esterno')) {
                $table->dropColumn('data_trasferimento_esterno');
            }
            if (Schema::hasColumn('rimborsi', 'data_richiesta_trasferimento_esterno')) {
                $table->dropColumn('data_richiesta_trasferimento_esterno');
            }
        });
    }
};
