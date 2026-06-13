@props([
    'id' => 'page-help',
    'buttonLabel' => 'Come funziona?',
    'title' => 'Come funziona?',
    'content' => '',
])

@if (trim((string) $content) !== '')
    <button type="button" class="sq-page-help-btn" data-help-open="{{ $id }}">{{ $buttonLabel }}</button>

    <div class="sq-page-help-backdrop" id="{{ $id }}" aria-hidden="true">
        <div class="sq-page-help-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
            <div class="sq-page-help-modal__head">
                <strong id="{{ $id }}-title">{{ $title }}</strong>
                <button type="button" class="sq-page-help-modal__close" data-help-close="{{ $id }}" aria-label="Chiudi">×</button>
            </div>
            <div class="sq-page-help-modal__body">{!! (string) $content !!}</div>
        </div>
    </div>

    <script>
    (function () {
        var modalId = @json($id);
        var modal = document.getElementById(modalId);
        if (!modal || modal.dataset.boundHelp === '1') return;
        modal.dataset.boundHelp = '1';
        var openBtn = document.querySelector('[data-help-open="' + modalId + '"]');
        var closeBtn = modal.querySelector('[data-help-close="' + modalId + '"]');
        function openModal() {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }
        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
        if (openBtn) openBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (ev) {
            if (ev.target === modal) closeModal();
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
        });
    })();
    </script>
@endif
