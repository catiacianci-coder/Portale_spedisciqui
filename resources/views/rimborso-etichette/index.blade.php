@extends('layouts.app')
@section('content')
<div class="sq-bleed-layout">
    <x-sq-page-banner title="Richiedi rimborso etichette" icon="fa-rotate-left" class="sq-page-banner--full" />
    <div class="home-spedizione-wrap sq-rimborso-page ordini-index-page">

    @if (session('rimborso_ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">
            @if (session('rimborso_credito_imediato'))
                Richiesta registrata: l’importo è stato accreditato.
            @else
                Richiesta registrata. Il rimborso sarà elaborato entro {{ (int) session('rimborso_giorni', $giorni_ldv_si) }} giorni lavorativi.
            @endif
        </div>
    @endif
    @if (session('rimborso_erro'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ session('rimborso_erro') }}</div>
    @endif

    <div class="sq-rimborso-card sq-mb-20">
        <h2 class="sq-rimborso-card__title">Cerca</h2>
        <form method="POST" action="{{ route('rimborso-etichette.buscar') }}" class="sq-rimborso-form">
            @csrf
            <div class="sq-rimborso-radio sq-mb-12">
                <label><input type="radio" name="modo" value="ordine" @checked(old('modo', $modo) === 'ordine')> Per numero ordine</label>
                <label><input type="radio" name="modo" value="etichetta" @checked(old('modo', $modo) === 'etichetta')> Per codice spedizione</label>
            </div>
            <label class="sq-filtri-label" for="valor">Riferimento</label>
            <input id="valor" name="valor" type="text" class="sq-filtri-email-input sq-mb-12" required
                   value="{{ old('valor', $valor ?? '') }}" placeholder="es. O42 o codice spedizione">
            <button type="submit" class="sq-filtri-submit">Cerca</button>
        </form>
    </div>

    @if (! empty($erro_busca))
        <div class="sq-alert sq-alert--info-warm sq-mb-16">{{ $erro_busca }}</div>
    @endif

    @if ($ordine && $spedizioni->isNotEmpty())
        @if (! empty($info_busca))
            <div class="sq-alert sq-alert--info-warm sq-mb-16">{{ $info_busca }}</div>
        @endif

        <div class="sq-rimborso-info sq-mb-16">
            <p>Le etichette che possono essere cancellate sono quelle pagate negli ultimi <strong>{{ $dias_elegibilidade }} giorni di calendario</strong> e non ancora affidate al corriere. Una volta annullate, le etichette non possono più essere utilizzate: il sistema procede alla cancellazione presso il corriere ove previsto.</p>
            <p>Il rimborso viene sempre accreditato sul <strong>wallet</strong>. L’elaborazione avviene entro {{ $giorni_ldv_si }} giorni lavorativi dalla richiesta per le etichette generate; per quelle non prodotte per anomalia di sistema l’accredito può avvenire in tempi più brevi. Per i dettagli completi consulta la <a href="{{ route('politica.rimborso') }}">Politica di rimborso</a>.</p>
        </div>

        <div class="sq-sped-tab-section">
            <p class="sq-fw-700 sq-mb-12">Ordine {{ $ordine->codice }}</p>
            @include('rimborso-etichette.partials.tabella-spedizioni', ['spedizioni' => $spedizioni])
        </div>
    @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.sq-rimborso-card { background: #fff; border: 1px solid var(--sq-border-warm, #e8dfd0); border-radius: 12px; padding: 20px 22px; }
.sq-rimborso-card__title { margin: 0 0 10px; font-size: 1.05rem; color: var(--sq-brand, #5c005c); }
.sq-rimborso-radio { display: flex; flex-wrap: wrap; gap: 16px; }
.sq-rimborso-radio label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.sq-rimborso-info { font-size: 0.95rem; line-height: 1.55; color: #444; }
.sq-rimborso-info p { margin: 0 0 10px; }
.sq-rimborso-info p:last-child { margin-bottom: 0; }
.sq-rimborso-trash-form { display: inline; margin: 0; padding: 0; }
</style>
@endpush
