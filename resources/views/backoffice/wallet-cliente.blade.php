@extends('layouts.app')
@section('content')
<div class="sq-bo-page-wrap sq-wallet-extrato-page sq-wallet-extrato-page--bo">
    @if ($invalidUserId ?? false)
        <div class="sq-alert sq-alert--info-warm sq-mb-16">Utente non trovato.</div>
    @endif

    @if (($candidatos ?? collect())->isNotEmpty())
        <ul class="sq-wallet-extrato-candidati">
            @foreach ($candidatos as $c)
                <li>
                    <a href="{{ route('backoffice.wallet.cliente', array_merge(request()->except('page'), ['user_id' => $c->id])) }}">
                        #{{ $c->id }} — {{ $c->headerDisplayName() }} &lt;{{ $c->email }}&gt;
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if (! empty($customPeriodoSemDatas))
        <p class="sq-wallet-extrato-hint">Periodo personalizzato: indica le date <strong>Da</strong> e/o <strong>A</strong> e clicca il filtro.</p>
    @endif

    @php
        $walletSaldoFmt = null;
        if ($selectedUser !== null) {
            $walletSaldoFmt = number_format((float) ($selectedUser->walletSaldo?->saldo ?? 0), 2, ',', '.').' €';
        }
    @endphp

    @include('wallet.partials.extrato-filtri', [
        'formAction' => $formAction,
        'filtros' => $filtros,
        'perPage' => $perPage,
        'tiposMovimento' => $tiposMovimento,
        'showUsuarioColumn' => true,
        'selectedUser' => $selectedUser,
        'busca' => $busca ?? '',
        'formId' => 'form-filtri-wallet-bo',
        'periodoId' => 'filtro-wallet-periodo-bo',
        'customWrapId' => 'filtro-wallet-datas-custom-bo',
        'tipoId' => 'filtro-wallet-tipo-bo',
        'perPageId' => 'filtro-wallet-per-page-bo',
    ])

    @include('wallet.partials.extrato-contenuto', [
        'linhas' => $linhas,
        'showUsuarioColumn' => true,
        'hasActiveFilters' => $hasActiveFilters,
        'walletSaldoFormatado' => $walletSaldoFmt,
        'selectedUser' => $selectedUser,
        'candidatos' => $candidatos ?? collect(),
        'buscaSemResultado' => $buscaSemResultado ?? false,
        'invalidUserId' => $invalidUserId ?? false,
    ])
</div>
<script>
(() => {
    document.querySelectorAll('.js-wallet-extrato-periodo').forEach((sel) => {
        const wrapId = sel.getAttribute('data-custom-wrap');
        const wrap = wrapId ? document.getElementById(wrapId) : null;
        if (!wrap) return;
        const sync = () => wrap.classList.toggle('is-on', sel.value === 'custom');
        sel.addEventListener('change', sync);
        sync();
    });
})();
</script>
@endsection
