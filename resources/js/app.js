import './bootstrap';

const SQ_NAV_MQ = window.matchMedia('(max-width: 899px)');

function initSqMobileNav() {
    const shell = document.querySelector('.sq-shell');
    const toggle = document.querySelector('.sq-nav-toggle');
    const backdrop = document.querySelector('.sq-sidebar-backdrop');
    if (!shell || !toggle) {
        return;
    }

    const setOpen = (open) => {
        shell.classList.toggle('sq-shell--nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('sq-body--nav-open', open);
    };

    toggle.addEventListener('click', () => {
        setOpen(!shell.classList.contains('sq-shell--nav-open'));
    });

    backdrop?.addEventListener('click', () => setOpen(false));

    document.querySelector('.sq-sidebar')?.addEventListener('click', (e) => {
        if (e.target.closest('a') && SQ_NAV_MQ.matches) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && shell.classList.contains('sq-shell--nav-open')) {
            setOpen(false);
        }
    });

    const onMq = () => {
        if (!SQ_NAV_MQ.matches) {
            setOpen(false);
        }
    };

    if (typeof SQ_NAV_MQ.addEventListener === 'function') {
        SQ_NAV_MQ.addEventListener('change', onMq);
    } else {
        SQ_NAV_MQ.addListener(onMq);
    }
}

function initSqPasswordToggle() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-password-toggle]');
        if (!btn) {
            return;
        }
        const id = btn.getAttribute('data-password-toggle');
        const input = id ? document.getElementById(id) : null;
        if (!input || input.nodeName.toUpperCase() !== 'INPUT') {
            return;
        }

        const showPlain = input.type === 'password';
        input.type = showPlain ? 'text' : 'password';

        btn.setAttribute('aria-pressed', showPlain ? 'true' : 'false');
        btn.setAttribute('aria-label', showPlain ? 'Nascondi password' : 'Mostra password');

        const open = btn.querySelector('.sq-password-toggle-eye-open');
        const closed = btn.querySelector('.sq-password-toggle-eye-closed');
        if (open && closed) {
            open.hidden = showPlain;
            closed.hidden = !showPlain;
        }
    });
}

function initSqSidebarHeaderAlign() {
    const header = document.querySelector('.sq-header');
    const root = document.documentElement;
    if (!header || !root) {
        return;
    }

    const sync = () => {
        const h = Math.max(0, Math.round(header.getBoundingClientRect().height));
        root.style.setProperty('--sq-header-h', `${h}px`);
    };

    sync();
    window.addEventListener('resize', sync, { passive: true });

    if (typeof ResizeObserver !== 'undefined') {
        const ro = new ResizeObserver(sync);
        ro.observe(header);
    }
}

function initSqHeaderNavMenu() {
    const menus = document.querySelectorAll('.sq-header-nav-menu');
    if (!menus.length) {
        return;
    }

    menus.forEach((menu) => {
        const trigger = menu.querySelector('.sq-header-nav-menu-trigger');
        const panel = menu.querySelector('.sq-header-nav-menu-body');

        const setOpen = (open) => {
            menu.classList.toggle('is-open', open);
            trigger?.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        trigger?.addEventListener('click', (event) => {
            event.stopPropagation();
            setOpen(!menu.classList.contains('is-open'));
        });

        panel?.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setOpen(false));
        });
    });

    document.addEventListener('click', (event) => {
        menus.forEach((menu) => {
            if (menu.classList.contains('is-open') && !menu.contains(event.target)) {
                menu.classList.remove('is-open');
                menu.querySelector('.sq-header-nav-menu-trigger')?.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        menus.forEach((menu) => {
            menu.classList.remove('is-open');
            menu.querySelector('.sq-header-nav-menu-trigger')?.setAttribute('aria-expanded', 'false');
        });
    });
}

function initSqHeaderNotificazioni() {
    const menus = document.querySelectorAll('.sq-header-notif-menu');
    if (!menus.length) {
        return;
    }

    document.addEventListener('click', (event) => {
        menus.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) {
                menu.open = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        menus.forEach((menu) => {
            menu.open = false;
        });
    });
}

function initSqBackofficePageBanner() {
    const indietroBtn = document.getElementById('sq-page-banner-bo-indietro');
    if (indietroBtn) {
        const fallback = indietroBtn.getAttribute('data-fallback-url') || '/backoffice';
        indietroBtn.addEventListener('click', () => {
            const ref = document.referrer || '';
            const origin = window.location.origin || '';
            const sameOrigin = ref.indexOf(origin) === 0;
            if (sameOrigin && window.history.length > 1) {
                window.history.back();
                return;
            }
            window.location.href = fallback;
        });
    }

    const shortcutsWrap = document.querySelector('.sq-page-banner__bo-shortcuts-menu');
    if (!shortcutsWrap) {
        return;
    }
    const trigger = shortcutsWrap.querySelector('.sq-page-banner__bo-shortcuts-trigger');
    if (!trigger) {
        return;
    }
    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = shortcutsWrap.classList.toggle('is-open');
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', () => {
        shortcutsWrap.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initSqMobileNav();
        initSqPasswordToggle();
        initSqSidebarHeaderAlign();
        initSqHeaderNavMenu();
        initSqHeaderNotificazioni();
        initSqBackofficePageBanner();
    });
} else {
    initSqMobileNav();
    initSqPasswordToggle();
    initSqSidebarHeaderAlign();
    initSqHeaderNavMenu();
    initSqHeaderNotificazioni();
    initSqBackofficePageBanner();
}
