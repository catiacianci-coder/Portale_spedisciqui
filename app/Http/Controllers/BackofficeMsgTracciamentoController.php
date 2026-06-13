<?php

namespace App\Http\Controllers;

use App\Models\corriere;
use App\Models\msg_traccaimento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BackofficeMsgTracciamentoController extends Controller
{
    public function index(Request $request): View
    {
        $testo = trim((string) $request->input('q', ''));
        $corrieriSelezionati = array_values(array_filter(array_map(
            'intval',
            (array) $request->input('corrieri', []),
        )));
        $soloVuoti = $request->boolean('solo_vuoti');

        $query = msg_traccaimento::query()->with('corriere');

        if ($testo !== '') {
            $query->where('msg_ricevuto', 'like', '%'.$testo.'%');
        }

        if ($corrieriSelezionati !== []) {
            $query->whereIn('corriere_id', $corrieriSelezionati);
        }

        if ($soloVuoti) {
            $query->where(function ($builder): void {
                $builder
                    ->whereNull('msg_per_cliente')
                    ->orWhere('msg_per_cliente', '');
            });
        }

        $messaggi = $query
            ->orderBy('msg_ricevuto')
            ->orderBy('corriere_id')
            ->paginate(50)
            ->withQueryString();

        $corrieri = corriere::query()
            ->orderBy('nome_corriere')
            ->orderBy('nome_visualizzato')
            ->orderBy('id')
            ->get();

        return view('backoffice.msg-tracciamento.index', [
            'messaggi' => $messaggi,
            'corrieri' => $corrieri,
            'testo' => $testo,
            'corrieriSelezionati' => $corrieriSelezionati,
            'soloVuoti' => $soloVuoti,
        ]);
    }

    public function create(): View
    {
        return view('backoffice.msg-tracciamento.create', [
            'corrieri' => $this->corrieriOrdinati(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validaRecord($request);

        msg_traccaimento::query()->create($validated);

        return redirect()
            ->route('backoffice.utilities.msg_tracciamento.index')
            ->with('ok', 'Messaggio tracking creato.');
    }

    public function edit(msg_traccaimento $msg_traccaimento): View
    {
        $msg_traccaimento->load('corriere');

        return view('backoffice.msg-tracciamento.edit', [
            'record' => $msg_traccaimento,
            'corrieri' => $this->corrieriOrdinati(),
        ]);
    }

    public function update(Request $request, msg_traccaimento $msg_traccaimento): RedirectResponse
    {
        $validated = $this->validaRecord($request, $msg_traccaimento->id);

        $msg_traccaimento->update($validated);

        return redirect()
            ->route('backoffice.utilities.msg_tracciamento.index', $request->only(['q', 'corrieri', 'solo_vuoti', 'page']))
            ->with('ok', 'Messaggio tracking aggiornato.');
    }

    public function destroy(Request $request, msg_traccaimento $msg_traccaimento): RedirectResponse
    {
        $msg_traccaimento->delete();

        return redirect()
            ->route('backoffice.utilities.msg_tracciamento.index', $request->only(['q', 'corrieri', 'solo_vuoti', 'page']))
            ->with('ok', 'Messaggio tracking eliminato.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('msg_traccaimentos', 'id')],
            'msg_per_cliente' => ['required', 'string', 'max:500'],
        ]);

        $msgPerCliente = trim((string) $validated['msg_per_cliente']);

        msg_traccaimento::query()
            ->whereIn('id', $validated['ids'])
            ->update(['msg_per_cliente' => $msgPerCliente]);

        return redirect()
            ->route('backoffice.utilities.msg_tracciamento.index', $request->only(['q', 'corrieri', 'solo_vuoti', 'page']))
            ->with('ok', 'Aggiornati '.count($validated['ids']).' messaggi per il cliente.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, corriere>
     */
    private function corrieriOrdinati()
    {
        return corriere::query()
            ->orderBy('nome_corriere')
            ->orderBy('nome_visualizzato')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{corriere_id: int, msg_ricevuto: string, msg_per_cliente: string|null}
     */
    private function validaRecord(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'corriere_id' => ['required', 'integer', Rule::exists('corrieres', 'id')],
            'msg_ricevuto' => [
                'required',
                'string',
                'max:500',
                Rule::unique('msg_traccaimentos', 'msg_ricevuto')
                    ->where(fn ($query) => $query->where('corriere_id', (int) $request->input('corriere_id')))
                    ->ignore($ignoreId),
            ],
            'msg_per_cliente' => ['nullable', 'string', 'max:500'],
        ]);

        $msgPerCliente = trim((string) ($validated['msg_per_cliente'] ?? ''));

        return [
            'corriere_id' => (int) $validated['corriere_id'],
            'msg_ricevuto' => trim((string) $validated['msg_ricevuto']),
            'msg_per_cliente' => $msgPerCliente !== '' ? $msgPerCliente : null,
        ];
    }
}
