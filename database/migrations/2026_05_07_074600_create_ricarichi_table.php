<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ricarichi', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->nullable();
            $table->decimal('percentuale', 10, 2);
            $table->timestamps();
        });

        DB::table('ricarichi')->insert([
            ['id' => 1, 'nome' => 'Standard 10%', 'percentuale' => 10.00, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nome' => 'Standard 13%', 'percentuale' => 13.00, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ricarichi');
    }
};
