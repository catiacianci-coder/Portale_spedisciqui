<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RipristinoDatiOperativiSeeder::class);
        $this->call(ModalitaPagamentoNconformitaSeeder::class);

        // Utenti demo (solo se users è vuota): php artisan db:seed --class=DevUsersSeeder
    }
}
