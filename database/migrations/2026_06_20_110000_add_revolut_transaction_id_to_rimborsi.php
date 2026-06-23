<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rimborsi', function (Blueprint $table): void {
            if (! Schema::hasColumn('rimborsi', 'revolut_transaction_id')) {
                $table->string('revolut_transaction_id', 36)->nullable()->after('stripe_refund_id');
                $table->index('revolut_transaction_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rimborsi', function (Blueprint $table): void {
            if (Schema::hasColumn('rimborsi', 'revolut_transaction_id')) {
                $table->dropIndex(['revolut_transaction_id']);
                $table->dropColumn('revolut_transaction_id');
            }
        });
    }
};
