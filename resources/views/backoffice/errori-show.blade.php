@extends('layouts.app')

@section('pageBanner')
    <x-sq-page-banner
        variant="backoffice"
        :title="'Errore #' . $errore->id"
        icon="fa-bug"
        :parent-href="route('backoffice.errori.index')"
        class="sq-page-banner--full"
    />
@endsection

@section('content')
<div class="sq-page-960">
    <p class="sq-mb-14"><a href="{{ route('backoffice.errori.index') }}" class="sq-header-link">← Elenco errori</a></p>

    <dl class="sq-paid-dl sq-mb-24">
        <div class="sq-paid-dl-row">
            <dt>Data</dt>
            <dd>{{ $errore->created_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>
        </div>
        <div class="sq-paid-dl-row">
            <dt>Utente</dt>
            <dd>
                @if ($errore->user)
                    ID {{ $errore->user_id }} — {{ $errore->user->email }}
                @elseif ($errore->user_id)
                    ID {{ $errore->user_id }} (account non trovato)
                @else
                    Non autenticato / ospite
                @endif
            </dd>
        </div>
        <div class="sq-paid-dl-row">
            <dt>HTTP</dt>
            <dd>{{ $errore->http_status }}</dd>
        </div>
        <div class="sq-paid-dl-row">
            <dt>Classe eccezione</dt>
            <dd><code class="sq-code">{{ $errore->exception_class }}</code></dd>
        </div>
        <div class="sq-paid-dl-row">
            <dt>Metodo</dt>
            <dd>{{ $errore->metodo ?? '—' }}</dd>
        </div>
        <div class="sq-paid-dl-row">
            <dt>IP</dt>
            <dd>{{ $errore->ip ?? '—' }}</dd>
        </div>
        <div class="sq-paid-dl-row">
            <dt>URL</dt>
            <dd class="sq-word-break">{{ $errore->url ?? '—' }}</dd>
        </div>
    </dl>

    <h2 class="sq-h2-card sq-mb-10">Messaggio</h2>
    <pre class="sq-pre-json sq-mb-24">{{ $errore->messaggio }}</pre>

    @if ($errore->trace)
        <h2 class="sq-h2-card sq-mb-10">Stack trace</h2>
        <pre class="sq-pre-json">{{ $errore->trace }}</pre>
    @endif
</div>
@endsection
