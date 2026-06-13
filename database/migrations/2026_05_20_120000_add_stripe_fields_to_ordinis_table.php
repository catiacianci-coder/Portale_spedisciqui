<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordinis', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id', 255)->nullable()->after('id_metodo_pagamentos');
            $table->string('stripe_payment_intent_id', 255)->nullable()->after('stripe_checkout_session_id');
            $table->index('stripe_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('ordinis', function (Blueprint $table) {
            $table->dropIndex(['stripe_checkout_session_id']);
            $table->dropColumn(['stripe_checkout_session_id', 'stripe_payment_intent_id']);
        });
    }
};
