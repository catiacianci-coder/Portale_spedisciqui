@extends('layouts.app')
@section('content')
<div class="carrello-page sq-page-preventivi">
    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">
            {{ session('ok') }}
        </div>
    @endif

    @if ($errors->has('carrello'))
        <div class="sq-alert sq-alert--error sq-mb-16">
            {{ $errors->first('carrello') }}
        </div>
    @endif

    @if (empty($items))
        <p class="sq-text-666 sq-line-height sq-m-0">
            Il carrello è vuoto.
            <a href="{{ route('home') }}">Clicca qui</a>
            per creare una nuova spedizione.
        </p>
    @else
        @php
            $lv = $liccardiVolume ?? ['applicato' => false, 'righe_liccardi' => 0, 'sconto_totale' => 0.0];
        @endphp
        @if (($lv['righe_liccardi'] ?? 0) > 0 && ! ($lv['applicato'] ?? false))
            <div class="sq-alert sq-alert--info sq-mb-16">
                {{ \App\Support\LiccardiVolumeSconto::messaggioPreventivo() }}
                <span class="sq-text-muted"> (nel carrello: {{ (int) ($lv['righe_liccardi'] ?? 0) }} spedizioni Liccardi)</span>
            </div>
        @elseif ($lv['applicato'] ?? false)
            <div class="sq-alert sq-alert--success sq-mb-16">
                Sconto volume Liccardi applicato: −{{ number_format((float) ($lv['sconto_totale'] ?? 0), 2, ',', '.') }} €
                su {{ (int) ($lv['righe_liccardi'] ?? 0) }} spedizioni (−{{ number_format(\App\Support\LiccardiVolumeSconto::EURO_PER_SPEDIZIONE, 2, ',', '.') }} € ciascuna).
            </div>
        @endif
        <ul class="sq-carrello-list">
            @foreach ($items as $it)
                <li class="sq-list-item-none">
                    <div class="sq-carrello-row">
                        <div class="sq-carrello-card-wrap">
                            @include('partials.spedizione-card-operativa', [
                                'it' => $it,
                                'spedCardWhiteBg' => true,
                                'spedCardCompact' => true,
                            ])
                        </div>
                        <form method="POST" action="{{ route('carrello.rimuovi') }}" class="sq-form-shrink"
                              onsubmit="return confirm('Rimuovendo questa spedizione perdi tutti i dati associati (indirizzi, pacco, servizi e prezzo). L’operazione non è annullabile. Vuoi procedere?');">
                            @csrf
                            @if (! empty($it['id']))
                                <input type="hidden" name="item_id" value="{{ $it['id'] }}">
                            @else
                                <input type="hidden" name="item_index" value="{{ $loop->index }}">
                            @endif
                            <button type="submit" class="sq-ordini-icon-action sq-ordini-icon-action--remove"
                                    title="Rimuovi dal carrello" aria-label="Rimuovi dal carrello">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="sq-carrello-footer-block">
            <div class="sq-carrello-footer-divider" role="presentation" aria-hidden="true"></div>
            <div class="sq-carrello-footer-actions">
                <a href="{{ route('home') }}" class="sq-btn-carrello-action sq-btn-carrello-action--primary">Home / Nuova spedizione</a>
                @auth
                    @if (auth()->user()->hasVerifiedEmail())
                        <form method="POST" action="{{ route('carrello.conferma') }}" class="sq-form-zero sq-carrello-conferma-form">
                            @csrf
                            <button type="submit" class="sq-btn-carrello-action sq-btn-carrello-action--primary">
                                Paga l’ordine in carrello
                            </button>
                        </form>
                    @else
                        <p class="sq-text-muted sq-text-14 sq-m-0 sq-carrello-footer-msg sq-carrello-footer-msg--wrap">Verifica l’indirizzo email per confermare l’ordine e pagare.</p>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="sq-btn-carrello-action sq-btn-carrello-action--primary">Accedi per confermare e pagare</a>
                    <span class="sq-login-hint sq-login-hint--carrello-wrap">Serve un account con email verificata.</span>
                @endauth
            </div>
        </div>
    @endif
</div>
@endsection
