<?php

namespace App\Http\Controllers;

use App\Models\comune;
use App\Models\mittenza;
use App\Services\UserMittenzeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserMittenzaController extends Controller
{
    public function __construct(
        private UserMittenzeService $mittenzeService,
    ) {}

    public function index(Request $request)
    {
        $this->mittenzeService->ensureForUser($request->user());

        $mittenti = $request->user()->mittenze()
            ->orderByDesc('is_preferito')
            ->orderByDesc('is_fatturazione')
            ->orderBy('citta')
            ->orderBy('id')
            ->get();

        return view('mittenze.index', ['mittenti' => $mittenti]);
    }

    public function create(Request $request)
    {
        return view('mittenze.create', [
            'tipoUtente' => (string) ($request->user()->tipo_utente ?? 'privato'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedMittenza($request);
        $request->user()->mittenze()->create(array_merge($validated, [
            'is_preferito' => false,
            'is_fatturazione' => false,
        ]));

        return redirect()->route('mittenze.index')->with('ok', 'Mittente salvato.');
    }

    public function edit(Request $request, mittenza $mittenza)
    {
        $this->authorizeMittenza($request, $mittenza);
        if ($mittenza->is_fatturazione) {
            return redirect()
                ->route('mittenze.index')
                ->with('info', 'L’indirizzo di fatturazione si modifica solo da Anagrafica.');
        }

        return view('mittenze.edit', [
            'mittenza' => $mittenza,
            'idComuneCorrente' => $mittenza->id_comune,
            'tipoUtente' => (string) ($request->user()->tipo_utente ?? 'privato'),
        ]);
    }

    public function update(Request $request, mittenza $mittenza): RedirectResponse
    {
        $this->authorizeMittenza($request, $mittenza);
        if ($mittenza->is_fatturazione) {
            return redirect()
                ->route('mittenze.index')
                ->with('info', 'L’indirizzo di fatturazione si modifica solo da Anagrafica.');
        }

        $validated = $this->validatedMittenza($request);
        $mittenza->update($validated);

        return redirect()->route('mittenze.index')->with('ok', 'Mittente aggiornato.');
    }

    public function destroy(Request $request, mittenza $mittenza): RedirectResponse
    {
        $this->authorizeMittenza($request, $mittenza);
        if ($mittenza->is_fatturazione) {
            return redirect()
                ->route('mittenze.index')
                ->withErrors(['mittenza' => 'Non puoi eliminare la sede di fatturazione.']);
        }

        $wasPreferito = $mittenza->is_preferito;
        $mittenza->delete();

        if ($wasPreferito) {
            $fallback = $request->user()->mittenze()->orderBy('id')->first();
            if ($fallback) {
                $fallback->update(['is_preferito' => true]);
            }
        }

        return redirect()->route('mittenze.index')->with('ok', 'Mittente eliminato.');
    }

    public function duplica(Request $request, mittenza $mittenza): RedirectResponse
    {
        $this->authorizeMittenza($request, $mittenza);

        $copy = $mittenza->replicate();
        $copy->is_preferito = false;
        $copy->is_fatturazione = false;
        $copy->sede_liccardi = false;
        $copy->save();

        return redirect()
            ->route('mittenze.edit', $copy)
            ->with('ok', 'Copia creata: completa o modifica i dati e salva.');
    }

    public function preferito(Request $request, mittenza $mittenza): RedirectResponse
    {
        $this->authorizeMittenza($request, $mittenza);
        $this->mittenzeService->setPreferito($request->user(), $mittenza);

        return redirect()->route('mittenze.index')->with('ok', 'Preferenza mittente aggiornata.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedMittenza(Request $request): array
    {
        $rules = [
            'mittente_anagrafica' => ['required', 'in:privato,azienda'],
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

        $cap = $this->mittenzeService->normalizzaCap($validated['cap']);
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

        $isMittAzienda = ($validated['mittente_anagrafica'] ?? 'privato') === 'azienda';
        if ($isMittAzienda) {
            $denom = trim((string) ($validated['denominazione_ragione_sociale'] ?? ''));
            if ($denom === '') {
                throw ValidationException::withMessages([
                    'denominazione_ragione_sociale' => 'Indica il nome impresa per un mittente azienda.',
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

    private function authorizeMittenza(Request $request, mittenza $mittenza): void
    {
        abort_if((int) $mittenza->user_id !== (int) $request->user()->id, 403);
    }
}
