@extends('layouts.app')

@section('content')



<div class="sq-page-1100 backoffice-parametri-globali">

    <p class="sq-intro">

        Configurazione impresa e credenziali API esterne. I servizi del portale leggono i valori da

        <code class="sq-code">parametri_globalis</code> (non da <code class="sq-code">.env</code>).

    </p>



    @if (session('ok'))

        <div class="sq-alert sq-alert--success sq-mb-18">{{ session('ok') }}</div>

    @endif



    <form method="POST" action="{{ route('backoffice.parametri_globali.update') }}" class="sq-bo-param-form">

        @csrf



        <h2 class="sq-h2-backoffice sq-mb-12">Dati impresa</h2>

        <div class="sq-bo-param-grid sq-mb-24">

            <div>

                <label for="nome_impresa" class="sq-bo-param-label">Nome impresa</label>

                <input id="nome_impresa" name="nome_impresa" type="text" maxlength="255" class="sq-bo-param-input"

                       value="{{ old('nome_impresa', $valori['nome_impresa'] ?? '') }}"

                       autocomplete="organization">

            </div>

            <div>

                <label for="indirizzo_impresa" class="sq-bo-param-label">Indirizzo impresa</label>

                <textarea id="indirizzo_impresa" name="indirizzo_impresa" rows="3" maxlength="1000" class="sq-bo-param-textarea">{{ old('indirizzo_impresa', $valori['indirizzo_impresa'] ?? '') }}</textarea>

            </div>

            <div>

                <label for="p_iva_impresa" class="sq-bo-param-label">P.IVA impresa</label>

                <input id="p_iva_impresa" name="p_iva_impresa" type="text" maxlength="40" class="sq-bo-param-input"

                       value="{{ old('p_iva_impresa', $valori['p_iva_impresa'] ?? '') }}"

                       autocomplete="off">

            </div>

            <div>

                <label for="sito_impresa" class="sq-bo-param-label">Sito impresa</label>

                <input id="sito_impresa" name="sito_impresa" type="text" maxlength="512" class="sq-bo-param-input"

                       value="{{ old('sito_impresa', $valori['sito_impresa'] ?? '') }}"

                       placeholder="https://…"

                       autocomplete="url">

            </div>

        </div>



        <h2 class="sq-h2-backoffice sq-mb-12">Pagamenti</h2>

        <div class="sq-bo-param-grid sq-mb-24">

            <div>

                <label for="iban_cc_r_b" class="sq-bo-param-label">

                    IBAN conto corrente bonifici

                    <span class="sq-text-muted sq-font-sm">(<code>iban_cc_r_b</code>)</span>

                </label>

                <input id="iban_cc_r_b" name="iban_cc_r_b" type="text" maxlength="64" class="sq-bo-param-input sq-bo-param-input--mono"

                       value="{{ old('iban_cc_r_b', $valoriPagamenti['iban_cc_r_b'] ?? '') }}"

                       autocomplete="off"

                       spellcheck="false"

                       placeholder="IT…">

                @error('iban_cc_r_b')

                    <div class="sq-field-error">{{ $message }}</div>

                @enderror

            </div>

        </div>



        <h2 class="sq-h2-backoffice sq-mb-12">Integrazioni API</h2>

        <p class="sq-text-muted sq-mb-18">Chiavi e URL in chiaro — modificabili senza deploy.</p>



        @foreach ($apiPerGruppo as $gruppo => $denominazioni)

            <fieldset class="sq-bo-param-fieldset sq-mb-20">

                <legend class="sq-bo-param-legend">{{ $gruppo }}</legend>

                <div class="sq-bo-param-grid">

                    @foreach ($denominazioni as $denom)

                        @php

                            $meta = $apiDefinizioni[$denom] ?? ['label' => $denom];

                            $field = 'api_'.$denom;

                            $label = $meta['label'] ?? $denom;

                        @endphp

                        <div>

                            <label for="{{ $field }}" class="sq-bo-param-label" title="{{ $denom }}">

                                {{ $label }}

                                <span class="sq-text-muted sq-font-sm">(<code>{{ $denom }}</code>)</span>

                            </label>

                            <input id="{{ $field }}" name="{{ $field }}" type="text" maxlength="2000" class="sq-bo-param-input sq-bo-param-input--mono"

                                   value="{{ old($field, $valoriApi[$denom] ?? '') }}"

                                   autocomplete="off"

                                   spellcheck="false">

                            @error($field)

                                <div class="sq-field-error">{{ $message }}</div>

                            @enderror

                        </div>

                    @endforeach

                </div>

            </fieldset>

        @endforeach



        <div class="sq-bo-param-actions">

            <button type="submit" class="sq-btn-primary">Salva tutti i parametri</button>

        </div>

    </form>

</div>

@endsection


