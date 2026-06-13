<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessaggio;
use App\Models\TicketStato;
use App\Services\Cliente\ClienteNotificazioniRiepilogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketClienteController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('assistenza.index');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorizeCliente($ticket);

        $ultimoMsgId = (int) (TicketMessaggio::query()
            ->where('ticket_id', $ticket->id)
            ->max('id') ?? 0);
        $ticket->forceFill([
            'cliente_ultima_visualizacao_at' => now(),
            'cliente_ultima_messaggio_id_visto' => $ultimoMsgId > 0 ? $ultimoMsgId : null,
        ])->save();
        ClienteNotificazioniRiepilogoService::pulisciCacheUtente((int) Auth::id());

        $ticket->load([
            'stato',
            'tipoProblema',
            'ordine',
            'spedizione',
            'messaggi.user',
        ]);

        return view('assistenza.ticket-show', [
            'ticket' => $ticket,
        ]);
    }

    public function storeMensagem(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeCliente($ticket);

        $ticket->refresh();
        $ticket->load('stato');
        if (! $ticket->clientePodeEnviarNovaMensagem()) {
            return redirect()
                ->route('assistenza.ticket.show', $ticket)
                ->withErrors([
                    'body' => 'Attendi la risposta del team Spedisciqui prima di inviare un altro messaggio.',
                ]);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:65000',
        ]);

        if ($ticket->stato?->codigo === TicketStato::CODIGO_EM_ESPERA) {
            $abertoId = TicketStato::idForCodigo(TicketStato::CODIGO_ABERTO);
            if ($abertoId !== null) {
                $ticket->ticket_stato_id = $abertoId;
                $ticket->save();
            }
        }

        $ticket->messaggi()->create([
            'user_id' => $request->user()->id,
            'is_staff' => false,
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('assistenza.ticket.show', $ticket)
            ->with('status', 'Messaggio inviato.');
    }

    private function authorizeCliente(Ticket $ticket): void
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
