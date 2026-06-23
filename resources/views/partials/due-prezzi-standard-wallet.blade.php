@php
    $prezzoStandard = $prezzoStandard ?? null;
    $prezzoWallet = $prezzoWallet ?? null;
    $mostraEtichette = (bool) ($mostraEtichette ?? true);
    $compact = (bool) ($compact ?? false);
    $fmt = static fn (?float $v): string => $v !== null ? \App\Support\ImportoEuro::format((float) $v) : '—';
@endphp
<div @class(['sq-due-prezzi', 'sq-due-prezzi--compact' => $compact])>
    <div class="sq-due-prezzi-riga">
        @if ($mostraEtichette)
            <span class="sq-due-prezzi-label">Carte/Bonifico</span>
        @endif
        <span class="sq-due-prezzi-val">{{ $fmt($prezzoStandard) }}</span>
    </div>
    <div class="sq-due-prezzi-riga sq-due-prezzi-riga--wallet">
        @if ($mostraEtichette)
            <span class="sq-due-prezzi-label">Wallet</span>
        @endif
        <span class="sq-due-prezzi-val">{{ $fmt($prezzoWallet) }}</span>
    </div>
</div>
