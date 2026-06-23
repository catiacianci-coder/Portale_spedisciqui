<?php

namespace App\Http\Controllers;

use App\Models\ordine;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Models\Ticket;
use App\Models\TicketStato;
use App\Models\TicketTipoProblema;
use App\Support\CodiceOrdine;
use App\Support\FiltriTabella;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssistenzaSolicitacaoController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = FiltriTabella::perPage($request);

        $tickets = Ticket::query()
            ->where('user_id', auth()->id())
            ->with('stato')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $tiposProblema = TicketTipoProblema::query()
            ->where('codigo', '!=', TicketTipoProblema::CODIGO_RICHIESTE_PREMIUM)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $user = $request->user();
        $user->loadMissing('anagrafica');
        $anag = $user->anagrafica;
        $nomeCognomeDefault = trim(implode(' ', array_filter([
            trim((string) ($anag->nome ?? '')),
            trim((string) ($anag->cognome ?? '')),
        ])));
        if ($nomeCognomeDefault === '') {
            $nomeCognomeDefault = trim((string) ($user->name ?? ''));
        }

        return view('assistenza.index', [
            'tickets' => $tickets,
            'perPage' => $perPage,
            'tiposProblema' => $tiposProblema,
            'tipoEntregaId' => $this->tipoId(TicketTipoProblema::CODIGO_ENTREGA),
            'tipoEtiquetaNaoGeradaId' => $this->tipoId(TicketTipoProblema::CODIGO_ETIQUETA_NAO_GERADA),
            'tipoFatturaMancanteId' => $this->tipoId(TicketTipoProblema::CODIGO_FATTURA_MANCANTE),
            'tipoTrackingId' => $this->tipoId(TicketTipoProblema::CODIGO_TRACKING),
            'tipoRiprenotazioneId' => $this->tipoId(TicketTipoProblema::CODIGO_RIPRENOTAZIONE_RITIRO),
            'tipoCommercialeId' => $this->tipoId(TicketTipoProblema::CODIGO_COMMERCIALE),
            'anniFattura' => range((int) date('Y'), (int) date('Y') - 5),
            'commercialeDefaults' => [
                'nome_cognome' => $nomeCognomeDefault,
                'nome_impresa' => trim((string) ($anag->denominazione_ragione_sociale ?? '')),
                'partita_iva' => trim((string) ($anag->partita_iva ?? '')),
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('assistenza.index', [], 302);
    }

    public function corrieriCliente(): JsonResponse
    {
        $corrieri = spedizione::query()
            ->where('user_id', auth()->id())
            ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
            ->where('spedizione_stato_id', '!=', stato_spedizione::ANNULLATA)
            ->whereNotNull('corriere')
            ->where('corriere', '!=', '')
            ->distinct()
            ->orderBy('corriere')
            ->pluck('corriere')
            ->values()
            ->all();

        return response()->json(['corrieri' => $corrieri]);
    }

    public function spedizioniPorPedido(Request $request): JsonResponse
    {
        $modo = (string) $request->query('modo', '');
        $modosValidi = ['entrega', 'etiqueta_nao_gerada', 'tracking', 'riprenotazione_ritiro'];
        if (! in_array($modo, $modosValidi, true)) {
            return response()->json(['error' => 'Modalità non valida.'], 422);
        }

        $codice = trim((string) $request->query('ordine_codice', ''));
        if ($codice === '') {
            return response()->json(['error' => 'Indica il numero ordine.'], 422);
        }

        $ordine = $this->resolveOrdinePagoDoCliente($codice);
        if ($ordine === null) {
            return response()->json(['error' => 'Ordine non trovato o non pagato.'], 404);
        }

        $q = spedizione::query()
            ->where('user_id', auth()->id())
            ->where('ordine_id', $ordine->id)
            ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
            ->where('spedizione_stato_id', '!=', stato_spedizione::ANNULLATA);

        if ($modo === 'entrega') {
            $q->where('spedizione_stato_id', stato_spedizione::PAGATA);
        }

        if ($modo === 'etiqueta_nao_gerada') {
            $q->where('spedizione_stato_id', stato_spedizione::PAGATA)->semRastreio();
        }

        if ($modo === 'riprenotazione_ritiro') {
            $corriere = trim((string) $request->query('corriere', ''));
            if ($corriere === '') {
                return response()->json(['error' => 'Seleziona un corriere.'], 422);
            }
            $q->where('corriere', $corriere);
            $this->applicaFiltroEtichettaGenerata($q);
        }

        $spedizioni = $q->orderBy('id')->get()->map(fn (spedizione $s) => $this->serializeSpedizione($s));

        return response()->json([
            'ordine' => ['id' => $ordine->id],
            'spedizioni' => $spedizioni,
        ]);
    }

    public function spedizionePorCodigo(Request $request): JsonResponse
    {
        $modo = (string) $request->query('modo', 'etiqueta_nao_gerada');
        $codigo = strtoupper(trim(preg_replace('/\s+/', '', (string) $request->query('codigo', ''))));
        if ($codigo === '') {
            return response()->json(['error' => 'Indica il codice spedizione.'], 422);
        }

        $q = spedizione::query()
            ->where('user_id', auth()->id())
            ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
            ->where('spedizione_stato_id', '!=', stato_spedizione::ANNULLATA)
            ->where('codice_interno', $codigo);

        if ($modo === 'etiqueta_nao_gerada') {
            $q->where('spedizione_stato_id', stato_spedizione::PAGATA)->semRastreio();
        }

        $spedizione = $q->with('ordine')->first();

        if ($spedizione === null) {
            $msg = $modo === 'tracking'
                ? 'Spedizione non trovata o non valida.'
                : 'Spedizione non trovata, non pagata o con tracking già presente.';

            return response()->json(['error' => $msg], 404);
        }

        return response()->json([
            'spedizione' => $this->serializeSpedizione($spedizione),
            'ordine' => ['id' => $spedizione->ordine_id],
        ]);
    }

    public function spedizionePorTracking(Request $request): JsonResponse
    {
        $tracking = trim((string) $request->query('tracking', ''));
        if ($tracking === '') {
            return response()->json(['error' => 'Indica il numero di tracking.'], 422);
        }

        $spedizione = spedizione::query()
            ->where('user_id', auth()->id())
            ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
            ->where('spedizione_stato_id', '!=', stato_spedizione::ANNULLATA)
            ->where('tracking', $tracking)
            ->with('ordine')
            ->first();

        if ($spedizione === null) {
            return response()->json(['error' => 'Nessuna spedizione trovata con questo tracking.'], 404);
        }

        return response()->json([
            'spedizione' => $this->serializeSpedizione($spedizione),
            'ordine' => ['id' => $spedizione->ordine_id],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $base = $request->validate([
            'ticket_tipo_problema_id' => ['required', 'integer', Rule::exists('ticket_tipo_problemas', 'id')],
            'oggetto' => 'required|string|max:500',
            'body' => 'required|string|max:65000',
        ]);

        $tipo = TicketTipoProblema::query()->findOrFail((int) $base['ticket_tipo_problema_id']);

        $novoId = TicketStato::idForCodigo(TicketStato::CODIGO_NOVO);
        if ($novoId === null) {
            abort(500, 'Stato «Nuovo» non configurato.');
        }

        $payload = [
            'user_id' => $request->user()->id,
            'ticket_stato_id' => $novoId,
            'ticket_tipo_problema_id' => $tipo->id,
            'oggetto' => $base['oggetto'],
            'ordine_id' => null,
            'spedizione_id' => null,
            'campo_1' => null,
            'campo_2' => null,
            'campo_3' => null,
            'campo_4' => null,
        ];

        match ($tipo->codigo) {
            TicketTipoProblema::CODIGO_ENTREGA => $this->payloadEntrega($request, $payload),
            TicketTipoProblema::CODIGO_ETIQUETA_NAO_GERADA => $this->payloadEtiquetaNaoGerada($request, $payload),
            TicketTipoProblema::CODIGO_FATTURA_MANCANTE => $this->payloadFatturaMancante($request, $payload),
            TicketTipoProblema::CODIGO_TRACKING => $this->payloadTracking($request, $payload),
            TicketTipoProblema::CODIGO_RIPRENOTAZIONE_RITIRO => $this->payloadRiprenotazione($request, $payload),
            TicketTipoProblema::CODIGO_COMMERCIALE => $this->payloadCommerciale($request, $payload),
            default => null,
        };

        $ticket = DB::transaction(function () use ($payload, $request) {
            $t = Ticket::create($payload);
            $t->messaggi()->create([
                'user_id' => $request->user()->id,
                'is_staff' => false,
                'body' => $request->input('body'),
            ]);

            return $t;
        });

        return redirect()
            ->route('assistenza.ticket.show', $ticket)
            ->with('status', 'Richiesta registrata. Il nostro team la prenderà in carico.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadEntrega(Request $request, array &$payload): void
    {
        $extra = $request->validate([
            'ordine_codice_entrega' => 'required|string|max:64',
            'spedizione_id_entrega' => ['required', 'integer'],
        ]);
        $ordine = $this->resolveOrdinePagoDoCliente($extra['ordine_codice_entrega']);
        if ($ordine === null) {
            throw ValidationException::withMessages(['ordine_codice_entrega' => 'Ordine non trovato o non pagato.']);
        }
        $spedizione = spedizione::query()
            ->where('user_id', auth()->id())
            ->where('id', (int) $extra['spedizione_id_entrega'])
            ->pagasNoPedido($ordine->id)
            ->first();
        if ($spedizione === null) {
            throw ValidationException::withMessages(['spedizione_id_entrega' => 'Etichetta non valida per questo ordine.']);
        }
        $payload['ordine_id'] = $ordine->id;
        $payload['spedizione_id'] = $spedizione->id;
        $payload['campo_1'] = (string) $ordine->id;
        $payload['campo_2'] = (string) $spedizione->id;
        $payload['campo_3'] = (string) $spedizione->corriere;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadEtiquetaNaoGerada(Request $request, array &$payload): void
    {
        $extra = $request->validate([
            'etiqueta_ng_modo' => ['required', Rule::in(['pedido', 'codigo'])],
            'ordine_codice_ng' => 'nullable|string|max:64',
            'codigo_remessa_ng' => 'nullable|string|max:64',
            'spedizione_ids_ng' => 'nullable|array',
            'spedizione_ids_ng.*' => 'integer',
        ]);
        if ($extra['etiqueta_ng_modo'] === 'pedido') {
            $ordineCod = trim((string) ($extra['ordine_codice_ng'] ?? ''));
            if ($ordineCod === '') {
                throw ValidationException::withMessages(['ordine_codice_ng' => 'Indica l\'ordine.']);
            }
            $ordine = $this->resolveOrdinePagoDoCliente($ordineCod);
            if ($ordine === null) {
                throw ValidationException::withMessages(['ordine_codice_ng' => 'Ordine non trovato o non pagato.']);
            }
            $ids = array_values(array_unique(array_map('intval', $extra['spedizione_ids_ng'] ?? [])));
            if ($ids === []) {
                throw ValidationException::withMessages(['spedizione_ids_ng' => 'Seleziona almeno un\'etichetta.']);
            }
            $count = spedizione::query()
                ->where('user_id', auth()->id())
                ->whereIn('id', $ids)
                ->pagasNoPedido($ordine->id)
                ->semRastreio()
                ->count();
            if ($count !== count($ids)) {
                throw ValidationException::withMessages(['spedizione_ids_ng' => 'Una o più etichette non sono valide (devono essere pagate e senza tracking).']);
            }
            $payload['ordine_id'] = $ordine->id;
            $payload['campo_1'] = implode(',', $ids);
        } else {
            $cod = strtoupper(trim(preg_replace('/\s+/', '', (string) ($extra['codigo_remessa_ng'] ?? ''))));
            if ($cod === '') {
                throw ValidationException::withMessages(['codigo_remessa_ng' => 'Indica il codice spedizione.']);
            }
            $spedizione = $this->findSpedizionePerCodigoInterno($cod, 'etiqueta_nao_gerada');
            if ($spedizione === null) {
                throw ValidationException::withMessages(['codigo_remessa_ng' => 'Spedizione non trovata o con tracking già presente.']);
            }
            $payload['ordine_id'] = $spedizione->ordine_id;
            $payload['spedizione_id'] = $spedizione->id;
            $payload['campo_1'] = (string) $spedizione->id;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadFatturaMancante(Request $request, array &$payload): void
    {
        $extra = $request->validate([
            'fattura_mese' => 'required|integer|min:1|max:12',
            'fattura_anno' => 'required|integer|min:2000|max:2100',
        ]);
        $payload['campo_1'] = str_pad((string) $extra['fattura_mese'], 2, '0', STR_PAD_LEFT);
        $payload['campo_2'] = (string) $extra['fattura_anno'];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadTracking(Request $request, array &$payload): void
    {
        $extra = $request->validate([
            'tracking_modo' => ['required', Rule::in(['ordine', 'codice_interno', 'tracking'])],
            'ordine_codice_tracking' => 'nullable|string|max:64',
            'codigo_remessa_tracking' => 'nullable|string|max:64',
            'numero_tracking' => 'nullable|string|max:128',
            'spedizione_ids_tracking' => 'nullable|array',
            'spedizione_ids_tracking.*' => 'integer',
        ]);

        $payload['campo_2'] = $extra['tracking_modo'];

        if ($extra['tracking_modo'] === 'ordine') {
            $ordineCod = trim((string) ($extra['ordine_codice_tracking'] ?? ''));
            if ($ordineCod === '') {
                throw ValidationException::withMessages(['ordine_codice_tracking' => 'Indica l\'ordine.']);
            }
            $ordine = $this->resolveOrdinePagoDoCliente($ordineCod);
            if ($ordine === null) {
                throw ValidationException::withMessages(['ordine_codice_tracking' => 'Ordine non trovato o non pagato.']);
            }
            $ids = array_values(array_unique(array_map('intval', $extra['spedizione_ids_tracking'] ?? [])));
            if ($ids === []) {
                throw ValidationException::withMessages(['spedizione_ids_tracking' => 'Seleziona almeno un\'etichetta.']);
            }
            $count = spedizione::query()
                ->where('user_id', auth()->id())
                ->whereIn('id', $ids)
                ->where('ordine_id', $ordine->id)
                ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
                ->where('spedizione_stato_id', '!=', stato_spedizione::ANNULLATA)
                ->count();
            if ($count !== count($ids)) {
                throw ValidationException::withMessages(['spedizione_ids_tracking' => 'Una o più etichette non sono valide per questo ordine.']);
            }
            $payload['ordine_id'] = $ordine->id;
            $payload['spedizione_id'] = $ids[0];
            $payload['campo_1'] = implode(',', $ids);
        } elseif ($extra['tracking_modo'] === 'codice_interno') {
            $cod = strtoupper(trim(preg_replace('/\s+/', '', (string) ($extra['codigo_remessa_tracking'] ?? ''))));
            if ($cod === '') {
                throw ValidationException::withMessages(['codigo_remessa_tracking' => 'Indica il codice spedizione.']);
            }
            $spedizione = $this->findSpedizionePerCodigoInterno($cod, 'tracking');
            if ($spedizione === null) {
                throw ValidationException::withMessages(['codigo_remessa_tracking' => 'Spedizione non trovata.']);
            }
            $payload['ordine_id'] = $spedizione->ordine_id;
            $payload['spedizione_id'] = $spedizione->id;
            $payload['campo_1'] = (string) $spedizione->id;
            $payload['campo_3'] = $cod;
        } else {
            $tracking = trim((string) ($extra['numero_tracking'] ?? ''));
            if ($tracking === '') {
                throw ValidationException::withMessages(['numero_tracking' => 'Indica il numero di tracking.']);
            }
            $spedizione = spedizione::query()
                ->where('user_id', auth()->id())
                ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
                ->where('spedizione_stato_id', '!=', stato_spedizione::ANNULLATA)
                ->where('tracking', $tracking)
                ->first();
            if ($spedizione === null) {
                throw ValidationException::withMessages(['numero_tracking' => 'Nessuna spedizione trovata con questo tracking.']);
            }
            $payload['ordine_id'] = $spedizione->ordine_id;
            $payload['spedizione_id'] = $spedizione->id;
            $payload['campo_1'] = (string) $spedizione->id;
            $payload['campo_3'] = $tracking;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadRiprenotazione(Request $request, array &$payload): void
    {
        $extra = $request->validate([
            'riprenot_corriere' => 'required|string|max:120',
            'ordine_codice_riprenot' => 'required|string|max:64',
            'spedizione_ids_riprenot' => 'required|array|min:1',
            'spedizione_ids_riprenot.*' => 'integer',
        ]);

        $ordine = $this->resolveOrdinePagoDoCliente($extra['ordine_codice_riprenot']);
        if ($ordine === null) {
            throw ValidationException::withMessages(['ordine_codice_riprenot' => 'Ordine non trovato o non pagato.']);
        }

        $corriere = trim((string) $extra['riprenot_corriere']);
        $ids = array_values(array_unique(array_map('intval', $extra['spedizione_ids_riprenot'])));

        $q = spedizione::query()
            ->where('user_id', auth()->id())
            ->whereIn('id', $ids)
            ->where('ordine_id', $ordine->id)
            ->where('corriere', $corriere)
            ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
            ->where('spedizione_stato_id', '!=', stato_spedizione::ANNULLATA);
        $this->applicaFiltroEtichettaGenerata($q);

        if ($q->count() !== count($ids)) {
            throw ValidationException::withMessages(['spedizione_ids_riprenot' => 'Seleziona etichette generate valide per corriere e ordine indicati.']);
        }

        $payload['ordine_id'] = $ordine->id;
        $payload['spedizione_id'] = $ids[0];
        $payload['campo_1'] = implode(',', $ids);
        $payload['campo_3'] = $corriere;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadCommerciale(Request $request, array &$payload): void
    {
        $extra = $request->validate([
            'commerciale_nome_cognome' => 'required|string|max:200',
            'commerciale_nome_impresa' => 'required|string|max:200',
            'commerciale_partita_iva' => 'required|string|max:20',
        ]);

        $payload['campo_1'] = trim((string) $extra['commerciale_nome_cognome']);
        $payload['campo_2'] = trim((string) $extra['commerciale_nome_impresa']);
        $payload['campo_3'] = strtoupper(trim((string) $extra['commerciale_partita_iva']));
    }

    private function findSpedizionePerCodigoInterno(string $codigo, string $modo): ?spedizione
    {
        $q = spedizione::query()
            ->where('user_id', auth()->id())
            ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
            ->where('spedizione_stato_id', '!=', stato_spedizione::ANNULLATA)
            ->where('codice_interno', $codigo);

        if ($modo === 'etiqueta_nao_gerada') {
            $q->where('spedizione_stato_id', stato_spedizione::PAGATA)->semRastreio();
        }

        return $q->first();
    }

    /** @param  Builder<spedizione>  $q */
    private function applicaFiltroEtichettaGenerata(Builder $q): void
    {
        $q->where(function (Builder $w): void {
            $w->where(function (Builder $x): void {
                $x->whereNotNull('tracking')->where('tracking', '!=', '');
            })
                ->orWhereNotNull('ldv_emessa_il')
                ->orWhere(function (Builder $x): void {
                    $x->whereNotNull('etiqueta_pdf_path')->where('etiqueta_pdf_path', '!=', '');
                })
                ->orWhere('esiste_integrazione', true)
                ->orWhereIn('spedizione_stato_id', [stato_spedizione::GENERATA, stato_spedizione::PAGATA]);
        });
    }

    private function resolveOrdinePagoDoCliente(string $codice): ?ordine
    {
        $codice = trim($codice);
        if ($codice === '') {
            return null;
        }

        $id = CodiceOrdine::idDaRiferimento($codice);
        if ($id === null) {
            return null;
        }

        return ordine::query()
            ->where('user_id', auth()->id())
            ->conStatoCodice(ordine::STATO_PAGATO)
            ->whereKey($id)
            ->first();
    }

    private function tipoId(string $codigo): ?int
    {
        $id = TicketTipoProblema::query()->where('codigo', $codigo)->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSpedizione(spedizione $s): array
    {
        $rastreio = $s->codigoRastreio() ?? '';

        return [
            'id' => $s->id,
            'codice_interno' => $s->codice_interno,
            'carrier' => $s->corriere,
            'service_description' => $s->service_description,
            'codigo_rastreio' => $rastreio !== '' ? $rastreio : null,
            'tem_rastreio' => $rastreio !== '',
            'destinatario' => $s->ragione_sociale_d
                ? trim((string) $s->ragione_sociale_d)
                : trim(implode(' ', array_filter([(string) $s->nome_d, (string) $s->sobrenome_d]))),
        ];
    }
}
