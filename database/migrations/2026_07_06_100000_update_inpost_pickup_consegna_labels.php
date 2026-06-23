<?php

use App\Support\SendcloudCorrierePickupLabels;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * InPost: pickup e consegna → "Locker o InPost Point".
 */
return new class extends Migration
{
    public function up(): void
    {
        $label = SendcloudCorrierePickupLabels::INPOST_PICKUP_CONSEGNA;

        DB::table('corrieres')
            ->where('carrier_code', 'inpost_it')
            ->update([
                'pickup' => $label,
                'consegna' => $label,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $now = now();

        DB::table('corrieres')
            ->where('carrier_code', 'inpost_it')
            ->where('codice_servizio', 'like', '%lockertolocker%')
            ->update([
                'pickup' => 'Punto InPost',
                'consegna' => 'Locker InPost',
                'updated_at' => $now,
            ]);

        DB::table('corrieres')
            ->where('carrier_code', 'inpost_it')
            ->where('codice_servizio', 'not like', '%lockertolocker%')
            ->update([
                'pickup' => 'Indirizzo',
                'consegna' => 'Locker InPost',
                'updated_at' => $now,
            ]);
    }
};
