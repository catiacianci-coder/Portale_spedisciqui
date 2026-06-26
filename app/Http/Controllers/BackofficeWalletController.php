<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\metodo_pagamento_wallet_ricarica;
use App\Models\wallet_descrizione;
use App\Models\wallet_movimento;
use App\Models\wallet_ricarica_richiesta;
use App\Services\Wallet\WalletExtratoFilters;
use App\Services\Wallet\WalletExtratoListService;
use App\Services\Wallet\WalletMovimentoManualeService;
use App\Services\Wallet\WalletRicaricaAccreditoService;
use App\Support\ImportoEuro;
use App\Support\WalletMovimentoRiferimentoPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BackofficeWalletController extends Controller
{
    public function __construct(
        private readonly WalletExtratoListService $extratoList,
    ) {}
    public function ricariche(Request $request)
    {
        $filtros = \App\Support\WalletRicaricaListaFilter::fromRequest($request, backoffice: true);

        $query = wallet_ricarica_richiesta::query()
            ->with(['user.anagrafica', 'metodoPagamentoWalletRicarica'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $filtros->applyToQuery($query);

        $ricariche = $query->paginate($filtros->perPage)->withQueryString();

        $selectedUser = $filtros->userId > 0
            ? User::query()->find($filtros->userId)
            : null;

        return view('backoffice.ricariche', [
            'ricariche' => $ricariche,
            'perPage' => $filtros->perPage,
            'filtros' => array_merge($filtros->toArray(), [
                'metodo_pagamento_id' => $filtros->metodoPagamentoId > 0 ? (string) $filtros->metodoPagamentoId : '',
            ]),
            'hasActiveFilters' => $filtros->hasActiveFilters(),
            'customPeriodoSemDatas' => $filtros->customPeriodoSemDatas(),
            'selectedUser' => $selectedUser,
            'metodosWallet' => metodo_pagamento_wallet_ricarica::query()
                ->orderBy('metodo_pagamento')
                ->get(['id', 'metodo_pagamento']),
            'metodiPagamentoAccredito' => metodo_pagamento_wallet_ricarica::query()
                ->abilitatiCliente()
                ->get(['id', 'metodo_pagamento']),
            'queryParams' => \App\Support\FiltriTabella::parametriQuery($request),
        ]);
    }

    public function walletCliente(Request $request): View
    {
        $filters = WalletExtratoFilters::fromRequest($request);

        $userId = $filters->userId;
        $busca = $filters->usuario;

        $selectedUser = null;
        $invalidUserId = false;
        /** @var Collection<int, User> $candidatos */
        $candidatos = collect();
        $buscaSemResultado = false;

        if ($userId > 0) {
            $selectedUser = User::query()->with(['walletSaldo', 'anagrafica'])->find($userId);
            if ($selectedUser === null) {
                $invalidUserId = true;
            }
        } elseif ($busca !== '') {
            $emailBusca = mb_strtolower($busca);
            $selectedUser = User::query()
                ->with(['walletSaldo', 'anagrafica'])
                ->whereRaw('LOWER(email) = ?', [$emailBusca])
                ->first();
            if ($selectedUser === null) {
                $like = '%'.addcslashes($emailBusca, '%_\\').'%';
                $candidatos = User::query()
                    ->with('anagrafica')
                    ->whereRaw('LOWER(email) LIKE ?', [$like])
                    ->orderBy('email')
                    ->limit(30)
                    ->get(['id', 'email']);
                $buscaSemResultado = $candidatos->isEmpty();
            }
        }

        $descrizioniMovimento = wallet_descrizione::query()
            ->orderBy('tipo')
            ->orderBy('descrizione')
            ->get(['id', 'tipo', 'descrizione', 'codice']);

        $descrizioniMovimentoManuale = $descrizioniMovimento
            ->reject(fn ($d) => \App\Support\WalletMovimentoRiferimentoPresenter::isRiferimentoAutomatico((string) $d->codice))
            ->values();

        $tiposMovimento = $descrizioniMovimento->map(fn ($d) => (object) [
            'id' => $d->id,
            'descrizione' => $d->descrizione,
        ]);

        if ($selectedUser !== null) {
            $linhas = $this->extratoList->paginateForUser($selectedUser, $filters, $request);
        } else {
            $linhas = $this->emptyPaginator($filters, $request);
        }

        return view('backoffice.wallet-cliente', [
            'selectedUser' => $selectedUser,
            'candidatos' => $candidatos,
            'busca' => $busca,
            'buscaSemResultado' => $buscaSemResultado,
            'invalidUserId' => $invalidUserId,
            'linhas' => $linhas,
            'tiposMovimento' => $tiposMovimento,
            'descrizioniMovimento' => $descrizioniMovimento,
            'descrizioniMovimentoManuale' => $descrizioniMovimentoManuale,
            'hasActiveFilters' => $filters->hasActiveFilters(),
            'customPeriodoSemDatas' => $filters->customPeriodoSemDatas(),
            'perPage' => $filters->perPage,
            'filtros' => [
                'periodo' => $filters->periodo,
                'data_de' => $filters->dataDe,
                'data_ate' => $filters->dataAte,
                'wallet_descrizione_id' => $filters->walletDescrizioneId > 0 ? $filters->walletDescrizioneId : '',
            ],
            'showUsuarioColumn' => true,
            'formAction' => route('backoffice.wallet.cliente'),
            'queryParams' => \App\Support\FiltriTabella::parametriQuery($request),
        ]);
    }

    public function storeMovimentoCliente(Request $request, User $user): RedirectResponse
    {
        $filtriRedirect = \App\Support\FiltriTabella::parametriQuery($request, [
            '_token',
            'tipo',
            'wallet_descrizione_id',
            'importo',
            'riferimento',
            'nota_interna',
        ]);

        $filtriRedirect['user_id'] = $user->id;

        $validated = $request->validate([
            'tipo' => ['required', 'string', Rule::in(['credito', 'debito'])],
            'wallet_descrizione_id' => [
                'required',
                'integer',
                Rule::exists('wallet_descrizionis', 'id'),
            ],
            'importo' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'riferimento' => ['required', 'string', 'max:255'],
            'nota_interna' => ['nullable', 'string', 'max:500'],
        ], [
            'tipo.required' => 'Seleziona il tipo di movimento.',
            'tipo.in' => 'Tipo di movimento non valido.',
            'wallet_descrizione_id.required' => 'Seleziona la descrizione del movimento.',
            'wallet_descrizione_id.exists' => 'Descrizione movimento non valida.',
            'importo.required' => 'Inserisci l\'importo.',
            'importo.numeric' => 'L\'importo deve essere un numero.',
            'importo.min' => 'L\'importo deve essere maggiore di zero.',
            'importo.max' => 'L\'importo supera il limite consentito.',
            'riferimento.required' => 'Indica il riferimento (Ordine/LdV) del movimento.',
            'riferimento.max' => 'Il riferimento non può superare 255 caratteri.',
            'nota_interna.max' => 'La nota interna non può superare 500 caratteri.',
        ]);

        $descr = wallet_descrizione::query()->findOrFail((int) $validated['wallet_descrizione_id']);
        if ($descr->tipo !== $validated['tipo']) {
            return redirect()
                ->route('backoffice.wallet.cliente', $filtriRedirect)
                ->withErrors(['backoffice' => 'La descrizione selezionata non corrisponde al tipo scelto.'])
                ->withInput();
        }

        if (WalletMovimentoRiferimentoPresenter::isRiferimentoAutomatico((string) $descr->codice)) {
            return redirect()
                ->route('backoffice.wallet.cliente', $filtriRedirect)
                ->withErrors(['backoffice' => 'Questa causale è gestita automaticamente dal sistema.'])
                ->withInput();
        }

        $importo = round((float) $validated['importo'], 2);

        $result = app(WalletMovimentoManualeService::class)->crea(
            $user,
            $validated['tipo'],
            (int) $validated['wallet_descrizione_id'],
            $importo,
            $validated['riferimento'],
            $validated['nota_interna'] ?? null,
        );

        if (! $result['ok']) {
            return redirect()
                ->route('backoffice.wallet.cliente', $filtriRedirect)
                ->withErrors(['backoffice' => $result['message'] ?? 'Operazione non eseguita.'])
                ->withInput();
        }

        $segno = $validated['tipo'] === 'credito' ? '+' : '−';

        return redirect()
            ->route('backoffice.wallet.cliente', $filtriRedirect)
            ->with(
                'ok',
                'Movimento registrato: '.$descr->descrizione.' ('.$segno.' '.ImportoEuro::format($importo).') per '.$user->email.'.',
            );
    }

    public function updateNotaInternaMovimento(Request $request, wallet_movimento $movimento): RedirectResponse
    {
        $validated = $request->validate([
            'nota_interna' => ['nullable', 'string', 'max:500'],
        ], [
            'nota_interna.max' => 'La nota interna non può superare 500 caratteri.',
        ]);

        $nota = trim((string) ($validated['nota_interna'] ?? ''));

        $movimento->forceFill([
            'nota_interna' => $nota !== '' ? $nota : null,
        ])->save();

        $filtriRedirect = \App\Support\FiltriTabella::parametriQuery($request, ['_token', '_method', 'nota_interna']);
        $filtriRedirect['user_id'] = $movimento->user_id;

        return redirect()
            ->route('backoffice.wallet.cliente', $filtriRedirect)
            ->with('ok', 'Nota interna aggiornata.');
    }

    private function emptyPaginator(WalletExtratoFilters $filters, Request $request): LengthAwarePaginator
    {
        return new Paginator(
            collect(),
            0,
            $filters->perPage,
            max(1, (int) $request->input('page', 1)),
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }

    public function accreditaRicarica(Request $request, int $id)
    {
        $richiesta = wallet_ricarica_richiesta::query()->findOrFail($id);

        $filtriRedirect = \App\Support\FiltriTabella::parametriQuery($request, ['_token', 'metodo_pagamento_id']);

        $validated = $request->validate([
            'metodo_pagamento_id' => [
                'required',
                'integer',
                Rule::exists('metodo_pagamento_wallet_ricariches', 'id')->where('abilitato', true),
            ],
        ], [
            'metodo_pagamento_id.required' => 'Seleziona il metodo di pagamento con cui stai accreditando la ricarica.',
            'metodo_pagamento_id.exists' => 'Metodo di pagamento non valido o non attivo.',
        ]);

        $metodoId = (int) $validated['metodo_pagamento_id'];
        $metodo = metodo_pagamento_wallet_ricarica::query()->findOrFail($metodoId);

        $result = app(WalletRicaricaAccreditoService::class)->accredita(
            $richiesta,
            $metodoId,
            riferimentoMovimento: 'Ricarica '.$richiesta->numero_ordine_wallet.' (back-office, '.$metodo->metodo_pagamento.')',
        );

        if (! $result['ok'] && ! ($result['already'] ?? false)) {
            return redirect()
                ->route('backoffice.ricariche.index', $filtriRedirect)
                ->withErrors(['backoffice' => $result['message'] ?? 'Operazione non eseguita.']);
        }

        $importoFmt = \App\Support\ImportoEuro::format((float) $richiesta->importo);

        return redirect()
            ->route('backoffice.ricariche.index', $filtriRedirect)
            ->with('ok', 'Ricarica '.$richiesta->numero_ordine_wallet.' confermata ('.$metodo->metodo_pagamento.'): '.$importoFmt.' accreditati sul wallet del cliente.');
    }
}
