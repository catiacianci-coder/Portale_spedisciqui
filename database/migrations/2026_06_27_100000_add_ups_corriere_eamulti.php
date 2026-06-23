<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corriere UPS su piattaforma eamultiexp-spediscionline (come GLS Light).
 */
return new class extends Migration
{
    private const PIATTAFORMA = 'eamultiexp-spediscionline';

    private const UPS_CARRIER = 'ups';

    private const UPS_CONTRACT = 'eyJpdiI6IlYzOGJWR2RGc0lVdDhPUFhkSXV3NEE9PSIsInZhbHVlIjoicjFteGFHaHNUYitmM0NIbWM0Zjd6L1paRU9KNE1Jb2hPT3pVNkpBbStSUT0iLCJtYWMiOiI4MWJjYjY0MjIyZDM0MjNhMDkzZjAzODc1MjZjZjJiNTQzYTI3ZDE2NzhhZmY4NzlkYTYyYWViNjZiYmZmN2YwIiwidGFnIjoiIn0=';

    public function up(): void
    {
        if (DB::table('corrieres')->where('id', 14)->exists()) {
            return;
        }

        $template = DB::table('corrieres')->where('id', 13)->first()
            ?? DB::table('corrieres')->where('id', 5)->first();

        if (! $template) {
            return;
        }

        $now = now();

        DB::table('corrieres')->insert([
            'id' => 14,
            'nome_corriere' => 'UPS',
            'nome_corriere_preventivo' => 'Standard',
            'nome_servizio' => 'UPS',
            'codice_servizio' => self::UPS_CONTRACT,
            'istat' => $template->istat,
            'nome_area' => $template->nome_area,
            'nome_visualizzato' => 'UPS',
            'tipo_o_d' => $template->tipo_o_d,
            'numero_contratto' => null,
            'attivo' => true,
            'tariffa_interna' => false,
            'id_ricarico' => 4,
            'piattaforma' => self::PIATTAFORMA,
            'carrier_code' => self::UPS_CARRIER,
            'contract_code' => null,
            'sicilia' => $template->sicilia,
            'calabria' => $template->calabria,
            'sardegna' => $template->sardegna,
            'fuel' => $template->fuel,
            'soglia_esenzione' => $template->soglia_esenzione,
            'pickup' => $template->pickup,
            'consegna' => $template->consegna,
            'punto_ritiro' => $template->punto_ritiro,
            'punto_consegna' => $template->punto_consegna,
            'trackingsn' => $template->trackingsn,
            'url_tracking' => $template->url_tracking,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE corrieres AUTO_INCREMENT = 15');
        }
    }

    public function down(): void
    {
        DB::table('corrieres')->where('id', 14)->delete();
    }
};
