<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletDescrizioniSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('wallet_descrizionis')->count() > 0) {
            return;
        }

        $now = now();
        $rows = [
            ['tipo' => 'debito', 'codice' => 'pagamento_ordine', 'descrizione' => 'Pagamento ordine'],
            ['tipo' => 'debito', 'codice' => 'multa', 'descrizione' => 'Multa'],
            ['tipo' => 'debito', 'codice' => 'pagamento_non_conformita', 'descrizione' => 'Pagamento non conformità'],
            ['tipo' => 'debito', 'codice' => 'trasferimento_uscita', 'descrizione' => 'Trasferimento altro account'],
            ['tipo' => 'credito', 'codice' => 'ricarica', 'descrizione' => 'Ricarica'],
            ['tipo' => 'credito', 'codice' => 'trasferimento_ingresso', 'descrizione' => 'Trasferimento da altro account'],
            ['tipo' => 'credito', 'codice' => 'bonus', 'descrizione' => 'Bonus'],
        ];

        foreach ($rows as $r) {
            DB::table('wallet_descrizionis')->insert([
                ...$r,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
