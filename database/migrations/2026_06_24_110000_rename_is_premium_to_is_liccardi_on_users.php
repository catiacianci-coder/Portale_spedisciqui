<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_premium') && ! Schema::hasColumn('users', 'is_liccardi')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_premium', 'is_liccardi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_liccardi') && ! Schema::hasColumn('users', 'is_premium')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_liccardi', 'is_premium');
            });
        }
    }
};
