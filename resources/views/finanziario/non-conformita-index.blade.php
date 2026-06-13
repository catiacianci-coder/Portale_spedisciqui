@extends('layouts.app')
@section('content')
<div class="home-spedizione-wrap wallet-ricarica-page finanziario-nc-pratiche-page">
    <h1 class="sq-h1-ricarica">Finanziario — Non conformità</h1>

    <div class="sq-text-wallet-body sq-nc-pratiche-intro sq-mb-18">
        <p class="sq-m-0 sq-mb-8">In questa sezione trovi le pratiche generate dal back-office a seguito delle rilevazioni effettuate dai corrieri.</p>
        <p class="sq-m-0 sq-mb-8"><strong>Come procedere:</strong></p>
        <p class="sq-m-0 sq-mb-8"><strong>Dettaglio e Pagamento:</strong> Accedendo al dettaglio della pratica, puoi scegliere se pagare singole spedizioni oppure saldare l’intero importo della pratica in un’unica soluzione, utilizzando il metodo di pagamento che preferisci.</p>
        <p class="sq-m-0 sq-mb-8"><strong>Trasparenza nel PDF:</strong> Scarica il documento PDF per consultare informazioni chiare e dettagliate su come si sono generate le differenze di tariffazione.</p>
        <p class="sq-m-0"><strong>Regolarizzazione:</strong> Salda le pendenze per regolarizzare tempestivamente la tua posizione amministrativa e continuare a spedire senza interruzioni.</p>
    </div>

    @if ($pratiche->isEmpty())
        <p class="sq-text-666 sq-m-0">Non hai pratiche di non conformità.</p>
    @else
        <div class="sq-table-wrap">
            <table class="sq-table">
                <thead>
                    <tr class="sq-thead-row sq-thead-row--neutral">
                        <th class="sq-th">Pratica</th>
                        <th class="sq-th">Stato</th>
                        <th class="sq-th">Emissione</th>
                        <th class="sq-th sq-th--right">Residuo da pagare</th>
                        <th class="sq-th sq-th--right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pratiche as $p)
                        @php
                            $residuo = (float) $p->righe
                                ->where('stato_riga', \App\Models\nc_pratica_riga::STATO_NON_PAGATO)
                                ->sum(fn ($r) => max((float) $r->delta, 0.0));
                        @endphp
                        <tr>
                            <td class="sq-td sq-fw-700">{{ $p->numero_pratica }}</td>
                            <td class="sq-td">{{ $p->stato === \App\Models\nc_pratica::STATO_CHIUSO ? 'Chiusa' : 'Aperta' }}</td>
                            <td class="sq-td sq-text-muted">{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="sq-td sq-td--right sq-fw-700">{{ number_format($residuo, 2, ',', '.') }} €</td>
                            <td class="sq-td sq-td--right">
                                <span class="sq-nc-actions-icons">
                                    <a href="{{ route('finanziario.nc.show', $p->id) }}" class="sq-nc-action-icon" title="Dettaglio" aria-label="Dettaglio pratica {{ e($p->numero_pratica) }}">
                                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    </a>
                                    @if ($p->pdf_path)
                                        <a href="{{ route('finanziario.nc.pdf', $p->id) }}" class="sq-nc-action-icon" title="Scarica PDF" aria-label="Scarica PDF pratica {{ e($p->numero_pratica) }}">
                                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
