@extends('layouts.app')

@section('content')
<div class="sq-page-1200 sq-bo-hub">
    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif

    <p class="sq-bo-hub-hint">Trascina le card tra i contenitori o riordinala al loro interno. L&apos;ordine resta salvato su questo browser.</p>

    <div
        id="sq-bo-hub"
        class="sq-bo-hub-sections"
        data-storage-key="backoffice-hub-sections-{{ auth()->id() }}"
    >
        @foreach ($sections as $section)
            @php
                $isLockedSection = ! empty($section['locked']);
                $isDbUnlocked = ! $isLockedSection || ($dbSectionUnlocked ?? false);
                $sectionClasses = 'sq-bo-hub-section';
                if ($isLockedSection) {
                    $sectionClasses .= ' sq-bo-hub-section--db';
                    $sectionClasses .= $isDbUnlocked ? ' sq-bo-hub-section--unlocked' : ' sq-bo-hub-section--locked';
                }
                $showPasswordPanel = $isLockedSection && ! $isDbUnlocked && (
                    $errors->has('db_panel_password') || session('db_panel_unlock_attempt')
                );
            @endphp
            <section
                class="{{ $sectionClasses }}"
                data-section-id="{{ $section['id'] }}"
                @if ($isLockedSection) data-db-locked="{{ $isDbUnlocked ? '0' : '1' }}" @endif
                aria-labelledby="sq-bo-hub-section-title-{{ $section['id'] }}"
            >
                @if ($isLockedSection && ! $isDbUnlocked)
                    <button
                        type="button"
                        class="sq-bo-hub-section__head sq-bo-hub-section__toggle"
                        id="sq-bo-hub-section-title-{{ $section['id'] }}"
                        aria-expanded="{{ $showPasswordPanel ? 'true' : 'false' }}"
                        aria-controls="sq-bo-hub-db-lock-panel"
                    >
                        <span class="sq-bo-hub-section__title">{{ $section['label'] }}</span>
                        <i class="fa-solid fa-lock sq-bo-hub-section__lock-ico" aria-hidden="true"></i>
                        <i class="fa-solid fa-chevron-down sq-bo-hub-section__chevron" aria-hidden="true"></i>
                    </button>
                    <div
                        id="sq-bo-hub-db-lock-panel"
                        class="sq-bo-hub-section__lock-panel @if($showPasswordPanel) is-open @endif"
                        @if(! $showPasswordPanel) hidden @endif
                    >
                        <form method="POST" action="{{ route('backoffice.hub.unlock_db') }}" class="sq-bo-hub-section__lock-form">
                            @csrf
                            <label for="sq-bo-hub-db-password" class="sq-bo-hub-section__lock-label">
                                Questa sezione necessita di password
                            </label>
                            <div class="sq-bo-hub-section__lock-row">
                                <input
                                    type="password"
                                    id="sq-bo-hub-db-password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    class="sq-bo-hub-section__lock-input"
                                    @if($showPasswordPanel) autofocus @endif
                                >
                                <button type="submit" class="sq-bo-hub-section__lock-btn">Accedi</button>
                            </div>
                            @error('db_panel_password')
                                <p class="sq-bo-hub-section__lock-error">{{ $message }}</p>
                            @enderror
                        </form>
                    </div>
                @else
                    <header class="sq-bo-hub-section__head @if($isLockedSection) sq-bo-hub-section__head--with-actions @endif">
                        <h2 id="sq-bo-hub-section-title-{{ $section['id'] }}" class="sq-bo-hub-section__title">
                            {{ $section['label'] }}
                        </h2>
                        @if ($isLockedSection)
                            <form method="POST" action="{{ route('backoffice.hub.lock_db') }}" class="sq-form-zero">
                                @csrf
                                <button type="submit" class="sq-bo-hub-section__close-btn">Chiudi</button>
                            </form>
                        @endif
                    </header>
                    <div
                        class="sq-bo-hub-grid"
                        data-section-id="{{ $section['id'] }}"
                    >
                        @foreach ($section['items'] as $item)
                            @include('backoffice.partials.hub-card', ['item' => $item])
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>

<script>
(() => {
    const hub = document.getElementById('sq-bo-hub');
    if (!hub) {
        return;
    }

    const dbToggle = hub.querySelector('.sq-bo-hub-section__toggle');
    const dbLockPanel = document.getElementById('sq-bo-hub-db-lock-panel');
    if (dbToggle && dbLockPanel) {
        dbToggle.addEventListener('click', () => {
            const open = dbLockPanel.classList.toggle('is-open');
            dbLockPanel.hidden = !open;
            dbToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                dbLockPanel.querySelector('input[type="password"]')?.focus();
            }
        });
    }

    const storageKey = hub.dataset.storageKey || 'backoffice-hub-sections';
    const grids = () => [...hub.querySelectorAll('.sq-bo-hub-grid')];
    const wraps = (grid) => [...grid.querySelectorAll('.sq-bo-hub-card-wrap')];
    const allWraps = () => [...hub.querySelectorAll('.sq-bo-hub-card-wrap')];

    if (grids().length === 0) {
        return;
    }

    const readSavedLayout = () => {
        try {
            const raw = localStorage.getItem(storageKey);
            if (!raw) {
                return null;
            }
            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
                return null;
            }
            /** @type {Record<string, string[]>} */
            const layout = {};
            Object.entries(parsed).forEach(([sectionId, ids]) => {
                if (Array.isArray(ids)) {
                    layout[String(sectionId)] = ids.map(String);
                }
            });

            return Object.keys(layout).length > 0 ? layout : null;
        } catch {
            return null;
        }
    };

    const applyLayout = (layout) => {
        const knownIds = new Set(allWraps().map((el) => el.dataset.id));
        const placed = new Set();

        grids().forEach((grid) => {
            const sectionId = grid.dataset.sectionId || '';
            const order = layout[sectionId] || [];
            const valid = order.filter((id) => knownIds.has(id) && !placed.has(id));
            valid.forEach((id) => placed.add(id));

            valid.forEach((id) => {
                const el = hub.querySelector(`.sq-bo-hub-card-wrap[data-id="${id}"]`);
                if (el) {
                    grid.appendChild(el);
                }
            });
        });

        allWraps().forEach((wrap) => {
            if (placed.has(wrap.dataset.id || '')) {
                return;
            }
            const defaultSection = wrap.dataset.defaultSection || '';
            const grid = hub.querySelector(`.sq-bo-hub-grid[data-section-id="${defaultSection}"]`);
            if (grid) {
                grid.appendChild(wrap);
            }
        });
    };

    const saveLayout = () => {
        /** @type {Record<string, string[]>} */
        const layout = {};
        grids().forEach((grid) => {
            const sectionId = grid.dataset.sectionId || '';
            layout[sectionId] = wraps(grid).map((el) => el.dataset.id || '').filter(Boolean);
        });
        localStorage.setItem(storageKey, JSON.stringify(layout));
    };

    const saved = readSavedLayout();
    if (saved) {
        applyLayout(saved);
    }

    let draggedWrap = null;

    const clearOverStates = () => {
        allWraps().forEach((el) => el.classList.remove('sq-bo-hub-card-wrap--over'));
        grids().forEach((grid) => grid.classList.remove('sq-bo-hub-grid--over'));
    };

    const insertBeforePosition = (grid, targetWrap, clientY) => {
        if (!draggedWrap) {
            return;
        }
        if (targetWrap && targetWrap !== draggedWrap) {
            const rect = targetWrap.getBoundingClientRect();
            const after = clientY > rect.top + rect.height / 2;
            grid.insertBefore(draggedWrap, after ? targetWrap.nextElementSibling : targetWrap);

            return;
        }
        grid.appendChild(draggedWrap);
    };

    hub.querySelectorAll('.sq-bo-hub-card-grip').forEach((grip) => {
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
            clearOverStates();
            draggedWrap = null;
        });
    });

    allWraps().forEach((wrap) => {
        wrap.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (!draggedWrap || draggedWrap === wrap) {
                return;
            }
            event.dataTransfer.dropEffect = 'move';
            clearOverStates();
            wrap.classList.add('sq-bo-hub-card-wrap--over');
            const grid = wrap.closest('.sq-bo-hub-grid');
            if (grid) {
                insertBeforePosition(grid, wrap, event.clientY);
            }
        });

        wrap.addEventListener('dragleave', () => {
            wrap.classList.remove('sq-bo-hub-card-wrap--over');
        });

        wrap.addEventListener('drop', (event) => {
            event.preventDefault();
            clearOverStates();
            saveLayout();
        });
    });

    grids().forEach((grid) => {
        grid.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (!draggedWrap) {
                return;
            }
            event.dataTransfer.dropEffect = 'move';
            clearOverStates();
            grid.classList.add('sq-bo-hub-grid--over');

            const targetWrap = event.target.closest('.sq-bo-hub-card-wrap');
            if (targetWrap && grid.contains(targetWrap)) {
                return;
            }
            insertBeforePosition(grid, null, event.clientY);
        });

        grid.addEventListener('dragleave', (event) => {
            if (!grid.contains(event.relatedTarget)) {
                grid.classList.remove('sq-bo-hub-grid--over');
            }
        });

        grid.addEventListener('drop', (event) => {
            event.preventDefault();
            clearOverStates();
            saveLayout();
        });
    });
})();
</script>
@endsection
