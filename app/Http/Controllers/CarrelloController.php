<?php

namespace App\Http\Controllers;

use App\Models\corrieri_servizi_aggiuntivi;
use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\parametri_globali;
use App\Models\spedizione;
use App\Models\spedizione_servizio_aggiuntivi;
use App\Models\tariffa_spedizione;
use App\Models\corriere;
use App\Models\comune;
use App\Models\tariffa;
use App\Services\Checkout\CheckoutServizioAggiuntivoQuoteService;
use App\Services\TariffaPrezzoBaseService;
use App\Models\tipo_spedizone;
use App\Services\ServiziAggiuntiviPrezzoService;
use App\Support\CarrelloPrezziWallet;
use App\Support\CarrelloUtente;
use App\Support\ChiaveCausaleOrdine;
use App\Support\CorriereLogo;
use App\Support\IndirizzoSpedizioneSnapshot;
use App\Support\LiccardiVolumeSconto;
use App\Support\PreventivoRigaSelezionabile;
use App\Support\PuntoConsegnaSessione;
use App\Support\SpedizioneCampiPersistenza;
use App\Support\OrdineTotaliPagamento;
use App\Support\TariffaSpedizioneDaRiga;
use App\Support\RigaCarrelloOrdine;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarrelloController extends Controller
{
    /**
     * Extra servizi lato cliente (IVA esc.): ricarico_k91 sul costo corriere, non sul valore merce.
     *
     * @param  array<int, array<string, mixed>>  $servizi
     */
    private function extraServiziClienteCarrello(float $trasportoBaseListino, float $ricaricoTariffaPct, array $servizi): float
    {
        $extra = 0.0;
        foreach ($servizi as $s) {
            if (! is_array($s)) {
                continue;
            }
            $row = null;
            $pid = isset($s['id']) ? (int) $s['id'] : 0;
            if ($pid > 0) {
                $row = corrieri_servizi_aggiuntivi::query()->find($pid);
            }
            if (! $row) {
                continue;
            }
            if (isset($s['costo_cliente']) && is_numeric($s['costo_cliente'])) {
                $extra += (float) $s['costo_cliente'];
                continue;
            }

            $merce = (float) ($s['valore_merce'] ?? 0);
            $netto = ServiziAggiuntiviPrezzoService::importoNettoListino($row, $merce, $trasportoBaseListino);
            $extra += ServiziAggiuntiviPrezzoService::importoClienteIvaEsc($netto, $row, $ricaricoTariffaPct);
        }

        return round($extra, 2);
    }

    /** @param  array<string, mixed>  $item */
    private function arricchisciItem(array $item): array
    {
        $trasporto = (float) ($item['trasporto_iva_esc'] ?? 0);
        $servizi = $item['servizi_selezionati'] ?? [];
        if (! is_array($servizi)) {
            $servizi = [];
        }
        $baseListino = (float) ($item['prezzo_base_trasporto_iva_esc'] ?? 0);
        $ric = (float) ($item['ricarico_tariffa_pct'] ?? 0);
        if ($ric === 0.0 && ! empty($item['id_tariffas'])) {
            $trow = tariffa::query()->find((int) $item['id_tariffas']);
            if ($trow && $trow->ricarico !== null) {
                $ric = (float) $trow->ricarico;
            }
        }
        $extra = $this->extraServiziClienteCarrello($baseListino, $ric, $servizi);
        $item['extra_servizi_iva_esc'] = $extra;
        $item['netto_iva_esc'] = round($trasporto + $extra, 2);
        $item = CarrelloPrezziWallet::sincronizzaDaTrasporto($item, $trasporto);

        $tipoNome = trim((string) ($item['tipo_spedizione_nome'] ?? ''));
        if ($tipoNome === '') {
            $idTipo = (int) data_get($item, 'preventivo_input.id_tipo_spediziones', 0);
            if ($idTipo > 0) {
                $lbl = tipo_spedizone::query()->whereKey($idTipo)->value('tipo_spedizione');
                if ($lbl !== null && trim((string) $lbl) !== '') {
                    $item['tipo_spedizione_nome'] = trim((string) $lbl);
                }
            }
        }

        return $item;
    }

    /**
     * Arricchisce le righe carrello e applica eventuale sconto volume Liccardi (≥10 spedizioni).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     liccardi_volume: array{applicato: bool, righe_liccardi: int, sconto_totale: float}
     * }
     */
    private function arricchisciItemsCarrello(array $items): array
    {
        $items = array_values(array_map(fn (array $it) => $this->arricchisciItem($it), $items));
        $esito = LiccardiVolumeSconto::applicaAlCarrello($items);

        return [
            'items' => $esito['items'],
            'liccardi_volume' => [
                'applicato' => $esito['applicato'],
                'righe_liccardi' => $esito['righe_liccardi'],
                'sconto_totale' => $esito['sconto_totale'],
            ],
        ];
    }

    /**
     * Stima acquisto servizi usando la stessa struttura %/€ del listino corriere–servizio, applicata al netto listino trasporto (prezzo base tariffa).
     *
     * @param  array<int, array<string, mixed>>  $servizi
     */
    private function sommaAcquistoServiziDaListino(float $baseListinoTrasporto, array $servizi): float
    {
        $sum = 0.0;
        foreach ($servizi as $s) {
            if (! is_array($s)) {
                continue;
            }
            $row = null;
            $pid = isset($s['id']) ? (int) $s['id'] : 0;
            if ($pid > 0) {
                $row = corrieri_servizi_aggiuntivi::query()->find($pid);
            }
            if (! $row) {
                continue;
            }
            $merce = (float) ($s['valore_merce'] ?? 0);
            $sum += ServiziAggiuntiviPrezzoService::importoNettoListino($row, $merce, $baseListinoTrasporto);
        }

        return round($sum, 2);
    }

    /**
     * @param  array<string, mixed>  $sel
     */
    private function risolviPivotServizio(int $corriereId, int $idTipoSpedizione, array $sel): ?corrieri_servizi_aggiuntivi
    {
        if (! empty($sel['id'])) {
            return corrieri_servizi_aggiuntivi::query()
                ->where('id_corriere', $corriereId)
                ->find((int) $sel['id']);
        }

        $legacyPivotId = (int) ($sel['id_servizi_aggiuntivi'] ?? 0);
        if ($legacyPivotId > 0) {
            $hint = corrieri_servizi_aggiuntivi::query()
                ->where('id_corriere', $corriereId)
                ->find($legacyPivotId);
            if ($hint) {
                $merce = RigaCarrelloOrdine::parseNumero($sel['valore_merce'] ?? 0) ?? 0.0;
                if (ServiziAggiuntiviPrezzoService::rigaSuValoreMerce($hint)) {
                    $q = ServiziAggiuntiviPrezzoService::scopeQueryCorriere($corriereId, $idTipoSpedizione)
                        ->where('testo_servizio', $hint->testo_servizio);

                    return ServiziAggiuntiviPrezzoService::risolviRigaPerMerce($q->get(), $merce);
                }

                return $hint;
            }
        }

        $testo = trim((string) ($sel['testo_servizio'] ?? ''));
        $merce = RigaCarrelloOrdine::parseNumero($sel['valore_merce'] ?? 0) ?? 0.0;

        $q = ServiziAggiuntiviPrezzoService::scopeQueryCorriere($corriereId, $idTipoSpedizione);
        if ($testo !== '') {
            $q->where('testo_servizio', $testo);
        }

        return ServiziAggiuntiviPrezzoService::risolviRigaPerMerce($q->get(), $merce);
    }

    /**
     * @param  array<int, array<string, mixed>>  $servizi
     */
    private function salvaServiziSpedizione(
        spedizione $spedizione,
        array $servizi,
        float $baseListinoTrasporto,
        float $ricaricoTariffaPct = 0.0,
    ): void {
        foreach ($servizi as $s) {
            if (! is_array($s)) {
                continue;
            }
            $row = null;
            $pid = isset($s['id']) ? (int) $s['id'] : 0;
            if ($pid > 0) {
                $row = corrieri_servizi_aggiuntivi::query()->find($pid);
            }
            if (! $row) {
                continue;
            }
            $merce = (float) ($s['valore_merce'] ?? 0);
            if (isset($s['costo_fornitore']) && is_numeric($s['costo_fornitore'])
                && isset($s['costo_cliente']) && is_numeric($s['costo_cliente'])) {
                $nostroLinea = round((float) $s['costo_fornitore'], 2);
                $costoCliente = round((float) $s['costo_cliente'], 2);
            } else {
                $nostroLinea = round(ServiziAggiuntiviPrezzoService::importoNettoListino($row, $merce, $baseListinoTrasporto), 2);
                $costoCliente = ServiziAggiuntiviPrezzoService::importoClienteIvaEsc($nostroLinea, $row, $ricaricoTariffaPct);
            }
            $giorniRimessaTra = (int) ($row->rimessa_tra ?? 0);
            $dPpt = $giorniRimessaTra > 0
                ? Carbon::today()->addDays($giorniRimessaTra)->toDateString()
                : null;

            spedizione_servizio_aggiuntivi::query()->create([
                'id_spedizionis' => $spedizione->id,
                'id_corrieri_servizi_aggiuntivis' => $row->id,
                'testo_servizio' => $row->testo_servizio,
                'valore_merce' => $merce > 0 ? round($merce, 2) : null,
                'maggiorazione_pct' => (float) ($row->percentuale_cor ?? 0),
                'maggiorazione_abs' => (float) ($row->valore_fisso_cor ?? 0),
                'nostro_acquisto_stimato_iva_esc' => $nostroLinea,
                'costo_cliente' => $costoCliente,
                'd_p_p_t' => $dPpt,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $servizi
     * @param  array<string, mixed>  $preventivo
     * @return array<int, array<string, mixed>>
     */
    private function arricchisciServiziConQuoteApi(array $servizi, ?corriere $corriere, array $preventivo): array
    {
        if (! $corriere || $preventivo === []) {
            return $servizi;
        }

        $quoteSvc = app(CheckoutServizioAggiuntivoQuoteService::class);
        if (! $quoteSvc->corriereUsaQuoteApiServizi($corriere)) {
            return $servizi;
        }

        $out = [];
        foreach ($servizi as $s) {
            if (! is_array($s)) {
                continue;
            }
            $row = corrieri_servizi_aggiuntivi::query()->find((int) ($s['id'] ?? 0));
            if (! $row) {
                $out[] = $s;
                continue;
            }
            $merce = (float) ($s['valore_merce'] ?? 0);
            if ($merce <= 0) {
                $out[] = $s;
                continue;
            }
            $esito = $quoteSvc->quote($preventivo, $corriere, $row, $merce);
            if ($esito['ok'] ?? false) {
                $s['costo_fornitore'] = $esito['costo_fornitore'];
                $s['costo_cliente'] = $esito['costo_cliente'];
                $s['quote_api'] = true;
                $s['fonte'] = $esito['fonte'] ?? 'api';
            }
            $out[] = $s;
        }

        return $out;
    }

    public function index(Request $request)
    {
        CarrelloUtente::idrataSessione($request);
        $carrello = $request->session()->get('carrello', ['items' => []]);
        $items = $carrello['items'] ?? [];
        $carrelloArricchito = $this->arricchisciItemsCarrello($items);
        $items = $carrelloArricchito['items'];

        $totaleNetto = 0.0;
        foreach ($items as $it) {
            $totaleNetto += (float) ($it['netto_iva_esc'] ?? 0);
        }
        $totaleNetto = round($totaleNetto, 2);

        return view('carrello', [
            'items' => $items,
            'totaleNetto' => $totaleNetto,
            'liccardiVolume' => $carrelloArricchito['liccardi_volume'],
        ]);
    }

    /**
     * Riga carrello grezza (prima di arricchisciItem) da sessione preventivo + servizi checkout.
     *
     * @return array{line: array}|array{error: string}
     */
    private function buildRawCartLineFromPreventivo(Request $request, int $corriereId, ?string $serviziJson): array
    {
        $preventivo = $request->session()->get('preventivo');
        if (! $preventivo || ! isset($preventivo['indirizzi']) || (int) ($preventivo['indirizzi']['corriere_id'] ?? 0) !== $corriereId) {
            return ['error' => 'Completa prima indirizzi e checkout per questo corriere.'];
        }

        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            abort(404);
        }

        $destArr = is_array($preventivo['indirizzi']['destinazione'] ?? null)
            ? $preventivo['indirizzi']['destinazione']
            : [];
        if (PuntoConsegnaSessione::richiestoPerRiga($riga) && ! PuntoConsegnaSessione::destinazioneHaPunto($destArr)) {
            return ['error' => PuntoConsegnaSessione::messaggioSelezionaObbligatorio($riga)];
        }

        $inputPrev = $preventivo['input'] ?? [];
        $inputArr = json_decode(json_encode($inputPrev), true);
        if (! is_array($inputArr)) {
            $inputArr = is_array($inputPrev) ? $inputPrev : [];
        }

        $servizi = json_decode($serviziJson ?? '[]', true);
        if (! is_array($servizi)) {
            $servizi = [];
        }

        $idTipo = (int) ($inputArr['id_tipo_spediziones'] ?? 0);
        $serviziNormalizzati = [];
        foreach ($servizi as $sel) {
            if (! is_array($sel)) {
                continue;
            }
            $pivot = $this->risolviPivotServizio($corriereId, $idTipo, $sel);
            if (! $pivot) {
                continue;
            }
            $vm = null;
            if (ServiziAggiuntiviPrezzoService::rigaSuValoreMerce($pivot)) {
                $vm = RigaCarrelloOrdine::parseNumero($sel['valore_merce'] ?? 0) ?? 0.0;
            }
            $entry = [
                'id' => $pivot->id,
                'valore_merce' => $vm,
            ];
            if (isset($sel['costo_fornitore']) && is_numeric($sel['costo_fornitore'])) {
                $entry['costo_fornitore'] = (float) $sel['costo_fornitore'];
            }
            if (isset($sel['costo_cliente']) && is_numeric($sel['costo_cliente'])) {
                $entry['costo_cliente'] = (float) $sel['costo_cliente'];
            }
            if (! empty($sel['quote_api'])) {
                $entry['quote_api'] = true;
            }
            $serviziNormalizzati[] = $entry;
        }
        $servizi = $serviziNormalizzati;

        $trasporto = (float) ($riga['prezzo_finale'] ?? 0);
        $prezzoBase = isset($riga['prezzo_base']) ? (float) $riga['prezzo_base'] : null;
        $tariffaArr = $riga['tariffa'] ?? null;
        $tariffaId = is_array($tariffaArr) && isset($tariffaArr['id']) ? (int) $tariffaArr['id'] : null;
        $ricaricoTariffa = is_array($tariffaArr) && isset($tariffaArr['ricarico']) && $tariffaArr['ricarico'] !== null
            ? (float) $tariffaArr['ricarico']
            : 0.0;

        $indArr = json_decode(json_encode($preventivo['indirizzi'] ?? []), true);
        if (! is_array($indArr)) {
            $indArr = is_array($preventivo['indirizzi'] ?? null) ? $preventivo['indirizzi'] : [];
        }
        if (PuntoConsegnaSessione::richiestoPerRiga($riga)) {
            $destMerged = PuntoConsegnaSessione::destinazioneConIndirizzoPunto(
                is_array($indArr['destinazione'] ?? null) ? $indArr['destinazione'] : [],
            );
            $indArr['destinazione'] = $destMerged;
            $preventivo['indirizzi']['destinazione'] = $destMerged;
            $request->session()->put('preventivo', $preventivo);
        }
        $destArr = is_array($indArr['destinazione'] ?? null) ? $indArr['destinazione'] : [];
        $nomeD = trim((string) ($destArr['nome'] ?? ''));
        $cognomeD = trim((string) ($destArr['cognome'] ?? ''));
        $nomeLinea = trim($nomeD.' '.$cognomeD);
        if ($nomeLinea === '') {
            $nomeLinea = trim((string) ($destArr['nome_destinatario'] ?? ''));
        }

        $tipoSpedNome = trim((string) data_get($preventivo, 'tipo_spedizione.tipo_spedizione', ''));
        if ($tipoSpedNome === '' && $idTipo > 0) {
            $tipoSpedNome = trim((string) (tipo_spedizone::query()->whereKey($idTipo)->value('tipo_spedizione') ?? ''));
        }

        return [
            'line' => [
                'id' => uniqid('cart_', true),
                'corriere_id' => $corriereId,
                'corriere_nome' => trim((string) ($riga['corriere']['nome_visualizzato'] ?? '')) ?: (string) ($riga['corriere']['nome_corriere'] ?? ''),
                'tipo_spedizione_nome' => $tipoSpedNome,
                'logo_url' => CorriereLogo::pubblico($corriereId),
                'trasporto_iva_esc' => $trasporto,
                'prezzi_esposti' => $preventivo['prezzi_esposti'] ?? null,
                'prezzo_base_trasporto_iva_esc' => $prezzoBase,
                'id_tariffas' => $tariffaId,
                'ricarico_tariffa_pct' => $ricaricoTariffa,
                'servizi_selezionati' => $servizi,
                'indirizzi' => $indArr,
                'preventivo_input' => $inputArr,
                'is_reso' => (bool) ($preventivo['reso'] ?? false),
                'reso_source_spedizione_id' => isset($preventivo['reso_source_spedizione_id']) ? (int) $preventivo['reso_source_spedizione_id'] : null,
                'nome_destinatario_linea' => $nomeLinea,
                'dati_pacco' => [
                    'peso_kg' => isset($inputArr['peso']) ? RigaCarrelloOrdine::parseNumero($inputArr['peso']) : null,
                    'altezza_cm' => isset($inputArr['altezza']) ? RigaCarrelloOrdine::parseNumero($inputArr['altezza']) : null,
                    'larghezza_cm' => isset($inputArr['larghezza']) ? RigaCarrelloOrdine::parseNumero($inputArr['larghezza']) : null,
                    'spessore_cm' => isset($inputArr['spessore']) ? RigaCarrelloOrdine::parseNumero($inputArr['spessore']) : null,
                ],
                'created_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawItems
     */
    private function persistOrdineCreationOnly(Request $request, array $rawItems): ordine
    {
        $mapped = [];
        foreach ($rawItems as $raw) {
            if (! is_array($raw)) {
                continue;
            }
            $mapped[] = RigaCarrelloOrdine::normalizza($raw);
        }
        $carrelloArricchito = $this->arricchisciItemsCarrello($mapped);
        $items = $carrelloArricchito['items'];
        if (count($items) === 0) {
            throw new \InvalidArgumentException('Il carrello non contiene righe valide.');
        }

        $aliquotaIva = parametri_globali::query()
            ->where('denominazione', 'Aliquota IVA')
            ->attivoOggi()
            ->value('valore_percentuale');
        $aliquotaIva = $aliquotaIva !== null ? (float) $aliquotaIva : 22.0;

        $totaliOrdine = OrdineTotaliPagamento::daRighe($items, $aliquotaIva);

        return DB::transaction(function () use ($request, $items, $totaliOrdine, $aliquotaIva, $carrelloArricchito): ordine {
            $ordine = ordine::query()->create([
                'user_id' => $request->user()->id,
                'stato_ordine_id' => ordine::statoId(ordine::STATO_NON_PAGATO),
                'chiave_causale' => ChiaveCausaleOrdine::generaUnica(),
                'costo_servizo' => $totaliOrdine['costo_servizo'],
                'total_pagamento' => $totaliOrdine['total_pagamento'],
                'total_pagamento_wallet' => $totaliOrdine['total_pagamento_wallet'],
                'dettaglio_json' => [
                    'aliquota_iva' => $aliquotaIva,
                    'righe' => $items,
                    'liccardi_volume_sconto' => $carrelloArricchito['liccardi_volume'],
                    'confermato_at' => now()->toIso8601String(),
                ],
            ]);

            foreach ($items as $it) {
                $ind = is_array($it['indirizzi'] ?? null) ? $it['indirizzi'] : [];
                $mittenteRaw = is_array($ind['partenza'] ?? null) ? $ind['partenza'] : [];
                $destinatarioRaw = is_array($ind['destinazione'] ?? null) ? $ind['destinazione'] : [];
                $servizi = $it['servizi_selezionati'] ?? [];
                if (! is_array($servizi)) {
                    $servizi = [];
                }

                $cid = isset($it['corriere_id']) ? (int) $it['corriere_id'] : 0;
                $tid = isset($it['id_tariffas']) ? (int) $it['id_tariffas'] : 0;
                $trow = $tid > 0 ? tariffa::query()->find($tid) : null;
                $crow = $cid > 0 ? corriere::query()->find($cid) : null;

                $costoFornitoreTrasporto = (float) ($it['prezzo_base_trasporto_iva_esc'] ?? 0);
                if ($trow && $crow) {
                    $regioneOrig = null;
                    $regioneDest = null;
                    $capO = trim((string) data_get($it, 'indirizzi.partenza.cap', ''));
                    $capD = trim((string) data_get($it, 'indirizzi.destinazione.cap', ''));
                    if ($capO !== '') {
                        $regioneOrig = comune::query()->where('cap', $capO)->where('attivo', true)->value('regione');
                    }
                    if ($capD !== '') {
                        $regioneDest = comune::query()->where('cap', $capD)->where('attivo', true)->value('regione');
                    }
                    $costoFornitoreTrasporto = TariffaPrezzoBaseService::prezzoBase(
                        $trow,
                        $crow,
                        $regioneOrig ? (string) $regioneOrig : null,
                        $regioneDest ? (string) $regioneDest : null
                    );
                }

                $spedizione = spedizione::query()->create(
                    SpedizioneCampiPersistenza::attributiDaRigaCarrello(
                        $it,
                        $request->user()->id,
                        $ordine->id,
                        $crow,
                        $trow,
                    ),
                );

                $spedizione->refresh();

                $ricaricoTariffaPct = (float) ($it['ricarico_tariffa_pct'] ?? 0);
                if ($ricaricoTariffaPct === 0.0 && $trow && $trow->ricarico !== null) {
                    $ricaricoTariffaPct = (float) $trow->ricarico;
                }
                if ($ricaricoTariffaPct === 0.0 && $crow && ! ($crow->tariffa_interna ?? true)) {
                    $crow->loadMissing('ricarico');
                    $ricaricoTariffaPct = $crow->percentualeRicarico();
                }

                $preventivoSessione = $request->session()->get('preventivo');
                $servizi = $this->arricchisciServiziConQuoteApi(
                    $servizi,
                    $crow,
                    is_array($preventivoSessione) ? $preventivoSessione : [],
                );

                tariffa_spedizione::query()->create(
                    TariffaSpedizioneDaRiga::attributiDaRigaCarrello(
                        $it,
                        $spedizione,
                        $costoFornitoreTrasporto,
                        $servizi,
                        $crow,
                        $aliquotaIva,
                        0,
                    ),
                );

                $this->salvaServiziSpedizione($spedizione, $servizi, $costoFornitoreTrasporto, $ricaricoTariffaPct);
            }

            return $ordine;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawItems
     */
    private function persistOrdineFromItems(Request $request, array $rawItems, bool $clearCarrelloSession, ?string $redirectFragment = null): RedirectResponse
    {
        try {
            $ordine = $this->persistOrdineCreationOnly($request, $rawItems);
        } catch (\InvalidArgumentException) {
            return redirect()
                ->route('carrello.index')
                ->withErrors(['carrello' => 'Il carrello non contiene righe valide.']);
        }

        $r = redirect()
            ->route('ordini.show', $ordine);

        if ($redirectFragment !== null && $redirectFragment !== '') {
            $r->withFragment($redirectFragment);
        }

        if ($clearCarrelloSession) {
            $request->session()->put('carrello', ['items' => []]);
            CarrelloUtente::salvaDaSessione($request);
        }

        return $r;
    }

    /**
     * Crea un ordine con una sola riga dal preventivo in sessione (checkout). Il carrello non viene toccato.
     *
     * @return ordine|RedirectResponse
     */
    public function creaOrdineSingoloDaCheckoutComeOrdine(Request $request): ordine|RedirectResponse
    {
        CarrelloUtente::idrataSessione($request);
        $validated = $request->validate([
            'corriere_id' => ['required', 'integer'],
            'servizi_json' => ['nullable', 'string', 'max:8000'],
            'punto_consegna_json' => ['nullable', 'string', 'max:4000'],
            'to_service_point' => ['nullable', 'integer'],
            'nome_punto' => ['nullable', 'string', 'max:255'],
            'to_post_number' => ['nullable', 'string', 'max:64'],
            'punto_street' => ['nullable', 'string', 'max:160'],
            'punto_house_number' => ['nullable', 'string', 'max:32'],
            'punto_address_line' => ['nullable', 'string', 'max:255'],
            'punto_postal_code' => ['nullable', 'string', 'max:16'],
            'punto_city' => ['nullable', 'string', 'max:120'],
        ]);
        $corriereId = (int) $validated['corriere_id'];

        $errRedirect = $this->sincronizzaPuntoConsegnaPreventivo($request, $corriereId);
        if ($errRedirect !== null) {
            return $errRedirect;
        }

        $built = $this->buildRawCartLineFromPreventivo($request, $corriereId, $validated['servizi_json'] ?? null);
        if (isset($built['error'])) {
            return redirect()
                ->route('checkout.show', ['corriere' => $corriereId])
                ->withErrors(['checkout' => $built['error']]);
        }

        try {
            return $this->persistOrdineCreationOnly($request, [$built['line']]);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('checkout.show', ['corriere' => $corriereId])
                ->withErrors(['checkout' => $e->getMessage()]);
        }
    }

    /**
     * Solo creazione ordine dal checkout (senza pagamento). Il carrello non viene svuotato.
     * Preferire dal checkout i pulsanti «Paga» che creano l’ordine e pagano in un solo passaggio.
     */
    public function creaOrdineSingoloDaCheckout(Request $request): RedirectResponse
    {
        $ordineOrRedirect = $this->creaOrdineSingoloDaCheckoutComeOrdine($request);
        if ($ordineOrRedirect instanceof RedirectResponse) {
            return $ordineOrRedirect;
        }
        $ordine = $ordineOrRedirect;

        return redirect()
            ->route('ordini.pagamento.show', $ordine)
            ->with(
                'ok',
                'Ordine '.$ordine->codice.' creato. Completa il pagamento per confermare l’ordine.'
            );
    }

    public function aggiungi(Request $request)
    {
        CarrelloUtente::idrataSessione($request);
        $validated = $request->validate([
            'corriere_id' => ['required', 'integer'],
            'servizi_json' => ['nullable', 'string', 'max:8000'],
            'punto_consegna_json' => ['nullable', 'string', 'max:4000'],
            'to_service_point' => ['nullable', 'integer'],
            'nome_punto' => ['nullable', 'string', 'max:255'],
            'to_post_number' => ['nullable', 'string', 'max:64'],
            'punto_street' => ['nullable', 'string', 'max:160'],
            'punto_house_number' => ['nullable', 'string', 'max:32'],
            'punto_address_line' => ['nullable', 'string', 'max:255'],
            'punto_postal_code' => ['nullable', 'string', 'max:16'],
            'punto_city' => ['nullable', 'string', 'max:120'],
        ]);

        $corriereId = (int) $validated['corriere_id'];
        $errRedirect = $this->sincronizzaPuntoConsegnaPreventivo($request, $corriereId, 'carrello');
        if ($errRedirect !== null) {
            return $errRedirect;
        }

        $built = $this->buildRawCartLineFromPreventivo($request, $corriereId, $validated['servizi_json'] ?? null);
        if (isset($built['error'])) {
            return redirect()
                ->route('preventivi')
                ->withErrors(['carrello' => $built['error']]);
        }

        $item = $this->arricchisciItem(RigaCarrelloOrdine::normalizza($built['line']));

        $carrello = $request->session()->get('carrello', ['items' => []]);
        $carrello['items'] = $carrello['items'] ?? [];
        $carrello['items'][] = $item;
        $request->session()->put('carrello', $carrello);
        CarrelloUtente::salvaDaSessione($request);

        return redirect()
            ->route('carrello.index')
            ->with('ok', 'Spedizione aggiunta al carrello.');
    }

    public function rimuovi(Request $request)
    {
        CarrelloUtente::idrataSessione($request);
        $validated = $request->validate([
            'item_id' => ['nullable', 'string', 'max:128', 'required_without:item_index'],
            'item_index' => ['nullable', 'integer', 'min:0', 'max:999', 'required_without:item_id'],
        ]);

        $carrello = $request->session()->get('carrello', ['items' => []]);
        $raw = $carrello['items'] ?? [];

        if ($validated['item_id'] !== null && $validated['item_id'] !== '') {
            $targetId = $validated['item_id'];
            $items = array_values(array_filter(
                $raw,
                fn ($it) => is_array($it) && (string) ($it['id'] ?? '') !== $targetId
            ));
        } else {
            $idx = (int) $validated['item_index'];
            if (! isset($raw[$idx]) || ! is_array($raw[$idx])) {
                return redirect()
                    ->route('carrello.index')
                    ->withErrors(['carrello' => 'Riga non trovata.']);
            }
            unset($raw[$idx]);
            $items = array_values($raw);
        }

        $carrello['items'] = $items;
        $request->session()->put('carrello', $carrello);
        CarrelloUtente::salvaDaSessione($request);

        return redirect()
            ->route('carrello.index')
            ->with(
                'ok',
                'Spedizione rimossa dal carrello. I dati di quella spedizione non sono più recuperabili e l’operazione non è annullabile.'
            );
    }

    public function riepilogo(Request $request)
    {
        CarrelloUtente::idrataSessione($request);
        $carrello = $request->session()->get('carrello', ['items' => []]);
        $items = $carrello['items'] ?? [];
        if (count($items) === 0) {
            return redirect()->route('carrello.index')->withErrors(['carrello' => 'Il carrello è vuoto.']);
        }

        $carrelloArricchito = $this->arricchisciItemsCarrello($items);
        $items = $carrelloArricchito['items'];
        $totaleNetto = 0.0;
        foreach ($items as $it) {
            $totaleNetto += (float) ($it['netto_iva_esc'] ?? 0);
        }
        $totaleNetto = round($totaleNetto, 2);

        $totaleTrasportoSolo = 0.0;
        $totaleExtraServizi = 0.0;
        foreach ($items as $it) {
            $totaleTrasportoSolo += (float) ($it['trasporto_iva_esc'] ?? 0);
            $totaleExtraServizi += (float) ($it['extra_servizi_iva_esc'] ?? 0);
        }
        $totaleTrasportoSolo = round($totaleTrasportoSolo, 2);
        $totaleExtraServizi = round($totaleExtraServizi, 2);

        $aliquotaIva = parametri_globali::query()
            ->where('denominazione', 'Aliquota IVA')
            ->attivoOggi()
            ->value('valore_percentuale');
        $aliquotaIva = $aliquotaIva !== null ? (float) $aliquotaIva : 22.0;

        $metodi = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->orderBy('id')
            ->get();

        $metodiJson = $metodi->map(function (metodo_pagamento_ordine $m) {
            return [
                'id' => $m->id,
                'nome' => $m->metodo_pagamento,
                'pct' => (float) $m->commissioni,
                'abs' => 0.0,
            ];
        })->values()->all();

        return view('carrello-riepilogo', [
            'items' => $items,
            'totaleNetto' => $totaleNetto,
            'totaleTrasportoSolo' => $totaleTrasportoSolo,
            'totaleExtraServizi' => $totaleExtraServizi,
            'aliquotaIva' => $aliquotaIva,
            'metodiJson' => $metodiJson,
            'liccardiVolume' => $carrelloArricchito['liccardi_volume'],
        ]);
    }

    public function conferma(Request $request)
    {
        CarrelloUtente::idrataSessione($request);
        $carrello = $request->session()->get('carrello', ['items' => []]);
        $items = $carrello['items'] ?? [];
        if (count($items) === 0) {
            return redirect()->route('carrello.index')->withErrors(['carrello' => 'Il carrello è vuoto.']);
        }

        return $this->persistOrdineFromItems($request, $items, true, null);
    }

    private function sincronizzaPuntoConsegnaPreventivo(
        Request $request,
        int $corriereId,
        string $errRoute = 'checkout',
    ): ?RedirectResponse {
        $preventivo = $request->session()->get('preventivo');
        if (! is_array($preventivo)) {
            return null;
        }

        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            return null;
        }

        $errPunto = PuntoConsegnaSessione::sincronizzaDaRichiesta($preventivo, $riga, $request);
        if ($errPunto !== null) {
            return redirect()
                ->route('checkout.show', ['corriere' => $corriereId])
                ->withErrors([$errRoute === 'carrello' ? 'carrello' : 'checkout' => $errPunto]);
        }

        $request->session()->put('preventivo', $preventivo);

        return null;
    }
}
