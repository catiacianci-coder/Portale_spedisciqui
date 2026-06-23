<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anagrafiche', function (Blueprint $table) {
            if (! Schema::hasColumn('anagrafiche', 'sede_liccardi')) {
                $table->boolean('sede_liccardi')->default(false)->after('codice_sdi');
            }
            if (! Schema::hasColumn('anagrafiche', 'varie_1')) {
                $table->string('varie_1', 255)->nullable()->after('sede_liccardi');
            }
            if (! Schema::hasColumn('anagrafiche', 'varie_2')) {
                $table->string('varie_2', 255)->nullable()->after('varie_1');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'varie_1')) {
                $table->string('varie_1', 255)->nullable()->after('mark');
            }
        });

        Schema::table('ordinis', function (Blueprint $table) {
            if (! Schema::hasColumn('ordinis', 'varie_1')) {
                $table->string('varie_1', 255)->nullable()->after('dettaglio_json');
            }
        });
    }

    public function down(): void
    {
        Schema::table('anagrafiche', function (Blueprint $table) {
            $cols = array_filter(
                ['sede_liccardi', 'varie_1', 'varie_2'],
                fn (string $c) => Schema::hasColumn('anagrafiche', $c),
            );
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'varie_1')) {
                $table->dropColumn('varie_1');
            }
        });

        Schema::table('ordinis', function (Blueprint $table) {
            if (Schema::hasColumn('ordinis', 'varie_1')) {
                $table->dropColumn('varie_1');
            }
        });
    }
};
