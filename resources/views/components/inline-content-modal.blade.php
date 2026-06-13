@props([
    'id' => 'inline-content-modal',
    'title' => '',
    'content' => '',
    'triggerLabel' => '',
])

@if (trim((string) $content) !== '' && trim((string) $triggerLabel) !== '')
    <button type="button" class="sq-inline-modal-trigger" data-inline-modal-open="{{ $id }}">{{ $triggerLabel }}</button>

    <div class="sq-inline-modal-backdrop" id="{{ $id }}" aria-hidden="true">
        <div class="sq-inline-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
            <div class="sq-inline-modal__head">
                <strong id="{{ $id }}-title">{{ $title }}</strong>
                <button type="button" class="sq-inline-modal__close" data-inline-modal-close="{{ $id }}" aria-label="Chiudi">×</button>
            </div>
            <div class="sq-inline-modal__body">{!! (string) $content !!}</div>
        </div>
    </div>

    <script>
    (function () {
        var modalId = @json($id);
        var modal = document.getElementById(modalId);
        if (!modal || modal.dataset.boundInlineModal === '1') return;
        modal.dataset.boundInlineModal = '1';
        var openBtn = document.querySelector('[data-inline-modal-open="' + modalId + '"]');
        var closeBtn = modal.querySelector('[data-inline-modal-close="' + modalId + '"]');
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
