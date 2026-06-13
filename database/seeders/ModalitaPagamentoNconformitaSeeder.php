<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModalitaPagamentoNconformitaSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['codice' => 'bonifico', 'nome' => 'Bonifico bancario', 'ordine' => 1],
            ['codice' => 'wallet', 'nome' => 'Wallet', 'ordine' => 2],
        ];
        foreach ($rows as $r) {
            DB::table('modalita_pagamento_nconformitas')->updateOrInsert(
                ['codice' => $r['codice']],
                [
                    'nome' => $r['nome'],
                    'abilitato' => true,
                    'ordine' => $r['ordine'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
