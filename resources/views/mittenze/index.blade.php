@extends('layouts.app')

@section('content')
<div class="sq-page-preventivi sq-mb-24">
    <h1 class="sq-h1-carrello sq-mb-12">Rubrica mittenti</h1>
    <p class="sq-text-muted sq-m-0 sq-mb-18">
        Il mittente <strong>preferito</strong> imposta il CAP di origine predefinito in “Nuova spedizione”.
        La <strong>sede di fatturazione</strong> coincide con l’indirizzo in Anagrafica e si modifica solo da lì.
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-14">{{ session('ok') }}</div>
    @endif
    @if (session('info'))
        <div class="sq-alert sq-alert--info-warm sq-mb-14">{{ session('info') }}</div>
    @endif
    @if ($errors->has('mittenza'))
        <div class="sq-alert sq-alert--error sq-mb-14">{{ $errors->first('mittenza') }}</div>
    @endif

    <div class="sq-mb-16">
        <a href="{{ route('mittenze.create') }}" class="sq-btn-primary">Aggiungi mittente</a>
    </div>

    @if ($mittenti->isEmpty())
        <p class="sq-text-muted">Nessun mittente salvato.</p>
    @else
        <div class="sq-table-wrap">
            <table class="sq-table">
                <thead>
                    <tr class="sq-thead-row sq-thead-row--neutral">
                        <th class="sq-th">Riferimento</th>
                        <th class="sq-th">Indirizzo</th>
                        <th class="sq-th">Contatti</th>
                        <th class="sq-th">Note</th>
                        <th class="sq-th sq-th--right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mittenti as $m)
                        <tr>
                            <td class="sq-td">
                                <div class="sq-fw-700">{{ $m->denominazione_ragione_sociale ?: trim($m->nome.' '.$m->cognome) ?: '—' }}</div>
                                @if ($m->denominazione_ragione_sociale && trim($m->nome.$m->cognome) !== '')
                                    <div class="sq-text-muted sq-text-13">{{ trim($m->nome.' '.$m->cognome) }}</div>
                                @endif
                                <div class="sq-mt-6 sq-text-12">
                                    @if ($m->is_fatturazione)
                                        <span class="sq-badge sq-badge--muted">Fatturazione</span>
                                    @endif
                                    @if ($m->is_preferito)
                                        <span class="sq-badge sq-badge--muted">Preferito</span>
                                    @endif
                                </div>
                            </td>
                            <td class="sq-td sq-td--muted">
                                {{ $m->indirizzo }} {{ $m->civico }}<br>
                                {{ $m->cap }} {{ $m->citta }} ({{ $m->provincia }})
                            </td>
                            <td class="sq-td sq-td--muted sq-text-13">
                                {{ $m->telefono }}<br>
                                {{ $m->email }}
                            </td>
                            <td class="sq-td sq-td--muted sq-text-13">
                                @if ($m->varie1 || $m->varie2 || $m->varie3 || $m->varie4)
                                    {{ implode(' · ', array_filter([$m->varie1, $m->varie2, $m->varie3, $m->varie4])) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="sq-td sq-td--right">
                                <div class="sq-ordini-actions-icons" style="flex-wrap:wrap;max-width:220px;margin-left:auto;">
                                    @if (! $m->is_fatturazione)
                                        <a href="{{ route('mittenze.edit', $m) }}" class="sq-ordini-icon-action sq-ordini-icon-action--view" title="Modifica"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                        <form method="POST" action="{{ route('mittenze.duplica', $m) }}" class="sq-ordini-pay-form-inline">@csrf
                                            <button type="submit" class="sq-ordini-icon-action sq-ordini-icon-action--view" title="Duplica"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('mittenze.destroy', $m) }}" class="sq-ordini-pay-form-inline" onsubmit="return confirm('Eliminare questo mittente?');">@csrf @method('DELETE')
                                            <button type="submit" class="sq-ordini-icon-action" title="Elimina" style="color:var(--sq-error-text);"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('mittenze.preferito', $m) }}" class="sq-ordini-pay-form-inline">@csrf
                                        <button type="submit" class="sq-ordini-icon-action sq-ordini-icon-action--view" title="{{ $m->is_preferito ? 'Rimuovi preferito' : 'Imposta come preferito' }}"><i class="{{ $m->is_preferito ? 'fa-solid fa-star' : 'fa-regular fa-star' }}" aria-hidden="true"></i></button>
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
