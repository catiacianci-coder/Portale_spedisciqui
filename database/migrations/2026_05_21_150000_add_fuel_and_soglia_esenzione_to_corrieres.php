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
            $table->decimal('fuel', 10, 4)->default(5)->after('sardegna')
                ->comment('Percentuale carburante (es. 5 = 5%)');
            $table->decimal('soglia_esenzione', 12, 2)->default(3000)->after('fuel')
                ->comment('Soglia esenzione (es. valore merce EUR)');
        });

        DB::table('corrieres')->update([
            'fuel' => 5,
            'soglia_esenzione' => 3000,
        ]);
    }

    public function down(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->dropColumn(['fuel', 'soglia_esenzione']);
        });
    }
};
