<?php

namespace App\Console\Commands;

use App\Services\Sendcloud\SendcloudClient;
use Illuminate\Console\Command;

class SendcloudProbeCommand extends Command
{
    protected $signature = 'sendcloud:probe';

    protected $description = 'Elenca contratti e prodotti spedizione Sendcloud (debug corrieri)';

    public function handle(SendcloudClient $client): int
    {
        if (! SendcloudClient::isConfigured()) {
            $this->error('Chiavi Sendcloud mancanti.');

            return self::FAILURE;
        }

        $contracts = $client->get('/contracts');
        if ($contracts->successful()) {
            $rows = $contracts->json('data') ?? $contracts->json('contracts') ?? $contracts->json() ?? [];
            $this->info('Contratti (v3):');
            foreach ((array) $rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $id = $row['id'] ?? '?';
                $carrier = $row['carrier']['code'] ?? $row['carrier_code'] ?? $row['name'] ?? json_encode($row);
                $this->line("  • id={$id} — {$carrier}");
            }
        } else {
            $this->warn('Contratti: HTTP '.$contracts->status());
        }

        $products = $client->http()
            ->baseUrl('https://panel.sendcloud.sc/api/v2')
            ->get('/shipping-products', [
                'from_country' => 'IT',
                'to_country' => 'IT',
            ]);

        if ($products->successful()) {
            $this->info('Shipping products IT→IT (v2, estratto carrier):');
            $seen = [];
            foreach ($products->json() as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $carrier = $item['carrier'] ?? $item['carrier_code'] ?? null;
                if (is_array($carrier)) {
                    $carrier = $carrier['code'] ?? $carrier['name'] ?? json_encode($carrier);
                }
                $carrier = (string) ($carrier ?? 'unknown');
                if (isset($seen[$carrier])) {
                    continue;
                }
                $seen[$carrier] = true;
                $this->line("  • {$carrier}");
            }
            if ($seen === []) {
                $this->warn('  (nessun prodotto — contratto InPost IT ancora in attivazione?)');
            }
        } else {
            $this->warn('Shipping products: HTTP '.$products->status().' — '.substr((string) $products->body(), 0, 300));
        }

        return self::SUCCESS;
    }
}
