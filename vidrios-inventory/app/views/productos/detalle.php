<?php
/** @var array $producto */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$bajo       = (float) $producto['stock_actual'] <= (float) $producto['stock_minimo'];
$margen     = ((float) $producto['precio_venta']) - ((float) $producto['precio_compra']);
$margenPct  = (float) $producto['precio_compra'] > 0
    ? ($margen / (float) $producto['precio_compra']) * 100
    : null;
$id         = (int) $producto['id'];

// Solo mostramos los campos dimensionales que aplican a la unidad guardada
// (m²/lámina → ancho×alto×grosor; tubo → longitud×diámetro; metro lineal →
// longitud; unidad/kit → sin dimensiones). Si un producto tiene una unidad
// "legado" (p. ej. la 'u' del seed), caemos al comportamiento previo.
$dimsPorUnidad = ProductoController::UNIDADES;
$unidadProducto = (string) ($producto['unidad'] ?? '');
$camposDim = $dimsPorUnidad[$unidadProducto]['dims'] ?? ['ancho', 'alto', 'grosor', 'longitud', 'diametro'];

$fmt = static fn(float $v): string => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . ' mm';
$dim = [];
foreach ($camposDim as $campo) {
    if (!empty($producto[$campo])) {
        $dim[] = $fmt((float) $producto[$campo]);
    }
}
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
                <strong><?= $h(fmt_cantidad($producto['stock_actual'])) ?></strong>
                <span class="stock-pill__min">mín. <?= $h(fmt_cantidad($producto['stock_minimo'])) ?></span>
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
