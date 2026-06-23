@php
    use App\Services\Rimborso\RimborsoElegibilidadeService;
    use App\Support\RimborsoEtichettaUi;
    $elegSvc = app(RimborsoElegibilidadeService::class);
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
@endphp
<div class="sq-table-wrap sq-table-wrap--warm sq-sped-table-wrap">
    <table class="sq-table sq-sped-clienti-table">
        <thead>
            <tr class="sq-thead-row sq-thead-row--warm">
                <th class="sq-th sq-th--warm sq-th--codice">Codice</th>
                <th class="sq-th sq-th--warm">Destinatario</th>
                <th class="sq-th sq-th--warm">Servizio</th>
                <th class="sq-th sq-th--warm">Status</th>
                <th class="sq-th sq-th--warm">Tracking</th>
                <th class="sq-th sq-th--warm sq-th--right">@include('partials.th-importo-iva-inclusa')</th>
                <th class="sq-th sq-th--warm sq-th--right sq-th--actions">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($spedizioni as $s)
                @php
                    $mostraCestino = RimborsoEtichettaUi::mostraCestino($s, $elegSvc);
                    $statoUi = RimborsoEtichettaUi::statoEtichettaUi($s);
                    $importo = RimborsoEtichettaUi::importoIvato($s);
                    $stripeClass = $loop->odd ? 'sq-sped-row--stripe-white' : 'sq-sped-row--stripe-grey';
                    $track = trim((string) ($s->tracking ?? ''));
                @endphp
                <tr class="{{ $stripeClass }}">
                    <td class="sq-td sq-td--border-warm sq-nowrap sq-fw-700">{{ e($s->codice_interno) }}</td>
                    <td class="sq-td sq-td--border-warm">{{ e(RimborsoEtichettaUi::nomeDestinatario($s)) }}</td>
                    <td class="sq-td sq-td--border-warm sq-text-14">{{ e(RimborsoEtichettaUi::nomeServizioVisualizzato($s)) }}</td>
                    <td class="sq-td sq-td--border-warm">
                        @include('partials.stato-tabella-badge', [
                            'stato' => $statoUi['badge'],
                            'label' => $statoUi['testo'],
                        ])
                    </td>
                    <td class="sq-td sq-td--border-warm sq-text-14">
                        @if ($track !== '')
                            <span class="sq-sped-track-txt" title="{{ e($track) }}">{{ e(\Illuminate\Support\Str::limit($track, 40)) }}</span>
                        @endif
                    </td>
                    <td class="sq-td sq-td--border-warm sq-td--right">
                        @include('partials.td-importo-ivato', ['importoIvato' => $importo])
                    </td>
                    <td class="sq-td sq-td--border-warm sq-td--right">
                        <div class="sq-ordini-actions-icons">
                            @if ($mostraCestino)
                                <form method="POST" action="{{ route('rimborso-etichette.solicitar') }}"
                                      class="sq-rimborso-trash-form"
                                      onsubmit="return confirm('Confermi la richiesta di rimborso per {{ e($s->codice_interno) }}?');">
                                    @csrf
                                    <input type="hidden" name="spedizione_id" value="{{ $s->id }}">
                                    <button type="submit" class="sq-ordini-icon-action sq-ordini-icon-action--remove" title="Richiedi rimborso" aria-label="Richiedi rimborso">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
