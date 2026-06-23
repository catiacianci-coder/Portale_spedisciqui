<?php

namespace App\Http\Controllers;

use App\Models\corriere;
use App\Models\corrieri_servizi_aggiuntivi;
use App\Services\Checkout\CheckoutServizioAggiuntivoQuoteService;
use App\Services\Liccardi\LiccardiTmsClient;
use App\Services\Liccardi\LiccardiTmsRatesService;
use App\Services\Sendcloud\SendcloudClient;
use App\Services\Sendcloud\SendcloudServicePointsService;
use App\Services\Sendcloud\SendcloudShippingOptionsService;
use App\Services\ServiziAggiuntiviPrezzoService;
use App\Services\SpedisciOnline\SpedisciOnlineRatesService;
use App\Support\CorriereLogo;
use App\Support\CorrierePuntoEtichetta;
use App\Support\DestinatarioConsegnaPunti;
use App\Support\MittentePickupPunti;
use App\Support\PiattaformaCorriere;
use App\Support\LiccardiPremiumPricing;
use App\Support\PreventivoColonnePagamento;
use App\Support\PreventivoRigheAlternativiFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreventiviController extends Controller
{
    private const SESSION_KEY = 'preventivo';

    public function show(
        Request $request,
        SpedisciOnlineRatesService $ratesService,
        SendcloudShippingOptionsService $sendcloudRatesService,
        LiccardiTmsRatesService $liccardiTmsRatesService,
    )
    {
        $preventivo = $request->session()->get(self::SESSION_KEY);

        if (! $preventivo) {
            return redirect()
                ->route('home')
                ->withErrors(['preventivo' => 'Nessun preventivo in sessione. Compila prima i vincoli spedizione.']);
        }

        $corrieriIds = collect($preventivo['righe'] ?? [])
            ->map(fn ($r) => $r['corriere']['id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $corrieriIdsInt = $corrieriIds->map(fn ($id) => (int) $id)->values();

        $logoUrlPerCorriere = [];
        $corriereCampiAggiornati = [];

        $corrieriPerId = $corrieriIdsInt->isEmpty()
            ? collect()
            : corriere::query()->whereIn('id', $corrieriIdsInt)->with('ricarico')->get()->keyBy('id');

        $righe = is_array($preventivo['righe'] ?? null) ? $preventivo['righe'] : [];
        foreach ($righe as $i => $riga) {
            $cid = (int) ($riga['corriere']['id'] ?? 0);
            $fresh = $corrieriPerId->get($cid);
            if (! $fresh) {
                continue;
            }
            $righe[$i]['corriere']['tariffa_interna'] = $fresh->tariffa_interna;
            $righe[$i]['corriere']['piattaforma'] = $fresh->piattaforma;
            $righe[$i]['corriere']['codice_servizio'] = $fresh->codice_servizio;
        }
        $preventivo['righe'] = $righe;
        $request->session()->put(self::SESSION_KEY, $preventivo);

        $spedisciOnlineProbePerCorriere = [];
        $spedisciQuotePerCorriere = [];
        $sendcloudQuotePerCorriere = [];
        $liccardiQuotePerCorriere = [];
        foreach ($corrieriIdsInt as $id) {
            $logoUrlPerCorriere[$id] = CorriereLogo::pubblico($id);
            $fresh = $corrieriPerId->get($id);
            $corriereCampiAggiornati[$id] = $fresh
                ? array_merge(
                    $fresh->only([
                        'nome_visualizzato',
                        'nome_corriere_preventivo',
                        'piattaforma',
                        'tariffa_interna',
                        'pickup',
                        'consegna',
                        'punto_ritiro',
                        'punto_consegna',
                        'carrier_code',
                        'id_ricarico',
                    ]),
                    ['ricarico_percentuale' => $fresh->percentualeRicarico()],
                )
                : [];
            if ($fresh && ($fresh->tariffa_interna ?? true) && PiattaformaCorriere::mostraProbeRatesInPreventivi($fresh->piattaforma)) {
                if (PiattaformaCorriere::usaPreventiviSendcloud($fresh->piattaforma)) {
                    continue;
                }

                $spedisciOnlineProbePerCorriere[$id] = $ratesService->probeRatesForPreventivo($preventivo, $fresh->piattaforma);
            }
        }

        $sendcloudCorrieri = $corrieriPerId
            ->filter(fn (corriere $c): bool => PiattaformaCorriere::usaPreventiviSendcloud($c->piattaforma));
        $sendcloudCodes = $sendcloudCorrieri
            ->mapWithKeys(fn (corriere $c): array => [(int) $c->id => trim((string) $c->codice_servizio)])
            ->filter(fn (string $code): bool => $code !== '');

        if ($sendcloudCodes->isNotEmpty()) {
            if (! SendcloudClient::isConfigured()) {
                foreach ($sendcloudCodes->keys() as $cid) {
                    $sendcloudQuotePerCorriere[(int) $cid] = [
                        'configured' => false,
                        'error' => 'Chiavi Sendcloud mancanti in parametri globali.',
                    ];
                }
            } else {
                $input = is_array($preventivo['input'] ?? null) ? $preventivo['input'] : [];
                $payload = $sendcloudRatesService->buildNationalPayload([
                    'cap_origine' => (string) ($input['cap_origine'] ?? ''),
                    'cap_destino' => (string) ($input['cap_destino'] ?? ''),
                    'citta_origine' => (string) (($preventivo['origine']['comune'] ?? 'Roma')),
                    'citta_destino' => (string) (($preventivo['destino']['comune'] ?? 'Milano')),
                    'peso' => (float) ($input['peso'] ?? 1),
                    'spessore' => (float) ($input['spessore'] ?? 30),
                    'larghezza' => (float) ($input['larghezza'] ?? 20),
                    'altezza' => (float) ($input['altezza'] ?? 15),
                ]);
                $requestPayload = array_merge(['calculate_quotes' => true], $payload);
                $response = $sendcloudRatesService->listWithQuotes($payload);
                $responseJson = $response->json();
                $responseJson = is_array($responseJson) ? $responseJson : null;
                $rawBody = $response->body();
                $rows = $response->successful() ? $sendcloudRatesService->parseQuoteRows($responseJson) : [];
                $rowsByCode = collect($rows)->keyBy('code');

                foreach ($sendcloudCodes as $cid => $code) {
                    $matched = $rowsByCode->get($code);
                    $sendcloudQuotePerCorriere[(int) $cid] = [
                        'configured' => true,
                        'api_base' => app(SendcloudClient::class)->baseUrl(),
                        'endpoint' => 'POST /shipping-options',
                        'http_status' => $response->status(),
                        'code' => $code,
                        'payload' => $requestPayload,
                        'response_json' => $responseJson,
                        'raw_body' => $rawBody,
                        'quote' => is_array($matched) ? $matched : null,
                        'error' => $response->successful()
                            ? (is_array($matched) ? null : 'Nessun preventivo Sendcloud trovato per questo codice.')
                            : 'Errore HTTP '.$response->status().' da Sendcloud.',
                    ];
                }
            }
        }

        $liccardiCorrieri = $corrieriPerId->filter(
            fn (corriere $c): bool => PiattaformaCorriere::corriereUsaPreventivoLiccardiTms($c)
        );
        $utenteLiccardi = LiccardiPremiumPricing::utenteLiccardi($request->user());
        foreach ($liccardiCorrieri as $cid => $corriereRow) {
            if (! $utenteLiccardi) {
                continue;
            }
            $liccardiQuotePerCorriere[(int) $cid] = $liccardiTmsRatesService->quoteForPreventivo($preventivo, $corriereRow);
        }

        $spedisciCorrieri = $corrieriPerId->filter(
            fn (corriere $c): bool => PiattaformaCorriere::corriereUsaPreventivoSpedisciOnline($c)
        );
        foreach ($spedisciCorrieri as $cid => $corriereRow) {
            $spedisciQuotePerCorriere[(int) $cid] = $ratesService->quoteForPreventivo($preventivo, $corriereRow);
        }

        $idTipoSped = (int) data_get($preventivo, 'input.id_tipo_spediziones', 0);

        $pivotRows = $corrieriIdsInt->isEmpty()
            ? collect()
            : corrieri_servizi_aggiuntivi::query()
                ->whereIn('id_corriere', $corrieriIdsInt)
                ->where('visualizzato', true)
                ->when($idTipoSped > 0, function ($q) use ($idTipoSped) {
                    $q->where(function ($w) use ($idTipoSped) {
                        $w->whereNull('id_tipo')->orWhere('id_tipo', $idTipoSped);
                    });
                })
                ->orderBy('id_corriere')
                ->orderBy('testo_servizio')
                ->orderBy('min_fascia')
                ->get();

        $quoteSvc = app(CheckoutServizioAggiuntivoQuoteService::class);
        $serviziIndicativiPerCorriere = [];
        foreach ($corrieriIdsInt as $idCorriere) {
            $rows = $pivotRows->where('id_corriere', $idCorriere)->values();
            $gruppi = ServiziAggiuntiviPrezzoService::raggruppaPerCheckout($rows);

            $trasportoBaseFornitore = 0.0;
            foreach ($righe as $rigaPrev) {
                if (! is_array($rigaPrev)) {
                    continue;
                }
                if ((int) ($rigaPrev['corriere']['id'] ?? 0) !== (int) $idCorriere) {
                    continue;
                }
                $corRiga = array_merge(
                    $rigaPrev['corriere'] ?? [],
                    $corriereCampiAggiornati[$idCorriere] ?? [],
                );
                $piattaformaRiga = PiattaformaCorriere::normalizza($corRiga['piattaforma'] ?? '');
                $usaTariffaInternaRiga = (bool) ($corRiga['tariffa_interna'] ?? true);
                if (PiattaformaCorriere::usaPreventiviSendcloud($piattaformaRiga)) {
                    $q = $sendcloudQuotePerCorriere[$idCorriere]['quote']['price_amount'] ?? null;
                    if ($q !== null) {
                        $trasportoBaseFornitore = (float) $q;
                    }
                } elseif (PiattaformaCorriere::usaPreventiviLiccardiTms($piattaformaRiga) && ! $usaTariffaInternaRiga) {
                    $q = $liccardiQuotePerCorriere[$idCorriere]['quote']['price_amount'] ?? null;
                    if ($q !== null) {
                        $trasportoBaseFornitore = $utenteLiccardi
                            ? LiccardiPremiumPricing::costoTrasportoBase((float) $q)
                            : (float) $q;
                    }
                } elseif (PiattaformaCorriere::usaPreventiviSpedisciOnline($piattaformaRiga) && ! $usaTariffaInternaRiga) {
                    $q = $spedisciQuotePerCorriere[$idCorriere]['quote']['price_amount'] ?? null;
                    if ($q !== null) {
                        $trasportoBaseFornitore = (float) $q;
                    }
                } else {
                    $trasportoBaseFornitore = (float) ($rigaPrev['prezzo_base'] ?? 0);
                }
                break;
            }

            $corriereModel = $corrieriPerId->get($idCorriere);
            $usaQuoteApiServizi = $corriereModel && $quoteSvc->corriereUsaQuoteApiServizi($corriereModel);

            $serviziIndicativiPerCorriere[$idCorriere] = ServiziAggiuntiviPrezzoService::indicativiPreventivi(
                $gruppi,
                $trasportoBaseFornitore,
                $usaQuoteApiServizi,
                $rows,
            );
        }

        $inputPreventivo = is_array($preventivo['input'] ?? null) ? $preventivo['input'] : [];

        $corrieriIdsNascosti = PreventivoRigheAlternativiFilter::corriereIdsDaNascondere(
            $sendcloudQuotePerCorriere,
            $spedisciQuotePerCorriere,
        );

        return view('preventivi', [
            'preventivo' => $preventivo,
            'colonnePagamento' => PreventivoColonnePagamento::colonneAttive(),
            'serviziIndicativiPerCorriere' => $serviziIndicativiPerCorriere,
            'logoUrlPerCorriere' => $logoUrlPerCorriere,
            'corriereCampiAggiornati' => $corriereCampiAggiornati,
            'spedisciOnlineProbePerCorriere' => $spedisciOnlineProbePerCorriere,
            'spedisciQuotePerCorriere' => $spedisciQuotePerCorriere,
            'sendcloudQuotePerCorriere' => $sendcloudQuotePerCorriere,
            'liccardiQuotePerCorriere' => $liccardiQuotePerCorriere,
            'corrieriIdsNascosti' => $corrieriIdsNascosti,
            'utenteLiccardi' => $utenteLiccardi,
            'sendcloudConfigured' => SendcloudClient::isConfigured(),
            'liccardiTmsConfigured' => LiccardiTmsClient::isConfigured(),
            'capOriginePreventivo' => (string) ($inputPreventivo['cap_origine'] ?? ''),
            'cittaOriginePreventivo' => (string) ($preventivo['origine']['comune'] ?? ''),
        ]);
    }

    public function puntiMittente(Request $request, SendcloudServicePointsService $servicePoints): JsonResponse
    {
        return $this->puntiServizio($request, $servicePoints);
    }

    public function puntiServizio(Request $request, SendcloudServicePointsService $servicePoints): JsonResponse
    {
        $preventivo = $request->session()->get(self::SESSION_KEY);
        if (! $preventivo) {
            return response()->json(['ok' => false, 'error' => 'Nessun preventivo in sessione.'], 403);
        }

        if (! SendcloudClient::isConfigured()) {
            return response()->json(['ok' => false, 'error' => 'API Sendcloud non configurata.'], 503);
        }

        $corriereId = (int) $request->query('corriere_id', 0);
        $tipo = trim((string) $request->query('tipo', 'ritiro'));
        $corriere = $corriereId > 0 ? corriere::query()->find($corriereId) : null;

        $input = is_array($preventivo['input'] ?? null) ? $preventivo['input'] : [];
        $filtri = null;
        $cap = '';
        $city = '';

        if ($corriere instanceof corriere) {
            if ($tipo === 'consegna') {
                $filtri = DestinatarioConsegnaPunti::filtriDaCorriere($corriere);
                $cap = trim((string) ($input['cap_destino'] ?? ''));
                $city = trim((string) ($preventivo['destino']['comune'] ?? ''));
            } else {
                $config = MittentePickupPunti::configDaCorriere($corriere);
                if ($config !== null) {
                    $filtri = [
                        'carrier_code' => $config['carrier_code'] ?? null,
                        'general_shop_type' => $config['filter_general'] ?? null,
                        'carrier_shop_type' => $config['filter_carrier_shop'] ?? null,
                    ];
                }
                $cap = trim((string) ($input['cap_origine'] ?? ''));
                $city = trim((string) ($preventivo['origine']['comune'] ?? ''));
            }
        } else {
            $general = trim((string) $request->query('general_shop_type', ''));
            $carrierShop = trim((string) $request->query('carrier_shop_type', ''));
            $carrierCode = trim((string) $request->query('carrier_code', ''));
            if ($general !== '' || $carrierShop !== '' || $carrierCode !== '') {
                $filtri = [
                    'carrier_code' => $carrierCode !== '' ? $carrierCode : null,
                    'general_shop_type' => $general !== '' ? $general : null,
                    'carrier_shop_type' => $carrierShop !== '' ? $carrierShop : null,
                ];
            }
            $cap = trim((string) ($input['cap_origine'] ?? ''));
            $city = trim((string) ($preventivo['origine']['comune'] ?? ''));
        }

        if ($filtri === null) {
            return response()->json(['ok' => false, 'error' => 'Ricerca punti non configurata per questo corriere.'], 422);
        }

        if ($cap === '' && $city === '') {
            return response()->json(['ok' => false, 'error' => 'CAP o città mancanti nel preventivo.'], 422);
        }

        $response = $servicePoints->searchMittente(
            $cap,
            $city,
            $filtri['general_shop_type'] ?? null,
            $filtri['carrier_shop_type'] ?? null,
            40,
            8000,
            $filtri['carrier_code'] ?? null,
        );
        if (! $response->successful()) {
            $decoded = $response->json();
            $msg = is_array($decoded)
                ? trim((string) ($decoded['error'] ?? $decoded['message'] ?? $decoded['detail'] ?? ''))
                : '';

            return response()->json([
                'ok' => false,
                'error' => $msg !== '' ? $msg : 'Errore HTTP '.$response->status().' da Sendcloud.',
            ], $response->status() >= 400 ? $response->status() : 502);
        }

        $rows = $servicePoints->parseRows($response->json());
        $geocoding = $servicePoints->parseGeocoding($response->json());

        return response()->json([
            'ok' => true,
            'count' => count($rows),
            'geocoding' => $geocoding,
            'points' => $rows,
            'reference' => ['cap' => $cap, 'city' => $city, 'tipo' => $tipo],
        ]);
    }
}
