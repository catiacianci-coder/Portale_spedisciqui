<?php

namespace App\Http\Controllers;

use App\Models\comune;
use App\Models\destinatario;
use App\Services\UserMittenzeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserDestinatarioController extends Controller
{
    public function __construct(
        private UserMittenzeService $capService,
    ) {}

    public function index(Request $request)
    {
        $rows = $request->user()->destinatari()
            ->orderBy('cognome')
            ->orderBy('nome')
            ->orderBy('id')
            ->get();

        return view('destinatari.index', ['destinatari' => $rows]);
    }

    public function create(Request $request)
    {
        return view('destinatari.create', [
            'tipoUtente' => (string) ($request->user()->tipo_utente ?? 'privato'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedDestinatario($request);
        $request->user()->destinatari()->create($validated);

        return redirect()->route('destinatari.index')->with('ok', 'Destinatario salvato.');
    }

    public function edit(Request $request, destinatario $destinatario)
    {
        $this->authorizeDestinatario($request, $destinatario);

        return view('destinatari.edit', [
            'destinatario' => $destinatario,
            'idComuneCorrente' => $destinatario->id_comune,
            'tipoUtente' => (string) ($request->user()->tipo_utente ?? 'privato'),
        ]);
    }

    public function update(Request $request, destinatario $destinatario): RedirectResponse
    {
        $this->authorizeDestinatario($request, $destinatario);
        $validated = $this->validatedDestinatario($request);
        $destinatario->update($validated);

        return redirect()->route('destinatari.index')->with('ok', 'Destinatario aggiornato.');
    }

    public function destroy(Request $request, destinatario $destinatario): RedirectResponse
    {
        $this->authorizeDestinatario($request, $destinatario);
        $destinatario->delete();

        return redirect()->route('destinatari.index')->with('ok', 'Destinatario eliminato.');
    }

    public function duplica(Request $request, destinatario $destinatario): RedirectResponse
    {
        $this->authorizeDestinatario($request, $destinatario);
        $copy = $destinatario->replicate();
        $copy->save();

        return redirect()
            ->route('destinatari.edit', $copy)
            ->with('ok', 'Copia creata: completa o modifica i dati e salva.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedDestinatario(Request $request): array
    {
        $rules = [
            'destinatario_anagrafica' => ['required', 'in:privato,azienda'],
            'nome' => ['required', 'string', 'max:255'],
            'cognome' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'cap' => ['required', 'string', 'max:10'],
            'citta' => ['required', 'string', 'max:255'],
            'provincia' => ['required', 'string', 'size:2'],
            'indirizzo' => ['required', 'string', 'max:255'],
            'civico' => ['required', 'string', 'max:10'],
            'id_comune' => ['required', 'integer', 'exists:comuni,id'],
            'denominazione_ragione_sociale' => ['nullable', 'string', 'max:255'],
            'varie1' => ['nullable', 'string', 'max:255'],
            'varie2' => ['nullable', 'string', 'max:255'],
            'varie3' => ['nullable', 'string', 'max:255'],
            'varie4' => ['nullable', 'string', 'max:255'],
        ];

        $validated = $request->validate($rules);

        $cap = $this->capService->normalizzaCap($validated['cap']);
        if (strlen($cap) !== 5) {
            throw ValidationException::withMessages(['cap' => 'Il CAP deve essere di 5 cifre.']);
        }

        $comune = comune::query()->findOrFail((int) $validated['id_comune']);
        $this->assertComuneAllineato($comune, $cap, $validated['citta'], $validated['provincia']);

        $out = [
            'nome' => trim($validated['nome']),
            'cognome' => trim($validated['cognome']),
            'telefono' => trim($validated['telefono']),
            'email' => mb_strtolower(trim($validated['email'])),
            'indirizzo' => trim($validated['indirizzo']),
            'civico' => trim($validated['civico']),
            'cap' => $cap,
            'citta' => trim($validated['citta']),
            'provincia' => strtoupper(substr(trim($validated['provincia']), 0, 2)),
            'id_comune' => (int) $validated['id_comune'],
        ];

        foreach (['varie1', 'varie2', 'varie3', 'varie4'] as $campoVarie) {
            if ($request->has($campoVarie)) {
                $out[$campoVarie] = trim((string) ($validated[$campoVarie] ?? '')) ?: null;
            }
        }

        $isDestAzienda = ($validated['destinatario_anagrafica'] ?? 'privato') === 'azienda';
        if ($isDestAzienda) {
            $denom = trim((string) ($validated['denominazione_ragione_sociale'] ?? ''));
            if ($denom === '') {
                throw ValidationException::withMessages([
                    'denominazione_ragione_sociale' => 'Indica il nome impresa per un destinatario azienda.',
                ]);
            }
            $out['denominazione_ragione_sociale'] = $denom;
        } else {
            $out['denominazione_ragione_sociale'] = null;
        }

        return $out;
    }

    private function assertComuneAllineato(comune $comune, string $capNorm, string $cittaInput, string $provInput): void
    {
        $capDb = str_pad((string) $comune->cap, 5, '0', STR_PAD_LEFT);
        $prov = strtoupper(substr(trim($provInput), 0, 2));
        $provDb = strtoupper(substr(trim((string) $comune->provincia), 0, 2));
        $cittaNorm = mb_strtolower(trim($cittaInput));
        $comuneNorm = mb_strtolower(trim((string) $comune->comune));

        if ($capDb !== $capNorm || $provDb !== $prov || $comuneNorm !== $cittaNorm) {
            throw ValidationException::withMessages([
                'id_comune' => 'CAP, città e provincia devono coincidere con il comune scelto dall’autocomplete.',
            ]);
        }
    }

    private function authorizeDestinatario(Request $request, destinatario $destinatario): void
    {
        abort_if((int) $destinatario->user_id !== (int) $request->user()->id, 403);
    }
}
