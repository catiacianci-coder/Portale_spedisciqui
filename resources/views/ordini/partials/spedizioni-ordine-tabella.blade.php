@php
    use App\Models\stato_spedizione;

    $mostraSelezione = (bool) ($mostraSelezione ?? false);
    $podeEditar = (bool) ($podeEditar ?? false);
    $cardTitle = (string) ($cardTitle ?? 'Spedizioni dell\'ordine');
    $colspanVazio = $mostraSelezione ? 9 : 8;
    $spedizioniOrdinate = $ordine->spedizioni->sortByDesc('id')->values();
    $totaleIvatoOrdine = (float) ($totaleIvatoOrdine ?? 0);
@endphp
<div class="sq-ordine-remessas-card">
    @if ($mostraSelezione)
        <form method="post" action="{{ route('ordini.spedizioni.elimina-marcate', $ordine) }}" id="form-elimina-spedizioni-marcate" class="sq-ordine-remessas-form">
            @csrf
    @endif
    <div class="sq-ordine-remessas-card-head">
        <strong>{{ $cardTitle }}</strong>
        @isset($cardMeta)
            <span class="sq-ordine-remessas-card-meta">{!! $cardMeta !!}</span>
        @endisset
        @if ($mostraSelezione && $podeEditar)
            <div class="sq-ordine-remessas-toolbar">
                <label class="sq-ordine-remessas-check-all">
                    <input type="checkbox" id="marcar-todos-spedizioni">
                    Seleziona tutte
                </label>
                <button type="submit" class="sq-btn-elimina-marcate" id="btn-elimina-spedizioni-marcate" disabled>Elimina selezionate</button>
            </div>
        @endif
    </div>
    <div class="sq-table-wrap sq-ordine-remessas-table-wrap">
        <table class="sq-table sq-ordine-remessas-table">
            <thead>
                <tr class="sq-thead-row sq-thead-row--neutral">
                    @if ($mostraSelezione)
                        <th class="sq-th" style="width:44px;">&nbsp;</th>
                    @endif
                    <th class="sq-th">Data</th>
                    <th class="sq-th">Codice spedizione</th>
                    <th class="sq-th">Ordine</th>
                    <th class="sq-th">Mittente</th>
                    <th class="sq-th">Destinatario</th>
                    <th class="sq-th">Servizio</th>
                    <th class="sq-th">Servizi aggiuntivi</th>
                    <th class="sq-th sq-th--right">@include('partials.th-importo-iva-inclusa')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($spedizioniOrdinate as $s)
                    @php
                        $annullata = (int) $s->spedizione_stato_id === stato_spedizione::ANNULLATA;
                        $nomeRem = trim((string) ($s->razione_sociale_o ?: trim((string) (($s->nome_o ?? '') . ' ' . ($s->cognome_o ?? '')))));
                        $nomeDest = trim((string) trim((string) (($s->nome_d ?? '') . ' ' . ($s->sobrenome_d ?? ''))));
                        $addrRem = trim(implode(' ', array_filter([
                            trim((string) ($s->indirizzo_o ?? '')),
                            trim((string) ($s->numero_o ?? '')),
                        ])));
                        $addrDest = trim(implode(' ', array_filter([
                            trim((string) ($s->indirizzo_d ?? '')),
                            trim((string) ($s->numero_d ?? '')),
                        ])));
                        $linhaRem2 = trim(implode(' - ', array_filter([
                            trim((string) ($s->frazione_o ?? '')),
                            trim(implode('/', array_filter([trim((string) ($s->citta_o ?? '')), trim((string) ($s->stato_o ?? ''))]))),
                            trim((string) ($s->cap_o ?? '')),
                        ])));
                        $linhaDest2 = trim(implode(' - ', array_filter([
                            trim((string) ($s->frazione_d ?? '')),
                            trim(implode('/', array_filter([trim((string) ($s->citta_d ?? '')), trim((string) ($s->stato_d ?? ''))]))),
                            trim((string) ($s->cap_d ?? '')),
                        ])));
                        $servico = trim((string) ($servizioPerSpedizione[(int) $s->id] ?? $s->service_description ?? $s->corriere ?? ''));
                        $importoIvato = $s->prezzoClienteIvato();
                    @endphp
                    <tr @class(['sq-ordine-remessa--annullata' => $annullata])>
                        @if ($mostraSelezione)
                            <td class="sq-td">
                                @if ($podeEditar && ! $annullata)
                                    <input type="checkbox" name="spedizioni[]" value="{{ $s->id }}" class="chk-spedizione-ordine">
                                @endif
                            </td>
                        @endif
                        <td class="sq-td sq-nowrap">
                            {{ $s->created_at?->format('d/m/Y H:i') ?? '—' }}
                            @if ($annullata)
                                <div class="sq-ordine-remessa-badges">
                                    <span class="sq-badge sq-badge--muted">Annullata</span>
                                </div>
                            @endif
                        </td>
                        <td class="sq-td sq-ordine-remessa-codice">{{ $s->codice_interno ?: '—' }}</td>
                        <td class="sq-td sq-fw-700">{{ $ordine->codice }}</td>
                        <td class="sq-td sq-ordine-remessa-person">
                            <span class="sq-ordine-remessa-nome">{{ $nomeRem !== '' ? $nomeRem : '—' }}</span>
                            @if ($addrRem !== '')
                                <span class="sq-ordine-remessa-indirizzo">{{ $addrRem }}</span>
                            @endif
                            @if ($linhaRem2 !== '')
                                <span class="sq-ordine-remessa-indirizzo">{{ $linhaRem2 }}</span>
                            @endif
                        </td>
                        <td class="sq-td sq-ordine-remessa-person">
                            <span class="sq-ordine-remessa-nome">{{ $nomeDest !== '' ? $nomeDest : '—' }}</span>
                            @if ($addrDest !== '')
                                <span class="sq-ordine-remessa-indirizzo">{{ $addrDest }}</span>
                            @endif
                            @if ($linhaDest2 !== '')
                                <span class="sq-ordine-remessa-indirizzo">{{ $linhaDest2 }}</span>
                            @endif
                        </td>
                        <td class="sq-td">{{ $servico !== '' ? $servico : '—' }}</td>
                        <td class="sq-td sq-text-14">
                            @include('ordini.partials.spedizione-servizi-aggiuntivi', ['spedizione' => $s])
                        </td>
                        <td class="sq-td sq-td--right">
                            @if ($annullata)
                                <span class="sq-text-muted">—</span>
                            @else
                                @include('partials.td-importo-ivato', ['importoIvato' => $importoIvato])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspanVazio }}" class="sq-td">Nessuna spedizione in questo ordine.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($spedizioniOrdinate->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="{{ $mostraSelezione ? 8 : 7 }}" class="sq-td sq-td--right sq-fw-700">Totale ordine (IVA inclusa)</td>
                        <td class="sq-td sq-td--right sq-fw-700">{{ number_format($totaleIvatoOrdine, 2, ',', '.') }} €</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @if ($mostraSelezione)
        </form>
    @endif
</div>
