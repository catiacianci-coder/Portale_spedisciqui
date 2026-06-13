<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->unsignedBigInteger('to_service_point')->nullable()->after('note_d');
            $table->string('nome_punto', 255)->nullable()->after('to_service_point');
            $table->string('to_post_number', 64)->nullable()->after('nome_punto');
        });
    }

    public function down(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->dropColumn(['to_service_point', 'nome_punto', 'to_post_number']);
        });
    }
};
