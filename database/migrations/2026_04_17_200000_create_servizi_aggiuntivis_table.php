<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servizi_aggiuntivis', function (Blueprint $table) {
            $table->id();
            $table->string('denominazione_servizio');
            $table->string('varie')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('servizi_aggiuntivis')->insert([
            ['denominazione_servizio' => 'Contrassegno', 'varie' => null, 'created_at' => $now, 'updated_at' => $now],
            ['denominazione_servizio' => 'Consegna al piano', 'varie' => null, 'created_at' => $now, 'updated_at' => $now],
            ['denominazione_servizio' => 'Consegna su appuntamento', 'varie' => null, 'created_at' => $now, 'updated_at' => $now],
            ['denominazione_servizio' => 'Consegna di sabato', 'varie' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('servizi_aggiuntivis');
    }
};
