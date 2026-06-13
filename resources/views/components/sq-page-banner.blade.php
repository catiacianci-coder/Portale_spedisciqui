@props([
    'title',
    'icon' => 'fa-circle',
    /** @var string brand | backoffice */
    'variant' => 'brand',
    /** URL esplicita per «Indietro» al posto di history.back() */
    'parentHref' => null,
])

@php
    $isBackoffice = $variant === 'backoffice';
    $panelUrl = route('backoffice.index');
    $showPanelActions = $isBackoffice && ! request()->routeIs('backoffice.index');
    $help = $pageHelp ?? null;
    $showPageHelp = ! $isBackoffice
        && $help !== null
        && trim((string) $help->modal_content) !== '';
    $bannerClass = $isBackoffice ? 'sq-page-banner--backoffice' : '';
@endphp

<div {{ $attributes->class(['sq-page-banner', $bannerClass]) }} role="heading" aria-level="1">
    <div class="sq-page-banner__lead">
        <i class="sq-page-banner__icon fas {{ $icon }}" aria-hidden="true"></i>
        <p class="sq-page-banner__title">{{ $title }}</p>
    </div>
    @if ($isBackoffice || isset($trailing) || $showPageHelp)
        <div class="sq-page-banner__trailing">
            @isset($trailing)
                <div class="sq-page-banner__trailing-custom">
                    {{ $trailing }}
                </div>
            @endisset
            @if ($showPageHelp)
                <x-page-help-button
                    :id="'help-' . $help->page_key"
                    :button-label="(string) ($help->button_label ?: 'Come funziona?')"
                    :title="(string) ($help->modal_title ?: 'Come funziona?')"
                    :content="(string) $help->modal_content"
                />
            @endif
            @if ($isBackoffice)
                <div class="sq-page-banner__bo-actions">
                    @if ($parentHref)
                        <a href="{{ $parentHref }}" class="sq-page-banner__bo-btn">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i> Indietro
                        </a>
                    @else
                        <button
                            type="button"
                            class="sq-page-banner__bo-btn"
                            id="sq-page-banner-bo-indietro"
                            data-fallback-url="{{ $panelUrl }}"
                        >
                            <i class="fas fa-arrow-left" aria-hidden="true"></i> Indietro
                        </button>
                    @endif
                    @if ($showPanelActions)
                        <div class="sq-page-banner__bo-shortcuts-menu">
                            <button
                                type="button"
                                class="sq-page-banner__bo-btn sq-page-banner__bo-shortcuts-trigger"
                                aria-haspopup="true"
                                aria-expanded="false"
                            >
                                <i class="fas fa-star" aria-hidden="true"></i> Le più usate
                                <i class="fas fa-chevron-down sq-page-banner__bo-shortcuts-chevron" aria-hidden="true"></i>
                            </button>
                            <div class="sq-page-banner__bo-shortcuts-dropdown" role="menu" aria-label="Aree più usate">
                                @foreach (\App\Support\BackofficeShortcuts::piuUsate() as $shortcut)
                                    <a href="{{ route($shortcut['route']) }}" class="sq-page-banner__bo-shortcuts-link" role="menuitem">
                                        <i class="fas {{ $shortcut['icon'] }}" aria-hidden="true"></i> {{ $shortcut['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <a href="{{ $panelUrl }}" class="sq-page-banner__bo-btn">
                            <i class="fas fa-table-columns" aria-hidden="true"></i> Pannello
                        </a>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
