@extends('layouts.app')

@section('pageBanner')
    <x-sq-page-banner
        variant="backoffice"
        title="Modifica versione documento"
        icon="fa-file-lines"
        :parent-href="route('backoffice.gestao_documentos.index')"
        class="sq-page-banner--full"
    />
@endsection

@section('content')
<div class="sq-page-1200">
    <p class="sq-mb-16">
        <a href="{{ route('backoffice.gestao_documentos.index', ['slug' => $slug]) }}" class="sq-link-brand">← Gestione documenti</a>
    </p>

    @if (session('status'))
        <p class="sq-alert-ok sq-mb-16" role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p class="sq-alert-err sq-mb-16" role="alert">{{ $errors->first() }}</p>
    @endif

    <div class="sq-bo-docs-card">
        <h2>{{ \App\Models\LegalDocumentVersion::labelPaginaPublica($slug) }} — modifica sul record esistente</h2>
        <p class="sq-bo-docs-hint">
            @if ($slug === \App\Models\LegalDocumentVersion::SLUG_CONDICOES_WALLET)
                Testo mostrato nel pop-up della pagina <strong>Ricarica wallet</strong>. Modifica lo <strong>stesso record</strong> (non crea una nuova versione).
            @else
                Modifica lo <strong>stesso record</strong> nel database (non crea una riga nuova).
            @endif
            Nell'editor, usa il menu <strong>Paragrafo</strong> (angolo della barra) per applicare <strong>Titolo 2</strong> o <strong>Titolo 3</strong>. Il pulsante « Struttura testo semplice » riconosce <code>17. Sezione</code> come Titolo 2 e <code>17.2. Sottosezione</code> come Titolo 3.
            @if ($versao->isPublicado())
                Indica le date di validità e, se vuoi, la data di « pubblicato » mostrata sul sito.
            @else
                Questa è una <strong>bozza</strong>: resta invisibile sul sito finché non pubblichi una versione dalla schermata principale.
            @endif
        </p>

        <form method="post" action="{{ route('backoffice.gestao_documentos.update', $versao) }}" id="form-doc-edit">
            @csrf
            @method('PUT')

            <div class="sq-bo-docs-field">
                <label for="titulo">Titolo</label>
                <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $versao->titulo) }}" required maxlength="255">
            </div>

            <div class="sq-bo-docs-field">
                <label for="texto_plano">Testo semplice (opzionale)</label>
                <textarea id="texto_plano" placeholder="Incolla testo semplice e usa il pulsante sotto…"></textarea>
                <div class="sq-bo-docs-row-actions">
                    <button type="button" class="sq-bo-docs-btn sq-bo-docs-btn--ghost" id="btn-formatar-plano">
                        <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Struttura testo semplice nell'editor
                    </button>
                    <span id="formatar-status" style="font-size:13px;color:#64748b;"></span>
                </div>
            </div>

            <div class="sq-bo-docs-field">
                <label for="conteudo_html">Contenuto (HTML)</label>
                <textarea id="conteudo_html" name="conteudo_html" rows="16">{{ old('conteudo_html', $versao->conteudo_html) }}</textarea>
            </div>

            <div class="sq-bo-docs-field">
                <label for="vigente_desde">Valido dal @if ($versao->isPublicado())<span style="color:#b91c1c">*</span>@endif</label>
                <input type="date" id="vigente_desde" name="vigente_desde" value="{{ old('vigente_desde', $versao->vigente_desde?->format('Y-m-d')) }}">
            </div>

            @if ($versao->isPublicado())
                <div class="sq-bo-docs-field">
                    <label for="publicado_em_data">Data di pubblicazione (opzionale)</label>
                    <input type="date" id="publicado_em_data" name="publicado_em_data" value="{{ old('publicado_em_data', $versao->publicado_em?->format('Y-m-d')) }}">
                    <p class="sq-bo-docs-hint" style="margin-top:8px;margin-bottom:0;">Se lasci vuoto, resta l'istante già registrato. Se compili, si usa l'inizio di quel giorno (00:00).</p>
                </div>
            @endif

            <button type="submit" class="sq-bo-docs-btn sq-bo-docs-btn--primary"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Salva modifiche</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';

    tinymce.init({
        selector: '#conteudo_html',
        height: 440,
        menubar: false,
        license_key: 'gpl',
        promotion: false,
        plugins: 'lists link table autoresize code paste',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table | removeformat | code',
        block_formats: 'Paragrafo=p; Titolo 1=h1; Titolo 2=h2; Titolo 3=h3; Titolo 4=h4',
        paste_merge_lists: true,
        paste_word_valid_elements: 'p[style],br,strong,b,em,i,u,h1,h2,h3,h4,h5,h6,ul,ol,li,a[href|name|target],table,thead,tbody,tr,th,td',
        convert_urls: false,
        relative_urls: false,
        remove_script_host: false,
        extended_valid_elements: 'a[href|target|title|rel|class|id|style]',
        content_style: 'body{font-family:Lexend,system-ui,sans-serif;font-size:15px;line-height:1.55;}h1{font-size:1.45em;font-weight:700;margin:1em 0 .5em;}h2{font-size:1.25em;font-weight:700;margin:1em 0 .45em;color:#111;}h3{font-size:1.1em;font-weight:700;margin:.9em 0 .4em;color:#1f2937;}h4{font-size:1.05em;font-weight:600;margin:.85em 0 .35em;}',
        setup: function (editor) {
            editor.on('change input', function () {
                editor.save();
            });
        }
    });

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

    document.getElementById('form-doc-edit').addEventListener('submit', function () {
        var ed = tinymce.get('conteudo_html');
        if (ed) ed.save();
    });
})();
</script>
@endsection
