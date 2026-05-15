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
        initModalTriggers();
        initFileUploads();
        initRemoveCurrentImage();
        initSidebarDrawer();
        initPwa();
    });

    /* -------------------------------------------------------------------------
     * Quitar la imagen actual al editar un producto.
     * Activación: <button data-quitar-imagen> dentro de [data-current-img].
     * Marca el flag `quitar_imagen=1` y muestra el bloque como "marcado para
     * eliminar"; al guardar, el controlador pone imagen=NULL. Click otra vez
     * deshace la acción.
     * ------------------------------------------------------------------------- */
    function initRemoveCurrentImage() {
        document.body.addEventListener('click', (ev) => {
            const btn = ev.target.closest('[data-quitar-imagen]');
            if (!btn) return;
            ev.preventDefault();
            const wrapper = btn.closest('[data-current-img]');
            if (!wrapper) return;
            const flag = wrapper.querySelector('input[name="quitar_imagen"]');
            const hint = wrapper.querySelector('[data-current-img-hint]');
            const willRemove = !wrapper.classList.contains('is-marked-remove');
            wrapper.classList.toggle('is-marked-remove', willRemove);
            if (flag) flag.value = willRemove ? '1' : '0';
            if (hint) {
                hint.textContent = willRemove
                    ? 'Se quitará al guardar. Haz click en el botón otra vez para deshacer.'
                    : 'Reemplázala subiendo una nueva abajo o quítala.';
            }
            btn.setAttribute('aria-pressed', willRemove ? 'true' : 'false');
        });
    }

    /* -------------------------------------------------------------------------
     * Sidebar drawer en móvil. El botón #sidebarToggle abre/cierra el sidebar
     * agregando .sidebar-open al <body>. El backdrop cierra al hacer click.
     * ------------------------------------------------------------------------- */
    function initSidebarDrawer() {
        const toggle   = document.getElementById('sidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');
        const sidebar  = document.getElementById('appSidebar');
        if (!toggle || !sidebar) return;

        const close = () => {
            document.body.classList.remove('sidebar-open');
            toggle.setAttribute('aria-expanded', 'false');
        };
        const open = () => {
            document.body.classList.add('sidebar-open');
            toggle.setAttribute('aria-expanded', 'true');
        };

        toggle.addEventListener('click', () => {
            if (document.body.classList.contains('sidebar-open')) close();
            else open();
        });
        backdrop?.addEventListener('click', close);

        // Cerrar al navegar a un link
        sidebar.addEventListener('click', (e) => {
            if (e.target.closest('a')) close();
        });

        // Cerrar al cambiar a desktop
        const mql = window.matchMedia('(min-width: 881px)');
        mql.addEventListener?.('change', (e) => { if (e.matches) close(); });
    }

    /* -------------------------------------------------------------------------
     * Registro del service worker (PWA). El SW se sirve desde /public/sw.js
     * relativo a BASE_URL. Si no existe, falla silenciosamente.
     * ------------------------------------------------------------------------- */
    function initPwa() {
        if (!('serviceWorker' in navigator)) return;
        const swUrl = `${BASE_URL}/sw.js`;
        navigator.serviceWorker.register(swUrl, { scope: BASE_URL + '/' })
            .catch(() => { /* SW no disponible — ignoramos en desarrollo */ });
    }

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

    /* -------------------------------------------------------------------------
     * Sistema de modales accesibles.
     * Trigger declarativo:
     *   <button data-modal-src="/ruta/parcial" data-modal-title="Crear">…</button>
     * O imperativo: window.Vitralia.modal.open({ title, html, src, size, onSubmit })
     *
     * Si la respuesta del fetch contiene un <form>, se intercepta el submit y
     * se envía vía fetch; tras éxito se recarga la página o se invoca onSuccess.
     * ------------------------------------------------------------------------- */
    function initModalTriggers() {
        document.body.addEventListener('click', (ev) => {
            const trigger = ev.target.closest('[data-modal-src]');
            if (!trigger) return;
            ev.preventDefault();
            openModal({
                src: trigger.dataset.modalSrc,
                title: trigger.dataset.modalTitle || trigger.textContent.trim(),
                kicker: trigger.dataset.modalKicker || '',
                caption: trigger.dataset.modalCaption || '',
                size: trigger.dataset.modalSize || ''
            });
        });
    }

    let activeModal = null;
    let lastFocused = null;

    function openModal({ title, html, src, kicker = '', caption = '', size = '', onSubmit, onSuccess } = {}) {
        closeModal({ skipFocus: true });

        const overlay = document.createElement('div');
        overlay.className = 'modal';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');

        const dialogClass = size ? `modal__dialog modal__dialog--${size}` : 'modal__dialog';
        overlay.innerHTML = `
            <div class="${dialogClass}" tabindex="-1">
                <header class="modal__head">
                    <div class="modal__head-text">
                        ${kicker ? `<span class="modal__kicker">${escapeHtml(kicker)}</span>` : ''}
                        <h2 class="modal__title">${escapeHtml(title || '')}</h2>
                        ${caption ? `<p class="modal__caption">${escapeHtml(caption)}</p>` : ''}
                    </div>
                    <button type="button" class="modal__close" aria-label="Cerrar">×</button>
                </header>
                <div class="modal__body">
                    ${html != null ? html : '<div class="modal__loader">Cargando…</div>'}
                </div>
            </div>
        `;

        lastFocused = document.activeElement;
        document.body.appendChild(overlay);
        document.body.classList.add('modal-open');
        activeModal = overlay;

        const dialog = overlay.querySelector('.modal__dialog');
        const body   = overlay.querySelector('.modal__body');

        // Cerrar: backdrop, botón close o botón [data-modal-cancel]
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay
                || e.target.classList.contains('modal__close')
                || e.target.closest('[data-modal-cancel]')) {
                closeModal();
            }
        });

        // Teclado: Esc cierra, Tab atrapa el foco dentro
        document.addEventListener('keydown', onModalKey);

        // Cargar contenido remoto
        if (src && html == null) {
            fetch(src, {
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'fetch' },
                credentials: 'same-origin'
            })
                .then((r) => r.ok ? r.text() : Promise.reject(r.status))
                .then((markup) => {
                    body.innerHTML = markup;
                    afterContentLoaded(body, { onSubmit, onSuccess });
                })
                .catch(() => {
                    body.innerHTML = '<p class="modal__error">No se pudo cargar el formulario.</p>';
                });
        } else {
            afterContentLoaded(body, { onSubmit, onSuccess });
        }

        // Foco inicial al primer campo interactivo
        requestAnimationFrame(() => {
            const target = body.querySelector('input, select, textarea, button') || dialog;
            target.focus();
        });

        return overlay;
    }

    function afterContentLoaded(body, { onSubmit, onSuccess } = {}) {
        // Re-inicializar listeners declarativos dentro del modal
        body.querySelectorAll('input[data-table-search]').forEach((i) => { /* tablas dentro de modal — opcional */ });

        // Cascade selects dentro del modal
        body.querySelectorAll('select[data-cascade]').forEach((parent) => {
            parent.addEventListener('change', () => parent.dispatchEvent(new Event('change', { bubbles: true })));
        });

        // Upload con preview dentro del modal
        body.querySelectorAll('[data-upload]').forEach(enhanceFileInput);

        // Interceptar submit del primer form para enviar vía fetch
        const form = body.querySelector('form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.textContent;
                submitBtn.textContent = 'Guardando…';
            }
            // Limpiar errores previos
            body.querySelectorAll('.modal__error').forEach((n) => n.remove());

            try {
                const fd = new FormData(form);
                const res = await fetch(form.action || window.location.href, {
                    method: (form.method || 'POST').toUpperCase(),
                    body: fd,
                    headers: { 'X-Requested-With': 'fetch', 'Accept': 'text/html' },
                    credentials: 'same-origin',
                    redirect: 'follow'
                });

                if (typeof onSubmit === 'function') {
                    const handled = await onSubmit(res, form, body);
                    if (handled === false) return;
                }

                if (res.ok || res.redirected) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(res);
                    } else {
                        // Patrón estándar: tras éxito recargamos para que se vea el flash
                        window.location.reload();
                    }
                } else {
                    const text = await res.text();
                    showModalError(body, text || 'Hubo un error al guardar.');
                    restoreSubmit(submitBtn);
                }
            } catch (err) {
                showModalError(body, 'No se pudo enviar el formulario. Revisa tu conexión.');
                restoreSubmit(submitBtn);
            }
        });
    }

    function restoreSubmit(btn) {
        if (!btn) return;
        btn.disabled = false;
        if (btn.dataset.originalText) {
            btn.textContent = btn.dataset.originalText;
        }
    }

    function showModalError(body, msg) {
        const err = document.createElement('div');
        err.className = 'modal__error';
        err.textContent = msg.length > 240 ? 'Hubo un error al guardar.' : msg;
        body.prepend(err);
    }

    function closeModal({ skipFocus = false } = {}) {
        if (!activeModal) return;
        activeModal.remove();
        activeModal = null;
        document.body.classList.remove('modal-open');
        document.removeEventListener('keydown', onModalKey);
        if (!skipFocus && lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
        lastFocused = null;
    }

    function onModalKey(e) {
        if (!activeModal) return;
        if (e.key === 'Escape') {
            e.preventDefault();
            closeModal();
            return;
        }
        if (e.key === 'Tab') {
            trapFocus(activeModal, e);
        }
    }

    function trapFocus(container, e) {
        const focusables = container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        if (!focusables.length) return;
        const first = focusables[0];
        const last  = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    /* -------------------------------------------------------------------------
     * Componente de upload de archivos con drag-and-drop y preview.
     * Activación: <div data-upload> envolviendo <input type="file">
     * Ver clases .upload-zone en custom.css.
     * ------------------------------------------------------------------------- */
    function initFileUploads() {
        document.querySelectorAll('[data-upload]').forEach(enhanceFileInput);
    }

    function enhanceFileInput(wrapper) {
        if (wrapper.dataset.uploadInit === '1') return;
        wrapper.dataset.uploadInit = '1';

        const input = wrapper.querySelector('input[type="file"]');
        if (!input) return;

        const accept = (input.accept || '').toLowerCase();
        const isImage = accept.includes('image') || accept === '';

        const zone = document.createElement('label');
        zone.className = 'upload-zone';
        zone.setAttribute('for', input.id || (input.id = 'upload-' + Math.random().toString(36).slice(2, 8)));
        zone.innerHTML = `
            <div class="upload-zone__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
            </div>
            <div class="upload-zone__copy">
                <strong class="upload-zone__title">Suelta el archivo aquí o <span class="upload-zone__browse">selecciónalo</span></strong>
                <span class="upload-zone__hint">${isImage ? 'PNG, JPG o WebP · hasta 5 MB' : 'Hasta 5 MB'}</span>
            </div>
            <div class="upload-zone__preview" hidden>
                <img class="upload-zone__thumb" alt="">
                <div class="upload-zone__file">
                    <span class="upload-zone__filename"></span>
                    <span class="upload-zone__filesize"></span>
                </div>
                <button type="button" class="upload-zone__remove" aria-label="Quitar archivo">×</button>
            </div>
        `;
        input.classList.add('upload-zone__input');
        wrapper.appendChild(zone);

        const preview  = zone.querySelector('.upload-zone__preview');
        const thumb    = zone.querySelector('.upload-zone__thumb');
        const fname    = zone.querySelector('.upload-zone__filename');
        const fsize    = zone.querySelector('.upload-zone__filesize');
        const remove   = zone.querySelector('.upload-zone__remove');

        const showFile = (file) => {
            if (!file) return;
            fname.textContent = file.name;
            fsize.textContent = humanFileSize(file.size);
            preview.hidden = false;
            zone.classList.add('is-loaded');
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = () => { thumb.src = reader.result; thumb.hidden = false; };
                reader.readAsDataURL(file);
            } else {
                thumb.hidden = true;
                thumb.src = '';
            }
        };

        input.addEventListener('change', () => showFile(input.files[0]));

        remove.addEventListener('click', (e) => {
            e.preventDefault();
            input.value = '';
            preview.hidden = true;
            thumb.src = '';
            zone.classList.remove('is-loaded');
        });

        ['dragenter', 'dragover'].forEach((ev) => zone.addEventListener(ev, (e) => {
            e.preventDefault(); zone.classList.add('is-dragover');
        }));
        ['dragleave', 'drop'].forEach((ev) => zone.addEventListener(ev, (e) => {
            e.preventDefault(); zone.classList.remove('is-dragover');
        }));
        zone.addEventListener('drop', (e) => {
            const file = e.dataTransfer?.files?.[0];
            if (!file) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            showFile(file);
        });
    }

    function humanFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    // API pública para uso imperativo desde otras vistas
    window.Vitralia = window.Vitralia || {};
    window.Vitralia.modal = { open: openModal, close: closeModal };
    window.Vitralia.upload = { enhance: enhanceFileInput };
})();
