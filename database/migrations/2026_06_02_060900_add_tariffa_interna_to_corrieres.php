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
            $table->boolean('tariffa_interna')->default(true)->after('attivo');
        });

        DB::table('corrieres')->whereIn('id', [1, 2, 4, 5, 6])->update(['tariffa_interna' => true]);
        DB::table('corrieres')->whereIn('id', [7, 8, 9])->update(['tariffa_interna' => false]);
    }

    public function down(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->dropColumn('tariffa_interna');
        });
    }
};
