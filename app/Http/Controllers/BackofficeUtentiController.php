<?php

namespace App\Http\Controllers;

use App\Models\Anagrafica;
use App\Models\mittenza;
use App\Models\User;
use App\Services\Anagrafica\AnagraficaRevisioneService;
use App\Support\FiltriTabella;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BackofficeUtentiController extends Controller
{
    public function __construct(
        private readonly AnagraficaRevisioneService $anagraficaRevisione,
    ) {}
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'created_range' => (string) $request->query('created_range', ''),
            'cf_piva' => preg_replace('/\D+/', '', (string) $request->query('cf_piva', '')) ?: '',
            'cap' => preg_replace('/\D+/', '', (string) $request->query('cap', '')) ?: '',
            'telefono' => preg_replace('/\D+/', '', (string) $request->query('telefono', '')) ?: '',
            'nome' => trim((string) $request->query('nome', '')),
            'cognome' => trim((string) $request->query('cognome', '')),
            'ragione_sociale' => trim((string) $request->query('ragione_sociale', '')),
            'habilitado' => (string) $request->query('habilitado', 'todos'),
            'com_pratiche' => (string) $request->query('com_pratiche', 'todos'),
            'per_page' => FiltriTabella::perPage($request),
        ];

        if (! in_array($filters['habilitado'], ['todos', 'sim', 'nao'], true)) {
            $filters['habilitado'] = 'todos';
        }
        if (! in_array($filters['com_pratiche'], ['todos', 'sim', 'nao'], true)) {
            $filters['com_pratiche'] = 'todos';
        }
        if (! in_array($filters['created_range'], ['', 'today', '7d', '30d', '90d'], true)) {
            $filters['created_range'] = '';
        }

        $users = User::query()
            ->with(['anagrafiche' => function ($query): void {
                $query->orderByDesc('attivo')->orderByDesc('id');
            }, 'walletSaldo'])
            ->addSelect([
                'rimborsi_count' => DB::table('rimborsi')
                    ->join('spedizionis', 'spedizionis.id', '=', 'rimborsi.spedizione_id')
                    ->whereColumn('spedizionis.user_id', 'users.id')
                    ->selectRaw('count(*)'),
            ])
            ->withCount([
                'ncPratiche',
                'ordini',
                'spedizioni',
                'anagrafiche',
                'mittenze',
                'walletRicaricheRichieste as ricariche_count',
            ])
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $q = $filters['q'];
                $query->where(function ($inner) use ($q): void {
                    $inner->where('email', 'like', "%{$q}%");
                    if (is_numeric($q)) {
                        $inner->orWhere('id', (int) $q);
                    } else {
                        $inner->orWhereHas('anagrafiche', function ($a) use ($q): void {
                            $a->where('nome', 'like', "%{$q}%")
                                ->orWhere('cognome', 'like', "%{$q}%")
                                ->orWhere('denominazione_ragione_sociale', 'like', "%{$q}%");
                        });
                    }
                });
            })
            ->when($filters['created_range'] !== '', function ($query) use ($filters): void {
                $now = now();
                match ($filters['created_range']) {
                    'today' => $query->whereDate('created_at', $now->toDateString()),
                    '7d' => $query->where('created_at', '>=', $now->copy()->subDays(7)),
                    '30d' => $query->where('created_at', '>=', $now->copy()->subDays(30)),
                    '90d' => $query->where('created_at', '>=', $now->copy()->subDays(90)),
                    default => null,
                };
            })
            ->when($filters['habilitado'] !== 'todos', function ($query) use ($filters): void {
                $query->where('is_account_disabled', $filters['habilitado'] === 'nao');
            })
            ->when($filters['com_pratiche'] !== 'todos', function ($query) use ($filters): void {
                if ($filters['com_pratiche'] === 'sim') {
                    $query->whereHas('ncPratiche');
                } else {
                    $query->whereDoesntHave('ncPratiche');
                }
            })
            ->when($filters['cf_piva'] !== '', function ($query) use ($filters): void {
                $doc = $filters['cf_piva'];
                $query->whereHas('anagrafiche', function ($a) use ($doc): void {
                    $a->whereRaw("REPLACE(REPLACE(UPPER(COALESCE(codice_fiscale, '')), ' ', ''), '.', '') LIKE ?", ["%{$doc}%"])
                        ->orWhereRaw("REPLACE(COALESCE(partita_iva, ''), ' ', '') LIKE ?", ["%{$doc}%"]);
                });
            })
            ->when($filters['cap'] !== '', function ($query) use ($filters): void {
                $cap = $filters['cap'];
                $query->whereHas('anagrafiche', function ($a) use ($cap): void {
                    $a->whereRaw("REPLACE(COALESCE(cap, ''), ' ', '') LIKE ?", ["%{$cap}%"]);
                });
            })
            ->when($filters['telefono'] !== '', function ($query) use ($filters): void {
                $tel = $filters['telefono'];
                $query->whereHas('anagrafiche', function ($a) use ($tel): void {
                    $a->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telefono, ''), '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?", ["%{$tel}%"]);
                });
            })
            ->when($filters['nome'] !== '', function ($query) use ($filters): void {
                $nome = $filters['nome'];
                $query->whereHas('anagrafiche', fn ($a) => $a->where('nome', 'like', "%{$nome}%"));
            })
            ->when($filters['cognome'] !== '', function ($query) use ($filters): void {
                $cognome = $filters['cognome'];
                $query->whereHas('anagrafiche', fn ($a) => $a->where('cognome', 'like', "%{$cognome}%"));
            })
            ->when($filters['ragione_sociale'] !== '', function ($query) use ($filters): void {
                $ragione = $filters['ragione_sociale'];
                $query->whereHas('anagrafiche', fn ($a) => $a->where('denominazione_ragione_sociale', 'like', "%{$ragione}%"));
            })
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('backoffice.utenti.index', [
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    public function section(User $user, string $section): View
    {
        $valid = [
            'anagrafica' => 'Anagrafica',
            'mittenti' => 'Rubrica mittenti',
            'rimborsi' => 'Rimborsi',
        ];

        abort_unless(isset($valid[$section]), 404);

        if ($section === 'anagrafica') {
            $user->load(['anagrafiche' => fn ($q) => $q->orderByDesc('attivo')->orderByDesc('id')]);
            $anagraficaAttiva = $user->anagrafiche->firstWhere('attivo', true) ?? $user->anagrafiche->first();
            $idComuneCorrente = $anagraficaAttiva
                ? $this->anagraficaRevisione->risolviIdComuneDaAnagrafica($anagraficaAttiva)
                : null;

            return view('backoffice.utenti.section-anagrafica', [
                'user' => $user,
                'anagraficaAttiva' => $anagraficaAttiva,
                'revisioniAnagrafica' => $user->anagrafiche,
                'idComuneCorrente' => $idComuneCorrente,
            ]);
        }

        if ($section === 'mittenti') {
            $mittenti = $user->mittenze()
                ->orderByDesc('is_fatturazione')
                ->orderByDesc('is_preferito')
                ->orderBy('citta')
                ->orderBy('id')
                ->get();

            return view('backoffice.utenti.section-mittenti', [
                'user' => $user,
                'mittenti' => $mittenti,
            ]);
        }

        return view('backoffice.utenti.placeholder', [
            'user' => $user,
            'sectionLabel' => $valid[$section],
        ]);
    }

    /**
     * Abilita/disabilita generazione automatica etichette post-pagamento.
     * Il cliente può usare il sito e pagare; le API corriere partono solo se abilitato.
     */
    public function toggleHabilitacaoPostagem(User $user): RedirectResponse
    {
        $user->is_account_disabled = ! $user->is_account_disabled;
        if (! $user->is_account_disabled) {
            $user->postagem_bloqueado_pelo_bo = false;
        } else {
            $user->postagem_bloqueado_pelo_bo = true;
        }
        $user->save();

        return redirect()->back()->with(
            'ok',
            $user->is_account_disabled
                ? 'Conta non abilitata per la postagem automatica. Il cliente usa il sito normalmente; dopo il pagamento l\'etichetta non verrà generata finché non riabiliti qui.'
                : 'Conta abilitata: dopo il pagamento il sistema può generare le etichette con i corrieri.'
        );
    }

    public function toggleLiccardi(User $user): RedirectResponse
    {
        $user->is_liccardi = ! $user->is_liccardi;
        $user->save();

        return redirect()->back()->with(
            'ok',
            $user->is_liccardi
                ? 'Liccardi abilitati: il cliente vedrà i preventivi Liccardi con tariffa scontata.'
                : 'Liccardi disabilitati per questo cliente.'
        );
    }

    public function updateAnagrafica(Request $request, User $user): RedirectResponse
    {
        $latest = Anagrafica::query()->where('user_id', $user->id)->attiva()->first();
        if (! $latest) {
            return redirect()
                ->route('backoffice.utenti.section', [$user, 'anagrafica'])
                ->withErrors(['anagrafica' => 'Nessuna anagrafica attiva da aggiornare per questo utente.']);
        }

        $tipo = (string) ($user->tipo_utente ?? 'privato');
        $rules = $this->anagraficaRevisione->validationRules($tipo);
        $rules['sede_liccardi'] = ['nullable', 'boolean'];
        $validated = $request->validate($rules);

        $nuova = $this->anagraficaRevisione->creaRevisioneSeModificato($user, $latest, $validated);
        if ($nuova === null) {
            return redirect()
                ->route('backoffice.utenti.section', [$user, 'anagrafica'])
                ->with('anagrafica_unchanged', true);
        }

        return redirect()
            ->route('backoffice.utenti.section', [$user, 'anagrafica'])
            ->with('ok', 'Anagrafica aggiornata');
    }

    public function toggleSedeLiccardiMittenza(User $user, mittenza $mittenza): RedirectResponse
    {
        abort_if((int) $mittenza->user_id !== (int) $user->id, 404);

        $mittenza->sede_liccardi = ! (bool) $mittenza->sede_liccardi;
        $mittenza->save();

        return redirect()
            ->route('backoffice.utenti.section', [$user, 'mittenti'])
            ->with('ok', 'Flag Sede Liccardi aggiornata per il mittente #'.$mittenza->id.'.');
    }
}
