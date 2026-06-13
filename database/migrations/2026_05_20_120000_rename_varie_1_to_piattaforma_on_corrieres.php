<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->renameColumn('varie_1', 'piattaforma');
        });
    }

    public function down(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->renameColumn('piattaforma', 'varie_1');
        });
    }
};
