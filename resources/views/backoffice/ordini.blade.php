@extends('layouts.app')
@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $importoOrdine = static function ($o) use ($fmt): string {
        if ($o->isPagato()) {
            $v = $o->pag_effettivo_or ?? $o->total_pagamento ?? $o->total_pagamento_wallet;

            return $v !== null ? $fmt($v).' €' : '—';
        }

        $v = $o->total_pagamento ?? $o->total_pagamento_wallet;

        return $v !== null && (float) $v > 0 ? $fmt($v).' €' : '—';
    };
    $statoBadgeClass = static function ($o): string {
        return match ($o->stato) {
            \App\Models\ordine::STATO_PAGATO => 'sq-bo-ordini-stato sq-bo-ordini-stato--pagato',
            \App\Models\ordine::STATO_NON_PAGATO => 'sq-bo-ordini-stato sq-bo-ordini-stato--non-pagato',
            \App\Models\ordine::STATO_ANNULLATO => 'sq-bo-ordini-stato sq-bo-ordini-stato--annullato',
            default => 'sq-bo-ordini-stato',
        };
    };
    $statoLabel = static function ($o): string {
        $nome = trim((string) ($o->statoOrdine?->denominazione ?? ''));

        return $nome !== '' ? $nome : match ($o->stato) {
            \App\Models\ordine::STATO_PAGATO => 'Pagato',
            \App\Models\ordine::STATO_NON_PAGATO => 'Non pagato',
            \App\Models\ordine::STATO_ANNULLATO => 'Annullato',
            default => '—',
        };
    };
    $varie4BoHint = static function ($o): ?string {
        if (! $o->isPagato()) {
            return null;
        }
        $raw = trim((string) ($o->varie4 ?? ''));
        if ($raw === '') {
            return null;
        }

        return $raw === '7' ? \App\Models\ordine::VARIE4_OPERAZIONE_BACKOFFICE : $raw;
    };
@endphp

<div class="sq-bo-page-wrap sq-listing-page sq-bo-ordini-page">
    <p class="sq-bo-ordini-lead">
        Elenco ordini spedizioni. Per confermare manualmente un pagamento (es. notifica carta o gateway non ricevuta) usa
        <strong>Paga</strong> e indica metodo, data e riferimento del gateway se disponibile.
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif
    @if (session('warning'))
        <div class="sq-alert sq-alert--info-warm sq-mb-16">{{ session('warning') }}</div>
    @endif
    @if ($errors->has('backoffice'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('backoffice') }}</div>
    @endif

    @if ($customPeriodoSemDatas ?? false)
        <p class="sq-wallet-extrato-hint">Periodo personalizzato: indica le date <strong>Da</strong> e/o <strong>A</strong> e clicca il filtro.</p>
    @endif

    @include('backoffice.partials.ordini-filtri', [
        'filtros' => $filtros,
        'perPage' => $perPage,
        'pagamentoFiltroUi' => $pagamentoFiltroUi,
        'selectedUser' => $selectedUser ?? null,
    ])

    <div class="sq-wallet-extrato-card">
        @if ($lista->total() === 0)
            <div class="sq-wallet-extrato-empty">
                {{ ($hasActiveFilters ?? false) ? 'Nessun ordine con questi filtri.' : 'Nessun ordine registrato.' }}
            </div>
        @else
            <div class="sq-table-wrap sq-wallet-extrato-table-wrap">
                <table class="sq-table sq-wallet-extrato-table sq-bo-ordini-table">
                    <thead>
                        <tr class="sq-thead-row">
                            <th class="sq-th">ID</th>
                            <th class="sq-th">Stato</th>
                            <th class="sq-th">Cliente</th>
                            <th class="sq-th">Creato</th>
                            <th class="sq-th">Pagato</th>
                            <th class="sq-th">Annullato</th>
                            <th class="sq-th sq-th--right">Importo ordine</th>
                            <th class="sq-th">Metodo di pagamento</th>
                            <th class="sq-th sq-th--right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lista as $o)
                            @php
                                $podeAcoes = $o->isNonPagato();
                                $totalNumero = (float) ($o->pag_effettivo_or ?? $o->total_pagamento ?? $o->total_pagamento_wallet ?? 0);
                                $metodoTexto = null;
                                if ($o->isPagato()) {
                                    $metodoSuOrdine = trim((string) ($o->metodo_pagamento ?? ''));
                                    $metodoNomeTab = trim((string) ($o->metodoPagamentoOrdine?->metodo_pagamento ?? ''));
                                    $metodoTexto = $metodoSuOrdine !== '' ? $metodoSuOrdine : ($metodoNomeTab !== '' ? $metodoNomeTab : null);
                                }
                                $boHint = $varie4BoHint($o);
                            @endphp
                            <tr @class(['sq-bo-ordini-row--annullato' => $o->isAnnullato()])>
                                <td class="sq-td sq-fw-700">
                                    <span>{{ $o->codice }}</span>
                                    <a href="{{ route('backoffice.spedizioni.index', ['cerca' => 1, 'numero_ordine' => $o->codice]) }}"
                                       class="sq-bo-ordini-link-spedizioni"
                                       title="Vedi spedizioni di questo ordine"
                                       aria-label="Apri spedizioni filtrate per ordine {{ $o->codice }}">
                                        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                    </a>
                                </td>
                                <td class="sq-td">
                                    <span class="{{ $statoBadgeClass($o) }}">{{ $statoLabel($o) }}</span>
                                </td>
                                <td class="sq-td">{{ $o->user?->email ?? '—' }}</td>
                                <td class="sq-td sq-text-muted sq-nowrap">{{ $o->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="sq-td sq-text-muted sq-nowrap">
                                    {{ $o->data_pagamento?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="sq-td sq-text-muted sq-nowrap">
                                    {{ $o->isAnnullato() ? ($o->annullato_in?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—') : '—' }}
                                </td>
                                <td class="sq-td sq-td--right sq-bo-ordini-importo">{{ $importoOrdine($o) }}</td>
                                <td class="sq-td sq-bo-ordini-metodo-col">
                                    @if ($metodoTexto)
                                        <span>{{ $metodoTexto }}</span>
                                    @else
                                        <span class="sq-text-muted">—</span>
                                    @endif
                                    @if ($boHint)
                                        <span class="sq-bo-ordini-metodo-bo-hint">{{ $boHint }}</span>
                                    @endif
                                </td>
                                <td class="sq-td sq-td--right sq-bo-ordini-acoes">
                                    <button
                                        type="button"
                                        class="sq-bo-ordini-btn-paga js-open-pagar-ordine-modal"
                                        data-action="{{ route('backoffice.ordini.segna_pagato', $o) }}"
                                        data-ordine-id="{{ $o->id }}"
                                        data-codice="{{ $o->codice }}"
                                        data-total="{{ number_format($totalNumero, 2, '.', '') }}"
                                        @disabled(! $podeAcoes)
                                    >Paga</button>
                                    <form
                                        method="POST"
                                        action="{{ route('backoffice.ordini.anular', $o) }}"
                                        class="sq-form-zero sq-bo-ordini-form-anular"
                                        @if ($podeAcoes) onsubmit="return confirm('Annullare questo ordine? Tutte le spedizioni verranno marcate come annullate.');" @else onsubmit="return false;" @endif
                                    >
                                        @csrf
                                        @foreach ($queryParams as $name => $value)
                                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                        @endforeach
                                        <button type="submit" class="sq-bo-ordini-btn-anular" @disabled(! $podeAcoes)>Annulla</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($lista->hasPages())
                <div class="sq-wallet-extrato-pag">
                    {{ $lista->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

<dialog id="bo-pagar-ordine-modal" class="sq-bo-ordini-modal">
    <form method="dialog" class="sq-bo-ordini-modal__head">
        <strong>Conferma pagamento manuale</strong>
        <button type="submit" class="sq-bo-ordini-modal__close" aria-label="Chiudi">&times;</button>
    </form>
    <form method="POST" id="bo-pagar-ordine-form" class="sq-bo-ordini-modal__body">
        @csrf
        @foreach ($queryParams as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <p class="sq-bo-ordini-modal__intro">
            Ordine <strong id="bo-pagar-ordine-codice">—</strong>
        </p>
        <div class="sq-bo-ordini-modal__field">
            <label for="bo-pagar-ordine-total">Importo ordine</label>
            <input type="text" id="bo-pagar-ordine-total" readonly class="sq-wallet-extrato-filtri__input-readonly">
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="bo-pagar-ordine-metodo-id">Metodo di pagamento (cliente)</label>
            <select id="bo-pagar-ordine-metodo-id" name="metodo_pagamento_id" required class="sq-wallet-extrato-filtri__select">
                @forelse ($metodosPagamento as $met)
                    <option value="{{ $met->id }}">{{ $met->metodo_pagamento }}</option>
                @empty
                    <option value="" disabled>Nessun metodo di pagamento disponibile</option>
                @endforelse
            </select>
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="bo-pagar-ordine-token-2">
                token_2
                <span class="sq-bo-ordini-modal__hint">(riferimento gateway: es. PaymentIntent Stripe, e2eId PIX, ID transazione Revolut)</span>
            </label>
            <input type="text" id="bo-pagar-ordine-token-2" name="token_2" maxlength="500" autocomplete="off" placeholder="Opzionale" class="sq-wallet-extrato-filtri__select">
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="bo-pagar-ordine-data">Data pagamento</label>
            <input type="datetime-local" id="bo-pagar-ordine-data" name="data_pagamento" required class="sq-wallet-extrato-filtri__date">
        </div>
        <div class="sq-bo-ordini-modal__actions">
            <button type="button" id="bo-pagar-ordine-cancelar" class="sq-bo-ordini-btn-anular">Annulla</button>
            <button type="submit" class="sq-bo-ordini-btn-paga">OK</button>
        </div>
    </form>
</dialog>

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

    const modal = document.getElementById('bo-pagar-ordine-modal');
    const form = document.getElementById('bo-pagar-ordine-form');
    if (!modal || !form) return;

    const codiceEl = document.getElementById('bo-pagar-ordine-codice');
    const totalEl = document.getElementById('bo-pagar-ordine-total');
    const token2El = document.getElementById('bo-pagar-ordine-token-2');
    const dataEl = document.getElementById('bo-pagar-ordine-data');
    const cancelBtn = document.getElementById('bo-pagar-ordine-cancelar');

    const toEur = (v) => {
        const n = Number(v || 0);
        return n.toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    };

    const setNowDatetimeLocal = () => {
        const d = new Date();
        const pad = (x) => String(x).padStart(2, '0');
        dataEl.value = d.getFullYear()
            + '-' + pad(d.getMonth() + 1)
            + '-' + pad(d.getDate())
            + 'T' + pad(d.getHours())
            + ':' + pad(d.getMinutes());
    };

    cancelBtn?.addEventListener('click', () => modal.close());

    document.querySelectorAll('.js-open-pagar-ordine-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            form.setAttribute('action', btn.getAttribute('data-action') || '');
            if (codiceEl) codiceEl.textContent = btn.getAttribute('data-codice') || ('#' + (btn.getAttribute('data-ordine-id') || ''));
            if (totalEl) totalEl.value = toEur(btn.getAttribute('data-total') || '0');
            if (token2El) token2El.value = '';
            setNowDatetimeLocal();
            modal.showModal();
        });
    });
})();
</script>
@endsection
