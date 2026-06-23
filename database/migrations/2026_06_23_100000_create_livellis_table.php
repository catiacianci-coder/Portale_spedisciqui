<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livellis', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('denominazione', 32);
            $table->timestamps();
        });

        $rows = [];
        $now = now();
        for ($i = 1; $i <= 50; $i++) {
            $rows[] = [
                'id' => $i,
                'denominazione' => 'Livello '.$i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('livellis')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('livellis');
    }
};
