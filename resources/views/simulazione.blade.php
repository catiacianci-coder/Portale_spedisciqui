@extends('layouts.app')
@section('content')
<div class="sq-sim-page">
    <div class="sq-sim-card">
        <h1 class="sq-sim-h1">PAGINA SIMULAZIONE</h1>

        <form method="GET" action="{{ route('simulazione.index') }}" class="sq-sim-form">
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="id_comune_origine"><strong>ID Comune Origine</strong></label>
                    <input id="id_comune_origine" name="id_comune_origine" type="number" min="1"
                           value="{{ old('id_comune_origine', $origineId) }}"
                           class="sq-sim-input">
                </div>
                <div class="sq-sim-field">
                    <label for="id_comune_destino"><strong>ID Comune Destino</strong></label>
                    <input id="id_comune_destino" name="id_comune_destino" type="number" min="1"
                           value="{{ old('id_comune_destino', $destinoId) }}"
                           class="sq-sim-input">
                </div>
            </div>
            <button type="submit" class="sq-sim-btn">
                Cerca corrieri per tratta
            </button>
        </form>

        <hr class="sq-sim-hr">

        @if(!empty($corriere))
            <h2 class="sq-sim-h2">Corriere: {{ $corriere->nome_corriere }}</h2>
        @endif

        @if(($ricercaEseguita ?? false) && !empty($origineId) && !empty($destinoId))
            <p class="sq-mb-14">
                <strong>Origine:</strong> {{ $origineComune ?? ('ID ' . $origineId) }} -
                <strong>Destino:</strong> {{ $destinoComune ?? ('ID ' . $destinoId) }}
            </p>
        @endif

        @if(($ricercaEseguita ?? false) && count($tratte) > 0)
            <div class="sq-sim-box">
                <p><strong>Corrieri che coprono la tratta:</strong> {{ count($corrieri) }}</p>
                <ul class="sq-sim-list-plain">
                    @foreach($corrieri as $c)
                        <li>
                            - {{ $c->nome_corriere }} @if(!empty($c->nome_servizio)) ({{ $c->nome_servizio }}) @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @elseif($ricercaEseguita ?? false)
            <p class="sq-sim-muted">Nessun corriere copre questa tratta.</p>
        @else
            <p class="sq-sim-muted">Inserisci i due ID comuni per simulare la tratta.</p>
        @endif

        <p class="sq-mt-16">
            <a href="/login" class="sq-sim-link-cta">VAI AL LOGIN</a>
        </p>
    </div>
</div>
@endsection
