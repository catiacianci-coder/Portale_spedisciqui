<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('wallet_descrizionis')->where('codice', 'rimborso_spedizione')->exists()) {
            return;
        }

        $now = now();
        DB::table('wallet_descrizionis')->insert([
            'tipo' => 'credito',
            'codice' => 'rimborso_spedizione',
            'descrizione' => 'Rimborso spedizione',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('wallet_descrizionis')->where('codice', 'rimborso_spedizione')->delete();
    }
};
