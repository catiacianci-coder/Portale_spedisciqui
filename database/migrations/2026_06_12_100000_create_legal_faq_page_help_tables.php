<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->index();
            $table->string('titulo');
            $table->longText('conteudo_html');
            $table->date('vigente_desde')->nullable();
            $table->timestamp('publicado_em')->nullable()->index();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question', 500);
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('page_help_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('page_key', 80)->unique();
            $table->string('button_label', 80)->default('Come funziona?');
            $table->string('modal_title', 120)->default('Aiuto');
            $table->text('modal_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_help_contents');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('legal_document_versions');
    }
};
