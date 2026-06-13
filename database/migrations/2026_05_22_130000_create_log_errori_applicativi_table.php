<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_errori_applicativi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('http_status')->default(500);
            $table->string('exception_class', 255)->nullable();
            $table->text('messaggio');
            $table->string('url', 2048)->nullable();
            $table->string('metodo', 16)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('trace')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_errori_applicativi');
    }
};
