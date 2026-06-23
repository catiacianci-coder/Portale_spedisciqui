@extends('layouts.app')
@section('content')
<div class="carrello-page sq-page-preventivi sq-cart-page">

    <h1 class="sq-cart-page-title">Carrello</h1>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif

    @if ($errors->has('carrello'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('carrello') }}</div>
    @endif

    @if (empty($items))
        <div class="sq-cart-card">
            <div class="sq-cart-empty">
                <p>Il carrello è vuoto.</p>
                <p class="sq-cart-empty-cta">
                    <a href="{{ route('home') }}" class="sq-cart-btn sq-cart-btn--primary">Nuovo preventivo</a>
                </p>
            </div>
        </div>
    @else
        @php
            $lv = $liccardiVolume ?? ['applicato' => false, 'righe_liccardi' => 0, 'sconto_totale' => 0.0];
            $aliquotaIva = (float) ($aliquotaIva ?? 22);
            $totaleIvatoStandard = (float) ($totaleIvatoStandard ?? 0);
            $totaleIvatoWallet = (float) ($totaleIvatoWallet ?? 0);
        @endphp
        @if (($lv['righe_liccardi'] ?? 0) > 0 && ! ($lv['applicato'] ?? false))
            <div class="sq-alert sq-alert--info sq-mb-16">
                {{ \App\Support\LiccardiVolumeSconto::messaggioPreventivo() }}
                <span class="sq-text-muted"> (nel carrello: {{ (int) ($lv['righe_liccardi'] ?? 0) }} spedizioni Liccardi)</span>
            </div>
        @elseif ($lv['applicato'] ?? false)
            <div class="sq-alert sq-alert--success sq-mb-16">
                Sconto volume Liccardi applicato: −{{ \App\Support\ImportoEuro::format((float) ($lv['sconto_totale'] ?? 0)) }}
                su {{ (int) ($lv['righe_liccardi'] ?? 0) }} spedizioni (−{{ \App\Support\ImportoEuro::format(\App\Support\LiccardiVolumeSconto::EURO_PER_SPEDIZIONE) }} ciascuna).
            </div>
        @endif

        <div class="sq-cart-stack">
            <div class="sq-cart-line-outer sq-cart-head-outer">
                <div class="sq-cart-card sq-cart-line-body">
                    <div class="sq-cart-grid sq-cart-grid-head">
                        <div>Codice</div>
                        <div>Servizio</div>
                        <div>Destino</div>
                        <div class="sq-cart-valor-head">
                            @include('partials.th-importo-iva-inclusa')
                        </div>
                    </div>
                </div>
                <div class="sq-cart-trash-spacer" aria-hidden="true"></div>
            </div>

            @foreach ($items as $it)
                @php
                    $ind = is_array($it['indirizzi'] ?? null) ? $it['indirizzi'] : [];
                    $dest = is_array($ind['destinazione'] ?? null) ? $ind['destinazione'] : [];
                    $nomeDest = trim((string) (($dest['nome'] ?? '') . ' ' . ($dest['cognome'] ?? '')));
                    if ($nomeDest === '') {
                        $nomeDest = trim((string) ($dest['nome_destinatario'] ?? ''));
                    }
                    if ($nomeDest === '') {
                        $nomeDest = trim((string) ($it['nome_destinatario_linea'] ?? ''));
                    }
                    if ($nomeDest === '') {
                        $nomeDest = trim((string) ($dest['ragione_sociale'] ?? ''));
                    }
                    $capDest = trim((string) ($dest['cap'] ?? ''));
                    $cittaDest = trim((string) ($dest['comune'] ?? ''));
                    $provDest = strtoupper(substr(trim((string) ($dest['provincia'] ?? '')), 0, 2));
                    $geoDest = trim($cittaDest.($provDest !== '' ? '/'.$provDest : '').($capDest !== '' ? ' — CAP '.$capDest : ''));
                    $corriereNome = trim((string) ($it['corriere_nome_visualizzato'] ?? $it['corriere_nome'] ?? '')) ?: '—';
                    $tipoNome = trim((string) ($it['tipo_spedizione_nome'] ?? ''));
                    $rawId = trim((string) ($it['id'] ?? ''));
                    $codiceRiga = $rawId !== ''
                        ? strtoupper(substr(str_replace('cart_', '', $rawId), 0, 8))
                        : '#'.($loop->iteration);
                    $importoStandard = \App\Support\TariffaSpedizioneClienteIvato::calcolaDaNetto(
                        (float) ($it['netto_iva_esc'] ?? 0),
                        $aliquotaIva,
                        0,
                    );
                    $importoWallet = \App\Support\TariffaSpedizioneClienteIvato::calcolaDaNetto(
                        (float) ($it['netto_wallet_iva_esc'] ?? $it['netto_iva_esc'] ?? 0),
                        $aliquotaIva,
                        0,
                    );
                @endphp
                <div class="sq-cart-line-outer">
                    <div class="sq-cart-card sq-cart-line-body">
                        <div class="sq-cart-grid">
                            <div class="sq-cart-codice-cell">
                                <strong>{{ $codiceRiga }}</strong>
                            </div>
                            <div>
                                <strong>{{ $corriereNome }}</strong>
                                @if ($tipoNome !== '')
                                    <span class="sq-cart-meta">{{ $tipoNome }}</span>
                                @endif
                            </div>
                            <div>
                                {{ $nomeDest !== '' ? $nomeDest : '—' }}
                                @if ($geoDest !== '')
                                    <span class="sq-cart-meta">{{ $geoDest }}</span>
                                @endif
                            </div>
                            <div class="sq-cart-valor-cell">
                                @include('partials.due-prezzi-standard-wallet', [
                                    'prezzoStandard' => $importoStandard,
                                    'prezzoWallet' => $importoWallet,
                                    'compact' => true,
                                ])
                            </div>
                        </div>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('carrello.rimuovi') }}"
                        class="sq-cart-remove-form"
                        onsubmit="return confirm('Rimuovere questa spedizione dal carrello?');"
                    >
                        @csrf
                        @if (! empty($it['id']))
                            <input type="hidden" name="item_id" value="{{ $it['id'] }}">
                        @else
                            <input type="hidden" name="item_index" value="{{ $loop->index }}">
                        @endif
                        <button type="submit" class="sq-cart-btn-icon-trash" title="Elimina" aria-label="Elimina spedizione dal carrello">
                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="sq-cart-line-outer sq-cart-summary-outer">
            <div class="sq-cart-card sq-cart-line-body">
                <div class="sq-cart-grid">
                    <div class="sq-cart-summary-actions">
                        <a href="{{ route('home') }}" class="sq-cart-btn sq-cart-btn--outline">
                            <i class="fas fa-plus" aria-hidden="true"></i> Nuova spedizione
                        </a>
                    </div>
                    <div class="sq-cart-valor-cell sq-cart-summary-valor">
                        @include('partials.due-prezzi-standard-wallet', [
                            'prezzoStandard' => $totaleIvatoStandard,
                            'prezzoWallet' => $totaleIvatoWallet,
                        ])
                        @auth
                            @if (auth()->user()->hasVerifiedEmail())
                                <form
                                    method="POST"
                                    action="{{ route('carrello.conferma') }}"
                                    class="sq-cart-conferma-form"
                                    onsubmit="return confirm('Confermare l\'ordine? Il carrello verrà svuotato e potrai pagare dalla pagina ordine.');"
                                >
                                    @csrf
                                    <button type="submit" class="sq-cart-btn sq-cart-btn--primary">Conferma ordine</button>
                                </form>
                            @else
                                <p class="sq-cart-auth-hint">Verifica l’indirizzo email per confermare l’ordine.</p>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="sq-cart-btn sq-cart-btn--primary">Accedi per confermare</a>
                        @endauth
                    </div>
                </div>
            </div>
            <div class="sq-cart-trash-spacer" aria-hidden="true"></div>
        </div>
    @endif
</div>
@endsection
