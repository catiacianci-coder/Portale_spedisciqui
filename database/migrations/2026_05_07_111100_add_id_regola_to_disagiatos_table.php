<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disagiatos', function (Blueprint $table) {
            $table->foreignId('id_regola')->nullable()->after('comune_id')->constrained('regole')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disagiatos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_regola');
        });
    }
};
