@extends('layouts.app')
@section('content')
<div class="sq-page-960">
    <p class="sq-text-muted sq-mb-18">
        Registro degli errori di server (HTTP 5xx) rilevati durante la navigazione degli utenti.
        In ambiente di sviluppo con debug attivo può comparire la pagina tecnica invece della pagina amichevole, ma l’errore viene comunque registrato qui quando possibile.
    </p>

    @if ($errori->isEmpty())
        <p class="sq-text-main sq-m-0">Nessun errore registrato.</p>
    @else
        <div class="sq-table-scroll">
            <table class="sq-table">
                <thead>
                    <tr class="sq-thead-row sq-thead-row--neutral">
                        <th class="sq-th">Data</th>
                        <th class="sq-th">Utente</th>
                        <th class="sq-th">HTTP</th>
                        <th class="sq-th">Messaggio</th>
                        <th class="sq-th">URL</th>
                        <th class="sq-th"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($errori as $e)
                        <tr>
                            <td class="sq-td sq-nowrap">{{ $e->created_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                            <td class="sq-td sq-nowrap">
                                @if ($e->user)
                                    #{{ $e->user_id }} — {{ $e->user->email }}
                                @elseif ($e->user_id)
                                    #{{ $e->user_id }}
                                @else
                                    <span class="sq-text-muted">Ospite</span>
                                @endif
                            </td>
                            <td class="sq-td">{{ $e->http_status }}</td>
                            <td class="sq-td">{{ \Illuminate\Support\Str::limit($e->messaggio, 120) }}</td>
                            <td class="sq-td sq-text-muted sq-text-14">{{ \Illuminate\Support\Str::limit($e->url, 60) }}</td>
                            <td class="sq-td sq-nowrap">
                                <a href="{{ route('backoffice.errori.show', $e) }}" class="sq-header-link">Dettaglio</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
