<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->string('codice_servizio', 512)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->string('codice_servizio', 64)->nullable()->change();
        });
    }
};
