@props([
    'id',
    'name' => null,
    'value' => '',
    'autocomplete' => 'new-password',
    'required' => false,
    'minlength' => null,
    'inputClass' => '',
    'placeholder' => null,
])

@php
    $name = $name ?? $id;
@endphp

<div class="sq-password-field-wrap">
    <input
        type="password"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        autocomplete="{{ $autocomplete }}"
        @if ($required) required @endif
        @if ($minlength !== null) minlength="{{ $minlength }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        class="sq-password-field-input {{ $inputClass }}"
    />
    <button
        type="button"
        class="sq-password-toggle"
        data-password-toggle="{{ $id }}"
        aria-label="Mostra password"
        aria-pressed="false"
        aria-controls="{{ $id }}"
    >
        <i class="fa-regular fa-eye sq-password-toggle-eye-open" aria-hidden="true"></i>
        <i class="fa-regular fa-eye-slash sq-password-toggle-eye-closed" aria-hidden="true" hidden></i>
    </button>
</div>
