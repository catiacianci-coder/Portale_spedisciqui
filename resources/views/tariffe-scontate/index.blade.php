@extends('layouts.app')
@section('content')
<div class="sq-bleed-layout">
    <x-sq-page-banner title="Tariffe scontate" icon="fa-tags" class="sq-page-banner--full" />

    <div class="home-spedizione-wrap sq-tariffe-scontate-page">
        @if (session('status'))
            <div class="sq-alert sq-alert--success sq-mb-16">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="sq-alert sq-alert--error sq-mb-16" role="alert">
                @foreach ($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        <div class="sq-card sq-card--p-14-16 sq-mb-20">
            @if ($versoes->isEmpty())
                <p class="sq-m-0 sq-text-muted">Informazioni sulle tariffe scontate in arrivo.</p>
            @else
                @php $v = $versoes->first(); @endphp
                <div class="sq-legal-body">
                    {!! $v->conteudo_html !!}
                </div>
            @endif
        </div>

        <div class="sq-card sq-card--p-14-16">
            <h2 class="sq-h2-brand sq-mb-12">Richiedi l'accesso</h2>
            <p class="sq-m-0 sq-mb-16 sq-text-wallet-body">
                Compila il modulo: la richiesta verrà inviata al team commerciale come ticket di assistenza.
            </p>

            @auth
                @if (! Auth::user()->hasVerifiedEmail())
                    <div class="sq-alert sq-alert--info-warm sq-mb-0">
                        Verifica l'email del tuo account per poter inviare la richiesta.
                    </div>
                @else
                    <form method="POST" action="{{ route('tariffe_scontate.store') }}" class="sq-rimborso-form">
                        @csrf
                        <label class="sq-filtri-label" for="nome_impresa">Nome impresa</label>
                        <input id="nome_impresa" name="nome_impresa" type="text" class="sq-filtri-email-input sq-mb-12" required maxlength="200"
                               value="{{ old('nome_impresa', $defaults['nome_impresa'] ?? '') }}" autocomplete="organization">

                        <label class="sq-filtri-label" for="partita_iva">Partita IVA</label>
                        <input id="partita_iva" name="partita_iva" type="text" class="sq-filtri-email-input sq-mb-12" required maxlength="20"
                               value="{{ old('partita_iva', $defaults['partita_iva'] ?? '') }}" autocomplete="off">

                        <label class="sq-filtri-label" for="indirizzo_mittente">Indirizzo di mittente</label>
                        <input id="indirizzo_mittente" name="indirizzo_mittente" type="text" class="sq-filtri-email-input sq-mb-12" required maxlength="500"
                               value="{{ old('indirizzo_mittente', $defaults['indirizzo_mittente'] ?? '') }}" autocomplete="street-address">

                        <label class="sq-filtri-label" for="spedizioni_settimanali">Numero di spedizioni settimanali</label>
                        <input id="spedizioni_settimanali" name="spedizioni_settimanali" type="number" min="1" max="100000" class="sq-filtri-email-input sq-mb-12" required
                               value="{{ old('spedizioni_settimanali') }}">

                        <button type="submit" class="sq-filtri-submit">Invia richiesta</button>
                    </form>
                @endif
            @else
                <p class="sq-m-0">
                    <a href="{{ route('login') }}" class="sq-link-brand">Accedi</a>
                    per compilare il modulo e inviare la richiesta.
                </p>
            @endauth
        </div>
    </div>
</div>
@endsection
