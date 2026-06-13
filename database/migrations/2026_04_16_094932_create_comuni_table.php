<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comuni', function (Blueprint $table) {
            $table->id();
            $table->string('cap', 5);
            $table->string('comune');
            $table->string('provincia', 2);
            $table->string('regione');
            $table->string('paese')->default('Italia');
            $table->boolean('attivo')->default(true);      // Default Si (true)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('comuni');
    }
};