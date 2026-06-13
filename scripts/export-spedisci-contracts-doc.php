<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = \App\Services\SpedisciOnline\SpedisciOnlineClient::forPiattaforma('quick_spediscionline_preventivi_propri');
$response = $client->http()->get('carriers');
$data = $response->json();
if (! is_array($data)) {
    fwrite(STDERR, "Errore: risposta non valida HTTP {$response->status()}\n");
    exit(1);
}

$base = $client->baseUrl();
$date = date('Y-m-d H:i');

$md = "# Contratti Spedisci.online (contractCode API)\n\n";
$md .= "Generato il: **{$date}**\n\n";
$md .= "## Origine dati\n\n";
$md .= "Elenco ottenuto chiamando l'API REST del tenant **quick** (quicksrl):\n\n";
$md .= "| | |\n|---|---|\n";
$md .= "| **Metodo** | `GET` |\n";
$md .= "| **URL** | `{$base}/carriers` |\n";
$md .= "| **Auth** | `Authorization: Bearer <SPEDISCI_ONLINE_API_KEY>` |\n";
$md .= "| **Documentazione** | [apidocs.spedisci.online](https://apidocs.spedisci.online/) — per `/shipping/rates` e `/pickup/create` il `contractCode` deve essere un contratto associato all'account |\n";
$md .= "| **Codice portale** | `App\\Services\\SpedisciOnline\\SpedisciOnlineCarriersService` (cache 1h) — usato nella pagina `/test/spedisci-online` |\n\n";
$md .= "Nel **pannello web** compaiono nomi leggibili (es. vettore *PosteDeliveryBusiness*, contratto *PDB Multi*).\n";
$md .= "Nelle chiamate API si usano `carrierCode` (vettore) e `contractCode` (contratto) come nella tabella sotto.\n\n";
$md .= "> L'elenco dipende dall'**API key** in `.env` (`SPEDISCI_ONLINE_API_KEY`). Un altro tenant (es. liccardi) può avere contratti diversi.\n\n";
$md .= "## Esempio (Poste PDB Multi)\n\n";
$md .= "| Pannello | API |\n|----------|-----|\n";
$md .= "| Vettore: PosteDeliveryBusiness | `carrierCode`: `postedeliverybusiness` |\n";
$md .= "| Contratto: PDB Multi | `contractCode`: `TPEp4Ph7OzIRWtTL` |\n\n";
$md .= "---\n\n";

$total = 0;
foreach ($data as $carrier) {
    if (! is_array($carrier)) {
        continue;
    }
    $contracts = $carrier['contracts'] ?? [];
    if (! is_array($contracts) || $contracts === []) {
        continue;
    }

    $carrierCode = (string) ($carrier['carrierCode'] ?? '');
    $carrierName = (string) ($carrier['carrierName'] ?? $carrierCode);

    $md .= "## {$carrierName} (`carrierCode`: `{$carrierCode}`)\n\n";
    $md .= "| Nome contratto (pannello) | contractCode (API) |\n";
    $md .= "|---------------------------|--------------------|\n";

    foreach ($contracts as $contract) {
        if (! is_array($contract)) {
            continue;
        }
        $name = str_replace('|', '\\|', (string) ($contract['contractName'] ?? ''));
        $code = (string) ($contract['contractCode'] ?? '');
        $md .= "| {$name} | `{$code}` |\n";
        $total++;
    }
    $md .= "\n";
}

$md .= "---\n\n**Totale contratti:** {$total}\n";

$out = __DIR__.'/../docs/SPEDISCI-ONLINE-CONTRACT-CODES.md';
file_put_contents($out, $md);
echo "Scritto {$out} ({$total} contratti)\n";
