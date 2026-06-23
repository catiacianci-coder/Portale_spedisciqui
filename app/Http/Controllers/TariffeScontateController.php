<?php

namespace App\Http\Controllers;

use App\Models\LegalDocumentVersion;
use App\Models\Ticket;
use App\Models\TicketStato;
use App\Models\TicketTipoProblema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TariffeScontateController extends Controller
{
    public function index(Request $request): View
    {
        $versoes = LegalDocumentVersion::query()
            ->where('slug', LegalDocumentVersion::SLUG_TARIFFE_SCONTATE)
            ->whereNotNull('publicado_em')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->get();

        $user = $request->user();
        $user?->loadMissing('anagrafica');
        $anag = $user?->anagrafica;

        return view('tariffe-scontate.index', [
            'versoes' => $versoes,
            'defaults' => [
                'nome_impresa' => trim((string) ($anag->denominazione_ragione_sociale ?? '')),
                'partita_iva' => trim((string) ($anag->partita_iva ?? '')),
                'indirizzo_mittente' => trim(implode(', ', array_filter([
                    trim((string) ($anag->indirizzo ?? '')),
                    trim((string) ($anag->civico ?? '')),
                    trim((string) ($anag->cap ?? '')),
                    trim((string) ($anag->citta ?? '')),
                ]))),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome_impresa' => 'required|string|max:200',
            'partita_iva' => 'required|string|max:20',
            'indirizzo_mittente' => 'required|string|max:500',
            'spedizioni_settimanali' => 'required|integer|min:1|max:100000',
        ], [
            'nome_impresa.required' => 'Indica il nome dell\'impresa.',
            'partita_iva.required' => 'Indica la partita IVA.',
            'indirizzo_mittente.required' => 'Indica l\'indirizzo di mittente.',
            'spedizioni_settimanali.required' => 'Indica il numero di spedizioni settimanali.',
        ]);

        $tipo = TicketTipoProblema::query()
            ->where('codigo', TicketTipoProblema::CODIGO_RICHIESTE_PREMIUM)
            ->first();

        if ($tipo === null) {
            abort(500, 'Tipo ticket «Richieste premium» non configurato.');
        }

        $novoId = TicketStato::idForCodigo(TicketStato::CODIGO_NOVO);
        if ($novoId === null) {
            abort(500, 'Stato «Nuovo» non configurato.');
        }

        $nomeImpresa = trim((string) $validated['nome_impresa']);
        $piva = strtoupper(trim((string) $validated['partita_iva']));
        $indirizzo = trim((string) $validated['indirizzo_mittente']);
        $spedSett = (int) $validated['spedizioni_settimanali'];

        $body = implode("\n", [
            'Richiesta accesso tariffe scontate (premium).',
            '',
            'Nome impresa: '.$nomeImpresa,
            'Partita IVA: '.$piva,
            'Indirizzo di mittente: '.$indirizzo,
            'Spedizioni settimanali: '.$spedSett,
        ]);

        $ticket = DB::transaction(function () use ($request, $tipo, $novoId, $nomeImpresa, $piva, $indirizzo, $spedSett, $body) {
            $t = Ticket::create([
                'user_id' => $request->user()->id,
                'ticket_stato_id' => $novoId,
                'ticket_tipo_problema_id' => $tipo->id,
                'oggetto' => 'Richiesta tariffe scontate',
                'campo_1' => $nomeImpresa,
                'campo_2' => $piva,
                'campo_3' => $indirizzo,
                'campo_4' => (string) $spedSett,
            ]);
            $t->messaggi()->create([
                'user_id' => $request->user()->id,
                'is_staff' => false,
                'body' => $body,
            ]);

            return $t;
        });

        return redirect()
            ->route('assistenza.ticket.show', $ticket)
            ->with('status', 'Richiesta inviata. Il nostro team commerciale la prenderà in carico.');
    }
}
