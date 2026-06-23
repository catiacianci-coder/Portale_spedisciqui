<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Piattaforma esterna eamultiexp-spediscionline (tenant eamulti) al posto di quick_spediscionline_preventivi_propri.
 * Preventivi da API: tariffa_interna = false.
 */
return new class extends Migration
{
    private const PIATTAFORMA = 'eamultiexp-spediscionline';

    private const PIATTAFORMA_LEGACY = 'quick_spediscionline_preventivi_propri';

    /** Poste Delivery Business Standard (eamulti). */
    private const POSTE_CARRIER = 'postedeliverybusiness';

    private const POSTE_CONTRACT = 'postedeliverybusiness-POSTE-DELIVERY-BUSINESS';

    /** GLS Standard / Light (eamulti). */
    private const GLS_CARRIER = 'gls';

    private const GLS_STANDARD_CONTRACT = 't1LjLIdcqk9OpFP1';

    private const GLS_LIGHT_CONTRACT = '0U2EP82LHnBqwxkm';

    public function up(): void
    {
        $now = now();

        DB::table('corrieres')
            ->where('id', 4)
            ->update([
                'nome_corriere' => 'Poste Italiane',
                'nome_corriere_preventivo' => 'Delivery Business Standard',
                'nome_servizio' => 'Delivery Business Standard',
                'nome_visualizzato' => 'Poste Delivery Business Standard',
                'piattaforma' => self::PIATTAFORMA,
                'tariffa_interna' => false,
                'id_ricarico' => 4,
                'carrier_code' => self::POSTE_CARRIER,
                'contract_code' => self::POSTE_CONTRACT,
                'updated_at' => $now,
            ]);

        DB::table('corrieres')
            ->where('id', 5)
            ->update([
                'nome_corriere' => 'GLS',
                'nome_corriere_preventivo' => 'Standard',
                'nome_servizio' => 'GLS Standard',
                'nome_visualizzato' => 'GLS Standard',
                'piattaforma' => self::PIATTAFORMA,
                'tariffa_interna' => false,
                'id_ricarico' => 4,
                'carrier_code' => self::GLS_CARRIER,
                'contract_code' => self::GLS_STANDARD_CONTRACT,
                'updated_at' => $now,
            ]);

        DB::table('corrieres')
            ->where('piattaforma', self::PIATTAFORMA_LEGACY)
            ->whereNotIn('id', [4, 5])
            ->update([
                'piattaforma' => self::PIATTAFORMA,
                'updated_at' => $now,
            ]);

        if (! DB::table('corrieres')->where('id', 13)->exists()) {
            $gls = DB::table('corrieres')->where('id', 5)->first();
            if ($gls) {
                DB::table('corrieres')->insert([
                    'id' => 13,
                    'nome_corriere' => 'GLS',
                    'nome_corriere_preventivo' => 'Light',
                    'nome_servizio' => 'GLS Light',
                    'codice_servizio' => null,
                    'istat' => $gls->istat,
                    'nome_area' => $gls->nome_area,
                    'nome_visualizzato' => 'GLS Light',
                    'tipo_o_d' => $gls->tipo_o_d,
                    'numero_contratto' => null,
                    'attivo' => true,
                    'tariffa_interna' => false,
                    'id_ricarico' => 4,
                    'piattaforma' => self::PIATTAFORMA,
                    'carrier_code' => self::GLS_CARRIER,
                    'contract_code' => self::GLS_LIGHT_CONTRACT,
                    'sicilia' => $gls->sicilia,
                    'calabria' => $gls->calabria,
                    'sardegna' => $gls->sardegna,
                    'fuel' => $gls->fuel,
                    'soglia_esenzione' => $gls->soglia_esenzione,
                    'pickup' => $gls->pickup,
                    'consegna' => $gls->consegna,
                    'punto_ritiro' => $gls->punto_ritiro,
                    'punto_consegna' => $gls->punto_consegna,
                    'trackingsn' => $gls->trackingsn,
                    'url_tracking' => $gls->url_tracking,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE corrieres AUTO_INCREMENT = 14');
        }
    }

    public function down(): void
    {
        $now = now();

        DB::table('corrieres')->where('id', 13)->delete();

        DB::table('corrieres')
            ->where('id', 4)
            ->update([
                'nome_corriere' => 'Poste Italiane',
                'nome_corriere_preventivo' => 'Delivery Business',
                'nome_servizio' => 'Delivery Business',
                'nome_visualizzato' => 'Poste Italiane - Delivery Business',
                'piattaforma' => self::PIATTAFORMA_LEGACY,
                'tariffa_interna' => true,
                'id_ricarico' => null,
                'carrier_code' => self::POSTE_CARRIER,
                'contract_code' => 'TPEp4Ph7OzIRWtTL',
                'updated_at' => $now,
            ]);

        DB::table('corrieres')
            ->where('id', 5)
            ->update([
                'nome_corriere' => "GLS\r\n",
                'nome_corriere_preventivo' => 'Nazionale',
                'nome_servizio' => 'GLS',
                'nome_visualizzato' => 'GLS Nazionale',
                'piattaforma' => self::PIATTAFORMA_LEGACY,
                'tariffa_interna' => true,
                'id_ricarico' => null,
                'carrier_code' => null,
                'contract_code' => null,
                'updated_at' => $now,
            ]);

        DB::table('corrieres')
            ->where('piattaforma', self::PIATTAFORMA)
            ->update([
                'piattaforma' => self::PIATTAFORMA_LEGACY,
                'updated_at' => $now,
            ]);
    }
};
