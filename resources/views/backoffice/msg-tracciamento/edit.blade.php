@extends('layouts.app')

@section('pageBanner')
    <x-sq-page-banner
        variant="backoffice"
        :title="'Modifica messaggio tracking #' . $record->id"
        icon="fa-route"
        :parent-href="route('backoffice.utilities.msg_tracciamento.index')"
        class="sq-page-banner--full"
    />
@endsection

@section('content')
<div class="sq-page-960">
    <p class="sq-intro">
        <a href="{{ route('backoffice.utilities.msg_tracciamento.index') }}" class="sq-header-link">← Elenco messaggi</a>
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-18">{{ session('ok') }}</div>
    @endif

    <form method="POST" action="{{ route('backoffice.utilities.msg_tracciamento.update', $record) }}" class="sq-bo-param-form">
        @csrf
        @include('backoffice.msg-tracciamento._form', ['record' => $record])
        <div class="sq-mt-16">
            <button type="submit" class="sq-btn-primary">Salva</button>
        </div>
    </form>
</div>
@endsection
