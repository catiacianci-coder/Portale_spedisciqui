<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->foreignId('spedizione_padre')
                ->nullable()
                ->after('reso')
                ->constrained('spedizionis')
                ->nullOnDelete();

            $table->string('codice_reso', 64)
                ->nullable()
                ->after('spedizione_padre');

            $table->index('codice_reso');
        });
    }

    public function down(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->dropIndex(['codice_reso']);
            $table->dropConstrainedForeignId('spedizione_padre');
            $table->dropColumn('codice_reso');
        });
    }
};

