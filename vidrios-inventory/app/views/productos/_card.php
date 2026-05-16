<?php
/**
 * Tarjeta individual del catálogo de productos.
 *
 * Se usa en dos lugares:
 *   - app/views/productos/index.php (loop completo del listado)
 *   - ProductoController::tarjeta($id) → respuesta AJAX para el cliente
 *     realtime, que reemplaza in-place el HTML de la tarjeta cuando un
 *     evento `entity_changed` con entidad="producto" llega por WebSocket.
 *
 * @var array       $p  fila del producto
 */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$bajo = (float) $p['stock_actual'] <= (float) $p['stock_minimo'];
?>
<article class="catalog-card<?= $bajo ? ' is-critical' : '' ?>"
         data-producto-id="<?= (int) $p['id'] ?>"
         data-entity-id="producto:<?= (int) $p['id'] ?>"
         data-realtime-refresh-url="<?= BASE_URL ?>/producto/tarjeta/<?= (int) $p['id'] ?>"
         data-stock-minimo="<?= htmlspecialchars((string) (float) $p['stock_minimo'], ENT_QUOTES, 'UTF-8') ?>">
    <button type="button"
            class="catalog-card__cover"
            aria-label="Ver detalles de <?= $h($p['nombre']) ?>"
            data-modal-src="<?= BASE_URL ?>/producto/detalle/<?= (int) $p['id'] ?>"
            data-modal-title="<?= $h($p['nombre']) ?>"
            data-modal-kicker="Catálogo · Detalle"
            data-modal-size="lg">
        <?php if (!empty($p['imagen'])): ?>
            <img src="<?= $h($p['imagen']) ?>" alt="<?= $h($p['nombre']) ?>" loading="lazy">
        <?php else: ?>
            <span class="catalog-card__cover-ph" data-color="<?= (int) $p['id'] % 6 ?>">
                <?= $h(mb_strtoupper(mb_substr((string) $p['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
            </span>
        <?php endif; ?>
    </button>

    <div class="catalog-card__actions">
        <a class="iconbtn" href="<?= BASE_URL ?>/producto/ajustarStock/<?= (int) $p['id'] ?>" title="Ajustar stock">
            <?= icon('adjust', 16) ?>
        </a>
        <a class="iconbtn"
           href="<?= BASE_URL ?>/producto/editar/<?= (int) $p['id'] ?>"
           title="Editar producto">
            <?= icon('edit', 16) ?>
        </a>
        <a class="iconbtn iconbtn--danger" href="<?= BASE_URL ?>/producto/eliminar/<?= (int) $p['id'] ?>"
           data-confirm="¿Archivar el producto «<?= $h($p['nombre']) ?>»? El stock quedará oculto del catálogo."
           title="Archivar">
            <?= icon('archive', 16) ?>
        </a>
    </div>

    <div class="catalog-card__body">
        <p class="catalog-card__code mono"><?= $h($p['codigo']) ?></p>
        <h3 class="catalog-card__name"><?= $h($p['nombre']) ?></h3>
        <p class="catalog-card__price mono">
            S/. <?= number_format((float) $p['precio_venta'], 2, '.', ',') ?>
        </p>
        <span class="catalog-card__stock <?= $bajo ? 'catalog-card__stock--alert' : '' ?>" data-stock-alert>
            <?= icon('package', 14) ?>
            Stock: <strong data-stock-display><?= $h(fmt_cantidad($p['stock_actual'])) ?></strong>
        </span>
    </div>
</article>
