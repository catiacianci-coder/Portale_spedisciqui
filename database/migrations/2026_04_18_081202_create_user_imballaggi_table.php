<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_imballaggi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('id_tipo_spediziones')->constrained('tipo_spediziones');
            $table->string('nome', 120);
            $table->decimal('altezza', 8, 2);
            $table->decimal('larghezza', 8, 2);
            $table->decimal('spessore', 8, 2);
            $table->decimal('peso', 8, 2);
            $table->boolean('is_preferito')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_imballaggi');
    }
};
