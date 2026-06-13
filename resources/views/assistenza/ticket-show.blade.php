@extends('layouts.app')
@section('content')
<div class="sq-bleed-layout assistenza-page">
    <x-sq-page-banner :title="'Richiesta #' . $ticket->id" icon="fa-ticket" class="sq-page-banner--full" />

    <div class="assistenza-page__inner">
        <p class="assistenza-back"><a href="{{ route('assistenza.index') }}">← Assistenza e richieste</a></p>

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

        <header class="assistenza-tk-brand">
            <strong>Ticket #{{ $ticket->id }}</strong>
            · Stato: {{ $ticket->stato?->nome ?? '—' }}
            · {{ $ticket->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
        </header>
        <div class="assistenza-tk-oggetto">{{ $ticket->oggetto }}</div>
        <div class="assistenza-tk-thread">
            @foreach ($ticket->messaggi as $msg)
                <div class="assistenza-tk-msg">
                    <div class="assistenza-tk-msg__head">
                        {{ $msg->is_staff ? 'Team Spedisciqui' : ($msg->user?->name ?? 'Io') }}
                        — {{ $msg->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                    </div>
                    <div class="assistenza-tk-msg__body">{{ $msg->body }}</div>
                </div>
            @endforeach
        </div>

        @if ($ticket->stato?->codigo === \App\Models\TicketStato::CODIGO_RESOLVIDO)
            <p class="assistenza-resolved-note">
                Questa richiesta è contrassegnata come risolta. Per un nuovo argomento apri una
                <a href="{{ route('assistenza.index') }}#nova-richiesta">nuova richiesta</a>.
            </p>
        @elseif ($ticket->clientePodeEnviarNovaMensagem())
            <div class="assistenza-tk-form">
                <form method="post" action="{{ route('assistenza.ticket.mensagem', $ticket) }}">
                    @csrf
                    <label for="body">Nuovo messaggio</label>
                    <textarea id="body" name="body" required placeholder="Scrivi il tuo messaggio…">{{ old('body') }}</textarea>
                    <button type="submit" class="assistenza-btn-submit">Invia</button>
                </form>
            </div>
        @else
            <div class="assistenza-tk-wait" role="status">
                <strong>In attesa di risposta dal team Spedisciqui</strong>
                <p>Non appena il team risponderà potrai inviare un nuovo messaggio qui.</p>
            </div>
        @endif
    </div>
</div>
@endsection
