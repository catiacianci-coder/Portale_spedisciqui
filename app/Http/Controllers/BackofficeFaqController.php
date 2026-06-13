<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BackofficeFaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('backoffice.faq.index', [
            'faqs' => $faqs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:65000'],
        ]);

        Faq::query()->create([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'sort_order' => Faq::nextSortOrder(),
        ]);

        return redirect()
            ->route('backoffice.faq.index')
            ->with('status', 'Domanda aggiunta.');
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:65000'],
        ]);

        $faq->update([
            'question' => $data['question'],
            'answer' => $data['answer'],
        ]);

        return redirect()
            ->route('backoffice.faq.index')
            ->with('status', 'FAQ aggiornata.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        $this->renumberSortOrder();

        return redirect()
            ->route('backoffice.faq.index')
            ->with('status', 'FAQ rimossa.');
    }

    public function move(Request $request, Faq $faq): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $items = Faq::query()->orderBy('sort_order')->orderBy('id')->get()->values();
        $i = $items->search(fn (Faq $f): bool => $f->id === $faq->id);
        if ($i === false) {
            abort(404);
        }

        $j = $data['direction'] === 'up' ? $i - 1 : $i + 1;
        if ($j < 0 || $j >= $items->count()) {
            return redirect()->route('backoffice.faq.index');
        }

        $list = $items->all();
        [$list[$i], $list[$j]] = [$list[$j], $list[$i]];

        DB::transaction(static function () use ($list): void {
            foreach ($list as $idx => $item) {
                $item->update(['sort_order' => $idx + 1]);
            }
        });

        return redirect()
            ->route('backoffice.faq.index')
            ->with('status', 'Ordine aggiornato.');
    }

    private function renumberSortOrder(): void
    {
        $items = Faq::query()->orderBy('sort_order')->orderBy('id')->get();
        DB::transaction(static function () use ($items): void {
            foreach ($items as $idx => $item) {
                $item->update(['sort_order' => $idx + 1]);
            }
        });
    }
}
