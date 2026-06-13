<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordine_stripe_rimborsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordine_id')->constrained('ordinis')->cascadeOnDelete();
            $table->string('stripe_refund_id', 255);
            $table->string('stripe_payment_intent_id', 255);
            $table->decimal('stripe_refund_amount', 14, 2);
            $table->timestamp('refunded_at');
            $table->timestamps();

            $table->unique('stripe_refund_id');
            $table->index('stripe_payment_intent_id');
            $table->index(['ordine_id', 'refunded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordine_stripe_rimborsi');
    }
};
