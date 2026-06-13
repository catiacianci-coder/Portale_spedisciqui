<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->string('codice_interno', 40)
                ->nullable()
                ->comment('COD-{id}');
            $table->string('varie1', 255)->nullable();
            $table->string('varie2', 255)->nullable();
            $table->string('varie3', 255)->nullable();
            $table->string('varie4', 255)->nullable();
        });

        DB::table('spedizionis')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $id = (int) $row->id;
                DB::table('spedizionis')
                    ->where('id', $id)
                    ->update(['codice_interno' => 'COD-'.$id]);
            }
        });

        Schema::table('spedizionis', function (Blueprint $table) {
            $table->unique('codice_interno');
        });
    }

    public function down(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->dropUnique(['codice_interno']);
            $table->dropColumn(['codice_interno', 'varie1', 'varie2', 'varie3', 'varie4']);
        });
    }
};
