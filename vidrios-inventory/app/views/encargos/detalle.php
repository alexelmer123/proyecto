<?php
/** @var array $encargo */ /** @var array $items */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$estadoLabels = ['pendiente' => 'Pendiente', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
$estado = (string) $encargo['estado'];

$totalUnidades = 0;
$totalValor    = 0.0;
foreach ($items as $it) {
    $totalUnidades += (int) $it['cantidad'];
    $totalValor    += (int) $it['cantidad'] * (float) ($it['precio_unitario'] ?? 0);
}
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Salidas · Encargos</p>
        <h1 class="page-head__title">Encargo <span class="mono"><?= $h($encargo['codigo']) ?></span></h1>
        <p class="page-head__caption">
            <span class="mov mov--<?= $h($estado) ?>"><?= $h($estadoLabels[$estado] ?? $estado) ?></span>
            · Creado el <?= $h((string) $encargo['created_at']) ?>
            <?php if (!empty($encargo['usuario_nombre'])): ?>
                por <?= $h((string) $encargo['usuario_nombre']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/encargo/index" class="btn btn--ghost">← Volver</a>
        <?php if ($estado === 'pendiente'): ?>
            <a href="<?= BASE_URL ?>/encargo/editar/<?= (int) $encargo['id'] ?>" class="btn btn--ghost">
                <?= icon('edit', 16) ?> <span>Editar</span>
            </a>
            <a href="<?= BASE_URL ?>/encargo/cancelar/<?= (int) $encargo['id'] ?>" class="btn btn--ghost"
               data-confirm="¿Cancelar el encargo «<?= $h($encargo['codigo']) ?>»? Se devolverá el stock de los productos.">
                <?= icon('archive', 16) ?> <span>Cancelar</span>
            </a>
            <a href="<?= BASE_URL ?>/encargo/entregar/<?= (int) $encargo['id'] ?>" class="btn btn--primary"
               data-confirm="¿Marcar el encargo «<?= $h($encargo['codigo']) ?>» como entregado?">
                <?= icon('check', 16) ?> <span>Marcar entregado</span>
            </a>
        <?php endif; ?>
    </div>
</header>

<section class="encargo-detalle">
    <div class="encargo-detalle__head">
        <div class="encargo-detalle__data">
            <h2 class="encargo-detalle__cliente"><?= $h($encargo['cliente']) ?></h2>
            <ul class="encargo-detalle__list">
                <?php if (!empty($encargo['telefono'])): ?>
                    <li>
                        <?= icon('phone', 14) ?>
                        <span><strong>Teléfono:</strong> <span class="mono"><?= $h($encargo['telefono']) ?></span></span>
                    </li>
                <?php endif; ?>
                <li>
                    <?= icon('calendar', 14) ?>
                    <span><strong>Fecha de entrega:</strong>
                        <span class="mono"><?= $encargo['fecha_entrega'] ? $h((string) $encargo['fecha_entrega']) : '—' ?></span>
                    </span>
                </li>
                <?php if (!empty($encargo['lugar_entrega'])): ?>
                    <li>
                        <?= icon('pin', 14) ?>
                        <span><strong>Lugar:</strong> <?= $h($encargo['lugar_entrega']) ?></span>
                    </li>
                <?php endif; ?>
            </ul>
            <?php if (!empty($encargo['detalles'])): ?>
                <div class="encargo-detalle__notes">
                    <strong>Detalles del encargo</strong>
                    <p><?= nl2br($h($encargo['detalles'])) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="encargo-detalle__totals">
            <div class="encargo-detalle__total">
                <span class="encargo-detalle__total-label">Productos</span>
                <strong class="encargo-detalle__total-value mono"><?= count($items) ?></strong>
            </div>
            <div class="encargo-detalle__total">
                <span class="encargo-detalle__total-label">Unidades</span>
                <strong class="encargo-detalle__total-value mono"><?= $totalUnidades ?></strong>
            </div>
            <?php if ($totalValor > 0): ?>
                <div class="encargo-detalle__total">
                    <span class="encargo-detalle__total-label">Total</span>
                    <strong class="encargo-detalle__total-value mono">S/. <?= number_format($totalValor, 2, '.', ',') ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-shell">
        <table class="table">
            <thead>
                <tr>
                    <th class="table__th">Producto</th>
                    <th class="table__th table__th--num">Cantidad</th>
                    <th class="table__th table__th--num">Precio unit.</th>
                    <th class="table__th table__th--num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it):
                    $sub = (int) $it['cantidad'] * (float) ($it['precio_unitario'] ?? 0);
                ?>
                    <tr class="table__row">
                        <td class="table__td">
                            <strong class="mono"><?= $h($it['producto_codigo']) ?></strong>
                            <span class="cell-product__sub"><?= $h($it['producto_nombre']) ?></span>
                        </td>
                        <td class="table__td table__td--num mono">
                            <?= (int) $it['cantidad'] ?> <?= $h($it['producto_unidad'] ?? '') ?>
                        </td>
                        <td class="table__td table__td--num mono">
                            <?= $it['precio_unitario'] !== null ? 'S/. ' . number_format((float) $it['precio_unitario'], 2, '.', ',') : '—' ?>
                        </td>
                        <td class="table__td table__td--num mono">
                            <?= $sub > 0 ? 'S/. ' . number_format($sub, 2, '.', ',') : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
