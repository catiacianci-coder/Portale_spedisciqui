<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mittenzes', function (Blueprint $table) {
            if (! Schema::hasColumn('mittenzes', 'sede_liccardi')) {
                $table->boolean('sede_liccardi')->default(false)->after('is_fatturazione');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mittenzes', function (Blueprint $table) {
            if (Schema::hasColumn('mittenzes', 'sede_liccardi')) {
                $table->dropColumn('sede_liccardi');
            }
        });
    }
};
