@extends('layouts.app')

@section('content')
<div class="sq-page-900 backoffice-homepage-avviso">
    <p class="sq-intro">
        Testo breve mostrato nella home, <strong>sopra il titolo «Calcola la tua spedizione»</strong>,
        con icona megafono. Lascia vuoto per non mostrare nulla.
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-18">{{ session('ok') }}</div>
    @endif

    @if ($errors->any())
        <div class="sq-alert sq-alert--error sq-mb-18">
            <ul class="sq-m-0 sq-pl-18">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('backoffice.homepage_avviso.update') }}" class="sq-bo-param-form sq-max-w-520">
        @csrf
        @method('PUT')
        <label for="testo" class="sq-bo-param-label">Testo avviso</label>
        <textarea id="testo" name="testo" rows="3" maxlength="280" class="sq-bo-param-input sq-w-full sq-mt-6"
            placeholder="Es.: Manutenzione programmata domenica dalle 2:00 alle 4:00.">{{ old('testo', $testo) }}</textarea>
        <p class="sq-text-muted sq-text-sm sq-mt-8 sq-mb-16">Massimo 280 caratteri. Solo testo semplice.</p>
        <button type="submit" class="sq-btn sq-btn--brand">Salva</button>
    </form>

    @if (trim($testo) !== '')
        <div class="sq-mt-32">
            <p class="sq-text-muted sq-text-sm sq-mb-8">Anteprima (come in home):</p>
            @include('partials.homepage-avviso', ['testo' => $testo])
            <h2 class="home-spedizione-title sq-mt-12">Calcola la tua spedizione</h2>
        </div>
    @endif
</div>
@endsection
