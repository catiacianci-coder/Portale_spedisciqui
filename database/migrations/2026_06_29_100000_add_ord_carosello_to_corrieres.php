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
            $table->unsignedSmallInteger('ord_carosello')->default(0)->after('attivo');
        });

        DB::table('corrieres')->update(['ord_carosello' => 0]);

        $ordini = [
            4 => 1,  // SDA M
            11 => 2, // InPost Locker Medium
            9 => 3,  // Poste delivery express
            6 => 4,  // Liccardi
            13 => 5, // GLS Light
        ];

        foreach ($ordini as $id => $ord) {
            DB::table('corrieres')->where('id', $id)->update(['ord_carosello' => $ord]);
        }
    }

    public function down(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->dropColumn('ord_carosello');
        });
    }
};
