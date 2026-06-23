<?php

namespace App\Http\Controllers;

use App\Models\spedizione;
use App\Models\Ticket;
use App\Models\TicketStato;
use App\Support\FiltriTabella;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BackofficeTicketController extends Controller
{
    public function index(Request $request): View
    {
        $stati = TicketStato::query()->orderBy('sort_order')->get();
        $tipoPremium = \App\Models\TicketTipoProblema::query()
            ->where('codigo', \App\Models\TicketTipoProblema::CODIGO_RICHIESTE_PREMIUM)
            ->first();

        $counts = [];
        foreach ($stati as $stato) {
            $counts[$stato->codigo] = Ticket::query()->where('ticket_stato_id', $stato->id)->count();
        }

        $countPremium = $tipoPremium
            ? Ticket::query()->where('ticket_tipo_problema_id', $tipoPremium->id)->count()
            : 0;

        $codiciValidi = $stati->pluck('codigo')->all();
        $filter = $request->query('stato');
        $filterTipo = $request->query('tipo');
        if ($filter !== null && $filter !== '' && ! in_array($filter, $codiciValidi, true)) {
            $filter = null;
        }
        if ($filterTipo !== null && $filterTipo !== '' && $filterTipo !== 'richieste_premium') {
            $filterTipo = null;
        }

        $query = Ticket::query()
            ->with(['user', 'stato', 'tipoProblema'])
            ->orderByDesc('created_at');

        if ($filterTipo === 'richieste_premium' && $tipoPremium) {
            $query->where('ticket_tipo_problema_id', $tipoPremium->id);
        } elseif ($filter !== null && $filter !== '') {
            $query->whereHas('stato', fn ($q) => $q->where('codigo', $filter));
        }

        $perPage = FiltriTabella::perPage($request);
        $tickets = $query->paginate($perPage)->withQueryString();

        return view('backoffice.tickets.index', [
            'stati' => $stati,
            'counts' => $counts,
            'countPremium' => $countPremium,
            'filter' => $filter,
            'filterTipo' => $filterTipo,
            'perPage' => $perPage,
            'tickets' => $tickets,
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'user',
            'stato',
            'tipoProblema',
            'ordine',
            'spedizione',
            'messaggi.user',
        ]);

        $statiRisposta = TicketStato::query()
            ->whereIn('codigo', [
                TicketStato::CODIGO_ABERTO,
                TicketStato::CODIGO_EM_ESPERA,
                TicketStato::CODIGO_EM_TRATAMENTO,
                TicketStato::CODIGO_RESOLVIDO,
            ])
            ->orderBy('sort_order')
            ->get();

        $tuttiStati = TicketStato::query()->orderBy('sort_order')->get();

        $idsRif = $ticket->referencedSpedizioneIds();
        $spedizioniRiferimento = collect();
        if ($idsRif !== []) {
            $byId = spedizione::query()->whereIn('id', $idsRif)->get()->keyBy('id');
            foreach ($idsRif as $id) {
                if ($byId->has($id)) {
                    $spedizioniRiferimento->push($byId->get($id));
                }
            }
        }

        return view('backoffice.tickets.show', [
            'ticket' => $ticket,
            'statiRisposta' => $statiRisposta,
            'tuttiStati' => $tuttiStati,
            'spedizioniRiferimento' => $spedizioniRiferimento,
        ]);
    }

    public function storeMessaggio(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:65000',
            'ticket_stato_id' => ['required', 'integer', Rule::exists('ticket_stati', 'id')],
        ]);

        $nuovoStato = TicketStato::query()->findOrFail((int) $validated['ticket_stato_id']);
        if ($nuovoStato->codigo === TicketStato::CODIGO_NOVO) {
            throw ValidationException::withMessages([
                'ticket_stato_id' => 'Dopo una risposta lo stato non può tornare a «Nuovo».',
            ]);
        }

        $ticket->messaggi()->create([
            'user_id' => $request->user()->id,
            'is_staff' => true,
            'body' => $validated['body'],
        ]);

        $ticket->update([
            'ticket_stato_id' => $nuovoStato->id,
        ]);

        return redirect()
            ->route('backoffice.tickets.show', $ticket)
            ->with('status', 'Risposta inviata e stato aggiornato.');
    }

    public function updateStato(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_stato_id' => ['required', 'integer', Rule::exists('ticket_stati', 'id')],
        ]);

        $ticket->update([
            'ticket_stato_id' => (int) $validated['ticket_stato_id'],
        ]);

        return redirect()
            ->route('backoffice.tickets.show', $ticket)
            ->with('status', 'Stato del ticket aggiornato.');
    }
}
