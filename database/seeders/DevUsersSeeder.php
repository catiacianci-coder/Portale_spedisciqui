<?php

namespace Database\Seeders;

use App\Models\Anagrafica;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Utenti di prova (solo ambiente non di produzione).
 * Esegui: php artisan db:seed --class=DevUsersSeeder
 *
 * Credenziali: password per tutti = {@see self::PASSWORD_CHIARO}
 */
class DevUsersSeeder extends Seeder
{
    public const PASSWORD_CHIARO = 'password';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DevUsersSeeder non è eseguibile in produzione.');

            return;
        }

        if (User::query()->exists()) {
            $this->command?->warn('La tabella users non è vuota: seeder saltato.');

            return;
        }

        $now = now();

        $bo = User::query()->create([
            'email' => 'backoffice@demo.local',
            'password' => Hash::make(self::PASSWORD_CHIARO),
            'email_verified_at' => $now,
            'tipo_utente' => 'privato',
        ]);

        $cliente = User::query()->create([
            'email' => 'cliente@demo.local',
            'password' => Hash::make(self::PASSWORD_CHIARO),
            'email_verified_at' => $now,
            'tipo_utente' => 'privato',
        ]);

        $roleUtente = Role::query()->where('nome', 'utente')->first();
        $roleSuper = Role::query()->where('nome', Role::nomeSuperUser())->first();
        if ($roleUtente && $roleSuper) {
            $bo->roles()->syncWithoutDetaching([$roleUtente->id, $roleSuper->id]);
            $cliente->roles()->syncWithoutDetaching([$roleUtente->id]);
        }

        foreach ([$bo, $cliente] as $u) {
            Anagrafica::query()->create([
                'user_id' => $u->id,
                'attivo' => true,
                'nome' => 'Demo',
                'cognome' => 'Utente',
                'indirizzo' => 'Via di dimostrazione',
                'civico' => '1',
                'cap' => '00100',
                'citta' => 'Roma',
                'provincia' => 'RM',
                'codice_fiscale' => 'RSSMRA80A01H501U',
            ]);
        }

        $this->command?->info('Creati utenti demo: backoffice@demo.local e cliente@demo.local (password: '.self::PASSWORD_CHIARO.').');
    }
}
