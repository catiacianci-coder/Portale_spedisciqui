<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffas', function (Blueprint $table) {
            $table->dropColumn('ricarico');
        });

        Schema::table('tariffas', function (Blueprint $table) {
            $table->foreignId('id_ricarico')->nullable()->after('peso_max_collo')->constrained('ricarichi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tariffas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_ricarico');
        });

        Schema::table('tariffas', function (Blueprint $table) {
            $table->decimal('ricarico', 10, 2)->nullable()->after('peso_max_collo');
        });
    }
};
