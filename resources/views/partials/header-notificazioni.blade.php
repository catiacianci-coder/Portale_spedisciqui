@php
    /** @var \App\Support\Cliente\ClienteNotificazioniRiepilogo|null $clienteNotificazioni */
    $clienteNotificazioni = $clienteNotificazioni ?? null;
@endphp

@if ($clienteNotificazioni !== null)
    <details class="sq-header-notif-menu">
        <summary
            class="sq-header-notif-trigger"
            title="Notifiche e avvisi"
            aria-label="Notifiche e avvisi{{ $clienteNotificazioni->badgeTotal > 0 ? ' — '.$clienteNotificazioni->badgeTotal.' in sospeso' : '' }}"
        >
            <i class="fa-solid fa-bell" aria-hidden="true"></i>
            @if ($clienteNotificazioni->badgeTotal > 0)
                <span class="sq-header-notif-badge">{{ $clienteNotificazioni->badgeTotal > 99 ? '99+' : $clienteNotificazioni->badgeTotal }}</span>
            @endif
        </summary>
        <div class="sq-header-notif-dropdown" role="region" aria-label="Elenco notifiche">
            <p class="sq-header-notif-dropdown__title">Notifiche</p>
            @forelse ($clienteNotificazioni->items as $item)
                @if ($item->id === 'avviso_piattaforma')
                    <div class="sq-header-notif-item sq-header-notif-item--info">
                        <div class="sq-header-notif-item__head">
                            <i class="fa-solid fa-bullhorn sq-header-notif-item__icon" aria-hidden="true"></i>
                            <strong>{{ $item->titolo }}</strong>
                        </div>
                        <p class="sq-header-notif-item__desc">{{ $item->descrizione }}</p>
                        <div class="sq-header-notif-item__actions">
                            <a href="{{ $item->url }}" class="sq-header-notif-item__link">Vai alla home</a>
                            <form method="post" action="{{ route('notifiche.dispensar_avviso') }}" class="sq-header-notif-dismiss-form">
                                @csrf
                                <button type="submit" class="sq-header-notif-dismiss-btn">Ignora</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a
                        href="{{ $item->url }}"
                        class="sq-header-notif-item @if($item->grave) sq-header-notif-item--grave @endif"
                    >
                        <div class="sq-header-notif-item__head">
                            @if ($item->id === 'nc_pratiche')
                                <i class="fa-solid fa-file-circle-exclamation sq-header-notif-item__icon" aria-hidden="true"></i>
                            @elseif ($item->id === 'assistenza')
                                <i class="fa-solid fa-headset sq-header-notif-item__icon" aria-hidden="true"></i>
                            @elseif ($item->id === 'rimborso')
                                <i class="fa-solid fa-hand-holding-dollar sq-header-notif-item__icon" aria-hidden="true"></i>
                            @else
                                <i class="fa-solid fa-circle-info sq-header-notif-item__icon" aria-hidden="true"></i>
                            @endif
                            <strong>{{ $item->titolo }}</strong>
                            @if ($item->contagem > 0)
                                <span class="sq-header-notif-item__badge">{{ $item->contagem > 99 ? '99+' : $item->contagem }}</span>
                            @endif
                        </div>
                        <p class="sq-header-notif-item__desc">{{ $item->descrizione }}</p>
                    </a>
                @endif
            @empty
                <p class="sq-header-notif-empty">Nessuna notifica al momento.</p>
            @endforelse
        </div>
    </details>
@endif
