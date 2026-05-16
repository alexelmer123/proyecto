/**
 * Vitralia — cliente WebSocket en tiempo real.
 *
 * Lee la URL del daemon desde <meta name="realtime-ws" content="ws://..."> y
 * se conecta. Al recibir un evento `stock_changed` muestra un toast con el
 * producto, la cantidad y el usuario que lo generó; también actualiza el
 * badge de "stock crítico" del topbar y marca las filas/tarjetas con el
 * `data-producto-id` correspondiente como "obsoletas" para que el operador
 * sepa que la pantalla ya no refleja el estado real.
 *
 * Si el daemon está caído reintenta cada 3s sin spamear errores.
 */
(function () {
    'use strict';

    var meta = document.querySelector('meta[name="realtime-ws"]');
    if (!meta) return;
    var wsUrl = (meta.getAttribute('content') || '').trim();
    if (!wsUrl) return;
    if (!('WebSocket' in window)) return;

    var WS_RETRY_MS = 3000;
    var TOAST_TTL_MS = 6500;
    var ws = null;
    var retryTimer = null;
    var indicator = null;

    function ensureIndicator() {
        if (indicator) return indicator;
        indicator = document.createElement('span');
        indicator.id = 'rtIndicator';
        indicator.className = 'rt-indicator rt-indicator--off';
        indicator.title = 'Tiempo real desconectado';
        indicator.innerHTML = '<span class="rt-indicator__dot"></span>'
            + '<span class="rt-indicator__label">live</span>';
        var actions = document.querySelector('.topbar__actions');
        if (actions) {
            actions.insertBefore(indicator, actions.firstChild);
        } else {
            document.body.appendChild(indicator);
        }
        return indicator;
    }

    function setStatus(on) {
        var el = ensureIndicator();
        el.classList.toggle('rt-indicator--on',  !!on);
        el.classList.toggle('rt-indicator--off', !on);
        el.title = on ? 'Tiempo real conectado' : 'Tiempo real desconectado — reintentando…';
    }

    function ensureToastContainer() {
        var c = document.getElementById('rtToasts');
        if (!c) {
            c = document.createElement('div');
            c.id = 'rtToasts';
            c.className = 'rt-toasts';
            document.body.appendChild(c);
        }
        return c;
    }

    function showToast(html, kind) {
        var c = ensureToastContainer();
        var el = document.createElement('div');
        el.className = 'rt-toast rt-toast--' + (kind || 'info');
        el.innerHTML = html;
        c.appendChild(el);
        // animación de entrada
        requestAnimationFrame(function () { el.classList.add('rt-toast--in'); });
        setTimeout(function () {
            el.classList.add('rt-toast--out');
            setTimeout(function () { el.remove(); }, 400);
        }, TOAST_TTL_MS);
    }

    function fmt(n) {
        var v = Number(n);
        if (isNaN(v)) return String(n);
        return v % 1 === 0 ? String(v) : v.toFixed(2);
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function tipoLabel(d) {
        if (d.tipo === 'ajuste')  return 'Ajuste de stock';
        if (d.tipo === 'entrada') return 'Entrada';
        if (d.tipo === 'salida') {
            if (d.motivo === 'venta')     return 'Venta';
            if (d.motivo === 'encargo')   return 'Encargo';
            if (d.motivo === 'accidente') return 'Accidente';
            if (d.motivo === 'merma')     return 'Merma';
            return 'Salida';
        }
        return 'Cambio';
    }

    function updateStockBadge(count) {
        if (typeof count !== 'number') return;
        var badge = document.querySelector('[data-stock-bajo]');
        if (badge) badge.textContent = String(count);
    }

    function markStaleRow(productoId) {
        if (!productoId) return;
        var selector = '[data-producto-id="' + String(productoId) + '"]';
        var nodes = document.querySelectorAll(selector);
        nodes.forEach(function (el) {
            el.classList.add('rt-stale');
            setTimeout(function () { el.classList.remove('rt-stale'); }, 2800);
        });
    }

    /**
     * Actualiza en vivo el texto del stock dentro de cada elemento marcado
     * con data-producto-id=<id>. También recalcula el estado crítico (clases
     * `is-critical` y `catalog-card__stock--alert`) usando el stock_minimo
     * que la tarjeta lleva en data-stock-minimo, o el que viene en el evento.
     */
    function applyStockToDom(productoId, stockNuevo, stockMinimoFromEvent) {
        if (productoId == null || stockNuevo == null || isNaN(Number(stockNuevo))) return;
        var stock = Number(stockNuevo);
        var nodes = document.querySelectorAll('[data-producto-id="' + String(productoId) + '"]');
        nodes.forEach(function (root) {
            // Texto del stock
            root.querySelectorAll('[data-stock-display]').forEach(function (el) {
                el.textContent = fmt(stock);
            });

            // Reevaluar estado crítico
            var min = root.getAttribute('data-stock-minimo');
            if (min === null && stockMinimoFromEvent != null) min = String(stockMinimoFromEvent);
            if (min !== null && min !== '') {
                var minNum = Number(min);
                if (!isNaN(minNum)) {
                    var critical = stock <= minNum;
                    root.classList.toggle('is-critical', critical);
                    root.querySelectorAll('[data-stock-alert]').forEach(function (el) {
                        el.classList.toggle('catalog-card__stock--alert', critical);
                    });
                }
            }
        });
    }

    function handleStockChange(d) {
        var delta = Number(d.delta);
        var hasDelta = !isNaN(delta) && delta !== 0;
        var sign = delta > 0 ? '+' : (delta < 0 ? '−' : '±');
        var absDelta = hasDelta ? fmt(Math.abs(delta)) : null;
        var kind = !hasDelta ? 'info' : (delta < 0 ? 'down' : 'up');

        var deltaHtml = hasDelta
            ? '<span class="rt-delta rt-delta--' + (delta < 0 ? 'down' : 'up') + '">'
                  + sign + absDelta + ' ' + escapeHtml(d.unidad || '') + '</span>'
            : '<span class="rt-delta rt-delta--neutral">recalculado</span>';

        var html = ''
            + '<div class="rt-toast__head">'
                + '<span class="rt-toast__tag">' + escapeHtml(tipoLabel(d)) + '</span>'
                + (d.motivo ? '<span class="rt-toast__motivo">' + escapeHtml(d.motivo) + '</span>' : '')
            + '</div>'
            + '<div class="rt-toast__body">'
                + '<strong>' + escapeHtml(d.producto_nombre || ('#' + d.producto_id)) + '</strong>'
                + (d.producto_codigo ? ' <span class="rt-toast__code">' + escapeHtml(d.producto_codigo) + '</span>' : '')
            + '</div>'
            + '<div class="rt-toast__meta">'
                + deltaHtml
                + '<span class="rt-toast__stock">stock <strong>' + fmt(d.stock_nuevo) + '</strong></span>'
            + '</div>'
            + '<div class="rt-toast__foot">por ' + escapeHtml(d.usuario || 'sistema') + '</div>';

        showToast(html, kind);
        applyStockToDom(d.producto_id, d.stock_nuevo, d.stock_minimo);
        markStaleRow(d.producto_id);
        updateStockBadge(d.stock_bajo_count);
    }

    function handleMessage(raw) {
        var msg;
        try { msg = JSON.parse(raw); } catch (e) { return; }
        if (!msg || typeof msg !== 'object') return;
        if (msg.event === 'stock_changed')  handleStockChange(msg.data || {});
        if (msg.event === 'entity_changed') handleEntityChange(msg.data || {});
    }

    // ───────────────────────────────────────────────────────────────────────
    // Eventos genéricos: cualquier acción auditada (crear/editar/eliminar de
    // cualquier módulo) emite un `entity_changed`. El cliente:
    //   1. Muestra un toast con quién hizo qué.
    //   2. Marca como obsoletos los nodos en la página vinculados a esa
    //      entidad ([data-entity-id="entidad:id"] o [data-<entidad>-id="id"]).
    //   3. Si la entidad expone `refresh_url`, hace fetch del HTML actualizado
    //      y reemplaza el outerHTML del nodo in-place.
    // ───────────────────────────────────────────────────────────────────────
    var ACCION_VERBOS = {
        crear:      'Nuevo',
        editar:     'Editado',
        eliminar:   'Eliminado',
        archivar:   'Archivado',
        cancelar:   'Cancelado',
        entregar:   'Entregado',
        ajustar:    'Ajustado',
        entrada:    'Entrada',
        salida:     'Salida',
        actualizar: 'Actualizado',
        guardar:    'Guardado'
    };

    function accionLabel(a) {
        return ACCION_VERBOS[a] || (a ? a.charAt(0).toUpperCase() + a.slice(1) : 'Cambio');
    }

    function entidadLabel(e) {
        if (!e) return 'registro';
        return e.charAt(0).toUpperCase() + e.slice(1);
    }

    function entitySelectors(entidad, id) {
        var idStr = String(id);
        return [
            '[data-entity-id="' + entidad + ':' + idStr + '"]',
            '[data-' + entidad + '-id="' + idStr + '"]'
        ];
    }

    function findEntityNodes(entidad, id) {
        var nodes = [];
        entitySelectors(entidad, id).forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                if (nodes.indexOf(el) === -1) nodes.push(el);
            });
        });
        return nodes;
    }

    function refreshNodeFromUrl(node, url) {
        if (!node || !url) return;
        fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch', 'Accept': 'text/html' }
        }).then(function (r) {
            if (!r.ok) return null;
            return r.text();
        }).then(function (html) {
            if (!html) return;
            var tpl = document.createElement('template');
            tpl.innerHTML = html.trim();
            var fresh = tpl.content.firstElementChild;
            if (!fresh) return;
            fresh.classList.add('rt-stale');
            node.replaceWith(fresh);
            setTimeout(function () { fresh.classList.remove('rt-stale'); }, 2800);
        }).catch(function () { /* silencio */ });
    }

    function handleEntityChange(d) {
        var entidad = d.entidad || 'registro';
        var accion  = d.accion  || 'cambio';
        var id      = d.entidad_id;

        // Toast genérico
        var html = ''
            + '<div class="rt-toast__head">'
                + '<span class="rt-toast__tag">' + escapeHtml(accionLabel(accion)) + '</span>'
                + '<span class="rt-toast__motivo">' + escapeHtml(entidadLabel(entidad)) + '</span>'
            + '</div>'
            + (d.descripcion
                ? '<div class="rt-toast__body">' + escapeHtml(d.descripcion) + '</div>'
                : (id != null
                    ? '<div class="rt-toast__body">#' + escapeHtml(id) + '</div>'
                    : ''))
            + '<div class="rt-toast__foot">por ' + escapeHtml(d.usuario || 'sistema') + '</div>';

        var kind = accion === 'eliminar' || accion === 'archivar' || accion === 'cancelar'
            ? 'down'
            : (accion === 'crear' ? 'up' : 'info');
        showToast(html, kind);

        // Buscar nodos relacionados en la página actual
        if (id == null || id === '') return;
        var nodes = findEntityNodes(entidad, id);
        if (nodes.length === 0) return;

        // Si la acción es eliminar/archivar/cancelar, sólo marcamos stale: la
        // fila debería desaparecer al recargar. No la borramos sola para no
        // ser disruptivos si el usuario aún está mirándola.
        if (accion === 'eliminar' || accion === 'archivar') {
            nodes.forEach(function (n) {
                n.classList.add('rt-stale');
                n.style.opacity = '0.5';
            });
            return;
        }

        // Para crear/editar/ajustar/etc.: si hay refresh_url, reemplazamos el
        // HTML del nodo; si no, sólo parpadeo.
        if (d.refresh_url) {
            nodes.forEach(function (n) { refreshNodeFromUrl(n, d.refresh_url); });
        } else {
            nodes.forEach(function (n) {
                n.classList.add('rt-stale');
                setTimeout(function () { n.classList.remove('rt-stale'); }, 2800);
            });
        }
    }

    function connect() {
        try { ws = new WebSocket(wsUrl); }
        catch (e) { scheduleRetry(); return; }

        ws.addEventListener('open',    function () { setStatus(true); });
        ws.addEventListener('message', function (ev) { handleMessage(ev.data); });
        ws.addEventListener('close',   function () { setStatus(false); scheduleRetry(); });
        ws.addEventListener('error',   function () { try { ws.close(); } catch (_) {} });
    }

    function scheduleRetry() {
        clearTimeout(retryTimer);
        retryTimer = setTimeout(connect, WS_RETRY_MS);
    }

    // Inicio
    setStatus(false);
    connect();

    // Cierre limpio al salir de la página (Firefox/Chrome)
    window.addEventListener('beforeunload', function () {
        try { if (ws) ws.close(); } catch (_) {}
    });
})();
