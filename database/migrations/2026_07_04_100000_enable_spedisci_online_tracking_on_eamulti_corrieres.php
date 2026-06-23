<?php

use App\Support\PiattaformaCorriere;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tracking API Spedisci.online (GET /tracking/{ldv}) per corrieri eamultiexp-spediscionline.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('corrieres')
            ->where('piattaforma', PiattaformaCorriere::EAMULTIEXP_SPEDISCIONLINE)
            ->update([
                'trackingsn' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('corrieres')
            ->where('piattaforma', PiattaformaCorriere::EAMULTIEXP_SPEDISCIONLINE)
            ->update([
                'trackingsn' => false,
                'updated_at' => now(),
            ]);
    }
};
