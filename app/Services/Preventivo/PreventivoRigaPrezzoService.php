<?php

namespace App\Services\Preventivo;

use App\Models\corriere;
use App\Models\tariffa;
use App\Services\Liccardi\LiccardiTmsRatesService;
use App\Services\OrdineTotaleIvatoService;
use App\Services\RegolePricingService;
use App\Services\Sendcloud\SendcloudClient;
use App\Services\Sendcloud\SendcloudShippingOptionsService;
use App\Services\TariffaPrezzoBaseService;
use App\Support\PiattaformaCorriere;
use Carbon\Carbon;

/**
 * Ricalcolo prezzo riga preventivo (listino interno o API esterna) — fonte di verità al checkout.
 */
final class PreventivoRigaPrezzoService
{
    public function __construct(
        private readonly SendcloudShippingOptionsService $sendcloudRates,
        private readonly LiccardiTmsRatesService $liccardiTmsRates,
        private readonly RegolePricingService $regolePricingService,
    ) {}

    /**
     * @return array{ok: bool, error?: string, prezzo_base?: float|null, prezzo_finale?: float|null, prezzo_wallet?: float|null, wallet_modifier_pct?: float|null, tariffa?: array<string, mixed>|null, quotazione_esterna?: array<string, mixed>|null}
     */
    public function ricalcola(array $preventivo, corriere $corriere): array
    {
        if (! (bool) ($corriere->tariffa_interna ?? true)) {
            return $this->ricalcolaEsterno($preventivo, $corriere);
        }

        return $this->ricalcolaInterno($preventivo, $corriere);
    }

    public function aggiornaSessione(array &$preventivo, int $corriereId): array
    {
        $corriere = corriere::query()->with('ricarico')->find($corriereId);
        if (! $corriere) {
            return ['ok' => false, 'error' => 'Corriere non trovato.'];
        }

        $esito = $this->ricalcola($preventivo, $corriere);
        if (! ($esito['ok'] ?? false)) {
            return $esito;
        }

        $righe = $preventivo['righe'] ?? [];
        foreach ($righe as $i => $riga) {
            if ((int) ($riga['corriere']['id'] ?? 0) !== $corriereId) {
                continue;
            }
            $righe[$i]['prezzo_base'] = $esito['prezzo_base'] ?? null;
            $righe[$i]['prezzo_finale'] = $esito['prezzo_finale'] ?? null;
            $righe[$i]['prezzo_wallet'] = $esito['prezzo_wallet'] ?? null;
            $righe[$i]['wallet_modifier_pct'] = $esito['wallet_modifier_pct'] ?? null;
            if (array_key_exists('tariffa', $esito)) {
                $righe[$i]['tariffa'] = $esito['tariffa'];
            }
            if (array_key_exists('quotazione_esterna', $esito)) {
                $righe[$i]['quotazione_esterna'] = $esito['quotazione_esterna'];
            }
            break;
        }
        $preventivo['righe'] = $righe;
        $preventivo['prezzo_ricalcolato_il'] = now()->toIso8601String();

        return $esito;
    }

    /**
     * @return array{ok: bool, error?: string, prezzo_base?: float, prezzo_finale?: float, prezzo_wallet?: float, wallet_modifier_pct?: float, tariffa?: array<string, mixed>}
     */
    private function ricalcolaInterno(array $preventivo, corriere $corriere): array
    {
        $input = is_array($preventivo['input'] ?? null) ? $preventivo['input'] : [];
        $idTipo = (int) ($input['id_tipo_spediziones'] ?? 0);
        $peso = (float) ($input['peso'] ?? 0);
        $dims = is_array($preventivo['misure'] ?? null) ? $preventivo['misure'] : [];
        $latoMax = (float) ($dims['lato_max'] ?? 0);
        $latoMed = (float) ($dims['lato_med'] ?? 0);
        $latoMin = (float) ($dims['lato_min'] ?? 0);
        $sommaLati = (float) ($dims['somma_lati'] ?? ($latoMax + $latoMed + $latoMin));

        $tariffaRow = $this->trovaTariffaCompatibile(
            (int) $corriere->id,
            $idTipo,
            $peso,
            $latoMax,
            $latoMed,
            $latoMin,
            $sommaLati,
        );

        if (! $tariffaRow) {
            return ['ok' => false, 'error' => 'Nessuna tariffa compatibile per peso e dimensioni attuali.'];
        }

        $origine = is_array($preventivo['origine'] ?? null) ? $preventivo['origine'] : [];
        $destino = is_array($preventivo['destino'] ?? null) ? $preventivo['destino'] : [];
        $regioneOrigine = isset($origine['regione']) ? (string) $origine['regione'] : null;
        $regioneDestino = isset($destino['regione']) ? (string) $destino['regione'] : null;

        $base = TariffaPrezzoBaseService::prezzoBase($tariffaRow, $corriere, $regioneOrigine, $regioneDestino);
        $ricarico = $tariffaRow->ricarico === null ? 0.0 : (float) $tariffaRow->ricarico;
        $sovrattassa = $this->regolePricingService->calcolaSovrattassaDisagiato(
            (int) $corriere->id,
            (int) ($input['id_comune_origine'] ?? 0),
            (int) ($input['id_comune_destino'] ?? 0),
            $peso,
        );

        $prezzoFinale = ($base * (1 + ($ricarico / 100))) + $sovrattassa;
        $wMod = $this->walletPaymentModifier();
        $prezzoWallet = round($prezzoFinale * (1 + ($wMod['pct'] / 100)) + $wMod['abs'], 2);

        return [
            'ok' => true,
            'prezzo_base' => $base,
            'prezzo_finale' => $prezzoFinale,
            'prezzo_wallet' => $prezzoWallet,
            'wallet_modifier_pct' => $wMod['pct'],
            'tariffa' => $tariffaRow->toArray(),
        ];
    }

    /**
     * @return array{ok: bool, error?: string, prezzo_base?: float|null, prezzo_finale?: float, prezzo_wallet?: float, wallet_modifier_pct?: float, tariffa?: array<string, mixed>|null, quotazione_esterna?: array<string, mixed>}
     */
    private function ricalcolaEsterno(array $preventivo, corriere $corriere): array
    {
        if (PiattaformaCorriere::corriereUsaPreventivoLiccardiTms($corriere)) {
            return $this->ricalcolaEsternoLiccardiTms($preventivo, $corriere);
        }

        if (PiattaformaCorriere::corriereUsaPreventivoSendcloud($corriere)) {
            return $this->ricalcolaEsternoSendcloud($preventivo, $corriere);
        }

        return ['ok' => false, 'error' => 'Piattaforma esterna non ancora supportata per il ricalcolo.'];
    }

    private function ricalcolaEsternoLiccardiTms(array $preventivo, corriere $corriere): array
    {
        $result = $this->liccardiTmsRates->quoteForPreventivo($preventivo, $corriere);
        $quote = is_array($result['quote'] ?? null) ? $result['quote'] : null;
        $amount = $quote['price_amount'] ?? null;

        if (! ($result['configured'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($result['error'] ?? 'API Liccardi TMS non configurata.')];
        }

        if ($amount === null || (float) $amount <= 0) {
            return ['ok' => false, 'error' => (string) ($result['error'] ?? 'Quotazione Liccardi TMS non disponibile.')];
        }

        return $this->esitoDaCostoApi($corriere, (float) $amount, [
            'piattaforma' => PiattaformaCorriere::LICCARDI_TMS,
            'quote' => $quote,
        ]);
    }

    private function ricalcolaEsternoSendcloud(array $preventivo, corriere $corriere): array
    {
        if (! SendcloudClient::isConfigured()) {
            return ['ok' => false, 'error' => 'API Sendcloud non configurata.'];
        }

        $code = trim((string) ($corriere->codice_servizio ?? ''));
        if ($code === '') {
            return ['ok' => false, 'error' => 'Codice servizio Sendcloud mancante sul corriere.'];
        }

        $input = is_array($preventivo['input'] ?? null) ? $preventivo['input'] : [];
        $payload = $this->sendcloudRates->buildNationalPayload([
            'cap_origine' => (string) ($input['cap_origine'] ?? ''),
            'cap_destino' => (string) ($input['cap_destino'] ?? ''),
            'citta_origine' => (string) (($preventivo['origine']['comune'] ?? 'Roma')),
            'citta_destino' => (string) (($preventivo['destino']['comune'] ?? 'Milano')),
            'peso' => (float) ($input['peso'] ?? 1),
            'spessore' => (float) ($input['spessore'] ?? 30),
            'larghezza' => (float) ($input['larghezza'] ?? 20),
            'altezza' => (float) ($input['altezza'] ?? 15),
        ]);

        $response = $this->sendcloudRates->listWithQuotes($payload);
        if (! $response->successful()) {
            return ['ok' => false, 'error' => 'Errore nel ricalcolo prezzo da Sendcloud.'];
        }

        $rows = $this->sendcloudRates->parseQuoteRows($response->json());
        $matched = collect($rows)->first(fn ($row) => ($row['code'] ?? '') === $code);

        if (! is_array($matched) || $matched['price_amount'] === null) {
            return ['ok' => false, 'error' => 'Quotazione Sendcloud non disponibile per questo servizio.'];
        }

        return $this->esitoDaCostoApi($corriere, (float) $matched['price_amount'], [
            'piattaforma' => PiattaformaCorriere::SENDCLOUD,
            'code' => $code,
            'quote' => $matched,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extraQuotazione
     * @return array{ok: true, prezzo_base: float, prezzo_finale: float, prezzo_wallet: float, wallet_modifier_pct: float, tariffa: array<string, mixed>, quotazione_esterna: array<string, mixed>}
     */
    private function esitoDaCostoApi(corriere $corriere, float $costoApi, array $extraQuotazione): array
    {
        $corriere->loadMissing('ricarico');
        $prezzi = $corriere->prezzoTrasportoDaCostoApi($costoApi);
        $prezzoFinale = $prezzi['prezzo_cliente'];
        $wMod = $this->walletPaymentModifier();
        $prezzoWallet = round($prezzoFinale * (1 + ($wMod['pct'] / 100)) + $wMod['abs'], 2);

        return [
            'ok' => true,
            'prezzo_base' => $prezzi['costo_api'],
            'prezzo_finale' => $prezzoFinale,
            'prezzo_wallet' => $prezzoWallet,
            'wallet_modifier_pct' => $wMod['pct'],
            'tariffa' => [
                'ricarico' => $prezzi['ricarico_percentuale'],
                'id_ricarico' => $corriere->id_ricarico,
                'fonte' => 'corriere_api',
            ],
            'quotazione_esterna' => array_merge($extraQuotazione, [
                'costo_api' => $prezzi['costo_api'],
                'ricarico_percentuale' => $prezzi['ricarico_percentuale'],
                'ricalcolato_il' => now()->toIso8601String(),
            ]),
        ];
    }

    private function trovaTariffaCompatibile(
        int $idCorriere,
        int $idTipoSpedizione,
        float $peso,
        float $latoMax,
        float $latoMed,
        float $latoMin,
        float $sommaLati,
    ): ?tariffa {
        $oggi = Carbon::today();

        $candidati = tariffa::query()
            ->where('id_corrieres', $idCorriere)
            ->where('id_tipo_spediziones', $idTipoSpedizione)
            ->where(function ($q) use ($peso) {
                $q->whereNull('peso_da')->orWhere('peso_da', '<=', $peso);
            })
            ->where(function ($q) use ($peso) {
                $q->whereNull('peso_a')->orWhere('peso_a', '>=', $peso);
            })
            ->where(function ($q) use ($oggi) {
                $q->whereNull('data_sospensione')->orWhereDate('data_sospensione', '>', $oggi);
            })
            ->orderBy('tariffa')
            ->get();

        foreach ($candidati as $t) {
            if ($this->tariffaRispettaDimensioni($t, $latoMax, $latoMed, $latoMin, $sommaLati, $peso)) {
                return $t;
            }
        }

        return null;
    }

    private function tariffaRispettaDimensioni(
        tariffa $t,
        float $latoMax,
        float $latoMed,
        float $latoMin,
        float $sommaLati,
        float $peso,
    ): bool {
        $latoMaxTariffaCm = $this->normalizzaLatoMaxInCm($t->lato_max);
        if ($latoMaxTariffaCm !== null && $latoMax > $latoMaxTariffaCm) {
            return false;
        }

        if ($t->max !== null && $sommaLati > (float) $t->max) {
            return false;
        }

        if ($t->peso_max_collo !== null && $peso > (float) $t->peso_max_collo) {
            return false;
        }

        return true;
    }

    private function normalizzaLatoMaxInCm(mixed $valore): ?float
    {
        if ($valore === null || $valore === '') {
            return null;
        }

        $v = (float) $valore;
        if ($v <= 0) {
            return null;
        }

        return $v <= 10 ? $v * 100 : $v;
    }

    /**
     * @return array{pct: float, abs: float}
     */
    private function walletPaymentModifier(): array
    {
        $pct = app(OrdineTotaleIvatoService::class)->commissioniWalletOrdine();

        return ['pct' => $pct, 'abs' => 0.0];
    }
}
