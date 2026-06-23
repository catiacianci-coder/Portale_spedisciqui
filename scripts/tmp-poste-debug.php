<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = App\Models\corriere::find(4);
echo 'Corriere #4: '.($c->nome_visualizzato ?? '?').' carrier='.$c->carrier_code.PHP_EOL;
$r = app(App\Services\SpedisciOnline\SpedisciOnlineRatesService::class)->quoteForPreventivo(
    app(App\Services\SpedisciOnline\SpedisciOnlineRatesService::class)->buildPreventivoStubFromInput([
        'cap_origine'=>'80129','cap_destino'=>'83048','peso'=>1,'spessore'=>30,'larghezza'=>20,'altezza'=>15,
    ]),
    $c
);
echo 'Prezzo: '.($r['quote']['price_amount'] ?? 'N/A').' | Errore: '.($r['error'] ?? 'nessuno').PHP_EOL;
