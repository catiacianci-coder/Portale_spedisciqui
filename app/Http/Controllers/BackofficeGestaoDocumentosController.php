<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\LegalDocumentVersion;
use App\Models\PageHelpContent;
use App\Services\LegalDocumentHtmlSanitizer;
use App\Services\LegalDocumentPlainTextToHtml;
use App\Support\FiltriTabella;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BackofficeGestaoDocumentosController extends Controller
{
    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'documentos');
        if (! in_array($tab, ['documentos', 'ajuda'], true)) {
            $tab = 'documentos';
        }

        $slug = (string) $request->query('slug', LegalDocumentVersion::SLUG_TERMOS);
        if ($slug === '' || ! in_array($slug, LegalDocumentVersion::allowedSlugs(), true)) {
            $slug = LegalDocumentVersion::SLUG_TERMOS;
        }

        $perPage = FiltriTabella::perPage($request);

        $versoes = LegalDocumentVersion::query()
            ->where('slug', $slug)
            ->with('publishedByUser')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $helpContents = PageHelpContent::query()
            ->whereIn('page_key', array_keys(PageHelpContent::managedPages()))
            ->orderBy('page_key')
            ->get()
            ->keyBy('page_key');

        $helpEditPageKey = (string) $request->query('help_page', '');
        if ($helpEditPageKey !== '' && ! array_key_exists($helpEditPageKey, PageHelpContent::managedPages())) {
            $helpEditPageKey = '';
        }

        return view('backoffice.gestao_documentos.index', [
            'tab' => $tab,
            'slug' => $slug,
            'versoes' => $versoes,
            'perPage' => $perPage,
            'helpContents' => $helpContents,
            'helpPages' => PageHelpContent::managedPages(),
            'helpEditPageKey' => $helpEditPageKey,
        ]);
    }

    public function updateHelpPage(
        Request $request,
        LegalDocumentHtmlSanitizer $sanitizer,
    ): RedirectResponse {
        $data = $request->validate([
            'page_key' => ['required', Rule::in(array_keys(PageHelpContent::managedPages()))],
            'button_label' => ['nullable', 'string', 'max:80'],
            'modal_title' => ['nullable', 'string', 'max:120'],
            'modal_content' => ['nullable', 'string', 'max:20000'],
            'acao' => ['required', Rule::in(['salvar_bozza', 'publicar'])],
        ]);

        $record = PageHelpContent::query()->firstOrNew([
            'page_key' => (string) $data['page_key'],
        ]);
        $record->button_label = trim((string) ($data['button_label'] ?? '')) !== ''
            ? trim((string) $data['button_label'])
            : 'Come funziona?';
        $record->modal_title = trim((string) ($data['modal_title'] ?? '')) !== ''
            ? trim((string) $data['modal_title'])
            : 'Come funziona?';
        $record->modal_content = $sanitizer->sanitize((string) ($data['modal_content'] ?? ''));
        $publicar = ((string) $data['acao']) === 'publicar';
        $record->is_active = $publicar;
        $record->save();

        $msg = $publicar
            ? 'Testo di aiuto pubblicato. Il pulsante compare nella fascia viola della pagina selezionata.'
            : 'Testo di aiuto salvato come bozza.';

        return redirect()
            ->route('backoffice.gestao_documentos.index', [
                'tab' => 'ajuda',
                'slug' => (string) $request->query('slug', LegalDocumentVersion::SLUG_TERMOS),
                'help_page' => (string) $data['page_key'],
            ])
            ->with('status', $msg);
    }

    public function store(
        Request $request,
        LegalDocumentHtmlSanitizer $sanitizer,
    ): RedirectResponse {
        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:64', Rule::in(LegalDocumentVersion::allowedSlugs())],
            'titulo' => ['required', 'string', 'max:255'],
            'vigente_desde' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $request->boolean('publicar')),
            ],
            'conteudo_html' => ['required', 'string'],
            'publicar' => ['nullable', 'boolean'],
        ]);

        $slug = isset($data['slug']) && $data['slug'] !== ''
            ? $data['slug']
            : LegalDocumentVersion::SLUG_TERMOS;

        $publicar = $request->boolean('publicar');
        $html = $sanitizer->sanitize($data['conteudo_html']);

        LegalDocumentVersion::query()->create([
            'slug' => $slug,
            'titulo' => $data['titulo'],
            'conteudo_html' => $html,
            'vigente_desde' => $publicar ? $data['vigente_desde'] : null,
            'publicado_em' => $publicar ? now() : null,
            'published_by_user_id' => $publicar ? $request->user()?->id : null,
        ]);

        $msg = $publicar
            ? match ($slug) {
                LegalDocumentVersion::SLUG_CONDICOES_WALLET => 'Versione salvata e pubblicata. Compare nel pop-up della pagina Ricarica wallet.',
                default => 'Versione salvata e pubblicata. Compare in '.LegalDocumentVersion::labelPaginaPublica($slug).'.',
            }
            : 'Bozza salvata (non visibile sul sito finché non viene pubblicata).';

        return redirect()
            ->route('backoffice.gestao_documentos.index', ['slug' => $slug])
            ->with('status', $msg);
    }

    public function edit(LegalDocumentVersion $versao): View
    {
        abort_unless(in_array($versao->slug, LegalDocumentVersion::allowedSlugs(), true), 404);

        return view('backoffice.gestao_documentos.edit', [
            'versao' => $versao,
            'slug' => $versao->slug,
        ]);
    }

    public function update(
        Request $request,
        LegalDocumentVersion $versao,
        LegalDocumentHtmlSanitizer $sanitizer,
    ): RedirectResponse {
        abort_unless(in_array($versao->slug, LegalDocumentVersion::allowedSlugs(), true), 404);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'conteudo_html' => ['required', 'string'],
            'vigente_desde' => [
                'nullable',
                'date',
                Rule::requiredIf(static fn () => $versao->isPublicado()),
            ],
            'publicado_em_data' => ['nullable', 'date'],
        ]);

        $html = $sanitizer->sanitize($data['conteudo_html']);

        $vigenteDesde = $data['vigente_desde'] ?? null;
        if ($versao->isPublicado() && ($vigenteDesde === null || $vigenteDesde === '')) {
            return redirect()
                ->back()
                ->withErrors(['vigente_desde' => 'Per le versioni pubblicate, indica « Valido dal ».'])
                ->withInput();
        }

        $publicadoEm = $versao->publicado_em;
        if ($versao->isPublicado()) {
            if ($request->filled('publicado_em_data')) {
                $publicadoEm = Carbon::parse($data['publicado_em_data'])->startOfDay();
            }
        } else {
            $publicadoEm = null;
        }

        $novaVigente = $versao->isPublicado()
            ? $vigenteDesde
            : ($request->filled('vigente_desde') ? $vigenteDesde : $versao->vigente_desde);

        $versao->update([
            'titulo' => $data['titulo'],
            'conteudo_html' => $html,
            'vigente_desde' => $novaVigente,
            'publicado_em' => $publicadoEm,
            'published_by_user_id' => $versao->isPublicado() ? ($request->user()?->id ?? $versao->published_by_user_id) : $versao->published_by_user_id,
        ]);

        return redirect()
            ->route('backoffice.gestao_documentos.index', ['slug' => $versao->slug])
            ->with('status', 'Versione aggiornata nello stesso record (testo e date). Non è stata creata una nuova versione.');
    }

    public function formatarTexto(
        Request $request,
        LegalDocumentPlainTextToHtml $formatter,
    ): JsonResponse {
        $data = $request->validate([
            'texto' => ['required', 'string', 'max:200000'],
        ]);

        return response()->json([
            'html' => $formatter->convert($data['texto']),
        ]);
    }
}
