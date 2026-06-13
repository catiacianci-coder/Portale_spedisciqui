@extends('layouts.app')

@section('pageBanner')
    <x-sq-page-banner
        variant="backoffice"
        title="Nuovo messaggio tracking"
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

    <form method="POST" action="{{ route('backoffice.utilities.msg_tracciamento.store') }}" class="sq-bo-param-form">
        @csrf
        @include('backoffice.msg-tracciamento._form')
        <div class="sq-mt-16">
            <button type="submit" class="sq-btn-primary">Salva</button>
        </div>
    </form>
</div>
@endsection
