/**
 * Vitralia · interacciones del cliente
 * Vanilla JS — sin jQuery ni dependencias externas.
 */
(function () {
    'use strict';

    const BASE_URL = (document.querySelector('meta[name="base-url"]')?.content || '').replace(/\/$/, '');

    document.addEventListener('DOMContentLoaded', () => {
        initTableSearch();
        initStockBajoCounter();
        initConfirmActions();
        initStockLookup();
        initCascadeSelects();
        initLightbox();
        initThemeToggle();
    });

    /* -------------------------------------------------------------------------
     * Toggle de tema (claro / oscuro).
     * El tema inicial se aplica vía script inline en el <head> para evitar
     * el flash de fondo oscuro→claro durante el primer paint.
     * ------------------------------------------------------------------------- */
    function initThemeToggle() {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;
        btn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            const next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('vitralia-theme', next); } catch (e) {}
        });
    }

    /* -------------------------------------------------------------------------
     * Lightbox para imágenes de productos.
     * Activación: <a data-lightbox href="..." data-caption="...">
     * Click → overlay con la imagen ampliada. Cierre por click, Esc o botón.
     * ------------------------------------------------------------------------- */
    function initLightbox() {
        document.body.addEventListener('click', (ev) => {
            const trigger = ev.target.closest('[data-lightbox]');
            if (!trigger) return;
            ev.preventDefault();
            openLightbox(trigger.getAttribute('href'), trigger.dataset.caption || '');
        });
    }

    function openLightbox(src, caption) {
        if (!src) return;
        const overlay = document.createElement('div');
        overlay.className = 'lightbox';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.innerHTML =
            '<button type="button" class="lightbox__close" aria-label="Cerrar">×</button>' +
            '<figure class="lightbox__figure">' +
                `<img class="lightbox__img" src="${escapeAttr(src)}" alt="${escapeHtml(caption)}">` +
                (caption ? `<figcaption class="lightbox__caption">${escapeHtml(caption)}</figcaption>` : '') +
            '</figure>';

        const close = () => {
            overlay.remove();
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
        const onKey = (e) => { if (e.key === 'Escape') close(); };

        overlay.addEventListener('click', (e) => {
            if (e.target.closest('.lightbox__figure') && !e.target.classList.contains('lightbox__close')) return;
            close();
        });

        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        document.body.appendChild(overlay);
    }

    function escapeAttr(s) {
        return String(s).replace(/"/g, '&quot;');
    }

    /* -------------------------------------------------------------------------
     * Selectores en cascada para geografía y similares.
     * Activación: <select data-cascade="hijo_name" data-cascade-url="/path">
     * Cuando cambia, hace fetch a `${url}/${valor}` y rellena el select cuyo
     * name coincida con `data-cascade`. También limpia los hijos siguientes.
     * ------------------------------------------------------------------------- */
    function initCascadeSelects() {
        const cascades = document.querySelectorAll('select[data-cascade]');
        if (!cascades.length) return;

        cascades.forEach((parent) => {
            parent.addEventListener('change', async () => {
                const childName = parent.dataset.cascade;
                const url = parent.dataset.cascadeUrl;
                if (!childName || !url) return;
                const child = parent.form?.querySelector(`select[name="${childName}"]`);
                if (!child) return;

                // Limpiar hijo y descendientes
                resetCascadeChain(child);

                if (!parent.value) {
                    child.disabled = true;
                    child.innerHTML = '<option value="">— Selecciona —</option>';
                    return;
                }

                child.disabled = true;
                child.innerHTML = '<option value="">Cargando…</option>';
                try {
                    const res = await fetch(`${url}/${encodeURIComponent(parent.value)}`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) throw new Error('http ' + res.status);
                    const json = await res.json();
                    if (!json.ok) throw new Error('respuesta inválida');

                    const opts = ['<option value="">— Selecciona —</option>']
                        .concat(json.data.map((r) =>
                            `<option value="${r.id}">${escapeHtml(r.nombre)}</option>`));
                    child.innerHTML = opts.join('');
                    child.disabled = false;
                } catch (err) {
                    child.innerHTML = '<option value="">No se pudo cargar</option>';
                    child.disabled = false;
                }
            });
        });
    }

    function resetCascadeChain(select) {
        select.innerHTML = '<option value="">—</option>';
        select.disabled = true;
        const nextName = select.dataset.cascade;
        if (!nextName) return;
        const next = select.form?.querySelector(`select[name="${nextName}"]`);
        if (next) resetCascadeChain(next);
    }

    /* -------------------------------------------------------------------------
     * Búsqueda en tiempo real sobre tablas.
     * Activación: <input data-table-search="#id-de-tabla">
     * Filtra filas que no coinciden añadiendo .is-hidden
     * ------------------------------------------------------------------------- */
    function initTableSearch() {
        const inputs = document.querySelectorAll('input[data-table-search]');
        inputs.forEach((input) => {
            const target = document.querySelector(input.dataset.tableSearch);
            if (!target) return;

            input.addEventListener('input', () => {
                const term = input.value.trim().toLowerCase();
                const rows = target.querySelectorAll('tbody tr');
                let visibles = 0;
                rows.forEach((row) => {
                    if (row.classList.contains('table__empty')) return;
                    const text = row.textContent.toLowerCase();
                    const match = term === '' || text.includes(term);
                    row.classList.toggle('is-hidden', !match);
                    if (match) visibles += 1;
                });

                // Mensaje de "sin resultados" volátil
                let msg = target.querySelector('.js-empty-search');
                if (term !== '' && visibles === 0) {
                    if (!msg) {
                        const tbody = target.querySelector('tbody');
                        const cols = target.querySelectorAll('thead th').length || 1;
                        msg = document.createElement('tr');
                        msg.className = 'js-empty-search';
                        msg.innerHTML =
                            `<td class="table__empty" colspan="${cols}">Sin coincidencias para «${escapeHtml(term)}».</td>`;
                        tbody.appendChild(msg);
                    } else {
                        msg.querySelector('td').textContent = `Sin coincidencias para «${term}».`;
                    }
                } else if (msg) {
                    msg.remove();
                }
            });
        });
    }

    /* -------------------------------------------------------------------------
     * Refresca el contador de stock crítico al cargar (lazy).
     * Si existe un endpoint, se podría sustituir por fetch; aquí
     * leemos el badge ya renderizado y aplicamos énfasis visual.
     * ------------------------------------------------------------------------- */
    function initStockBajoCounter() {
        const counter = document.querySelector('[data-stock-bajo]');
        if (!counter) return;
        const n = parseInt(counter.textContent, 10) || 0;
        const badge = counter.closest('.badge');
        if (badge) {
            badge.classList.toggle('badge--alert', n > 0);
            badge.classList.toggle('badge--neutral', n === 0);
        }
        if (n > 0) {
            counter.animate(
                [
                    { transform: 'scale(1)', filter: 'brightness(1)' },
                    { transform: 'scale(1.18)', filter: 'brightness(1.4)' },
                    { transform: 'scale(1)',  filter: 'brightness(1)' }
                ],
                { duration: 900, easing: 'ease-out' }
            );
        }
    }

    /* -------------------------------------------------------------------------
     * Confirmación antes de ejecutar acciones destructivas.
     * Activación: cualquier elemento con data-confirm="..."
     * ------------------------------------------------------------------------- */
    function initConfirmActions() {
        document.body.addEventListener('click', (ev) => {
            const trigger = ev.target.closest('[data-confirm]');
            if (!trigger) return;
            const message = trigger.dataset.confirm;
            // eslint-disable-next-line no-alert
            if (!window.confirm(message)) {
                ev.preventDefault();
                ev.stopPropagation();
            }
        }, true);
    }

    /* -------------------------------------------------------------------------
     * Lookup de stock al cambiar el selector de producto.
     * Activación: <select data-stock-target="#stockInfo"> apuntando al
     * contenedor donde se mostrará el stock.
     * ------------------------------------------------------------------------- */
    function initStockLookup() {
        const selects = document.querySelectorAll('select[data-stock-target]');
        selects.forEach((select) => {
            const target = document.querySelector(select.dataset.stockTarget);
            if (!target) return;

            select.addEventListener('change', async () => {
                const id = select.value;
                resetStockInfo(target);

                if (!id) {
                    target.textContent = 'Selecciona un producto para ver el stock actual.';
                    return;
                }

                target.textContent = 'Consultando stock…';
                try {
                    const res = await fetch(`${BASE_URL}/producto/stockActual/${encodeURIComponent(id)}`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) throw new Error('http ' + res.status);
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.msg || 'No disponible');

                    const low = data.stock_actual <= data.stock_minimo;
                    target.classList.add('is-loaded');
                    target.classList.toggle('is-low', low);
                    target.innerHTML =
                        `<strong>${data.codigo}</strong> · ${escapeHtml(data.nombre)} — ` +
                        `Stock disponible: <strong>${data.stock_actual}</strong> ${escapeHtml(data.unidad)} ` +
                        `<small>(mín. ${data.stock_minimo}${low ? ' · CRÍTICO' : ''})</small>`;
                } catch (err) {
                    target.textContent = 'No fue posible consultar el stock.';
                    target.classList.add('is-low');
                }
            });
        });
    }

    function resetStockInfo(el) {
        el.classList.remove('is-loaded', 'is-low');
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
})();
