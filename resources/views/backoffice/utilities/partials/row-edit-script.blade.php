<script>
(() => {
    const rows = () => [...document.querySelectorAll('.sq-bo-util-row')];
    const modificaBtns = () => [...document.querySelectorAll('.js-util-modifica')];
    const salvaBtns = () => [...document.querySelectorAll('.js-util-salva')];
    const annullaBtns = () => [...document.querySelectorAll('.js-util-annulla')];
    const duplicaBtns = () => [...document.querySelectorAll('.js-util-duplica')];
    const nuovoBtn = document.getElementById('sq-bo-util-nuovo-open');
    const snapshots = new Map();

    const rowEl = (rowId) => document.querySelector(`.sq-bo-util-row[data-util-row="${rowId}"]`);

    const snapshotRow = (rowId) => {
        const row = rowEl(rowId);
        if (!row) {
            return;
        }
        const fields = [...row.querySelectorAll('.sq-bo-util-edit')].map((el) => ({
            el,
            value: el.value,
        }));
        snapshots.set(String(rowId), fields);
    };

    const restoreRow = (rowId) => {
        const fields = snapshots.get(String(rowId));
        if (!fields) {
            return;
        }
        fields.forEach(({ el, value }) => {
            el.value = value;
        });
        snapshots.delete(String(rowId));
    };

    const setEditing = (rowId) => {
        const activeId = rowId !== null && rowId !== undefined ? String(rowId) : null;

        rows().forEach((row) => {
            row.classList.toggle('is-editing', activeId !== null && row.dataset.utilRow === activeId);
        });

        salvaBtns().forEach((btn) => {
            btn.hidden = activeId === null || btn.dataset.row !== activeId;
        });

        annullaBtns().forEach((btn) => {
            btn.hidden = activeId === null || btn.dataset.row !== activeId;
        });

        modificaBtns().forEach((btn) => {
            const isActive = activeId !== null && btn.dataset.row === activeId;
            btn.hidden = isActive;
            btn.disabled = activeId !== null && btn.dataset.row !== activeId;
        });

        duplicaBtns().forEach((btn) => {
            btn.hidden = activeId !== null;
            btn.disabled = activeId !== null;
        });

        if (nuovoBtn) {
            nuovoBtn.hidden = activeId !== null;
            nuovoBtn.disabled = activeId !== null;
        }
    };

    modificaBtns().forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled || btn.hidden) {
                return;
            }
            snapshotRow(btn.dataset.row);
            setEditing(btn.dataset.row);
        });
    });

    annullaBtns().forEach((btn) => {
        btn.addEventListener('click', () => {
            restoreRow(btn.dataset.row);
            setEditing(null);
        });
    });

    setEditing(null);
})();
</script>
