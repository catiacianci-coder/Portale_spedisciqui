@extends('layouts.app')

@section('pageBanner')
    <x-sq-page-banner
        variant="backoffice"
        :title="'Ticket #' . $ticket->id"
        icon="fa-headset"
        :parent-href="route('backoffice.tickets.index')"
        class="sq-page-banner--full"
    />
@endsection

@section('content')
<div class="sq-bo-page-wrap assistenza-bo-page">
    <p class="assistenza-back sq-mb-12">
        <a href="{{ route('backoffice.tickets.index', array_filter(['stato' => request('stato')])) }}">← Elenco ticket</a>
    </p>

    @if (session('status'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="sq-alert sq-alert--error sq-mb-16" role="alert">
            @foreach ($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <div class="assistenza-bo-detail">
        <aside class="assistenza-bo-meta" aria-label="Metadati ticket">
            <h3>Utente</h3>
            <dl>
                <dt>Nome</dt>
                <dd>{{ $ticket->user?->name ?? '—' }}</dd>
                <dt>E-mail</dt>
                <dd><a href="mailto:{{ e($ticket->user?->email) }}">{{ $ticket->user?->email ?? '—' }}</a></dd>
            </dl>
            @if ($ticket->tipoProblema)
                <h3 class="sq-mt-16">Tipo di problema</h3>
                <dl>
                    <dt></dt>
                    <dd>{{ $ticket->tipoProblema->nome }}</dd>
                </dl>
            @endif
            @if ($ticket->ordine || $ticket->spedizione)
                <h3 class="sq-mt-16">Riferimenti</h3>
                <dl>
                    @if ($ticket->ordine)
                        <dt>Ordine</dt>
                        <dd>{{ $ticket->ordine->id }}</dd>
                    @endif
                    @if ($ticket->spedizione)
                        <dt>Etichetta / spedizione</dt>
                        <dd>
                            {{ $ticket->spedizione->codice_interno ?? ('#'.$ticket->spedizione_id) }}
                            @if ($ticket->spedizione->codigoRastreio())
                                <div class="sq-mt-6" style="font-size:12px;word-break:break-all;"><strong>Tracking:</strong> {{ $ticket->spedizione->codigoRastreio() }}</div>
                            @endif
                        </dd>
                    @endif
                </dl>
            @endif
            @if ($spedizioniRiferimento->isNotEmpty())
                @if (in_array($ticket->tipoProblema?->codigo, [
                    \App\Models\TicketTipoProblema::CODIGO_ETIQUETA_NAO_GERADA,
                    \App\Models\TicketTipoProblema::CODIGO_TRACKING,
                    \App\Models\TicketTipoProblema::CODIGO_RIPRENOTAZIONE_RITIRO,
                ], true))
                    <h3 class="sq-mt-16">Etichette (richiesta)</h3>
                    <dl>
                        <dt>Codici interni</dt>
                        <dd class="assistenza-bo-etichette-stack">{{ $spedizioniRiferimento->map(fn ($sp) => $sp->codice_interno !== null && $sp->codice_interno !== '' ? $sp->codice_interno : '#'.$sp->id)->join("\n") }}</dd>
                    </dl>
                @else
                    <h3 class="sq-mt-16">Spedizioni referenziate</h3>
                    @foreach ($spedizioniRiferimento as $sp)
                        <dl class="assistenza-bo-sped-block">
                            <dt>ID</dt>
                            <dd>{{ $sp->id }}</dd>
                            <dt>Codice interno</dt>
                            <dd>{{ $sp->codice_interno ?? '—' }}</dd>
                            <dt>Corriere</dt>
                            <dd>{{ $sp->corriere ?? '—' }}</dd>
                            <dt>Servizio</dt>
                            <dd>{{ $sp->service_description ?? '—' }}</dd>
                            <dt>Tracking</dt>
                            <dd style="word-break:break-all;">{{ $sp->codigoRastreio() ?? '—' }}</dd>
                        </dl>
                    @endforeach
                @endif
            @endif
            @if ($ticket->tipoProblema?->codigo === \App\Models\TicketTipoProblema::CODIGO_FATTURA_MANCANTE)
                <h3 class="sq-mt-16">Periodo fattura</h3>
                <dl>
                    <dt>Mese / Anno</dt>
                    <dd>{{ $ticket->campo_1 ?? '—' }} / {{ $ticket->campo_2 ?? '—' }}</dd>
                </dl>
            @endif
            @if ($ticket->tipoProblema?->codigo === \App\Models\TicketTipoProblema::CODIGO_TRACKING && ($ticket->campo_2 || $ticket->campo_3))
                <h3 class="sq-mt-16">Riferimento tracking</h3>
                <dl>
                    @if ($ticket->campo_2)
                        <dt>Modalità</dt>
                        <dd>{{ $ticket->campo_2 }}</dd>
                    @endif
                    @if ($ticket->campo_3)
                        <dt>Codice / tracking indicato</dt>
                        <dd>{{ $ticket->campo_3 }}</dd>
                    @endif
                </dl>
            @endif
            @if ($ticket->tipoProblema?->codigo === \App\Models\TicketTipoProblema::CODIGO_RIPRENOTAZIONE_RITIRO && $ticket->campo_3)
                <h3 class="sq-mt-16">Riprenotazione ritiro</h3>
                <dl>
                    <dt>Corriere</dt>
                    <dd>{{ $ticket->campo_3 }}</dd>
                </dl>
            @endif
            @if ($ticket->tipoProblema?->codigo === \App\Models\TicketTipoProblema::CODIGO_RICHIESTE_PREMIUM)
                <h3 class="sq-mt-16">Richiesta tariffe scontate</h3>
                <dl>
                    <dt>Nome impresa</dt>
                    <dd>{{ $ticket->campo_1 ?? '—' }}</dd>
                    <dt>Partita IVA</dt>
                    <dd>{{ $ticket->campo_2 ?? '—' }}</dd>
                    <dt>Indirizzo di mittente</dt>
                    <dd>{{ $ticket->campo_3 ?? '—' }}</dd>
                    <dt>Spedizioni settimanali</dt>
                    <dd>{{ $ticket->campo_4 ?? '—' }}</dd>
                </dl>
            @endif
            @if ($ticket->tipoProblema?->codigo === \App\Models\TicketTipoProblema::CODIGO_COMMERCIALE)
                <h3 class="sq-mt-16">Contatto commerciale</h3>
                <dl>
                    <dt>Nome e cognome</dt>
                    <dd>{{ $ticket->campo_1 ?? '—' }}</dd>
                    <dt>Nome impresa</dt>
                    <dd>{{ $ticket->campo_2 ?? '—' }}</dd>
                    <dt>Partita IVA</dt>
                    <dd>{{ $ticket->campo_3 ?? '—' }}</dd>
                </dl>
            @endif
            <h3 class="sq-mt-16">Campi aggiuntivi</h3>
            <dl>
                @for ($i = 1; $i <= 9; $i++)
                    @if (in_array($ticket->tipoProblema?->codigo, [
                        \App\Models\TicketTipoProblema::CODIGO_ETIQUETA_NAO_GERADA,
                        \App\Models\TicketTipoProblema::CODIGO_TRACKING,
                        \App\Models\TicketTipoProblema::CODIGO_RIPRENOTAZIONE_RITIRO,
                        \App\Models\TicketTipoProblema::CODIGO_FATTURA_MANCANTE,
                        \App\Models\TicketTipoProblema::CODIGO_COMMERCIALE,
                        \App\Models\TicketTipoProblema::CODIGO_RICHIESTE_PREMIUM,
                    ], true) && in_array($i, [1, 2, 3, 4], true))
                        @continue
                    @endif
                    @php
                        $campo = 'campo_'.$i;
                        $val = $ticket->{$campo};
                        $rotulo = match ($i) {
                            2 => 'Campo 2 (ID spedizione)',
                            3 => 'Campo 3 (corriere)',
                            4 => 'Campo 4 (testo cliente)',
                            default => 'Campo '.$i,
                        };
                    @endphp
                    <dt>{{ $rotulo }}</dt>
                    <dd>{{ $val !== null && $val !== '' ? $val : '—' }}</dd>
                @endfor
            </dl>
        </aside>

        <div class="assistenza-bo-main">
            <header class="assistenza-tk-brand">
                <strong>Ticket #{{ $ticket->id }}</strong>
                <span><a href="mailto:{{ e($ticket->user?->email) }}">{{ $ticket->user?->name ?? 'Utente' }}</a></span>
                <span class="assistenza-tk-date">Inviato il {{ $ticket->created_at?->timezone(config('app.timezone'))->format('d/m/Y') }} alle {{ $ticket->created_at?->timezone(config('app.timezone'))->format('H:i') }}</span>
            </header>
            <div class="assistenza-tk-oggetto">{{ $ticket->oggetto }}</div>
            <div class="assistenza-tk-thread">
                @foreach ($ticket->messaggi as $msg)
                    <article class="assistenza-tk-msg">
                        <div class="assistenza-tk-msg__head">
                            <span class="assistenza-tk-badge {{ $msg->is_staff ? 'is-staff' : '' }}">{{ $msg->is_staff ? 'Team' : 'Cliente' }}</span>
                            <span>{{ $msg->user?->name ?? '—' }}</span>
                            <span>·</span>
                            <span>{{ $msg->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="assistenza-tk-msg__body">{{ $msg->body }}</div>
                    </article>
                @endforeach
            </div>

            <div class="assistenza-bo-actions">
                <h3>Rispondi al cliente</h3>
                <form method="post" action="{{ route('backoffice.tickets.mensagem', $ticket) }}">
                    @csrf
                    <textarea name="body" required placeholder="Scrivi la risposta…" aria-label="Messaggio">{{ old('body') }}</textarea>
                    <div class="assistenza-bo-actions__row">
                        <label>
                            Stato dopo questa risposta
                            <select name="ticket_stato_id" required>
                                @foreach ($statiRisposta as $s)
                                    <option value="{{ $s->id }}" @selected((int) old('ticket_stato_id') === $s->id || (old('ticket_stato_id') === null && $ticket->ticket_stato_id === $s->id))>
                                        {{ $s->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="assistenza-btn-submit">Invia risposta</button>
                    </div>
                </form>

                <div class="assistenza-bo-stato-only">
                    <h3>Modifica solo lo stato</h3>
                    <form method="post" action="{{ route('backoffice.tickets.stato', $ticket) }}">
                        @csrf
                        <label>
                            Stato
                            <select name="ticket_stato_id" required>
                                @foreach ($tuttiStati as $s)
                                    <option value="{{ $s->id }}" @selected($ticket->ticket_stato_id === $s->id)>{{ $s->nome }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="assistenza-btn-sec">Aggiorna stato</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
