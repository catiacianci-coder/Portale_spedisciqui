@extends('layouts.app')
@section('content')
@php
    $dimsPreview = [
        (float) old('altezza', $input['altezza'] ?? 0),
        (float) old('larghezza', $input['larghezza'] ?? 0),
        (float) old('spessore', $input['spessore'] ?? 0),
    ];
    rsort($dimsPreview);
    $lmaxPreview = $dimsPreview[0] ?? 0.0;
    $lmedPreview = $dimsPreview[1] ?? 0.0;
    $lminPreview = $dimsPreview[2] ?? 0.0;
    $sommaLatiPreview = $lmaxPreview + $lmedPreview + $lminPreview;
@endphp

<div class="home-spedizione-wrap">
    <div class="home-spedizione-card">
        @include('partials.homepage-avviso', ['testo' => $homepageAvviso ?? ''])
        <h1 class="home-spedizione-title">Calcola la tua spedizione</h1>

        @if ($errors->any())
            <div class="home-spedizione-errors">
                <strong>Attenzione</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('vincoli.spedizione.store') }}" class="home-spedizione-form">
            @csrf

            @php
                $ambitoVal = old('ambito_spedizione', $input['ambito_spedizione'] ?? 'nazionale');
            @endphp

            <div class="home-form-stack">
                {{-- Riga 1: ambito (senza didascalia) + tipo spedizione + Package --}}
                <div class="home-row home-row-first">
                    <div class="home-field home-field-ambito">
                        <select id="ambito_spedizione" name="ambito_spedizione" required class="home-input home-select" title="Nazionale o internazionale" aria-label="Nazionale o internazionale">
                            <option value="nazionale" @selected($ambitoVal === 'nazionale')>Nazionale</option>
                            <option value="internazionale" @selected($ambitoVal === 'internazionale')>Internazionale</option>
                        </select>
                    </div>
                    <div class="home-field">
                        <label for="id_tipo_spediziones">Tipo spedizione</label>
                        <select id="id_tipo_spediziones" name="id_tipo_spediziones" required class="home-input home-select">
                            <option value="">— Scegli —</option>
                            @foreach ($tipi as $t)
                                <option value="{{ $t->id }}" @selected(old('id_tipo_spediziones', $input['id_tipo_spediziones'] ?? null) == $t->id)>
                                    {{ $t->tipo_spedizione }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="home-field">
                        <label for="user_imballaggio_preset">Package</label>
                        @if (! empty($userImballaggiJson))
                            <select id="user_imballaggio_preset" class="home-input home-select" title="Solo Package del tipo di spedizione scelto. Le modifiche ai numeri valgono solo per questa richiesta.">
                                <option value="">— I miei Package —</option>
                            </select>
                            <p class="home-imballaggio-hint">Scegli prima il tipo di spedizione: per vedere i tuoi Package</p>
                        @else
                            <select id="user_imballaggio_preset" class="home-input home-select" disabled>
                                <option value="">— Accedi per usare i tuoi Package —</option>
                            </select>
                        @endif
                    </div>
                </div>

                {{-- Riga 2: CAP CAP peso --}}
                <div class="home-row home-row-3">
                    <div class="home-field">
                        <label for="cap_origine">CAP / origine</label>
                        <div class="home-suggest-wrap">
                            <input id="cap_origine" name="cap_origine" value="{{ old('cap_origine', $input['cap_origine'] ?? ($capMittentePreferitoDefault ?? '')) }}" maxlength="80" required autocomplete="off"
                                   class="home-input"
                                   placeholder="CAP o comune">
                            <input type="hidden" id="id_comune_origine" name="id_comune_origine" value="{{ old('id_comune_origine', $input['id_comune_origine'] ?? ($idComuneMittentePreferitoDefault ?? '')) }}">
                            <div id="suggest_cap_origine" class="home-suggest-list is-hidden"></div>
                        </div>
                    </div>
                    <div class="home-field">
                        <label for="cap_destino">CAP / destinazione</label>
                        <div class="home-suggest-wrap">
                            <input id="cap_destino" name="cap_destino" value="{{ old('cap_destino', $input['cap_destino'] ?? '') }}" maxlength="80" required autocomplete="off"
                                   class="home-input"
                                   placeholder="CAP o comune">
                            <input type="hidden" id="id_comune_destino" name="id_comune_destino" value="{{ old('id_comune_destino', $input['id_comune_destino'] ?? '') }}">
                            <div id="suggest_cap_destino" class="home-suggest-list is-hidden"></div>
                        </div>
                    </div>
                    <div class="home-field">
                        <label for="peso">Peso (kg)</label>
                        <input id="peso" name="peso" type="number" step="0.01" min="0.01" value="{{ old('peso', $input['peso'] ?? '') }}" required class="home-input">
                    </div>
                </div>

                {{-- Riga 3: dimensioni --}}
                <div class="home-row home-row-3">
                    <div class="home-field">
                        <label for="altezza">Altezza (cm)</label>
                        <input id="altezza" name="altezza" type="number" step="0.01" min="0.01" value="{{ old('altezza', $input['altezza'] ?? '') }}" required class="home-input">
                    </div>
                    <div class="home-field">
                        <label for="larghezza">Larghezza (cm)</label>
                        <input id="larghezza" name="larghezza" type="number" step="0.01" min="0.01" value="{{ old('larghezza', $input['larghezza'] ?? '') }}" required class="home-input">
                    </div>
                    <div class="home-field">
                        <label for="spessore">Spessore (cm)</label>
                        <input id="spessore" name="spessore" type="number" step="0.01" min="0.01" value="{{ old('spessore', $input['spessore'] ?? '') }}" required class="home-input">
                    </div>
                </div>
            </div>

            <button type="submit" class="home-spedizione-cta">Calcola ora</button>
        </form>
    </div>

    @php
        $homeVantaggi = config('home_vantaggi', []);
    @endphp
    @if (count($homeVantaggi) > 0)
        <section class="home-vantaggi" aria-label="Servizi e vantaggi">
            <h2 class="home-vantaggi-title">Con Spedisciqui la logistica diventa semplice</h2>
            <div class="home-vantaggi-grid">
                @foreach ($homeVantaggi as $idx => $row)
                    <div class="home-vantaggio-item">
                        <div class="home-vantaggio-circle">
                            @if (! empty($row['img']) && is_string($row['img']) && is_file(public_path($row['img'])))
                                <img src="{{ asset($row['img']) }}" alt="{{ $row['titolo'] ?? '' }}" class="home-vantaggio-img" width="64" height="64" loading="lazy" decoding="async">
                            @elseif (! empty($row['icon']) && is_string($row['icon']))
                                <i class="fa-solid {{ $row['icon'] }} home-vantaggio-icon" aria-hidden="true"></i>
                            @else
                                <span class="home-vantaggio-fallback" aria-hidden="true">{{ $idx + 1 }}</span>
                            @endif
                        </div>
                        <div class="home-vantaggio-copy">
                            <span class="home-vantaggio-titolo">{{ $row['titolo'] ?? '' }}</span>
                            <span class="home-vantaggio-testo">{{ $row['testo'] ?? '' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @php
        $partnerCorrieriList = $partnerCorrieri ?? [];
    @endphp
    @if (count($partnerCorrieriList) > 0)
        <section class="home-partners" aria-label="I nostri partner">
            <h2 class="home-partners-title">I nostri partner</h2>
            <div class="home-partners-viewport">
                <div class="home-partners-track">
                    @foreach (array_merge($partnerCorrieriList, $partnerCorrieriList) as $p)
                        <div class="home-partner-slide">
                            @if (!empty($p['logo_url']))
                                <img src="{{ $p['logo_url'] }}" alt="{{ $p['nome'] }}" class="home-partner-logo" width="140" height="70" loading="lazy" decoding="async">
                            @else
                                <div class="home-partner-placeholder">{{ function_exists('mb_substr') ? mb_strtoupper(mb_substr($p['nome'], 0, 1, 'UTF-8')) : strtoupper(substr($p['nome'], 0, 1)) }}</div>
                            @endif
                            <span class="home-partner-name">{{ $p['nome'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (!empty($input['id_comune_origine'] ?? null))
        <div class="home-spedizione-debug">
            <strong>Risoluzione CAP</strong><br>
            Origine: comune ID <strong>{{ $input['id_comune_origine'] }}</strong> &middot;
            Destino: comune ID <strong>{{ $input['id_comune_destino'] }}</strong><br>
            Lati ordinati (max/med/min): <strong>{{ number_format($input['lato_max'] ?? 0, 2, ',', '.') }}</strong> /
            <strong>{{ number_format($input['lato_med'] ?? 0, 2, ',', '.') }}</strong> /
            <strong>{{ number_format($input['lato_min'] ?? 0, 2, ',', '.') }}</strong> cm
            &middot; Somma lati (max+med+min): <strong>{{ number_format($sommaLatiPreview, 2, ',', '.') }}</strong> cm
            &middot; Perimetro geometrico (2×somma): <strong>{{ number_format(2 * $sommaLatiPreview, 2, ',', '.') }}</strong> cm
        </div>
    @endif

    @if (is_array($risultati))
        <div class="home-spedizione-risultati">
            <h2 class="sq-vincoli-esito-h2">Esito</h2>
            <ul class="sq-vincoli-esito-list">
                @foreach ($risultati as $r)
                    <li class="sq-vincoli-esito-item">
                        <strong>{{ $r['corriere']->nome_corriere }}</strong>
                        <span class="sq-vincoli-tipo">({{ $r['corriere']->tipo_o_d }})</span>
                        @if ($r['ok_tratta'])
                            <span class="sq-vincoli-ok"> &mdash; Tratta OK</span>
                        @else
                            <span class="sq-vincoli-no"> &mdash; Tratta NO</span>
                            <div class="sq-vincoli-motivo">{{ $r['motivo_tratta'] }}</div>
                        @endif

                        @if ($r['ok_tratta'])
                            @if (!empty($r['tariffa']))
                                <div class="sq-vincoli-detail">
                                    <div><strong>Tariffa trovata</strong> (servizio: {{ $r['tariffa']->servizio ?? '—' }})</div>
                                    <div class="sq-vincoli-motivo">
                                        Base: <strong>{{ number_format((float) $r['tariffa']->tariffa, 2, ',', '.') }}</strong>
                                        &middot; Ricarico: <strong>{{ $r['tariffa']->ricarico === null ? '0' : number_format((float) $r['tariffa']->ricarico, 2, ',', '.') }}%</strong>
                                        &middot; Totale: <strong>{{ number_format((float) $r['prezzo_finale'], 2, ',', '.') }}</strong>
                                    </div>
                                    <div class="sq-vincoli-detail-small">
                                        Vincoli tariffa: lati max/med/min pacco
                                        ({{ number_format($input['lato_max'] ?? 0, 2, ',', '.') }} /
                                        {{ number_format($input['lato_med'] ?? 0, 2, ',', '.') }} /
                                        {{ number_format($input['lato_min'] ?? 0, 2, ',', '.') }})
                                        ≤
                                        ({{ $r['tariffa']->lato_max ?? '—' }} /
                                        {{ $r['tariffa']->lato_med ?? '—' }} /
                                        {{ $r['tariffa']->lato_min ?? '—' }});
                                        somma lati {{ number_format($sommaLatiPreview, 2, ',', '.') }} ≤ {{ $r['tariffa']->max ?? '—' }};
                                        peso {{ number_format((float) ($input['peso'] ?? 0), 2, ',', '.') }} ≤ {{ $r['tariffa']->peso_max_collo ?? '—' }}
                                    </div>
                                </div>
                            @else
                                <div class="sq-vincoli-err-box">
                                    <strong>Tariffa NO</strong>
                                    <div class="sq-vincoli-motivo">{{ $r['motivo_tariffa'] }}</div>
                                </div>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>


<script>
(() => {
    const suggestUrl = @json(route('api.comuni.suggest'));

    function debounce(fn, ms) {
        let t = null;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    }

    function hideList(el) {
        el.style.display = 'none';
        el.innerHTML = '';
    }

    function wireField({ inputId, hiddenId, listId }) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const list = document.getElementById(listId);
        if (!input || !hidden || !list) return;

        let controller = null;
        let lastSelectedCap = '';

        const run = debounce(async () => {
            const q = (input.value || '').trim();
            if (q.length === 0) {
                hidden.value = '';
                lastSelectedCap = '';
                hideList(list);
                return;
            }

            const padded = q.replace(/\D/g, '').length === q.length && q.length <= 5
                ? q.padStart(5, '0')
                : q;
            if (hidden.value && padded !== lastSelectedCap) {
                hidden.value = '';
            }

            if (/^\d+$/.test(q)) {
                if (q.length < 1) { hideList(list); return; }
            } else {
                if (q.length < 2) { hideList(list); return; }
            }

            try {
                if (controller) controller.abort();
                controller = new AbortController();

                const res = await fetch(`${suggestUrl}?q=${encodeURIComponent(q)}`, {
                    signal: controller.signal,
                    headers: { 'Accept': 'application/json' },
                });

                if (!res.ok) {
                    hideList(list);
                    return;
                }

                const items = await res.json();
                if (!Array.isArray(items) || items.length === 0) {
                    hideList(list);
                    return;
                }

                list.innerHTML = '';
                for (const it of items) {
                    const row = document.createElement('button');
                    row.type = 'button';
                    const label = it.label || `${it.cap} — ${it.comune} (${it.provincia})`;
                    row.textContent = label;
                    row.style.display = 'block';
                    row.style.width = '100%';
                    row.style.textAlign = 'left';
                    row.style.padding = '10px 12px';
                    row.style.border = '0';
                    row.style.borderBottom = '1px solid #eee';
                    row.style.background = '#fff';
                    row.style.cursor = 'pointer';
                    row.style.color = '#111';
                    row.style.fontSize = '14px';
                    row.style.lineHeight = '1.2';
                    row.style.fontFamily = 'system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';

                    row.addEventListener('mouseenter', () => {
                        row.style.background = '#fff7ed';
                    });
                    row.addEventListener('mouseleave', () => {
                        row.style.background = '#fff';
                    });

                    row.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        input.value = it.cap;
                        hidden.value = String(it.id);
                        lastSelectedCap = it.cap;
                        hideList(list);
                    });

                    list.appendChild(row);
                }

                list.style.display = 'block';
            } catch (e) {
                if (e.name === 'AbortError') return;
                hideList(list);
            }
        }, 200);

        input.addEventListener('input', run);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') hideList(list);
        });

        document.addEventListener('click', (e) => {
            if (!list.style.display || list.style.display === 'none') return;
            if (e.target === input || list.contains(e.target)) return;
            hideList(list);
        });
    }

    wireField({ inputId: 'cap_origine', hiddenId: 'id_comune_origine', listId: 'suggest_cap_origine' });
    wireField({ inputId: 'cap_destino', hiddenId: 'id_comune_destino', listId: 'suggest_cap_destino' });

    const userImballaggiPreset = @json($userImballaggiJson ?? []);
    const presetSel = document.getElementById('user_imballaggio_preset');
    const tipoSpedSel = document.getElementById('id_tipo_spediziones');

    function fmtDimHome(n) {
        const x = Number(n);
        if (Number.isNaN(x)) return '';
        return String(x).replace('.', ',');
    }

    function presetOptionLabel(row) {
        const dims = fmtDimHome(row.altezza) + '×' + fmtDimHome(row.larghezza) + '×' + fmtDimHome(row.spessore) + ' cm';
        const peso = fmtDimHome(row.peso) + ' kg';
        const pref = row.is_preferito ? '★ ' : '';
        return pref + row.nome + ' — ' + dims + ' — ' + peso;
    }

    function rebuildImballaggiPresetSelect() {
        if (!presetSel || !Array.isArray(userImballaggiPreset) || !userImballaggiPreset.length) return;
        const tipoId = tipoSpedSel && tipoSpedSel.value ? String(tipoSpedSel.value) : '';
        const prev = presetSel.value;
        presetSel.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = tipoId ? '— I miei Package (stesso tipo) —' : '— Scegli prima il tipo di spedizione —';
        presetSel.appendChild(opt0);

        let filtered = tipoId
            ? userImballaggiPreset.filter((x) => String(x.id_tipo_spediziones) === tipoId)
            : [];
        filtered = filtered.slice().sort((a, b) => {
            const pa = a.is_preferito ? 1 : 0;
            const pb = b.is_preferito ? 1 : 0;
            if (pb !== pa) return pb - pa;
            return (a.nome || '').localeCompare(b.nome || '', 'it');
        });

        filtered.forEach((row) => {
            const o = document.createElement('option');
            o.value = String(row.id);
            o.textContent = presetOptionLabel(row);
            presetSel.appendChild(o);
        });

        if (prev && [...presetSel.options].some((o) => o.value === prev)) {
            presetSel.value = prev;
        } else {
            presetSel.value = '';
        }
    }

    if (presetSel && Array.isArray(userImballaggiPreset) && userImballaggiPreset.length) {
        rebuildImballaggiPresetSelect();
        if (tipoSpedSel) {
            tipoSpedSel.addEventListener('change', () => rebuildImballaggiPresetSelect());
        }
        presetSel.addEventListener('change', () => {
            const id = presetSel.value;
            if (!id) return;
            const row = userImballaggiPreset.find((x) => String(x.id) === String(id));
            if (!row) return;
            const setVal = (elId, v) => {
                const el = document.getElementById(elId);
                if (el) el.value = String(v);
            };
            setVal('altezza', row.altezza);
            setVal('larghezza', row.larghezza);
            setVal('spessore', row.spessore);
            setVal('peso', row.peso);
        });
    }
})();
</script>
@endsection
