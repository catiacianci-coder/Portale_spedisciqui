@extends('layouts.app')

@section('content')
@php
    $docQueryParams = ['tab' => 'documentos', 'slug' => $slug];
@endphp
<div class="sq-page-1200">

    <nav class="sq-bo-docs-top-tabs" aria-label="Sezioni">
        <a href="{{ route('backoffice.gestao_documentos.index', ['tab' => 'documentos', 'slug' => $slug]) }}" @class(['is-active' => $tab === 'documentos'])>Documenti</a>
        <a href="{{ route('backoffice.gestao_documentos.index', ['tab' => 'ajuda', 'slug' => $slug]) }}" @class(['is-active' => $tab === 'ajuda'])>Testi di aiuto</a>
    </nav>

    @if (session('status'))
        <p class="sq-alert-ok sq-mb-16" role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p class="sq-alert-err sq-mb-16" role="alert">{{ $errors->first() }}</p>
    @endif

    @if ($tab === 'documentos')
        <nav class="sq-bo-docs-tabs" aria-label="Tipo di documento">
            <a href="{{ route('backoffice.gestao_documentos.index', ['tab' => 'documentos', 'slug' => \App\Models\LegalDocumentVersion::SLUG_TERMOS]) }}" @class(['is-active' => $slug === \App\Models\LegalDocumentVersion::SLUG_TERMOS])>Termini legali</a>
            <a href="{{ route('backoffice.gestao_documentos.index', ['tab' => 'documentos', 'slug' => \App\Models\LegalDocumentVersion::SLUG_PRIVACIDADE]) }}" @class(['is-active' => $slug === \App\Models\LegalDocumentVersion::SLUG_PRIVACIDADE])>Informativa privacy</a>
            <a href="{{ route('backoffice.gestao_documentos.index', ['tab' => 'documentos', 'slug' => \App\Models\LegalDocumentVersion::SLUG_COOKIES]) }}" @class(['is-active' => $slug === \App\Models\LegalDocumentVersion::SLUG_COOKIES])>Politica cookie</a>
            <a href="{{ route('backoffice.gestao_documentos.index', ['tab' => 'documentos', 'slug' => \App\Models\LegalDocumentVersion::SLUG_REEMBOLSO]) }}" @class(['is-active' => $slug === \App\Models\LegalDocumentVersion::SLUG_REEMBOLSO])>Politica di rimborso</a>
            <a href="{{ route('backoffice.gestao_documentos.index', ['tab' => 'documentos', 'slug' => \App\Models\LegalDocumentVersion::SLUG_CONDICOES_WALLET]) }}" @class(['is-active' => $slug === \App\Models\LegalDocumentVersion::SLUG_CONDICOES_WALLET])>Condizioni Wallet</a>
        </nav>
    @endif

    @php
        $rotaPublica = \App\Models\LegalDocumentVersion::publicRouteNameForSlug($slug);
    @endphp
    @if ($tab === 'documentos' && $rotaPublica !== null)
        <p class="sq-mb-16">
            <a href="{{ route($rotaPublica) }}" target="_blank" rel="noopener" class="sq-link-brand">Vedi pagina pubblica « {{ \App\Models\LegalDocumentVersion::labelPaginaPublica($slug) }} »</a>
        </p>
    @endif
    @if ($tab === 'documentos' && $slug === \App\Models\LegalDocumentVersion::SLUG_CONDICOES_WALLET)
        <p class="sq-mb-16">
            <a href="{{ route('wallet.ricarica') }}" target="_blank" rel="noopener" class="sq-link-brand">Vedi pagina Ricarica wallet (pop-up in fondo)</a>
        </p>
    @endif

    @if ($tab === 'documentos')
        <div class="sq-bo-docs-card">
            <h2>Nuova versione — {{ \App\Models\LegalDocumentVersion::labelPaginaPublica($slug) }}</h2>
            @if ($slug === \App\Models\LegalDocumentVersion::SLUG_CONDICOES_WALLET)
                <p class="sq-bo-docs-hint">
                    Questo testo compare nel <strong>pop-up</strong> in fondo alla pagina <strong>Ricarica wallet</strong> (link « come funziona il pagamento con Wallet »).
                    Non crea una pagina pubblica separata. Per i termini completi della piattaforma, usa <strong>Termini legali</strong>.
                </p>
            @endif
            <p class="sq-bo-docs-hint">
                Usa l'editor: nel menu <strong>Paragrafo</strong> scegli <strong>Titolo 2</strong> o <strong>Titolo 3</strong> per le sezioni. Incollare da Word di solito mantiene grassetto e liste; i titoli Word non sempre arrivano come blocchi — usa il menu dei blocchi o il pulsante di strutturazione.
                Se preferisci testo semplice, scrivi o incolla in « Testo semplice » e usa <strong>Struttura testo semplice</strong>: <code>17. Sezione</code> (numero e punto) diventa Titolo 2; <code>17.2. Sottosezione</code> (due livelli) diventa Titolo 3; righe con <code>- </code> o <code>• </code> diventano elenco.
            </p>
            <form method="post" action="{{ route('backoffice.gestao_documentos.store') }}" id="form-doc">
                @csrf
                <input type="hidden" name="slug" value="{{ e($slug) }}">

                <div class="sq-bo-docs-field">
                    <label for="titulo">Titolo di questa versione</label>
                    <input type="text" id="titulo" name="titulo" value="{{ old('titulo', \App\Models\LegalDocumentVersion::defaultTituloForSlug($slug)) }}" required maxlength="255">
                </div>

                <div class="sq-bo-docs-field">
                    <label for="texto_plano">Testo semplice (opzionale — per il pulsante di strutturazione)</label>
                    <textarea id="texto_plano" placeholder="Incolla qui testo senza formattazione o esportato come .txt…"></textarea>
                    <div class="sq-bo-docs-row-actions">
                        <button type="button" class="sq-bo-docs-btn sq-bo-docs-btn--ghost" id="btn-formatar-plano">
                            <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Struttura testo semplice nell'editor
                        </button>
                        <span id="formatar-status" style="font-size:13px;color:#64748b;"></span>
                    </div>
                </div>

                <div class="sq-bo-docs-field">
                    <label for="conteudo_html">Contenuto (HTML)</label>
                    <textarea id="conteudo_html" name="conteudo_html" rows="16">{{ old('conteudo_html') }}</textarea>
                </div>

                <div class="sq-bo-docs-field">
                    <label for="vigente_desde">Valido dal</label>
                    <input type="date" id="vigente_desde" name="vigente_desde" value="{{ old('vigente_desde', now()->format('Y-m-d')) }}">
                </div>

                <div class="sq-bo-docs-check-row">
                    <input type="checkbox" name="publicar" id="publicar" value="1" {{ old('publicar') ? 'checked' : '' }}>
                    <label for="publicar" style="margin:0;font-weight:500;">
                        @if ($slug === \App\Models\LegalDocumentVersion::SLUG_CONDICOES_WALLET)
                            Pubblica ora (visibile nel pop-up della pagina Ricarica wallet)
                        @else
                            Pubblica ora (visibile nella pagina: {{ \App\Models\LegalDocumentVersion::labelPaginaPublica($slug) }})
                        @endif
                    </label>
                </div>

                <button type="submit" class="sq-bo-docs-btn sq-bo-docs-btn--primary"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Salva</button>
            </form>
        </div>

        <div class="sq-bo-docs-card">
            <h2>Versioni registrate</h2>
            <form method="get" action="{{ route('backoffice.gestao_documentos.index') }}" style="display:flex;justify-content:flex-end;margin-bottom:12px;">
                <input type="hidden" name="tab" value="documentos">
                <input type="hidden" name="slug" value="{{ $slug }}">
                <label for="per_page_doc_versoes" class="filtro-label" style="margin-right:8px;">Per pagina</label>
                <select id="per_page_doc_versoes" name="per_page" class="sq-etichette-per-page-select" onchange="this.form.submit()">
                    @foreach ([10, 25, 50] as $n)
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </form>
            @if ($versoes->isEmpty())
                <p style="color:#64748b;margin:0;">Nessuna versione per questo documento.</p>
            @else
                <table class="sq-bo-docs-table">
                    <thead>
                        <tr>
                            <th>Stato</th>
                            <th>Titolo</th>
                            <th>Valido dal</th>
                            <th>Pubblicato</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($versoes as $v)
                            <tr>
                                <td>
                                    @if ($v->isPublicado())
                                        <span class="sq-bo-docs-badge sq-bo-docs-badge--live">Pubblicato</span>
                                    @else
                                        <span class="sq-bo-docs-badge sq-bo-docs-badge--draft">Bozza</span>
                                    @endif
                                </td>
                                <td>{{ $v->titulo }}</td>
                                <td>{{ $v->vigente_desde?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $v->publicado_em?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('backoffice.gestao_documentos.edit', $v) }}" class="sq-bo-docs-btn sq-bo-docs-btn--ghost" style="padding:6px 12px;font-size:13px;white-space:nowrap;">Modifica</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @include('partials.tabella-paginazione', [
                    'paginator' => $versoes,
                    'perPage' => $perPage,
                    'queryParams' => array_merge($docQueryParams, ['per_page' => $perPage]),
                ])
            @endif
        </div>
    @endif

    @if ($tab === 'ajuda')
        @php
            $editingPage = $helpEditPageKey !== '' ? $helpEditPageKey : old('page_key', '');
            $editing = $editingPage !== '' ? $helpContents->get($editingPage) : null;
        @endphp
        <div class="sq-bo-docs-card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
                <h2 style="margin:0;">Testi di aiuto (pulsante nella fascia viola)</h2>
                <a href="{{ route('backoffice.gestao_documentos.index', ['tab' => 'ajuda', 'slug' => $slug]) }}" class="sq-bo-docs-btn sq-bo-docs-btn--ghost" style="padding:8px 14px;font-size:13px;">+ Nuovo</a>
            </div>
            <p class="sq-bo-docs-hint">
                Crea o modifica il contenuto di aiuto per pagina. Con <strong>Pubblica</strong>, il pulsante compare nella fascia viola della pagina scelta.
            </p>
            <form method="post" action="{{ route('backoffice.gestao_documentos.ajuda_pagina', ['slug' => $slug]) }}" id="form-ajuda" style="border:1px solid #eee;border-radius:12px;padding:14px;margin:0 0 14px;">
                @csrf
                <div class="sq-bo-docs-field">
                    <label for="help_page_key">Pagina pubblica</label>
                    <select name="page_key" id="help_page_key">
                        <option value="">Seleziona…</option>
                        @foreach ($helpPages as $pageKey => $pageLabel)
                            <option value="{{ $pageKey }}" @selected($editingPage === $pageKey)>{{ $pageLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sq-bo-docs-field">
                    <label for="help_button_label">Testo del pulsante</label>
                    <input type="text" id="help_button_label" name="button_label" value="{{ old('button_label', (string) ($editing->button_label ?? 'Come funziona?')) }}" maxlength="80">
                </div>
                <div class="sq-bo-docs-field">
                    <label for="help_modal_title">Titolo del pop-up</label>
                    <input type="text" id="help_modal_title" name="modal_title" value="{{ old('modal_title', (string) ($editing->modal_title ?? 'Come funziona?')) }}" maxlength="120">
                </div>
                <div class="sq-bo-docs-field">
                    <label for="help_modal_content">Testo di aiuto</label>
                    <textarea id="help_modal_content" name="modal_content" rows="10" maxlength="20000" placeholder="Scrivi qui il testo mostrato nel pop-up.">{{ old('modal_content', (string) ($editing->modal_content ?? '')) }}</textarea>
                </div>
                <div class="sq-bo-docs-row-actions">
                    <button type="submit" class="sq-bo-docs-btn sq-bo-docs-btn--ghost" name="acao" value="salvar_bozza" style="padding:8px 14px;font-size:13px;">Salva bozza</button>
                    <button type="submit" class="sq-bo-docs-btn sq-bo-docs-btn--primary" name="acao" value="publicar" style="padding:8px 14px;font-size:13px;">Pubblica</button>
                </div>
            </form>
        </div>

        <div class="sq-bo-docs-card">
            <h2>Testi già creati</h2>
            @if ($helpContents->isEmpty())
                <p style="color:#64748b;margin:0;">Nessun testo di aiuto ancora.</p>
            @else
                <table class="sq-bo-docs-table">
                    <thead>
                        <tr>
                            <th>Pagina</th>
                            <th>Titolo</th>
                            <th>Stato</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($helpPages as $pageKey => $pageLabel)
                            @php
                                $item = $helpContents->get($pageKey);
                            @endphp
                            @if ($item)
                                <tr>
                                    <td>{{ $pageLabel }}</td>
                                    <td>{{ $item->modal_title }}</td>
                                    <td>
                                        @if ($item->is_active)
                                            <span class="sq-bo-docs-badge sq-bo-docs-badge--live">Pubblicato</span>
                                        @else
                                            <span class="sq-bo-docs-badge sq-bo-docs-badge--draft">Bozza</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('backoffice.gestao_documentos.index', ['tab' => 'ajuda', 'slug' => $slug, 'help_page' => $pageKey]) }}" class="sq-bo-docs-btn sq-bo-docs-btn--ghost" style="padding:6px 12px;font-size:13px;white-space:nowrap;">Modifica</a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';

    var sqDocEditorStyle = 'body{font-family:Lexend,system-ui,sans-serif;font-size:15px;line-height:1.55;}h1{font-size:1.45em;font-weight:700;margin:1em 0 .5em;}h2{font-size:1.25em;font-weight:700;margin:1em 0 .45em;color:#111;}h3{font-size:1.1em;font-weight:700;margin:.9em 0 .4em;color:#1f2937;}h4{font-size:1.05em;font-weight:600;margin:.85em 0 .35em;}';
    var sqDocBlockFormats = 'Paragrafo=p; Titolo 1=h1; Titolo 2=h2; Titolo 3=h3; Titolo 4=h4';
    var sqDocPasteWord = 'p[style],br,strong,b,em,i,u,h1,h2,h3,h4,h5,h6,ul,ol,li,a[href|name|target],table,thead,tbody,tr,th,td';

    if (document.getElementById('conteudo_html')) {
        tinymce.init({
            selector: '#conteudo_html',
            height: 440,
            menubar: false,
            license_key: 'gpl',
            promotion: false,
            plugins: 'lists link table autoresize code paste',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table | removeformat | code',
            block_formats: sqDocBlockFormats,
            paste_merge_lists: true,
            paste_word_valid_elements: sqDocPasteWord,
            convert_urls: false,
            relative_urls: false,
            remove_script_host: false,
            extended_valid_elements: 'a[href|target|title|rel|class|id|style]',
            content_style: sqDocEditorStyle,
            setup: function (editor) {
                editor.on('change input', function () {
                    editor.save();
                });
            }
        });
    }

    if (document.getElementById('help_modal_content')) {
        tinymce.init({
            selector: '#help_modal_content',
            height: 320,
            menubar: false,
            license_key: 'gpl',
            promotion: false,
            plugins: 'lists link table autoresize code paste',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | removeformat | code',
            block_formats: sqDocBlockFormats,
            paste_merge_lists: true,
            paste_word_valid_elements: sqDocPasteWord,
            convert_urls: false,
            relative_urls: false,
            remove_script_host: false,
            extended_valid_elements: 'a[href|target|title|rel|class|id|style]',
            content_style: sqDocEditorStyle,
            setup: function (editor) {
                editor.on('change input', function () {
                    editor.save();
                });
            }
        });
    }

    var btn = document.getElementById('btn-formatar-plano');
    var statusEl = document.getElementById('formatar-status');
    if (btn) {
        btn.addEventListener('click', function () {
            var ta = document.getElementById('texto_plano');
            var texto = ta ? ta.value : '';
            if (!texto.trim()) {
                statusEl.textContent = 'Scrivi o incolla testo nel riquadro « Testo semplice ».';
                return;
            }
            statusEl.textContent = 'Elaborazione…';
            btn.disabled = true;
            fetch('{{ route('backoffice.gestao_documentos.formatar_texto') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ texto: texto })
            }).then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function (data) {
                var ed = tinymce.get('conteudo_html');
                if (ed && data.html) {
                    ed.setContent(data.html);
                    ed.save();
                }
                statusEl.textContent = 'Contenuto generato nell\'editor. Puoi rivederlo prima di salvare.';
            }).catch(function () {
                statusEl.textContent = 'Errore durante la strutturazione. Riprova.';
            }).finally(function () {
                btn.disabled = false;
            });
        });
    }

    var formDoc = document.getElementById('form-doc');
    if (formDoc) {
        formDoc.addEventListener('submit', function () {
            var ed = tinymce.get('conteudo_html');
            if (ed) ed.save();
        });
    }

    var formAjuda = document.getElementById('form-ajuda');
    if (formAjuda) {
        formAjuda.addEventListener('submit', function () {
            var ed = tinymce.get('help_modal_content');
            if (ed) ed.save();
        });
    }
})();
</script>
@endsection
