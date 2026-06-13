<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutPagamentoRequest;
use App\Models\corriere;
use App\Models\corrieri_servizi_aggiuntivi;
use App\Models\metodo_pagamento_ordine;
use App\Models\parametri_globali;
use App\Services\Checkout\CheckoutPagamentoService;
use App\Services\Checkout\CheckoutServizioAggiuntivoQuoteService;
use App\Services\OrdineTotaleIvatoService;
use App\Services\Preventivo\PreventivoRigaPrezzoService;
use App\Services\ServiziAggiuntiviPrezzoService;
use App\Services\Sendcloud\SendcloudClient;
use App\Services\Sendcloud\SendcloudServicePointsService;
use App\Services\Stripe\StripeConfig;
use App\Support\CarrelloPrezziWallet;
use App\Support\CorriereLogo;
use App\Support\CorrierePuntoEtichetta;
use App\Support\DestinatarioConsegnaPunti;
use App\Support\LiccardiVolumeSconto;
use App\Support\MetodoPagamentoIcon;
use App\Support\PreventivoPrezziEsposti;
use App\Support\PreventivoRigaSelezionabile;
use App\Support\PuntoConsegnaSessione;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutPagamentoService $checkoutPagamentoSvc,
    ) {}

    public function show(Request $request)
    {
        $preventivo = $request->session()->get('preventivo');
        $corriereId = (int) $request->query('corriere', 0);

        if (! $preventivo || $corriereId < 1) {
            return redirect()
                ->route('preventivi')
                ->withErrors(['checkout' => 'Seleziona un corriere dalla pagina preventivi.']);
        }

        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            abort(404);
        }

        $indirizzi = $preventivo['indirizzi'] ?? null;
        if (! $indirizzi || (int) ($indirizzi['corriere_id'] ?? 0) !== $corriereId) {
            return redirect()
                ->route('spedizione.indirizzi', ['corriere' => $corriereId]);
        }

        $prezzoSvc = app(PreventivoRigaPrezzoService::class);
        $esitoPrezzo = $prezzoSvc->aggiornaSessione($preventivo, $corriereId);
        if (! ($esitoPrezzo['ok'] ?? false)) {
            return redirect()
                ->route('preventivi')
                ->withErrors(['checkout' => $esitoPrezzo['error'] ?? 'Impossibile ricalcolare il prezzo.']);
        }

        PreventivoPrezziEsposti::aggiornaDaRiga($preventivo, $corriereId);
        $request->session()->put('preventivo', $preventivo);

        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            abort(404);
        }

        $consegnaPunto = PuntoConsegnaSessione::richiestoPerRiga($riga);
        $consegnaMode = trim((string) ($riga['corriere']['consegna'] ?? ''));
        $dst = $preventivo['destino'] ?? [];
        $capArrivo = (string) data_get($preventivo, 'input.cap_destino', $dst['cap'] ?? '');

        $puntiDestRows = [];
        $puntiDestError = null;
        $corriereModel = corriere::query()->find($corriereId);
        $puntoConsegnaLabel = $corriereModel
            ? CorrierePuntoEtichetta::etichettaSelezionaCheckout($corriereModel->punto_consegna)
            : null;

        if ($consegnaPunto && SendcloudClient::isConfigured() && $corriereModel) {
            [$puntiDestRows, $puntiDestError] = $this->caricaPuntiDestinatario(
                app(SendcloudServicePointsService::class),
                $capArrivo,
                (string) ($dst['comune'] ?? ''),
                $corriereModel,
            );
        } elseif ($consegnaPunto) {
            $puntiDestError = 'API Sendcloud non configurata.';
        }

        $dest = is_array($indirizzi['destinazione'] ?? null) ? $indirizzi['destinazione'] : [];
        $puntoSelezionato = is_array($dest['punto_consegna'] ?? null) ? $dest['punto_consegna'] : [];
        if ($puntoSelezionato === [] && (int) ($dest['to_service_point'] ?? 0) > 0) {
            $puntoSelezionato = [
                'id' => (int) $dest['to_service_point'],
                'name' => (string) ($dest['nome_punto'] ?? ''),
                'to_post_number' => (string) ($dest['to_post_number'] ?? ''),
                'street' => (string) ($dest['via'] ?? ''),
                'house_number' => (string) ($dest['numero'] ?? ''),
                'address_line' => (string) ($dest['indirizzo'] ?? ''),
                'postal_code' => (string) ($dest['cap'] ?? ''),
                'city' => (string) ($dest['comune'] ?? ''),
            ];
        }
        $puntoSelezionato = $this->arricchisciPuntoDaElenco($puntoSelezionato, $puntiDestRows);

        $trasportoIvaEsc = (float) ($riga['prezzo_finale'] ?? 0);
        $trasportoBaseListino = (float) ($riga['prezzo_base'] ?? 0);
        $ricaricoTariffaPct = isset($riga['tariffa']['ricarico']) && $riga['tariffa']['ricarico'] !== null
            ? (float) $riga['tariffa']['ricarico']
            : 0.0;

        $idTipoSped = (int) data_get($preventivo, 'input.id_tipo_spediziones', 0);

        $serviziRows = ServiziAggiuntiviPrezzoService::scopeQueryCorriere($corriereId, $idTipoSped)->get();
        $serviziCheckoutGrouped = ServiziAggiuntiviPrezzoService::raggruppaPerCheckout($serviziRows);

        $aliquotaIva = parametri_globali::query()
            ->where('denominazione', 'Aliquota IVA')
            ->attivoOggi()
            ->value('valore_percentuale');
        $aliquotaIva = $aliquotaIva !== null ? (float) $aliquotaIva : 22.0;

        $metodi = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->orderBy('id')
            ->get();

        $totaleSvc = app(OrdineTotaleIvatoService::class);
        $metodiJson = $metodi->map(function (metodo_pagamento_ordine $m) use ($totaleSvc) {
            return [
                'id' => $m->id,
                'nome' => $m->metodo_pagamento,
                'pct' => (float) $m->commissioni,
                'abs' => 0.0,
                'is_wallet' => $totaleSvc->metodoIsWallet((int) $m->id),
                'is_carta' => $totaleSvc->metodoIsCarta((int) $m->id),
                'is_bonifico' => $totaleSvc->metodoIsBonifico((int) $m->id),
                'icon_url' => MetodoPagamentoIcon::pubblico((int) $m->id),
            ];
        })->values()->all();

        $cid = $corriereId;
        $logoUrl = CorriereLogo::pubblico($cid);
        $nomeCorriere = trim((string) ($riga['corriere']['nome_visualizzato'] ?? ''));
        if ($nomeCorriere === '') {
            $nomeCorriere = (string) ($riga['corriere']['nome_corriere'] ?? 'Corriere');
        }

        $usaQuoteApiServizi = $corriereModel
            && app(CheckoutServizioAggiuntivoQuoteService::class)->corriereUsaQuoteApiServizi($corriereModel);

        $isLiccardiTms = $corriereModel
            && LiccardiVolumeSconto::isCorriereLiccardiTms($corriereModel);

        return view('checkout', [
            'preventivo' => $preventivo,
            'riga' => $riga,
            'corriereId' => $cid,
            'nomeCorriere' => $nomeCorriere,
            'logoUrl' => $logoUrl,
            'trasportoIvaEsc' => $trasportoIvaEsc,
            'trasportoBaseListino' => $trasportoBaseListino,
            'ricaricoTariffaPct' => $ricaricoTariffaPct,
            'serviziCheckoutGrouped' => $serviziCheckoutGrouped,
            'aliquotaIva' => $aliquotaIva,
            'metodi' => $metodi,
            'metodiJson' => $metodiJson,
            'indirizzi' => $indirizzi,
            'stripeConfigured' => StripeConfig::isConfigured(),
            'consegnaPunto' => $consegnaPunto,
            'consegnaMode' => $consegnaMode,
            'puntoConsegnaLabel' => $puntoConsegnaLabel,
            'puntiDestRows' => $puntiDestRows,
            'puntiDestError' => $puntiDestError,
            'puntoSelezionato' => $puntoSelezionato,
            'usaQuoteApiServizi' => $usaQuoteApiServizi,
            'quoteServizioUrl' => route('checkout.quote-servizio'),
            'isLiccardiTms' => $isLiccardiTms,
            'liccardiVolumeMessaggio' => $isLiccardiTms ? LiccardiVolumeSconto::messaggioPreventivo() : null,
            'walletCommissionPct' => CarrelloPrezziWallet::commissioniPct(),
            'liccardiPrezzoVolume' => $isLiccardiTms
                ? LiccardiVolumeSconto::trasportoScontato($trasportoIvaEsc)
                : null,
        ]);
    }

    public function quoteServizio(Request $request, CheckoutServizioAggiuntivoQuoteService $quoteSvc): JsonResponse
    {
        $validated = $request->validate([
            'corriere_id' => ['required', 'integer', 'min:1'],
            'pivot_id' => ['required', 'integer', 'min:1'],
            'valore_merce' => ['required', 'numeric', 'min:0.01'],
        ]);

        $corriereId = (int) $validated['corriere_id'];
        $preventivo = $request->session()->get('preventivo');
        if (! is_array($preventivo)) {
            return response()->json(['ok' => false, 'error' => 'Sessione preventivo non valida.'], 422);
        }

        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            return response()->json(['ok' => false, 'error' => 'Corriere non trovato nel preventivo.'], 404);
        }

        $corriereModel = corriere::query()->find($corriereId);
        if (! $corriereModel) {
            return response()->json(['ok' => false, 'error' => 'Corriere non trovato.'], 404);
        }

        $config = corrieri_servizi_aggiuntivi::query()
            ->where('id_corriere', $corriereId)
            ->where('visualizzato', true)
            ->find((int) $validated['pivot_id']);

        if (! $config) {
            return response()->json(['ok' => false, 'error' => 'Servizio aggiuntivo non configurato per questo corriere.'], 404);
        }

        $esito = $quoteSvc->quote(
            $preventivo,
            $corriereModel,
            $config,
            (float) $validated['valore_merce'],
        );

        $status = ($esito['ok'] ?? false) ? 200 : 422;

        return response()->json($esito, $status);
    }

    /**
     * Crea l’ordine per la spedizione corrente (preventivo + servizi) ed esegue subito il pagamento / registrazione metodo.
     * Il carrello non viene modificato.
     */
    public function paga(CheckoutPagamentoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        return $this->checkoutPagamentoSvc->paga(
            $request,
            (int) $validated['corriere_id'],
            (int) $validated['metodo_pagamento_id'],
        );
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: string|null}
     */
    private function caricaPuntiDestinatario(
        SendcloudServicePointsService $servicePoints,
        string $cap,
        string $city,
        corriere $corriere,
    ): array {
        $filtri = DestinatarioConsegnaPunti::filtriDaCorriere($corriere);
        if ($filtri === null) {
            return [[], null];
        }

        $response = $servicePoints->searchDestinatario(
            $cap,
            $city,
            $filtri['general_shop_type'] ?? null,
            $filtri['carrier_shop_type'] ?? null,
            50,
            5000,
            $filtri['carrier_code'] ?? null,
        );

        if (! $response->successful()) {
            $decoded = $response->json();
            $msg = is_array($decoded)
                ? trim((string) ($decoded['error'] ?? $decoded['message'] ?? $decoded['detail'] ?? ''))
                : '';

            return [[], $msg !== '' ? $msg : 'Errore nel recupero punti Sendcloud.'];
        }

        $rows = $servicePoints->parseRows($response->json());

        return [$rows, $rows === [] ? 'Nessun punto trovato in questa zona.' : null];
    }

    /**
     * @param  array<string, mixed>  $punto
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function arricchisciPuntoDaElenco(array $punto, array $rows): array
    {
        if ($punto === [] || $rows === []) {
            return $punto;
        }

        $id = (int) ($punto['id'] ?? 0);
        if ($id < 1) {
            return $punto;
        }

        foreach ($rows as $row) {
            if (! is_array($row) || (int) ($row['id'] ?? 0) !== $id) {
                continue;
            }

            return array_merge($row, $punto);
        }

        return $punto;
    }
}
