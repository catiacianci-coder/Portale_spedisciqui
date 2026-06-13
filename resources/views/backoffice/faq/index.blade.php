@extends('layouts.app')

@section('content')
<div class="sq-page-1200">

    <p class="sq-mb-16">
        <a href="{{ route('faq.index') }}" target="_blank" rel="noopener" class="sq-link-brand">Vedi pagina pubblica FAQ</a>
    </p>

    @if (session('status'))
        <p class="sq-alert-ok sq-mb-16" role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p class="sq-alert-err sq-mb-16" role="alert">{{ $errors->first() }}</p>
    @endif

    <div class="sq-bo-docs-card">
        <h2>Nuova domanda</h2>
        <form method="post" action="{{ route('backoffice.faq.store') }}">
            @csrf
            <div class="sq-bo-docs-field">
                <label for="new_question">Domanda</label>
                <input type="text" id="new_question" name="question" value="{{ old('question') }}" required maxlength="500">
            </div>
            <div class="sq-bo-docs-field">
                <label for="new_answer">Risposta</label>
                <textarea id="new_answer" name="answer" required maxlength="65000" rows="6">{{ old('answer') }}</textarea>
            </div>
            <button type="submit" class="sq-bo-docs-btn sq-bo-docs-btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Aggiungi
            </button>
        </form>
    </div>

    @forelse ($faqs as $faq)
        <div class="sq-bo-docs-card">
            <div class="sq-bo-faq-row-title">FAQ #{{ $loop->iteration }} · ordine {{ $faq->sort_order }}</div>
            <form method="post" action="{{ route('backoffice.faq.update', $faq) }}">
                @csrf
                @method('PUT')
                <div class="sq-bo-docs-field">
                    <label for="q_{{ $faq->id }}">Domanda</label>
                    <input type="text" id="q_{{ $faq->id }}" name="question" value="{{ old('question', $faq->question) }}" required maxlength="500">
                </div>
                <div class="sq-bo-docs-field">
                    <label for="a_{{ $faq->id }}">Risposta</label>
                    <textarea id="a_{{ $faq->id }}" name="answer" required maxlength="65000" rows="6">{{ old('answer', $faq->answer) }}</textarea>
                </div>
                <div class="sq-bo-docs-row-actions">
                    <button type="submit" class="sq-bo-docs-btn sq-bo-docs-btn--primary">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Salva modifiche
                    </button>
                </div>
            </form>
            <div class="sq-bo-docs-row-actions">
                <form method="post" action="{{ route('backoffice.faq.move', $faq) }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="direction" value="up">
                    <button type="submit" class="sq-bo-docs-btn sq-bo-docs-btn--ghost" title="Sposta su" @if ($loop->first) disabled style="opacity:0.45;cursor:not-allowed;" @endif>
                        <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                    </button>
                </form>
                <form method="post" action="{{ route('backoffice.faq.move', $faq) }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="direction" value="down">
                    <button type="submit" class="sq-bo-docs-btn sq-bo-docs-btn--ghost" title="Sposta giù" @if ($loop->last) disabled style="opacity:0.45;cursor:not-allowed;" @endif>
                        <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                    </button>
                </form>
                <form method="post" action="{{ route('backoffice.faq.destroy', $faq) }}" style="display:inline;" onsubmit="return confirm('Rimuovere questa FAQ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="sq-bo-docs-btn sq-bo-docs-btn--danger">
                        <i class="fa-solid fa-trash" aria-hidden="true"></i> Rimuovi
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="sq-bo-docs-card">
            <p class="sq-m-0" style="color:#64748b;">Nessuna FAQ ancora. Aggiungine una sopra oppure esegui <code>php artisan db:seed --class=FaqSeeder</code> per il set iniziale.</p>
        </div>
    @endforelse
</div>
@endsection
