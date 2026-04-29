<?php
/** @var array $productos */ /** @var float $total */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$money = static fn(float $v): string => 'S/. ' . number_format($v, 0, ',', '.');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Reporte</p>
        <h1 class="page-head__title">Valor del inventario</h1>
        <p class="page-head__caption">Suma de stock × precio de compra por cada producto activo.</p>
    </div>
    <div class="hero-metric">
        <span class="hero-metric__label">Capital inmovilizado</span>
        <span class="hero-metric__value mono"><?= $money($total) ?></span>
    </div>
</header>

<section class="table-shell">
    <table class="table" id="tabla-valor">
        <thead>
        <tr>
            <th class="table__th">Código</th>
            <th class="table__th">Producto</th>
            <th class="table__th">Categoría</th>
            <th class="table__th table__th--num">Stock</th>
            <th class="table__th table__th--num">P. compra</th>
            <th class="table__th table__th--num">Valor</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$productos): ?>
            <tr><td colspan="6" class="table__empty">No hay productos activos.</td></tr>
        <?php endif; ?>
        <?php foreach ($productos as $p): ?>
            <tr class="table__row">
                <td class="table__td mono"><?= $h($p['codigo']) ?></td>
                <td class="table__td"><strong><?= $h($p['nombre']) ?></strong></td>
                <td class="table__td"><?= $h($p['categoria_nombre'] ?? '—') ?></td>
                <td class="table__td table__td--num mono"><?= (int) $p['stock_actual'] ?></td>
                <td class="table__td table__td--num mono"><?= $money((float) $p['precio_compra']) ?></td>
                <td class="table__td table__td--num mono"><strong><?= $money((float) $p['valor_total']) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <th class="table__th" colspan="5" style="text-align:right;">Total</th>
            <th class="table__th table__th--num mono"><?= $money($total) ?></th>
        </tr>
        </tfoot>
    </table>
</section>
