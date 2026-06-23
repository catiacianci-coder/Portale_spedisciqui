<?php

namespace App\Http\Controllers;

use App\Models\wallet_descrizione;
use App\Services\Wallet\WalletExtratoFilters;
use App\Services\Wallet\WalletExtratoListService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletMovimentiController extends Controller
{
    public function __construct(
        private readonly WalletExtratoListService $extratoList,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        assert($user !== null);
        $user->loadMissing('walletSaldo');

        $filters = WalletExtratoFilters::fromRequest($request);

        $tiposMovimento = wallet_descrizione::query()
            ->orderBy('tipo')
            ->orderBy('descrizione')
            ->get(['id', 'descrizione']);

        $linhas = $this->extratoList->paginateForUser($user, $filters, $request);

        return view('wallet.movimenti', [
            'linhas' => $linhas,
            'tiposMovimento' => $tiposMovimento,
            'hasActiveFilters' => $filters->hasActiveFilters(),
            'customPeriodoSemDatas' => $filters->customPeriodoSemDatas(),
            'perPage' => $filters->perPage,
            'filtros' => [
                'periodo' => $filters->periodo,
                'data_de' => $filters->dataDe,
                'data_ate' => $filters->dataAte,
                'wallet_descrizione_id' => $filters->walletDescrizioneId > 0 ? $filters->walletDescrizioneId : '',
            ],
            'showUsuarioColumn' => false,
            'formAction' => route('wallet.movimenti'),
            'walletSaldoFormatado' => \App\Support\ImportoEuro::format((float) ($user->walletSaldo?->saldo ?? 0)),
        ]);
    }
}
