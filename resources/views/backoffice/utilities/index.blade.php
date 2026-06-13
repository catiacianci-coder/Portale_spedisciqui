@extends('layouts.app')

@section('content')
<div class="sq-page-1100">
    <p class="sq-intro">
        <a href="{{ route('backoffice.index') }}" class="sq-header-link">← Menu back office</a>
    </p>
    <p class="sq-text-muted">Le utility sono state spostate nel menu principale del back office.</p>
    <a href="{{ route('backoffice.utilities.msg_tracciamento.index') }}" class="sq-btn-secondary">Messaggi tracking</a>
</div>
@endsection
