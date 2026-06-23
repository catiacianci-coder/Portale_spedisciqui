<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Stato tracking Spedisci.online: testo Stato + Luogo supera i 64 caratteri. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->string('tracking_status', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->string('tracking_status', 64)->nullable()->change();
        });
    }
};
