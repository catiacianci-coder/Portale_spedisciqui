<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('corrieres')
            ->where('id', 6)
            ->update([
                'nome_corriere' => 'Liccardi',
                'piattaforma' => 'liccardi_tms',
                'codice_servizio' => 'E',
                'carrier_code' => null,
                'contract_code' => null,
                'tariffa_interna' => 0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('corrieres')
            ->where('id', 6)
            ->update([
                'piattaforma' => 'liccardi_spediscionline_preventivi_propri',
                'codice_servizio' => null,
                'updated_at' => now(),
            ]);
    }
};
