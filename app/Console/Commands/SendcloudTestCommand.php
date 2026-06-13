<?php

namespace App\Console\Commands;

use App\Services\Sendcloud\SendcloudClient;
use App\Services\Sendcloud\SendcloudShippingOptionsService;
use Illuminate\Console\Command;

class SendcloudTestCommand extends Command
{
    protected $signature = 'sendcloud:test {--carrier=inpost_global : Filtra per carrier_code (default InPost)} {--dump : Mostra corpo risposta se vuota}';

    protected $description = 'Verifica le credenziali Sendcloud con una richiesta shipping-options (IT → IT)';

    public function handle(SendcloudShippingOptionsService $shippingOptions): int
    {
        if (! SendcloudClient::isConfigured()) {
            $this->error('Chiavi Sendcloud mancanti. Imposta sendcloud_public_key e sendcloud_secret_key in parametri globali');

            return self::FAILURE;
        }

        $carrier = $this->option('carrier');
        $carrierLabel = is_string($carrier) && $carrier !== ''
            ? "carrier_code={$carrier}"
            : 'tutti i corrieri attivi';
        $this->info("Chiamata POST /shipping-options (IT → IT, 2 kg, {$carrierLabel})...");

        $payload = [
            'from_address' => [
                'country_code' => 'IT',
                'postal_code' => '00100',
                'city' => 'Roma',
            ],
            'to_address' => [
                'country_code' => 'IT',
                'postal_code' => '20100',
                'city' => 'Milano',
            ],
            'parcels' => [
                [
                    'weight' => ['value' => '2', 'unit' => 'kg'],
                    'dimensions' => [
                        'length' => '30',
                        'width' => '20',
                        'height' => '15',
                        'unit' => 'cm',
                    ],
                ],
            ],
        ];

        if (is_string($carrier) && $carrier !== '') {
            $payload['carrier_code'] = $carrier;
        }

        $response = $shippingOptions->listWithQuotes($payload);

        if ($response->status() === 401) {
            $this->error('Autenticazione fallita (401). Controlla Public Key e Secret Key.');

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error('Errore HTTP '.$response->status());
            $this->line($response->body());

            return self::FAILURE;
        }

        $body = $response->json();
        $options = $body['data'] ?? $body['shipping_options'] ?? (is_array($body) && array_is_list($body) ? $body : []);
        if (! is_array($options)) {
            $options = [];
        }

        $count = count($options);
        $this->info("Connessione OK. Opzioni ricevute: {$count}");

        if ($count === 0) {
            $this->warn('Nessuna opzione: abilita corrieri/metodi nel pannello Sendcloud (es. InPost per IT).');
            if ($this->option('dump')) {
                $this->line(substr((string) $response->body(), 0, 2000));
            }
        } else {
            $sample = array_slice($options, 0, 5);
            foreach ($sample as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $code = $row['code'] ?? $row['shipping_option_code'] ?? '?';
                $name = $row['name'] ?? $row['carrier'] ?? '';
                $priceBlock = is_array($row['quotes'][0]['price'] ?? null) ? $row['quotes'][0]['price'] : null;
                $total = is_array($priceBlock['total'] ?? null) ? $priceBlock['total'] : null;
                $quote = $total['value'] ?? $priceBlock['value'] ?? null;
                $currency = (string) ($total['currency'] ?? $priceBlock['currency'] ?? 'EUR');
                $price = $quote !== null ? "{$quote} {$currency}" : '(senza quote)';
                $this->line("  • {$code} — {$name} — {$price}");
            }
            if ($count > 5) {
                $this->line('  …');
            }
        }

        return self::SUCCESS;
    }
}
