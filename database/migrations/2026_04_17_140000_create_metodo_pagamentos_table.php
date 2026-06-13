<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodo_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->string('metodo_pagamento', 120);
            $table->boolean('abilitato')->default(true);
            $table->string('varie')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodo_pagamentos');
    }
};
