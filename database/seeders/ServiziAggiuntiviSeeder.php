<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiziAggiuntiviSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servizi = [
            ['nome_servizio' => 'Contrassegno'],
            ['nome_servizio' => 'Servizio al piano'],
            ['nome_servizio' => 'Sponda Idraulica'],
            ['nome_servizio' => 'Consegna su appuntamento'],
            ['nome_servizio' => 'Contatto telefonico nel PickUp'],
            ['nome_servizio' => 'Contatto telefonico nella consegna'],
            ['nome_servizio' => 'Consegna il sabato'],
        ];

        DB::table('servizi_aggiuntivi')->insert($servizi);
    }
}