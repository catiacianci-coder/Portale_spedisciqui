<?php

namespace App\Console\Commands;

use App\Services\SpedisciOnline\SpedisciOnlineClient;
use App\Services\SpedisciOnline\SpedisciOnlineRatesService;
use Illuminate\Console\Command;

class SpedisciOnlineTestRatesCommand extends Command
{
    protected $signature = 'spedisci-online:test-rates {--tenant=quick : quick o liccardi}';

    protected $description = 'Test POST /shipping/rates su Spedisci.online (tenant quick o liccardi)';

    public function handle(SpedisciOnlineRatesService $rates): int
    {
        $tenant = (string) $this->option('tenant');
        $piattaforma = $tenant === 'liccardi'
            ? 'liccardi_spediscionline_preventivi_propri'
            : 'quick_spediscionline_preventivi_propri';

        $client = SpedisciOnlineClient::forPiattaforma($piattaforma);
        if (! $client->isConfigured()) {
            $this->error('API key mancante per tenant '.$tenant.' in .env');

            return self::FAILURE;
        }

        $this->info('Tenant: '.$client->tenant().' → '.$client->baseUrl());

        $input = [
            'cap_origine' => '00100',
            'citta_origine' => 'Roma',
            'cap_destino' => '20100',
            'citta_destino' => 'Milano',
            'peso' => 2,
            'lunghezza' => 30,
            'larghezza' => 20,
            'altezza' => 15,
        ];

        $response = $rates->fetchRates($input, $piattaforma);
        $list = $rates->parseRatesFromResponse($response);

        $this->info('HTTP '.$response->status().' — tariffe: '.count($list));
        $this->line($response->body());

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }
}
