<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'carrello_json')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('carrello_json')->nullable()->after('tipo_utente');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'carrello_json')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('carrello_json');
            });
        }
    }
};
