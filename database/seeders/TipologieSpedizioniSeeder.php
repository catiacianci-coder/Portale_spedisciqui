<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipologieSpedizioniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipologie = [
            ['tipologia' => 'Pacco'],
            ['tipologia' => 'Documento'],
            ['tipologia' => 'Pallet'],
        ];

        DB::table('tipologie_spedizioni')->insert($tipologie);
    }
}