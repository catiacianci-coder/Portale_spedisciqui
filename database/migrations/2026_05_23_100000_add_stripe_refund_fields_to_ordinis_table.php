<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordinis', function (Blueprint $table) {
            $table->string('stripe_refund_id', 255)->nullable()->after('stripe_payment_intent_id');
            $table->decimal('stripe_refund_amount', 14, 2)->nullable()->after('stripe_refund_id');
            $table->timestamp('stripe_refunded_at')->nullable()->after('stripe_refund_amount');
            $table->index('stripe_refund_id');
        });
    }

    public function down(): void
    {
        Schema::table('ordinis', function (Blueprint $table) {
            $table->dropIndex(['stripe_refund_id']);
            $table->dropColumn(['stripe_refund_id', 'stripe_refund_amount', 'stripe_refunded_at']);
        });
    }
};
