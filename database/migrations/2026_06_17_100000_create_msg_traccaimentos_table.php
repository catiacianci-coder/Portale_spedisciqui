<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('msg_traccaimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corriere_id')->constrained('corrieres')->cascadeOnDelete();
            $table->string('msg_ricevuto', 500);
            $table->string('msg_per_cliente', 500)->nullable();
            $table->timestamps();

            $table->unique(['corriere_id', 'msg_ricevuto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('msg_traccaimentos');
    }
};
