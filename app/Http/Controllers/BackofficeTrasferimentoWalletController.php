<?php

namespace App\Http\Controllers;

use App\Models\rimborso;
use App\Models\User;
use App\Services\Rimborso\RimborsoTrasferimentoEsternoService;
use App\Support\RimborsoTrasferimentoWalletFilter;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BackofficeTrasferimentoWalletController extends Controller
{
    public function __construct(
        private readonly RimborsoTrasferimentoEsternoService $trasferimento,
    ) {}

    public function index(Request $request): View
    {
        $filtros = RimborsoTrasferimentoWalletFilter::fromRequest($request);
        $selectedUser = $filtros->resolveSelectedUser();

        if ($selectedUser === null && $filtros->cliente !== '') {
            $emailBusca = mb_strtolower($filtros->cliente);
            $selectedUser = User::query()->whereRaw('LOWER(email) = ?', [$emailBusca])->first();
        }

        $lista = $this->resolveLista($request, $filtros);

        return view('backoffice.rimborsi.trasferimento-wallet', [
            'sezione' => 'trasferimento_wallet',
            'lista' => $lista,
            'filtros' => $filtros->toArray(),
            'perPage' => $filtros->perPage,
            'hasActiveFilters' => $filtros->hasActiveFilters(),
            'customPeriodoSemDatas' => $filtros->customPeriodoSemDatas(),
            'selectedUser' => $selectedUser,
        ]);
    }

    public function registraRichiesta(rimborso $rimborso): RedirectResponse
    {
        try {
            $this->trasferimento->registraRichiestaCliente($rimborso);
        } catch (DomainException $e) {
            return redirect()->back()->with('rimborso_bo_erro', $e->getMessage());
        }

        return redirect()->back()->with('rimborso_bo_ok', 'Richiesta di trasferimento registrata.');
    }

    public function trasferisciCarta(rimborso $rimborso): RedirectResponse
    {
        try {
            $this->trasferimento->trasferisciSuCarta($rimborso);
        } catch (DomainException $e) {
            return redirect()->back()->with('rimborso_bo_erro', $e->getMessage());
        }

        return redirect()->back()->with('rimborso_bo_ok', 'Storno carta eseguito e wallet addebitato.');
    }

    public function trasferisciBonifico(Request $request, rimborso $rimborso): RedirectResponse
    {
        $validated = $request->validate([
            'iban' => ['required', 'string', 'max:34'],
            'beneficiario' => ['nullable', 'string', 'max:120'],
        ]);

        $beneficiario = trim((string) ($validated['beneficiario'] ?? ''));
        if ($beneficiario === '') {
            $beneficiario = $this->trasferimento->nomeBeneficiarioDefault($rimborso);
        }

        try {
            $this->trasferimento->trasferisciSuBonifico(
                $rimborso,
                (string) $validated['iban'],
                $beneficiario,
            );
        } catch (DomainException $e) {
            return redirect()->back()->with('rimborso_bo_erro', $e->getMessage());
        }

        return redirect()->back()->with('rimborso_bo_ok', 'Bonifico Revolut avviato e wallet addebitato.');
    }

    public function segnaCompletato(rimborso $rimborso): RedirectResponse
    {
        try {
            $this->trasferimento->segnaCompletatoManuale($rimborso);
        } catch (DomainException $e) {
            return redirect()->back()->with('rimborso_bo_erro', $e->getMessage());
        }

        return redirect()->back()->with('rimborso_bo_ok', 'Trasferimento segnato come completato e wallet addebitato.');
    }

    private function resolveLista(Request $request, RimborsoTrasferimentoWalletFilter $filtros): LengthAwarePaginator
    {
        if (! $filtros->hasActiveFilters()) {
            return new LengthAwarePaginator([], 0, $filtros->perPage, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        }

        $query = rimborso::query()
            ->with(['spedizione.user', 'spedizione.ordine.metodoPagamentoOrdine', 'ordine.metodoPagamentoOrdine', 'metodoPagamentoRimborso'])
            ->orderByDesc('data_richiesta_trasferimento_esterno')
            ->orderByDesc('data_reale')
            ->orderByDesc('id');

        $filtros->applyToQuery($query);

        return $query->paginate($filtros->perPage)->withQueryString();
    }
}
