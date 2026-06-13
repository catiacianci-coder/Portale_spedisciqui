<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ripopola corrieri (id 1–3, coerenti con tariffas_template.csv), servizi aggiuntivi e pivot corriere–servizio.
 * I comuni vanno importati prima: php artisan import:comuni
 * Le tariffe: tariffas_template.csv (corrieri 1–3) + tariffas_Poste.csv (corriere 4) in append.
 */
class RipristinoDatiOperativiSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        if (DB::table('comuni')->count() === 0) {
            $this->command->warn('Tabella comuni vuota. Esegui prima: php artisan import:comuni');
        }

        if (DB::table('servizi_aggiuntivis')->count() === 0) {
            DB::table('servizi_aggiuntivis')->insert([
                ['denominazione_servizio' => 'Contrassegno', 'varie' => null, 'created_at' => $now, 'updated_at' => $now],
                ['denominazione_servizio' => 'Consegna al piano', 'varie' => null, 'created_at' => $now, 'updated_at' => $now],
                ['denominazione_servizio' => 'Consegna su appuntamento', 'varie' => null, 'created_at' => $now, 'updated_at' => $now],
                ['denominazione_servizio' => 'Consegna di sabato', 'varie' => null, 'created_at' => $now, 'updated_at' => $now],
            ]);
            $this->command->info('Inseriti 4 servizi aggiuntivi base.');
        }

        if (DB::table('corrieres')->count() === 0) {
            DB::table('corrieres')->insert([
                [
                    'id' => 1,
                    'nome_corriere' => 'Corriere Espresso A',
                    'nome_servizio' => 'Espresso',
                    'nome_area' => 'Italia',
                    'nome_visualizzato' => 'Espresso',
                    'tipo_o_d' => 'italia_italia',
                    'numero_contratto' => null,
                    'attivo' => true,
                    'piattaforma' => null,
                    'pickup' => null,
                    'consegna' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 2,
                    'nome_corriere' => 'Corriere Economico B',
                    'nome_servizio' => 'Economico',
                    'nome_area' => 'Italia',
                    'nome_visualizzato' => 'Economico',
                    'tipo_o_d' => 'italia_italia',
                    'numero_contratto' => null,
                    'attivo' => true,
                    'piattaforma' => null,
                    'pickup' => null,
                    'consegna' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 3,
                    'nome_corriere' => 'Corriere Espresso C',
                    'nome_servizio' => 'Espresso',
                    'nome_area' => 'Italia',
                    'nome_visualizzato' => 'Espresso',
                    'tipo_o_d' => 'italia_italia',
                    'numero_contratto' => null,
                    'attivo' => true,
                    'piattaforma' => null,
                    'pickup' => null,
                    'consegna' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE corrieres AUTO_INCREMENT = 4');
            }

            $this->command->info('Inseriti 3 corrieri (id 1–3, italia_italia, attivi).');
        }

        if (DB::table('corrieri_servizi_aggiuntivis')->count() === 0
            && DB::table('corrieres')->whereIn('id', [1, 2, 3])->count() === 3
            && DB::table('servizi_aggiuntivis')->whereIn('id', [1, 2, 3, 4])->count() === 4) {
            $rows = [];
            $poste = 1;
            $velociraptor = 2;
            $tirannosauro = 3;

            foreach ([1, 2, 3, 4] as $sid) {
                $rows[] = ['id_corriere' => $poste, 'id_servizi_aggiuntivi' => $sid, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach ([1, 2, 3, 4] as $sid) {
                $rows[] = ['id_corriere' => $tirannosauro, 'id_servizi_aggiuntivi' => $sid, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach ([2, 3, 4] as $sid) {
                $rows[] = ['id_corriere' => $velociraptor, 'id_servizi_aggiuntivi' => $sid, 'created_at' => $now, 'updated_at' => $now];
            }

            DB::table('corrieri_servizi_aggiuntivis')->insert($rows);
            $this->command->info('Inserite righe corrieri_servizi_aggiuntivis (associazioni demo).');
        }

        $templateCsv = storage_path('app/tariffas_template.csv');
        $posteCsv = storage_path('app/tariffas_Poste.csv');

        if (DB::table('tariffas')->count() === 0 && file_exists($templateCsv)) {
            Artisan::call('import:tariffas', ['--truncate' => true]);
            $this->command->info(trim(Artisan::output()));
            $this->command->info('Tariffe base importate da tariffas_template.csv');
        } elseif (DB::table('tariffas')->count() === 0) {
            $this->command->warn('Tariffe vuote e tariffas_template.csv assente.');
        }

        if (file_exists($posteCsv) && DB::table('tariffas')->where('id_corrieres', 4)->count() === 0) {
            Artisan::call('import:tariffas', [
                'file' => 'tariffas_Poste.csv',
                '--replace-corriere' => 4,
            ]);
            $this->command->info(trim(Artisan::output()));
            $this->command->info('Tariffe Poste (corriere 4) aggiunte da tariffas_Poste.csv');
        }

        $this->call(WalletDescrizioniSeeder::class);

        $this->command->info('Ripristino dati operativi completato.');
    }
}
