@php
    $linkLabel = trim((string) ($linkLabel ?? ''));
    $corriereId = (int) ($corriereId ?? 0);
    $tipo = trim((string) ($tipo ?? 'ritiro'));
@endphp
@if ($linkLabel !== '' && $corriereId > 0 && ($sendcloudConfigured ?? false))
    <button type="button"
            class="sq-prev-sp-link sq-prev-sp-link-btn"
            data-corriere-id="{{ $corriereId }}"
            data-tipo="{{ $tipo }}"
            data-link-label="{{ $linkLabel }}">
        {{ $linkLabel }}
    </button>
@endif
