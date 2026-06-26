@extends('layouts.app')
@section('content')
<div class="sq-bo-page-wrap sq-wallet-extrato-page sq-wallet-extrato-page--bo">
    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif
    @if ($errors->has('backoffice'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('backoffice') }}</div>
    @endif

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
            $walletSaldoFmt = \App\Support\ImportoEuro::format((float) ($selectedUser->walletSaldo?->saldo ?? 0));
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

    @if ($selectedUser !== null)
        <div class="sq-wallet-extrato-toolbar">
            <button type="button" id="bo-wallet-nuovo-movimento-btn" class="sq-wallet-extrato-btn-nuovo-movimento">
                Nuovo movimento
            </button>
        </div>
    @endif

    @include('wallet.partials.extrato-contenuto', [
        'linhas' => $linhas,
        'showUsuarioColumn' => true,
        'showNotaInterna' => true,
        'editNotaInterna' => true,
        'queryParams' => $queryParams ?? [],
        'hasActiveFilters' => $hasActiveFilters,
        'walletSaldoFormatado' => $walletSaldoFmt,
        'selectedUser' => $selectedUser,
        'candidatos' => $candidatos ?? collect(),
        'buscaSemResultado' => $buscaSemResultado ?? false,
        'invalidUserId' => $invalidUserId ?? false,
    ])

    @include('backoffice.partials.wallet-nuovo-movimento-modal', [
        'selectedUser' => $selectedUser,
        'queryParams' => $queryParams ?? [],
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

    const modal = document.getElementById('bo-wallet-nuovo-movimento-modal');
    const openBtn = document.getElementById('bo-wallet-nuovo-movimento-btn');
    const form = document.getElementById('bo-wallet-nuovo-movimento-form');
    if (!modal || !openBtn || !form) return;

    const tipoSel = document.getElementById('bo-wallet-movimento-tipo');
    const descWrap = document.getElementById('bo-wallet-movimento-desc-wrap');
    const descSel = document.getElementById('bo-wallet-movimento-desc');
    const importoEl = document.getElementById('bo-wallet-movimento-importo');
    const riferimentoEl = document.getElementById('bo-wallet-movimento-riferimento');
    const notaEl = document.getElementById('bo-wallet-movimento-nota-interna');
    const cancelBtn = document.getElementById('bo-wallet-movimento-cancelar');

    const descrizioniByTipo = @json(
        ($descrizioniMovimentoManuale ?? collect())
            ->groupBy('tipo')
            ->map(fn ($items) => $items->map(fn ($d) => ['id' => $d->id, 'descrizione' => $d->descrizione])->values())
    );

    const oldTipo = @json(old('tipo'));
    const oldDescId = @json(old('wallet_descrizione_id'));
    const oldRiferimento = @json(old('riferimento'));
    const oldNota = @json(old('nota_interna'));

    const tipoLabel = (tipo) => (tipo === 'credito' ? 'Credito' : 'Debito');

    const syncDescrizioni = () => {
        const tipo = tipoSel.value;
        descSel.innerHTML = '';

        if (!tipo) {
            descWrap.hidden = true;
            descSel.disabled = true;
            descSel.removeAttribute('required');
            return;
        }

        descWrap.hidden = false;
        descSel.disabled = false;
        descSel.required = true;

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Seleziona dettaglio…';
        placeholder.disabled = true;
        placeholder.selected = true;
        descSel.appendChild(placeholder);

        (descrizioniByTipo[tipo] || []).forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = item.descrizione;
            if (oldDescId && String(oldDescId) === String(item.id)) {
                opt.selected = true;
                placeholder.selected = false;
            }
            descSel.appendChild(opt);
        });
    };

    const resetForm = () => {
        form.reset();
        syncDescrizioni();
    };

    openBtn.addEventListener('click', () => {
        resetForm();
        if (oldTipo) {
            tipoSel.value = oldTipo;
            syncDescrizioni();
        }
        if (importoEl && @json(old('importo'))) {
            importoEl.value = @json(old('importo'));
        }
        if (riferimentoEl && oldRiferimento) {
            riferimentoEl.value = oldRiferimento;
        }
        if (notaEl && oldNota) {
            notaEl.value = oldNota;
        }
        modal.showModal();
    });

    cancelBtn?.addEventListener('click', () => modal.close());

    tipoSel?.addEventListener('change', syncDescrizioni);

    form.addEventListener('submit', (e) => {
        const tipo = tipoSel.value;
        const descOpt = descSel.options[descSel.selectedIndex];
        const importo = importoEl.value;

        const riferimento = riferimentoEl?.value || '';

        if (!tipo || !descSel.value || !importo || !riferimento) {
            return;
        }

        const msg = [
            'Confermi il movimento?',
            '',
            'Tipo: ' + tipoLabel(tipo),
            'Dettaglio: ' + (descOpt?.textContent || '—'),
            'Ordine/LdV: ' + riferimento,
            'Importo: ' + importo + ' €',
        ].join('\n');

        if (!window.confirm(msg)) {
            e.preventDefault();
        }
    });

    @if ($errors->has('backoffice') || old('tipo') || old('wallet_descrizione_id') || old('importo') || old('riferimento'))
        modal.showModal();
        if (oldTipo) {
            tipoSel.value = oldTipo;
            syncDescrizioni();
        }
        if (riferimentoEl && oldRiferimento) {
            riferimentoEl.value = oldRiferimento;
        }
        if (notaEl && oldNota) {
            notaEl.value = oldNota;
        }
    @endif
})();
</script>
@endsection
