@extends('layouts.app')

@section('content')
<div class="sq-page-1200 sq-bo-hub">
    <p class="sq-bo-hub-hint">Trascina le card per riordinarle. L&apos;ordine resta salvato su questo browser.</p>

    <div
        id="sq-bo-hub-grid"
        class="sq-bo-hub-grid"
        data-storage-key="backoffice-hub-order-{{ auth()->id() }}"
    >
        @foreach ($items as $item)
            <div class="sq-bo-hub-card-wrap" data-id="{{ $item['id'] }}">
                <button
                    type="button"
                    class="sq-bo-hub-card-grip"
                    draggable="true"
                    aria-label="Trascina per riordinare {{ $item['label'] }}"
                    title="Trascina per riordinare"
                >
                    <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                </button>
                <a href="{{ route($item['route']) }}" class="sq-bo-hub-card">
                    <span class="sq-bo-hub-card-ico-wrap" aria-hidden="true">
                        <i class="{{ $item['icon'] }} sq-bo-hub-card-ico"></i>
                    </span>
                    <span class="sq-bo-hub-card-body">
                        <span class="sq-bo-hub-card-title">{{ $item['label'] }}</span>
                        <span class="sq-bo-hub-card-desc">{{ $item['description'] }}</span>
                    </span>
                </a>
            </div>
        @endforeach
    </div>
</div>

<script>
(() => {
    const grid = document.getElementById('sq-bo-hub-grid');
    if (!grid) {
        return;
    }

    const storageKey = grid.dataset.storageKey || 'backoffice-hub-order';
    const wraps = () => [...grid.querySelectorAll('.sq-bo-hub-card-wrap')];

    const readSavedOrder = () => {
        try {
            const raw = localStorage.getItem(storageKey);
            if (!raw) {
                return null;
            }
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed.map(String) : null;
        } catch {
            return null;
        }
    };

    const applyOrder = (order) => {
        const known = new Set(wraps().map((el) => el.dataset.id));
        const valid = order.filter((id) => known.has(id));
        const missing = wraps()
            .map((el) => el.dataset.id)
            .filter((id) => !valid.includes(id));
        const finalOrder = [...valid, ...missing];

        finalOrder.forEach((id) => {
            const el = grid.querySelector(`.sq-bo-hub-card-wrap[data-id="${id}"]`);
            if (el) {
                grid.appendChild(el);
            }
        });
    };

    const saveOrder = () => {
        localStorage.setItem(
            storageKey,
            JSON.stringify(wraps().map((el) => el.dataset.id)),
        );
    };

    const saved = readSavedOrder();
    if (saved) {
        applyOrder(saved);
    }

    let draggedWrap = null;

    grid.querySelectorAll('.sq-bo-hub-card-grip').forEach((grip) => {
        grip.addEventListener('dragstart', (event) => {
            draggedWrap = grip.closest('.sq-bo-hub-card-wrap');
            if (!draggedWrap) {
                return;
            }
            draggedWrap.classList.add('sq-bo-hub-card-wrap--dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedWrap.dataset.id || '');
        });

        grip.addEventListener('dragend', () => {
            draggedWrap?.classList.remove('sq-bo-hub-card-wrap--dragging');
            wraps().forEach((el) => el.classList.remove('sq-bo-hub-card-wrap--over'));
            draggedWrap = null;
        });
    });

    wraps().forEach((wrap) => {
        wrap.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (!draggedWrap || draggedWrap === wrap) {
                return;
            }
            event.dataTransfer.dropEffect = 'move';
            wraps().forEach((el) => el.classList.remove('sq-bo-hub-card-wrap--over'));
            wrap.classList.add('sq-bo-hub-card-wrap--over');

            const rect = wrap.getBoundingClientRect();
            const after = event.clientY > rect.top + rect.height / 2;
            grid.insertBefore(draggedWrap, after ? wrap.nextElementSibling : wrap);
        });

        wrap.addEventListener('dragleave', () => {
            wrap.classList.remove('sq-bo-hub-card-wrap--over');
        });

        wrap.addEventListener('drop', (event) => {
            event.preventDefault();
            wrap.classList.remove('sq-bo-hub-card-wrap--over');
            saveOrder();
        });
    });
})();
</script>
@endsection
