<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->timestamps();
        });

        // Inserimento degli stati iniziali
        DB::table('status')->insert([
            ['label' => 'Anagrafica incompleta'],
            ['label' => 'Registrato'],
            ['label' => 'Abilitato'],
            ['label' => 'Bloccato'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status');
    }
};