<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_imballaggi') || Schema::hasColumn('user_imballaggi', 'is_preferito')) {
            return;
        }

        Schema::table('user_imballaggi', function (Blueprint $table) {
            $table->boolean('is_preferito')->default(false)->after('peso');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_imballaggi') || ! Schema::hasColumn('user_imballaggi', 'is_preferito')) {
            return;
        }

        Schema::table('user_imballaggi', function (Blueprint $table) {
            $table->dropColumn('is_preferito');
        });
    }
};
