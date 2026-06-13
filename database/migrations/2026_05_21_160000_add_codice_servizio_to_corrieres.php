<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->string('codice_servizio', 64)->nullable()->after('nome_servizio');
        });
    }

    public function down(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->dropColumn('codice_servizio');
        });
    }
};
