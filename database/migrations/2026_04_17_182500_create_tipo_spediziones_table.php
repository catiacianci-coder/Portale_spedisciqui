<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_spediziones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_spedizione');
            $table->string('varie')->nullable();
            $table->timestamps();
        });

        DB::table('tipo_spediziones')->insert([
            [
                'tipo_spedizione' => 'Pacco',
                'varie' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_spedizione' => 'Documento',
                'varie' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_spedizione' => 'Pallet',
                'varie' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_spediziones');
    }
};
