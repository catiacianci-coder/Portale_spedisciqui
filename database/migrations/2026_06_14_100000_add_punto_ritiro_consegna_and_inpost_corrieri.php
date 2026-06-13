<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->string('punto_ritiro')->nullable()->after('consegna');
            $table->string('punto_consegna')->nullable()->after('punto_ritiro');
        });

        $now = now();

        DB::table('corrieres')->where('id', 7)->update([
            'punto_consegna' => 'Seleziona un ufficio postale vicino a te',
            'updated_at' => $now,
        ]);

        DB::table('corrieres')->where('id', 8)->update([
            'codice_servizio' => 'poste_it_delivery:express_puntoposte',
            'punto_consegna' => 'Seleziona un punto Poste vicino a te',
            'updated_at' => $now,
        ]);

        DB::table('corrieres')->where('id', 10)->update([
            'punto_ritiro' => 'Vedi i punti Poste vicini a te',
            'punto_consegna' => 'Seleziona un punto Poste vicino a te',
            'updated_at' => $now,
        ]);

        if (! DB::table('corrieres')->where('id', 11)->exists()) {
            DB::table('corrieres')->insert([
                'id' => 11,
                'nome_corriere' => 'InPost',
                'nome_corriere_preventivo' => 'InPost',
                'nome_servizio' => 'Address to Locker Medium',
                'nome_visualizzato' => 'InPost Locker Medium',
                'codice_servizio' => 'inpost_it:addresstolocker/pickup,size=m',
                'nome_area' => 'Italia',
                'tipo_o_d' => 'italia_italia',
                'numero_contratto' => null,
                'attivo' => true,
                'tariffa_interna' => false,
                'id_ricarico' => 4,
                'piattaforma' => 'sendcloud',
                'carrier_code' => 'inpost_it',
                'contract_code' => null,
                'pickup' => 'Domicilio',
                'consegna' => 'Locker InPost',
                'punto_ritiro' => null,
                'punto_consegna' => 'Seleziona un locker InPost vicino a te',
                'fuel' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('corrieres')->where('id', 12)->exists()) {
            DB::table('corrieres')->insert([
                'id' => 12,
                'nome_corriere' => 'InPost',
                'nome_corriere_preventivo' => 'InPost',
                'nome_servizio' => 'Address to Locker Large',
                'nome_visualizzato' => 'InPost Locker Large',
                'codice_servizio' => 'inpost_it:addresstolocker/pickup,size=l',
                'nome_area' => 'Italia',
                'tipo_o_d' => 'italia_italia',
                'numero_contratto' => null,
                'attivo' => true,
                'tariffa_interna' => false,
                'id_ricarico' => 4,
                'piattaforma' => 'sendcloud',
                'carrier_code' => 'inpost_it',
                'contract_code' => null,
                'pickup' => 'Domicilio',
                'consegna' => 'Locker InPost',
                'punto_ritiro' => null,
                'punto_consegna' => 'Seleziona un locker InPost vicino a te',
                'fuel' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE corrieres AUTO_INCREMENT = 13');
        }
    }

    public function down(): void
    {
        DB::table('corrieres')->whereIn('id', [11, 12])->delete();

        DB::table('corrieres')->whereIn('id', [7, 8, 10])->update([
            'punto_ritiro' => null,
            'punto_consegna' => null,
            'updated_at' => now(),
        ]);

        Schema::table('corrieres', function (Blueprint $table) {
            $table->dropColumn(['punto_ritiro', 'punto_consegna']);
        });
    }
};
