<?php

use App\Models\ordine;
use App\Support\ChiaveCausaleOrdine;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordinis', function (Blueprint $table) {
            $table->char('chiave_causale', 12)->nullable()->after('stripe_refunded_at');
            $table->string('revolut_transaction_id', 36)->nullable()->after('chiave_causale');
            $table->unique('chiave_causale');
            $table->index('revolut_transaction_id');
        });

        Schema::table('spedizionis', function (Blueprint $table) {
            $table->string('revolut_transaction_id', 36)->nullable()->after('stripe_payment_intent_id');
            $table->index('revolut_transaction_id');
        });

        ordine::query()
            ->whereNull('chiave_causale')
            ->orderBy('id')
            ->each(function (ordine $ordine): void {
                $ordine->update(['chiave_causale' => ChiaveCausaleOrdine::generaUnica()]);
            });
    }

    public function down(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->dropIndex(['revolut_transaction_id']);
            $table->dropColumn('revolut_transaction_id');
        });

        Schema::table('ordinis', function (Blueprint $table) {
            $table->dropUnique(['chiave_causale']);
            $table->dropIndex(['revolut_transaction_id']);
            $table->dropColumn(['chiave_causale', 'revolut_transaction_id']);
        });
    }
};
