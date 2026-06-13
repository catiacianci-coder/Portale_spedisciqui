@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@php
    $accent = '#ff6600';
@endphp

<style>
    .imballaggi-page {
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        padding: 24px 32px 48px;
        background-color: #ffffff;
        min-height: calc(100vh - 80px);
        box-sizing: border-box;
    }
    .imballaggi-page .hero-title {
        color: {{ $accent }};
        text-align: center;
        margin: 0 0 32px;
        font-weight: 700;
        font-size: 28px;
    }
    .imballaggi-page .intro-copy {
        width: 100%;
        max-width: min(1303px, 100%);
        margin: 0 auto 26px;
        color: var(--sq-text-main);
        font-size: 15px;
        line-height: 1.45;
    }
    .imballaggi-page .intro-copy h1 {
        margin: 0 0 14px;
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--sq-text-main);
    }
    .imballaggi-page .intro-copy p {
        margin: 0 0 12px;
    }
    .imballaggi-page .page-container-grid {
        display: grid;
        grid-template-columns: 1.15fr 1fr;
        gap: 28px;
        width: 100%;
        max-width: min(1303px, 100%);
        margin: 0 auto;
        align-items: flex-start;
    }
    @media (max-width: 960px) {
        .imballaggi-page .page-container-grid {
            grid-template-columns: 1fr;
        }
    }
    .imballaggi-page .card-style {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: var(--sq-card-shadow);
        padding: 28px;
        border: var(--sq-shell-border-width) solid var(--sq-shell-border);
    }
    .imballaggi-page .form-group { margin-bottom: 18px; }
    .imballaggi-page .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #555;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .imballaggi-page .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-sizing: border-box;
        font-size: 14px;
        background: #f9f9f9;
        transition: border-color 0.2s, background 0.2s;
        font-family: inherit;
    }
    .imballaggi-page .form-control:focus {
        outline: none;
        border-color: {{ $accent }};
        background: #fff;
    }
    .imballaggi-page .form-control:read-only {
        background-color: #f1f1f1;
        color: #777;
        cursor: not-allowed;
    }
    .imballaggi-page .btn-save {
        background: {{ $accent }};
        color: white;
        padding: 14px;
        border: none;
        border-radius: 8px;
        width: 100%;
        cursor: pointer;
        font-weight: 700;
        font-size: 15px;
        margin-top: 8px;
        transition: background 0.2s, transform 0.15s;
        display: none;
        font-family: inherit;
    }
    .imballaggi-page .btn-save:hover:not(:disabled) {
        background: #e65c00;
        transform: translateY(-1px);
    }
    .imballaggi-page .btn-save:disabled { opacity: 0.6; cursor: not-allowed; }
    .imballaggi-page .package-card-box {
        background: white;
        border: var(--sq-shell-border-width) solid var(--sq-shell-border);
        border-left: 5px solid transparent;
        border-radius: 10px;
        box-shadow: var(--sq-card-shadow);
        padding: 14px 14px 14px 12px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        transition: 0.2s;
    }
    .imballaggi-page .package-card-box:hover {
        border-color: {{ $accent }};
        border-left-color: {{ $accent }};
        transform: translateY(-2px);
        box-shadow: var(--sq-card-shadow), 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    .imballaggi-page .package-card-box.is-active {
        border-left-color: {{ $accent }};
        background: #fff5eb;
    }
    .imballaggi-page .item-info {
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        flex-grow: 1;
        min-width: 0;
        text-align: left;
        border: none;
        background: none;
        padding: 0;
        font: inherit;
    }
    .imballaggi-page .item-info b {
        display: block;
        color: #333;
        font-size: 15px;
    }
    .imballaggi-page .item-info span { font-size: 13px; color: #777; }
    .imballaggi-page .item-actions { display: flex; gap: 14px; padding-right: 6px; flex-shrink: 0; }
    .imballaggi-page .action-btn {
        color: #bbb;
        cursor: pointer;
        transition: color 0.2s;
        font-size: 16px;
        padding: 4px;
        border: none;
        background: none;
    }
    .imballaggi-page .action-btn.edit:hover { color: #3498db; }
    .imballaggi-page .action-btn.copy:hover { color: #27ae60; }
    .imballaggi-page .action-btn.delete:hover { color: #e74c3c; }
    .imballaggi-page .flash-ok {
        width: 100%;
        max-width: min(1303px, 100%);
        margin: 0 auto 20px;
        background: #f0fff4;
        border: 1px solid #9ae6b4;
        color: #276749;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
    }
    .imballaggi-page .hint {
        font-size: 13px;
        color: #666;
        margin: 0 0 20px;
        max-width: 1100px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.5;
    }
    .imballaggi-page .form-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .imballaggi-page .form-cat-emoji {
        font-size: 28px;
        line-height: 1;
    }
    .imballaggi-page .form-cat-emoji:empty {
        display: none;
    }
    .imballaggi-page .new-box {
        border: 2px dashed #ddd;
        text-align: center;
        padding: 16px;
        margin-bottom: 22px;
        cursor: pointer;
        border-radius: 12px;
        background: #fff;
        transition: border-color 0.2s, background 0.2s;
        font: inherit;
        width: 100%;
    }
    .imballaggi-page .new-box:hover {
        border-color: {{ $accent }};
        background: #fff5eb;
    }
    .imballaggi-page .list-heading {
        font-size: 11px;
        color: #bbb;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 12px;
        padding-left: 4px;
    }
    .imballaggi-page .tipo-pill {
        display: inline-block;
        margin-top: 4px;
        font-size: 11px;
        font-weight: 600;
        color: {{ $accent }};
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .imballaggi-page .preferito-wrap {
        display: none;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        margin-top: 14px;
        padding: 12px 14px;
        background: #fafafa;
        border-radius: 8px;
        border: 1px dashed #ddd;
        width: 100%;
        box-sizing: border-box;
    }
    .imballaggi-page .preferito-label {
        font-size: 13px;
        color: #333;
        font-weight: 600;
        line-height: 1.35;
        flex: 1 1 auto;
        min-width: 0;
    }
    /* app.css imposta button { width:100% }: qui serve esplicito o la stella “mangia” la riga */
    .imballaggi-page .star-btn {
        flex-shrink: 0;
        width: auto;
        max-width: none;
        margin-left: auto;
        align-self: center;
        background: transparent;
        background-color: transparent;
        color: inherit;
        border: none;
        cursor: pointer;
        font-size: 24px;
        padding: 6px 10px;
        line-height: 1;
        border-radius: 8px;
        transition: background 0.15s;
    }
    .imballaggi-page .star-btn:hover {
        background: rgba(255, 102, 0, 0.12);
    }
    .imballaggi-page .imballaggi-cat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 18px;
    }
    @media (max-width: 520px) {
        .imballaggi-page .imballaggi-cat-grid {
            grid-template-columns: 1fr;
        }
    }
    .imballaggi-page .imballaggi-cat-card {
        border: var(--sq-shell-border-width) solid var(--sq-shell-border);
        border-radius: 12px;
        box-shadow: var(--sq-card-shadow);
        padding: 14px 12px;
        text-align: center;
        cursor: pointer;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
        font: inherit;
        width: 100%;
        box-sizing: border-box;
    }
    .imballaggi-page .imballaggi-cat-card:hover {
        border-color: {{ $accent }};
        box-shadow: var(--sq-card-shadow), 0 4px 12px rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
    }
    .imballaggi-page .imballaggi-cat-card.is-selected {
        border-color: {{ $accent }};
        background: #fff8f2;
        box-shadow: var(--sq-card-shadow), 0 0 0 2px rgba(255, 102, 0, 0.2);
    }
    .imballaggi-page .imballaggi-cat-card .cat-emoji { font-size: 28px; line-height: 1; margin-bottom: 6px; }
    .imballaggi-page .imballaggi-cat-card .cat-label {
        font-weight: 700;
        font-size: 14px;
        color: #333;
    }
    .imballaggi-page .imballaggi-cat-card .cat-count {
        font-size: 11px;
        color: #888;
        margin-top: 4px;
    }
    .imballaggi-page .imballaggi-pref-hint {
        font-size: 11px;
        color: #888;
        line-height: 1.4;
        margin: 0 0 12px;
        padding: 8px 10px;
        background: #fafafa;
        border-radius: 8px;
        border: 1px dashed #e0e0e0;
        display: none;
    }
    .imballaggi-page .imballaggi-pref-hint.is-visible { display: block; }
    .imballaggi-page .imballaggi-list-placeholder {
        font-size: 13px;
        color: #888;
        text-align: center;
        padding: 22px 12px;
        border: 1px dashed #ddd;
        border-radius: 10px;
        margin-bottom: 14px;
    }
</style>

<div class="imballaggi-page">
    @if (session('ok'))
        <div class="flash-ok">{{ session('ok') }}</div>
    @endif

    <div class="intro-copy">
        <p>In questa sezione puoi configurare e gestire i tuoi set di Package personalizzati per velocizzare la creazione delle tue spedizioni.</p>
        <p><strong>Come funziona:</strong></p>
        <p><strong>Organizzazione per categoria:</strong> I Package sono suddivisi per tipologia (ad esempio Pacco, Pallet o Documenti). Clicca su una categoria per visualizzare l'elenco di quelli che hai gia creato.</p>
        <p><strong>Gestione flessibile:</strong> Puoi aggiungere nuovi formati in qualsiasi momento e modificare o eliminare quelli esistenti per adattarli alle tue necessita.</p>
        <p><strong>Package Preferito:</strong> Per ogni categoria, hai la possibilita di definire un Package come "Preferito". Il preferito sara visualizzato sempre in cima per ogni categoria.</p>
        <p><strong>Utilizzo rapido:</strong> Nell'inserimo di una spedizione, potrai richiamare i tuoi Package salvati con un semplice clic, evitando di dover inserire ogni volta misure e pesi manualmente.</p>
    </div>

    <div class="page-container-grid">
        <div class="card-style">
            <h2 class="form-title-row" style="color:{{ $accent }}; margin:0 0 22px; font-size: 20px;">
                <span id="form-cat-emoji" class="form-cat-emoji" aria-hidden="true"></span>
                <span id="form-title-label">Dettaglio Package</span>
            </h2>

            <form id="packageForm" onsubmit="return false;">
                <input type="hidden" id="p_id" value="">

                <div class="form-group">
                    <label for="p_name">Nome</label>
                    <input type="text" id="p_name" class="form-control" maxlength="120" placeholder="Es. Scatola media" autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="p_tipo">Tipo spedizione</label>
                    <select id="p_tipo" class="form-control">
                        <option value="">— Scegli —</option>
                        @foreach ($tipi as $t)
                            <option value="{{ $t->id }}">{{ $t->tipo_spedizione }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="p_alt">Altezza (cm)</label>
                        <input type="number" id="p_alt" class="form-control" step="0.01" min="0.01" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="p_larg">Larghezza (cm)</label>
                        <input type="number" id="p_larg" class="form-control" step="0.01" min="0.01" placeholder="0">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="p_spe">Spessore (cm)</label>
                        <input type="number" id="p_spe" class="form-control" step="0.01" min="0.01" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="p_peso">Peso (kg)</label>
                        <input type="text" id="p_peso" class="form-control" placeholder="" inputmode="decimal" autocomplete="off">
                        <p class="peso-field-hint" style="font-size:11px;color:#888;margin:6px 0 0;">Decimali con virgola, es. 0,5</p>
                    </div>
                </div>

                <div id="preferito-wrap" class="preferito-wrap" role="group" aria-label="Preferito per tipo di spedizione">
                    <span id="preferito-label-text" class="preferito-label"></span>
                    <button type="button" id="btn-preferito" class="star-btn"
                            title="Questo Package sarà elencato per primo nella home quando scegli lo stesso tipo di spedizione. Una sola stella per categoria (es. Pacco, Documento o Pallet). Clicca di nuovo sulla stella per rimuovere il preferito."
                            aria-label="Preferito per questo tipo di spedizione">
                        <i class="fa-regular fa-star" aria-hidden="true" style="color:#bbb;"></i>
                    </button>
                </div>

                <button type="button" id="main-button" class="btn-save" onclick="handleSave()">Salva</button>
            </form>
        </div>

        <div class="column-right">
            <button type="button" class="new-box" onclick="resetFormForNew()">
                <strong style="color:{{ $accent }};">+ Nuovo Package</strong>
            </button>

            <div class="imballaggi-cat-grid" id="imballaggi-cat-grid" role="tablist" aria-label="Tipo di spedizione">
                @foreach ($categorieNav as $cat)
                    <button type="button"
                            class="imballaggi-cat-card"
                            id="cat-card-{{ $cat['slug'] }}"
                            data-tipo-id="{{ $cat['tipo_id'] }}"
                            data-slug="{{ $cat['slug'] }}"
                            role="tab"
                            aria-selected="false">
                        <div class="cat-emoji" aria-hidden="true">
                            @if ($cat['slug'] === 'pacco') 📦 @elseif ($cat['slug'] === 'pallet') 🛷 @else 📄 @endif
                        </div>
                        <div class="cat-label">{{ $cat['label'] }}</div>
                        <div class="cat-count js-cat-count" data-tipo-id="{{ $cat['tipo_id'] }}"></div>
                    </button>
                @endforeach
            </div>

            <div class="list-heading js-list-heading">Seleziona una categoria</div>

            <p id="pref-hint-cat" class="imballaggi-pref-hint" aria-live="polite">
                Non hai ancora indicato il tuo preferito per questa categoria.
            </p>

            <div id="lista-placeholder" class="imballaggi-list-placeholder"></div>

            <div id="lista-packages" style="display: none;"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const CSRF = @json(csrf_token());
    const BASE = @json(url('/imballaggi'));
    const STORE_URL = @json(route('imballaggi.store'));
    const rows = @json($imballaggiJson);
    const categorieNav = @json($categorieNav);
    const INDEX_URL = @json(route('imballaggi.index'));
    let selectedTipoId = null;

    function rowById(id) {
        return rows.find((r) => String(r.id) === String(id));
    }

    function setActiveCard(id) {
        document.querySelectorAll('.imballaggi-page .package-card-box').forEach((el) => {
            el.classList.toggle('is-active', String(el.dataset.rowId) === String(id));
        });
    }

    function tipoNomeDaSelect() {
        const sel = document.getElementById('p_tipo');
        if (!sel || !sel.value) return '';
        const opt = sel.options[sel.selectedIndex];
        return opt ? String(opt.text).trim() : '';
    }

    function syncPreferitoBar() {
        const wrap = document.getElementById('preferito-wrap');
        const id = document.getElementById('p_id').value.trim();
        const btn = document.getElementById('btn-preferito');
        const labelEl = document.getElementById('preferito-label-text');
        if (!wrap || !btn) return;
        const icon = btn.querySelector('i');
        if (!id) {
            wrap.style.display = 'none';
            if (labelEl) labelEl.textContent = '';
            return;
        }
        wrap.style.display = 'flex';
        const r = rowById(id);
        const cat = tipoNomeDaSelect() || (r && r.tipo_label ? String(r.tipo_label) : '');
        if (labelEl) {
            labelEl.textContent = cat
                ? 'Definisci come preferito nella categoria ' + cat + '.'
                : 'Definisci come preferito.';
        }
        const on = !!(r && r.is_preferito);
        if (icon) {
            icon.classList.remove('fa-solid', 'fa-regular');
            icon.classList.add(on ? 'fa-solid' : 'fa-regular');
            icon.style.color = on ? '#f4b400' : '#bbb';
        }
    }

    function validateWeight(el) {
        let val = el.value.replace(',', '.');
        val = val.replace(/[^0-9.]/g, '');
        const parts = val.split('.');
        if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
        el.value = val;
    }

    document.getElementById('p_peso').addEventListener('input', function () {
        validateWeight(this);
    });

    document.getElementById('btn-preferito').addEventListener('click', async function () {
        const id = document.getElementById('p_id').value.trim();
        if (!id) return;
        const fd = new FormData();
        fd.append('_token', CSRF);
        const res = await fetch(BASE.replace(/\/$/, '') + '/' + encodeURIComponent(id) + '/preferito', {
            method: 'POST',
            body: fd,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) {
            Swal.fire('Errore', 'Impossibile aggiornare il preferito.', 'error');
            return;
        }
        const tipoId = data.id_tipo_spediziones;
        rows.forEach((r) => {
            if (String(r.id_tipo_spediziones) === String(tipoId)) r.is_preferito = false;
        });
        if (data.is_preferito) {
            const rr = rowById(data.imballaggio_id);
            if (rr) rr.is_preferito = true;
        }
        syncPreferitoBar();
        document.querySelectorAll('.package-card-box').forEach((card) => {
            const rid = card.dataset.rowId;
            const rr = rowById(rid);
            const icon = card.querySelector('.js-list-pref-star');
            if (!icon || !rr) return;
            const on = !!rr.is_preferito;
            icon.classList.remove('fa-solid', 'fa-regular');
            icon.classList.add(on ? 'fa-solid' : 'fa-regular');
            icon.style.color = on ? '#f4b400' : '#ddd';
        });
        if (selectedTipoId) {
            renderLista(selectedTipoId);
        }
        updateCatCounts();
    });

    function setFieldsReadOnly(ro) {
        ['p_name', 'p_tipo', 'p_alt', 'p_larg', 'p_spe', 'p_peso'].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            if (el.tagName === 'SELECT') el.disabled = ro;
            else el.readOnly = ro;
        });
    }

    function fmtPesoDisplay(n) {
        const x = Number(n);
        if (Number.isNaN(x)) return '';
        return x.toFixed(2).replace('.', ',');
    }

    function slugForTipoId(tipoId) {
        return (categorieNav.find((c) => String(c.tipo_id) === String(tipoId)) || {}).slug || '';
    }

    function syncFormCategoryIcon() {
        const el = document.getElementById('form-cat-emoji');
        if (!el) {
            return;
        }
        const id = document.getElementById('p_id').value.trim();
        let tipoId = document.getElementById('p_tipo').value;
        if (!tipoId && !id && selectedTipoId) {
            tipoId = String(selectedTipoId);
        }
        if (!tipoId && id) {
            const rr = rowById(id);
            if (rr) {
                tipoId = String(rr.id_tipo_spediziones);
            }
        }
        const slug = slugForTipoId(tipoId);
        let e = '';
        if (slug === 'pallet') {
            e = '🛷';
        } else if (slug === 'documenti') {
            e = '📄';
        } else if (slug === 'pacco') {
            e = '📦';
        } else if (tipoId) {
            const sample = rows.find((x) => String(x.id_tipo_spediziones) === String(tipoId));
            const lab = sample && sample.tipo_label ? String(sample.tipo_label).toLowerCase() : '';
            if (lab.includes('pallet')) {
                e = '🛷';
            } else if (lab.includes('document')) {
                e = '📄';
            } else {
                e = '📦';
            }
        }
        el.textContent = e;
    }

    document.getElementById('p_tipo').addEventListener('change', function () {
        if (document.getElementById('p_id').value.trim()) {
            syncPreferitoBar();
        }
        syncFormCategoryIcon();
    });

    function fillForm(id, mode) {
        const btn = document.getElementById('main-button');
        const title = document.getElementById('form-title-label');
        const tipoSelect = document.getElementById('p_tipo');
        const r = rowById(id);
        if (!r && (mode === 'view' || mode === 'edit' || mode === 'duplicate')) return;

        let name = r ? r.nome : '';
        let idTipo = r ? String(r.id_tipo_spediziones) : '';
        let alt = r ? r.altezza : '';
        let larg = r ? r.larghezza : '';
        let spe = r ? r.spessore : '';
        let peso = r ? r.peso : '';

        if (mode === 'duplicate' && r) {
            let finalName = 'Copia di ' + r.nome;
            if (finalName.length > 120) finalName = finalName.slice(0, 120);
            name = finalName;
            idTipo = String(r.id_tipo_spediziones);
            alt = r.altezza;
            larg = r.larghezza;
            spe = r.spessore;
            peso = r.peso;
        }

        document.getElementById('p_id').value = (mode === 'duplicate' || mode === 'new') ? '' : (r ? String(r.id) : '');
        document.getElementById('p_name').value = name;
        document.getElementById('p_tipo').value = idTipo;
        document.getElementById('p_alt').value = alt === '' ? '' : String(alt);
        document.getElementById('p_larg').value = larg === '' ? '' : String(larg);
        document.getElementById('p_spe').value = spe === '' ? '' : String(spe);
        document.getElementById('p_peso').value = peso === '' ? '' : fmtPesoDisplay(peso);

        const pesoHint = document.querySelector('.peso-field-hint');
        if (pesoHint) {
            pesoHint.style.display = mode === 'view' ? 'none' : 'block';
        }
        if (mode === 'view') {
            btn.style.display = 'none';
            title.textContent = 'Dettaglio Package';
            setFieldsReadOnly(true);
            if (tipoSelect) tipoSelect.disabled = true;
            setActiveCard(id);
        } else {
            btn.style.display = 'block';
            btn.textContent = mode === 'edit' ? 'Salva modifiche' : 'Crea Package';
            title.textContent =
                mode === 'edit' ? 'Modifica Package' :
                mode === 'duplicate' ? 'Duplica Package' : 'Nuovo Package';
            setFieldsReadOnly(false);
            if (tipoSelect) tipoSelect.disabled = (mode === 'edit' || mode === 'duplicate');
            if (mode === 'edit') setActiveCard(id);
            else setActiveCard(null);
        }
        syncPreferitoBar();
        syncFormCategoryIcon();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function iconForListRow(tipoIdRow, nome) {
        const slug = slugForTipoId(tipoIdRow);
        if (slug === 'pallet') {
            return '🛷';
        }
        if (slug === 'documenti') {
            return '📄';
        }
        if (slug === 'pacco') {
            const nomeLower = String(nome || '').toLowerCase();
            return nomeLower.includes('busta') || nomeLower.includes('envelope') || nomeLower.includes('lettera') ? '✉️' : '📦';
        }
        const sample = rows.find((x) => String(x.id_tipo_spediziones) === String(tipoIdRow));
        const lab = sample && sample.tipo_label ? String(sample.tipo_label).toLowerCase() : '';
        if (lab.includes('pallet')) {
            return '🛷';
        }
        if (lab.includes('document')) {
            return '📄';
        }
        const nomeLower = String(nome || '').toLowerCase();
        return nomeLower.includes('busta') || nomeLower.includes('envelope') || nomeLower.includes('lettera') ? '✉️' : '📦';
    }

    function rowsForTipoSorted(tipoId) {
        return rows
            .filter((r) => String(r.id_tipo_spediziones) === String(tipoId))
            .slice()
            .sort((a, b) => {
                if (!!a.is_preferito !== !!b.is_preferito) {
                    return a.is_preferito ? -1 : 1;
                }
                return String(a.nome).localeCompare(String(b.nome), 'it', { sensitivity: 'base' });
            });
    }

    function updateCatCounts() {
        categorieNav.forEach((c) => {
            const n = rows.filter((r) => String(r.id_tipo_spediziones) === String(c.tipo_id)).length;
            const el = document.querySelector('.js-cat-count[data-tipo-id="' + c.tipo_id + '"]');
            if (el) {
                el.textContent = n === 0 ? 'Nessuno' : (n === 1 ? '1 Package' : n + ' Package');
            }
        });
    }

    function renderLista(tipoId) {
        const list = document.getElementById('lista-packages');
        const hint = document.getElementById('pref-hint-cat');
        const ph = document.getElementById('lista-placeholder');
        const heading = document.querySelector('.js-list-heading');
        if (!list || !hint || !ph) {
            return;
        }
        const listArr = rowsForTipoSorted(tipoId);
        const hasPref = listArr.some((r) => r.is_preferito);
        hint.classList.toggle('is-visible', listArr.length > 0 && !hasPref);
        ph.style.display = 'none';
        list.style.display = 'block';
        const catMeta = categorieNav.find((c) => String(c.tipo_id) === String(tipoId)) || {};
        const label = catMeta.label || '';
        if (heading) {
            heading.textContent = listArr.length ? ('Package · ' + label) : ('Nessun Package · ' + label);
        }
        document.querySelectorAll('.imballaggi-cat-card').forEach((el) => {
            const on = String(el.dataset.tipoId) === String(tipoId);
            el.classList.toggle('is-selected', on);
            el.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        const fmt = (n) => Number(n).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (listArr.length === 0) {
            list.innerHTML = '<p style="color:#888;font-size:14px;">Non hai ancora Package in questa categoria. Usa «Nuovo Package».</p>';
            return;
        }
        list.innerHTML = listArr.map((emb) => {
            const icon = iconForListRow(emb.id_tipo_spediziones, emb.nome);
            const prefCls = emb.is_preferito ? 'fa-solid' : 'fa-regular';
            const prefStyle = emb.is_preferito ? 'color:#f4b400;' : 'color:#ddd;';
            const tipoPill = escapeHtml(emb.tipo_label || '');
            const nomeEsc = escapeHtml(emb.nome || '');
            return '<div class="package-card-box" data-row-id="' + emb.id + '" data-tipo-id="' + emb.id_tipo_spediziones + '">'
                + '<button type="button" class="item-info js-view-imb" data-id="' + emb.id + '" title="Visualizza">'
                + '<span style="font-size:26px;" aria-hidden="true">' + icon + '</span>'
                + '<div style="min-width:0;"><b>' + nomeEsc
                + ' <i class="js-list-pref-star ' + prefCls + ' fa-star" aria-hidden="true" style="margin-left:6px;font-size:14px;' + prefStyle + '"></i></b>'
                + '<span class="tipo-pill">' + tipoPill + '</span><span>'
                + fmt(emb.altezza) + ' × ' + fmt(emb.larghezza) + ' × ' + fmt(emb.spessore) + ' cm • '
                + fmt(emb.peso) + ' kg</span></div></button>'
                + '<div class="item-actions">'
                + '<button type="button" class="action-btn edit fas fa-pencil-alt js-edit-imb" title="Modifica" data-id="' + emb.id + '"></button>'
                + '<button type="button" class="action-btn copy fas fa-copy js-copy-imb" title="Duplica" data-id="' + emb.id + '"></button>'
                + '<button type="button" class="action-btn delete fas fa-trash-alt js-del-imb" title="Elimina" data-id="' + emb.id + '" data-name="' + escapeHtml(emb.nome || '') + '"></button>'
                + '</div></div>';
        }).join('');
    }

    function selectCategoria(tipoId) {
        if (!tipoId) {
            return;
        }
        selectedTipoId = String(tipoId);
        const url = new URL(window.location.href);
        const slug = (categorieNav.find((c) => String(c.tipo_id) === String(tipoId)) || {}).slug;
        if (slug) {
            url.searchParams.set('categoria', slug);
        } else {
            url.searchParams.delete('categoria');
        }
        window.history.replaceState({}, '', url.pathname + url.search);
        renderLista(selectedTipoId);
        const pid = document.getElementById('p_id').value.trim();
        if (!pid) {
            document.getElementById('p_tipo').value = String(tipoId);
        }
        syncFormCategoryIcon();
    }

    function redirectIndexWithCategoria() {
        const slug = (categorieNav.find((c) => String(c.tipo_id) === String(selectedTipoId)) || {}).slug;
        const q = slug ? ('?categoria=' + encodeURIComponent(slug)) : '';
        window.location.href = INDEX_URL + q;
    }

    function slugToTipoId(slug) {
        if (!slug) {
            return null;
        }
        const c = categorieNav.find((x) => x.slug === String(slug));
        return c ? String(c.tipo_id) : null;
    }

    function showListPlaceholder() {
        selectedTipoId = null;
        const hint = document.getElementById('pref-hint-cat');
        const ph = document.getElementById('lista-placeholder');
        const list = document.getElementById('lista-packages');
        const heading = document.querySelector('.js-list-heading');
        if (hint) {
            hint.classList.remove('is-visible');
        }
        if (ph) {
            ph.style.display = 'block';
        }
        if (list) {
            list.innerHTML = '';
            list.style.display = 'none';
        }
        if (heading) {
            heading.textContent = 'Seleziona una categoria';
        }
        document.querySelectorAll('.imballaggi-cat-card').forEach((el) => {
            el.classList.remove('is-selected');
            el.setAttribute('aria-selected', 'false');
        });
        const url = new URL(window.location.href);
        url.searchParams.delete('categoria');
        window.history.replaceState({}, '', url.pathname + url.search);
    }

    document.querySelectorAll('.imballaggi-cat-card').forEach((btn) => {
        btn.addEventListener('click', () => selectCategoria(btn.dataset.tipoId));
    });

    const listaPackagesEl = document.getElementById('lista-packages');
    if (listaPackagesEl) {
        listaPackagesEl.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('.js-view-imb');
            if (viewBtn) {
                fillForm(viewBtn.dataset.id, 'view');
                return;
            }
            const ed = e.target.closest('.js-edit-imb');
            if (ed) {
                e.stopPropagation();
                fillForm(ed.dataset.id, 'edit');
                return;
            }
            const cp = e.target.closest('.js-copy-imb');
            if (cp) {
                e.stopPropagation();
                fillForm(cp.dataset.id, 'duplicate');
                return;
            }
            const del = e.target.closest('.js-del-imb');
            if (del) {
                e.stopPropagation();
                confirmDelete(del.dataset.id, del.dataset.name || '');
            }
        });
    }

    window.resetFormForNew = function () {
        document.getElementById('packageForm').reset();
        document.getElementById('p_id').value = '';
        const btn = document.getElementById('main-button');
        const tipoSelect = document.getElementById('p_tipo');
        btn.style.display = 'block';
        btn.textContent = 'Crea Package';
        document.getElementById('form-title-label').textContent = 'Nuovo Package';
        setFieldsReadOnly(false);
        if (tipoSelect) tipoSelect.disabled = false;
        setActiveCard(null);
        if (selectedTipoId) {
            document.getElementById('p_tipo').value = String(selectedTipoId);
        }
        document.getElementById('p_peso').value = '';
        const pesoHint = document.querySelector('.peso-field-hint');
        if (pesoHint) {
            pesoHint.style.display = 'block';
        }
        syncPreferitoBar();
        syncFormCategoryIcon();
    };

    function setFormLockedOnEntry() {
        const btn = document.getElementById('main-button');
        const titolo = document.getElementById('form-title-label');
        const currentTipo = selectedTipoId ? String(selectedTipoId) : '';

        document.getElementById('packageForm').reset();
        document.getElementById('p_id').value = '';
        if (currentTipo) {
            document.getElementById('p_tipo').value = currentTipo;
        }
        if (btn) btn.style.display = 'none';
        if (titolo) titolo.textContent = 'Dettaglio Package';
        setFieldsReadOnly(true);
        setActiveCard(null);
        syncPreferitoBar();
        syncFormCategoryIcon();
    }

    async function handleSave() {
        const id = document.getElementById('p_id').value.trim();
        const nome = document.getElementById('p_name').value.trim();
        const idTipo = document.getElementById('p_tipo').value;
        const alt = document.getElementById('p_alt').value;
        const larg = document.getElementById('p_larg').value;
        const spe = document.getElementById('p_spe').value;
        let pesoRaw = document.getElementById('p_peso').value.replace(',', '.');

        if (!nome || !idTipo || !alt || !larg || !spe || !pesoRaw) {
            Swal.fire('Attenzione', 'Compila tutti i campi.', 'warning');
            return;
        }

        const fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('nome', nome);
        fd.append('id_tipo_spediziones', idTipo);
        fd.append('altezza', alt);
        fd.append('larghezza', larg);
        fd.append('spessore', spe);
        fd.append('peso', pesoRaw);

        let url = STORE_URL;
        if (id) {
            fd.append('_method', 'PATCH');
            url = BASE.replace(/\/$/, '') + '/' + encodeURIComponent(id);
        }

        const res = await fetch(url, {
            method: 'POST',
            body: fd,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok && data.ok) {
            await Swal.fire({ title: 'Fatto', text: data.message || '', icon: 'success', timer: 900, showConfirmButton: false });
            const savedTipo = document.getElementById('p_tipo').value;
            const slug = (categorieNav.find((c) => String(c.tipo_id) === String(savedTipo)) || {}).slug;
            const q = slug ? ('?categoria=' + encodeURIComponent(slug)) : '';
            window.location.href = INDEX_URL + q;
            return;
        }

        if (res.status === 422 && data.errors) {
            const first = Object.values(data.errors)[0];
            const msg = Array.isArray(first) ? first[0] : String(first);
            Swal.fire('Attenzione', msg, 'warning');
            return;
        }

        Swal.fire('Errore', data.message || 'Operazione non riuscita.', 'error');
    }
    window.handleSave = handleSave;

    function confirmDelete(id, name) {
        Swal.fire({
            title: 'Eliminare?',
            text: name,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonText: 'Annulla',
            confirmButtonText: 'Sì, elimina',
        }).then(async (result) => {
            if (!result.isConfirmed) return;
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('_method', 'DELETE');
            const res = await fetch(BASE.replace(/\/$/, '') + '/' + encodeURIComponent(id), {
                method: 'POST',
                body: fd,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.ok) {
                redirectIndexWithCategoria();
            } else {
                Swal.fire('Errore', data.message || 'Eliminazione non riuscita.', 'error');
            }
        });
    }

    const params = new URLSearchParams(window.location.search);
    const modifica = params.get('modifica');
    const nuovo = params.get('nuovo');
    const catParam = params.get('categoria');
    const initialTipo = slugToTipoId(catParam);

    updateCatCounts();

    if (nuovo) {
        if (initialTipo) {
            selectCategoria(initialTipo);
        } else {
            showListPlaceholder();
        }
        resetFormForNew();
    } else if (modifica && rowById(modifica)) {
        const r = rowById(modifica);
        selectCategoria(String(r.id_tipo_spediziones));
        fillForm(modifica, 'view');
    } else if (initialTipo) {
        selectCategoria(initialTipo);
        setFormLockedOnEntry();
    } else {
        showListPlaceholder();
        setFormLockedOnEntry();
    }

    syncFormCategoryIcon();
})();
</script>
@endsection
