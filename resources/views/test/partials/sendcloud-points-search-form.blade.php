@php
    $pfx = $prefix;
    $capId = $pfx.'_cap';
    $cityId = $pfx.'_citta';
    $defaultCarrierCode = $defaultCarrierCode ?? ($input[$pfx.'_carrier_code'] ?? 'poste_italiane');
    $defaultShopType = $defaultShopType ?? ($input[$pfx.'_shop_type'] ?? '');
    $defaultUseIntegration = $defaultUseIntegration ?? ($input[$pfx.'_use_integration_carriers'] ?? '0');
@endphp
<form method="POST" action="{{ route('test.sendcloud-rates') }}" class="sq-sim-form sq-sc-points-form">
    @csrf
    <input type="hidden" name="probe" value="{{ $probe }}">
    <div class="sq-sim-row">
        <div class="sq-sim-field">
            <label for="{{ $capId }}"><strong>CAP</strong></label>
            <input id="{{ $capId }}" name="{{ $pfx }}_cap" class="sq-sim-input"
                   value="{{ old($pfx.'_cap', $input[$pfx.'_cap'] ?? '') }}">
        </div>
        <div class="sq-sim-field">
            <label for="{{ $cityId }}"><strong>Città</strong></label>
            <input id="{{ $cityId }}" name="{{ $pfx }}_citta" class="sq-sim-input"
                   value="{{ old($pfx.'_citta', $input[$pfx.'_citta'] ?? '') }}">
        </div>
        <div class="sq-sim-field">
            <label for="{{ $pfx }}_radius"><strong>Raggio (m)</strong></label>
            <input id="{{ $pfx }}_radius" name="{{ $pfx }}_radius" class="sq-sim-input" type="number" min="100" max="50000"
                   value="{{ old($pfx.'_radius', $input[$pfx.'_radius'] ?? '5000') }}">
        </div>
        <div class="sq-sim-field">
            <label for="{{ $pfx }}_limit"><strong>Max risultati</strong></label>
            <input id="{{ $pfx }}_limit" name="{{ $pfx }}_limit" class="sq-sim-input" type="number" min="1" max="200"
                   value="{{ old($pfx.'_limit', $input[$pfx.'_limit'] ?? '40') }}">
        </div>
    </div>
    <div class="sq-sim-row">
        <div class="sq-sim-field">
            <label for="{{ $pfx }}_carrier_code"><strong>carrier_code</strong></label>
            <input id="{{ $pfx }}_carrier_code" name="{{ $pfx }}_carrier_code" class="sq-sim-input"
                   value="{{ old($pfx.'_carrier_code', $defaultCarrierCode) }}"
                   @disabled(old($pfx.'_use_integration_carriers', $defaultUseIntegration) === '1')>
        </div>
        <div class="sq-sim-field">
            <label for="{{ $pfx }}_shop_type"><strong>Tipo (opz.)</strong></label>
            <select id="{{ $pfx }}_shop_type" name="{{ $pfx }}_shop_type" class="sq-sim-input">
                @php $st = old($pfx.'_shop_type', $defaultShopType); @endphp
                <option value="" @selected($st === '')>Tutti</option>
                <option value="post_office" @selected($st === 'post_office')>Ufficio postale</option>
                <option value="servicepoint" @selected($st === 'servicepoint')>Service point</option>
                <option value="locker" @selected($st === 'locker')>Locker</option>
            </select>
        </div>
        <div class="sq-sim-field" style="display:flex; align-items:flex-end; padding-bottom:6px;">
            <label style="display:flex; gap:8px; align-items:center; cursor:pointer;">
                <input type="checkbox" name="{{ $pfx }}_use_integration_carriers" value="1"
                    @checked(old($pfx.'_use_integration_carriers', $defaultUseIntegration) === '1')>
                <span><strong>Corrieri integrazione</strong></span>
            </label>
        </div>
    </div>
    <button type="submit" class="sq-sim-btn">{{ $submitLabel }}</button>
</form>
