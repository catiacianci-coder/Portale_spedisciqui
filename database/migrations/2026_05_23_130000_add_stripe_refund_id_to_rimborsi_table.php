<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rimborsi', function (Blueprint $table): void {
            if (! Schema::hasColumn('rimborsi', 'stripe_refund_id')) {
                $table->string('stripe_refund_id', 255)->nullable()->after('payment_id');
                $table->index('stripe_refund_id');
            }
        });

        if (Schema::hasColumn('rimborsi', 'stripe_refund_id')) {
            DB::table('rimborsi')
                ->whereNull('stripe_refund_id')
                ->whereNotNull('payment_id')
                ->where('payment_id', 'like', 're_%')
                ->update(['stripe_refund_id' => DB::raw('payment_id')]);
        }
    }

    public function down(): void
    {
        Schema::table('rimborsi', function (Blueprint $table): void {
            if (Schema::hasColumn('rimborsi', 'stripe_refund_id')) {
                $table->dropIndex(['stripe_refund_id']);
                $table->dropColumn('stripe_refund_id');
            }
        });
    }
};
