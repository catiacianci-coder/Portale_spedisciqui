<?php

use App\Support\SendcloudCorrierePickupLabels;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * InPost: testi punto ritiro/consegna in tabella corrieres.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('corrieres')
            ->where('carrier_code', 'inpost_it')
            ->update([
                'punto_ritiro' => SendcloudCorrierePickupLabels::INPOST_PUNTO_RITIRO,
                'punto_consegna' => SendcloudCorrierePickupLabels::INPOST_PUNTO_CONSEGNA,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('corrieres')
            ->where('carrier_code', 'inpost_it')
            ->update([
                'punto_ritiro' => null,
                'punto_consegna' => 'Seleziona un locker InPost vicino a te',
                'updated_at' => now(),
            ]);
    }
};
