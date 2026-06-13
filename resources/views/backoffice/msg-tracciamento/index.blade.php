@extends('layouts.app')

@section('content')
<div class="sq-page-1100">
    <p class="sq-intro">
        <a href="{{ route('backoffice.index') }}" class="sq-header-link">← Menu back office</a>
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-18">{{ session('ok') }}</div>
    @endif

    <form method="GET" action="{{ route('backoffice.utilities.msg_tracciamento.index') }}" class="sq-bo-param-form sq-mb-24">
        <div class="sq-bo-param-grid">
            <div>
                <label for="q" class="sq-label">Cerca nel messaggio ricevuto</label>
                <input
                    type="text"
                    id="q"
                    name="q"
                    value="{{ $testo }}"
                    class="sq-bo-param-input"
                    placeholder="Es. Parcel does not exist"
                >
            </div>
            <div>
                <label class="sq-label">Corrieri da includere</label>
                <select name="corrieri[]" multiple size="8" class="sq-bo-param-input">
                    @foreach ($corrieri as $c)
                        <option value="{{ $c->id }}" @selected(in_array($c->id, $corrieriSelezionati, true))>
                            #{{ $c->id }} — {{ $c->nome_visualizzato ?: $c->nome_corriere }}
                        </option>
                    @endforeach
                </select>
                <p class="sq-text-muted sq-font-sm sq-mt-8">Tieni premuto Ctrl (o Cmd) per selezione multipla. Nessuna selezione = tutti i corrieri.</p>
            </div>
            <div>
                <label class="sq-label sq-d-block sq-mb-8">
                    <input type="checkbox" name="solo_vuoti" value="1" @checked($soloVuoti)>
                    Solo record con msg per cliente vuoto
                </label>
            </div>
        </div>
        <div class="sq-mt-16">
            <button type="submit" class="sq-btn-primary">Cerca</button>
            <a href="{{ route('backoffice.utilities.msg_tracciamento.create') }}" class="sq-btn-secondary sq-ml-8">Nuovo record</a>
        </div>
    </form>

    @if ($messaggi->isEmpty())
        <p class="sq-text-main sq-m-0">Nessun messaggio trovato.</p>
    @else
        <div class="sq-table-wrap sq-table-wrap--warm">
            <table class="sq-table">
                <thead>
                    <tr class="sq-thead-row sq-thead-row--warm">
                        <th class="sq-th sq-th--warm">
                            <input type="checkbox" data-select-all-msg-tracciamento>
                        </th>
                        <th class="sq-th sq-th--warm">ID</th>
                        <th class="sq-th sq-th--warm">Corriere</th>
                        <th class="sq-th sq-th--warm">Msg ricevuto</th>
                        <th class="sq-th sq-th--warm">Msg per cliente</th>
                        <th class="sq-th sq-th--warm">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($messaggi as $m)
                        <tr>
                            <td class="sq-td sq-td--border-warm sq-td--top">
                                <input
                                    type="checkbox"
                                    name="ids[]"
                                    value="{{ $m->id }}"
                                    class="js-msg-tracciamento-row"
                                    form="msg-tracciamento-bulk-form"
                                >
                            </td>
                            <td class="sq-td sq-td--border-warm sq-td--top">{{ $m->id }}</td>
                            <td class="sq-td sq-td--border-warm sq-td--top">
                                #{{ $m->corriere_id }}
                                @if ($m->corriere)
                                    — {{ $m->corriere->nome_visualizzato ?: $m->corriere->nome_corriere }}
                                @endif
                            </td>
                            <td class="sq-td sq-td--border-warm sq-td--top">{{ $m->msg_ricevuto }}</td>
                            <td class="sq-td sq-td--border-warm sq-td--top">
                                @if ($m->haMessaggioCliente())
                                    {{ $m->msg_per_cliente }}
                                @else
                                    <span class="sq-text-muted">—</span>
                                @endif
                            </td>
                            <td class="sq-td sq-td--border-warm sq-td--top sq-nowrap">
                                <a href="{{ route('backoffice.utilities.msg_tracciamento.edit', $m) }}" class="sq-header-link">Modifica</a>
                                <form method="POST" action="{{ route('backoffice.utilities.msg_tracciamento.destroy', $m) }}" class="sq-d-inline" onsubmit="return confirm('Eliminare questo record?');">
                                    @csrf
                                    @method('DELETE')
                                    @foreach (request()->only(['q', 'corrieri', 'solo_vuoti', 'page']) as $key => $value)
                                        @if (is_array($value))
                                            @foreach ($value as $item)
                                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                            @endforeach
                                        @elseif ($value !== null && $value !== '')
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach
                                    <button type="submit" class="sq-header-link" style="background:none;border:none;padding:0;cursor:pointer;">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form
            id="msg-tracciamento-bulk-form"
            method="POST"
            action="{{ route('backoffice.utilities.msg_tracciamento.bulk_update') }}"
            class="sq-bo-param-form sq-mt-24"
        >
            @csrf
            @foreach (request()->only(['q', 'corrieri', 'solo_vuoti', 'page']) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $item)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                    @endforeach
                @elseif ($value !== null && $value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <h2 class="sq-h2 sq-mb-12">Aggiornamento multiplo</h2>
            <label for="msg_per_cliente_bulk" class="sq-label">Msg per cliente (applicato ai record selezionati)</label>
            <textarea id="msg_per_cliente_bulk" name="msg_per_cliente" rows="3" class="sq-bo-param-input" required></textarea>
            <div class="sq-mt-12">
                <button type="submit" class="sq-btn-primary">Applica ai selezionati</button>
            </div>
        </form>

        <div class="sq-mt-18">
            {{ $messaggi->links() }}
        </div>
    @endif
</div>

<script>
(() => {
    const selectAll = document.querySelector('[data-select-all-msg-tracciamento]');
    if (!selectAll) {
        return;
    }
    selectAll.addEventListener('change', () => {
        document.querySelectorAll('.js-msg-tracciamento-row').forEach((el) => {
            el.checked = selectAll.checked;
        });
    });
})();
</script>
@endsection
