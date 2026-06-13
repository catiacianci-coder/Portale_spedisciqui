<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_ricarica_richiestas', function (Blueprint $table) {
            $table->string('numero_ordine_wallet', 40)
                ->nullable()
                ->after('id')
                ->comment('ORW-{id}');
        });

        Schema::table('spedizionis', function (Blueprint $table) {
            $table->string('numero_ordine_spedizione', 40)
                ->nullable()
                ->after('id')
                ->comment('ORS-{id}');
        });

        DB::table('wallet_ricarica_richiestas')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('wallet_ricarica_richiestas')
                    ->where('id', $row->id)
                    ->update(['numero_ordine_wallet' => 'ORW-'.$row->id]);
            }
        });

        DB::table('spedizionis')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('spedizionis')
                    ->where('id', $row->id)
                    ->update(['numero_ordine_spedizione' => 'ORS-'.$row->id]);
            }
        });

        Schema::table('wallet_ricarica_richiestas', function (Blueprint $table) {
            $table->unique('numero_ordine_wallet');
        });

        Schema::table('spedizionis', function (Blueprint $table) {
            $table->unique('numero_ordine_spedizione');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_ricarica_richiestas', function (Blueprint $table) {
            $table->dropUnique(['numero_ordine_wallet']);
            $table->dropColumn('numero_ordine_wallet');
        });

        Schema::table('spedizionis', function (Blueprint $table) {
            $table->dropUnique(['numero_ordine_spedizione']);
            $table->dropColumn('numero_ordine_spedizione');
        });
    }
};
