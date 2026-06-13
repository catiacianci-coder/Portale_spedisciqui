@extends('layouts.app')

@section('content')
<div class="sq-page-preventivi sq-mb-24">
    <h1 class="sq-h1-carrello sq-mb-12">Rubrica destinatari</h1>
    <p class="sq-text-muted sq-m-0 sq-mb-18">
        Destinatari salvati: puoi richiamarli dalla pagina <strong>Indirizzi</strong> durante una spedizione, oppure salvarne uno nuovo spuntando l’opzione in fondo al modulo.
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-14">{{ session('ok') }}</div>
    @endif

    <div class="sq-mb-16">
        <a href="{{ route('destinatari.create') }}" class="sq-btn-primary">Aggiungi destinatario</a>
    </div>

    @if ($destinatari->isEmpty())
        <p class="sq-text-muted">Nessun destinatario salvato.</p>
    @else
        <div class="sq-table-wrap">
            <table class="sq-table">
                <thead>
                    <tr class="sq-thead-row sq-thead-row--neutral">
                        <th class="sq-th">Riferimento</th>
                        <th class="sq-th">Indirizzo</th>
                        <th class="sq-th">Contatti</th>
                        <th class="sq-th sq-th--right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($destinatari as $d)
                        <tr>
                            <td class="sq-td">
                                <div class="sq-fw-700">{{ $d->denominazione_ragione_sociale ?: trim($d->nome.' '.$d->cognome) ?: '—' }}</div>
                                @if ($d->denominazione_ragione_sociale && trim($d->nome.$d->cognome) !== '')
                                    <div class="sq-text-muted sq-text-13">{{ trim($d->nome.' '.$d->cognome) }}</div>
                                @endif
                            </td>
                            <td class="sq-td sq-td--muted">
                                {{ $d->indirizzo }} {{ $d->civico }}<br>
                                {{ $d->cap }} {{ $d->citta }} ({{ $d->provincia }})
                            </td>
                            <td class="sq-td sq-td--muted sq-text-13">
                                {{ $d->telefono }}<br>
                                {{ $d->email }}
                            </td>
                            <td class="sq-td sq-td--right">
                                <div class="sq-ordini-actions-icons" style="flex-wrap:wrap;max-width:200px;margin-left:auto;">
                                    <a href="{{ route('destinatari.edit', $d) }}" class="sq-ordini-icon-action sq-ordini-icon-action--view" title="Modifica"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('destinatari.duplica', $d) }}" class="sq-ordini-pay-form-inline">@csrf
                                        <button type="submit" class="sq-ordini-icon-action sq-ordini-icon-action--view" title="Duplica"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('destinatari.destroy', $d) }}" class="sq-ordini-pay-form-inline" onsubmit="return confirm('Eliminare questo destinatario?');">@csrf @method('DELETE')
                                        <button type="submit" class="sq-ordini-icon-action" title="Elimina" style="color:var(--sq-error-text);"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
