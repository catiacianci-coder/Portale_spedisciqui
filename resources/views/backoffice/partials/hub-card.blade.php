<div
    class="sq-bo-hub-card-wrap"
    data-id="{{ $item['id'] }}"
    data-default-section="{{ $item['section'] }}"
>
    <button
        type="button"
        class="sq-bo-hub-card-grip"
        draggable="true"
        aria-label="Trascina per riordinare {{ $item['label'] }}"
        title="Trascina per riordinare"
    >
        <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
    </button>
    <a href="{{ route($item['route']) }}" class="sq-bo-hub-card">
        <span class="sq-bo-hub-card-ico-wrap" aria-hidden="true">
            <i class="{{ $item['icon'] }} sq-bo-hub-card-ico"></i>
        </span>
        <span class="sq-bo-hub-card-body">
            <span class="sq-bo-hub-card-title">{{ $item['label'] }}</span>
            <span class="sq-bo-hub-card-desc">{{ $item['description'] }}</span>
        </span>
    </a>
</div>
