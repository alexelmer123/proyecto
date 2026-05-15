<?php
/** @var array $producto */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$bajo       = (int) $producto['stock_actual'] <= (int) $producto['stock_minimo'];
$margen     = ((float) $producto['precio_venta']) - ((float) $producto['precio_compra']);
$margenPct  = (float) $producto['precio_compra'] > 0
    ? ($margen / (float) $producto['precio_compra']) * 100
    : null;
$id         = (int) $producto['id'];

$dim = [];
if (!empty($producto['ancho']))  $dim[] = rtrim(rtrim(number_format((float) $producto['ancho'],  2, '.', ''), '0'), '.') . ' mm';
if (!empty($producto['alto']))   $dim[] = rtrim(rtrim(number_format((float) $producto['alto'],   2, '.', ''), '0'), '.') . ' mm';
if (!empty($producto['grosor'])) $dim[] = rtrim(rtrim(number_format((float) $producto['grosor'], 2, '.', ''), '0'), '.') . ' mm';
$dimensiones = $dim ? implode(' × ', $dim) : '—';
?>
<div class="producto-detalle">
    <div class="producto-detalle__cover">
        <?php if (!empty($producto['imagen'])): ?>
            <a href="<?= $h($producto['imagen']) ?>" data-lightbox
               data-caption="<?= $h($producto['nombre']) ?>" class="producto-detalle__cover-link">
                <img src="<?= $h($producto['imagen']) ?>" alt="<?= $h($producto['nombre']) ?>" loading="lazy">
            </a>
        <?php else: ?>
            <div class="producto-detalle__cover-ph" data-color="<?= $id % 6 ?>">
                <span><?= $h(mb_strtoupper(mb_substr((string) $producto['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="producto-detalle__head">
        <p class="producto-detalle__code mono"><?= $h($producto['codigo']) ?></p>
        <h3 class="producto-detalle__name"><?= $h($producto['nombre']) ?></h3>
        <?php if (!empty($producto['descripcion'])): ?>
            <p class="producto-detalle__desc"><?= nl2br($h($producto['descripcion'])) ?></p>
        <?php endif; ?>
    </div>

    <div class="producto-detalle__grid">
        <div class="producto-detalle__metric">
            <span class="producto-detalle__metric-label">Categoría</span>
            <span class="producto-detalle__metric-value"><?= $h($producto['categoria_nombre'] ?? '—') ?></span>
        </div>
        <div class="producto-detalle__metric">
            <span class="producto-detalle__metric-label">Proveedor</span>
            <span class="producto-detalle__metric-value"><?= $h($producto['proveedor_nombre'] ?? '—') ?></span>
        </div>
        <div class="producto-detalle__metric">
            <span class="producto-detalle__metric-label">Unidad</span>
            <span class="producto-detalle__metric-value mono"><?= $h($producto['unidad'] ?? '—') ?></span>
        </div>
        <div class="producto-detalle__metric">
            <span class="producto-detalle__metric-label">Dimensiones</span>
            <span class="producto-detalle__metric-value mono"><?= $h($dimensiones) ?></span>
        </div>
    </div>

    <div class="producto-detalle__grid producto-detalle__grid--prices">
        <div class="producto-detalle__metric">
            <span class="producto-detalle__metric-label">Precio compra</span>
            <span class="producto-detalle__metric-value mono">S/. <?= number_format((float) $producto['precio_compra'], 2, '.', ',') ?></span>
        </div>
        <div class="producto-detalle__metric">
            <span class="producto-detalle__metric-label">Precio venta</span>
            <span class="producto-detalle__metric-value mono producto-detalle__metric-value--accent">S/. <?= number_format((float) $producto['precio_venta'], 2, '.', ',') ?></span>
        </div>
        <div class="producto-detalle__metric">
            <span class="producto-detalle__metric-label">Margen</span>
            <span class="producto-detalle__metric-value mono">
                S/. <?= number_format($margen, 2, '.', ',') ?>
                <?php if ($margenPct !== null): ?>
                    <small class="producto-detalle__metric-hint">(<?= number_format($margenPct, 1, '.', '') ?>%)</small>
                <?php endif; ?>
            </span>
        </div>
        <div class="producto-detalle__metric">
            <span class="producto-detalle__metric-label">Stock</span>
            <span class="stock-pill <?= $bajo ? 'stock-pill--alert' : 'stock-pill--ok' ?>">
                <strong><?= (int) $producto['stock_actual'] ?></strong>
                <span class="stock-pill__min">mín. <?= (int) $producto['stock_minimo'] ?></span>
            </span>
        </div>
    </div>

    <div class="modal__foot modal__foot--inline">
        <button type="button" class="btn btn--ghost" data-modal-cancel>Cerrar</button>
        <a href="<?= BASE_URL ?>/producto/ajustarStock/<?= $id ?>" class="btn btn--ghost">
            <?= icon('adjust', 16) ?>
            <span>Ajustar stock</span>
        </a>
        <a href="<?= BASE_URL ?>/producto/editar/<?= $id ?>" class="btn btn--primary">
            <?= icon('edit', 16) ?>
            <span>Editar producto</span>
        </a>
    </div>
</div>
