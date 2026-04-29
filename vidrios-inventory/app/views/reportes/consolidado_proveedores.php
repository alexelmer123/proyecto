<?php
/** @var array $datos */ /** @var array $totales */
$h     = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$money = static fn(float $n): string  => 'S/. ' . number_format($n, 0, ',', '.');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Reporte</p>
        <h1 class="page-head__title">Consolidado de proveedores</h1>
        <p class="page-head__caption">
            <?= count($datos) ?> proveedores ·
            <?= (int) $totales['total_pedidos'] ?> pedidos ·
            comprado total <strong class="mono"><?= $h($money((float) $totales['total_comprado'])) ?></strong> ·
            deuda activa <strong class="mono text-rose"><?= $h($money((float) $totales['deuda_activa'])) ?></strong>
        </p>
    </div>
</header>

<section class="table-shell">
    <table class="table" id="tabla-consolidado">
        <thead>
        <tr>
            <th class="table__th">Proveedor</th>
            <th class="table__th">Provee</th>
            <th class="table__th">Ubicación</th>
            <th class="table__th table__th--num">Productos</th>
            <th class="table__th table__th--num">Inventario actual</th>
            <th class="table__th table__th--num">Pedidos</th>
            <th class="table__th table__th--num">Comprado</th>
            <th class="table__th table__th--num">Pagado</th>
            <th class="table__th table__th--num">Deuda</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$datos): ?>
            <tr><td colspan="9" class="table__empty">Aún no hay proveedores registrados.</td></tr>
        <?php endif; ?>
        <?php foreach ($datos as $r):
            $deuda = (float) $r['deuda_activa'];
            $ubic = trim(($r['ciudad'] ?? '') . (!empty($r['pais']) ? ', ' . $r['pais'] : ''), ', ');
        ?>
            <tr class="table__row<?= ((int) $r['estado'] === 0 ? ' is-disabled' : '') ?>">
                <td class="table__td">
                    <strong><?= $h($r['nombre']) ?></strong>
                    <?php if (!empty($r['email'])): ?>
                        <span class="cell-product__sub mono"><?= $h($r['email']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="table__td">
                    <?php if (!empty($r['descripcion_productos'])): ?>
                        <small><?= $h(mb_strimwidth((string) $r['descripcion_productos'], 0, 80, '…', 'UTF-8')) ?></small>
                    <?php else: ?>
                        <small class="muted">—</small>
                    <?php endif; ?>
                </td>
                <td class="table__td"><?= $ubic !== '' ? $h($ubic) : '<small class="muted">—</small>' ?></td>
                <td class="table__td table__td--num mono"><?= (int) $r['total_productos'] ?></td>
                <td class="table__td table__td--num mono"><?= $h($money((float) $r['valor_inventario'])) ?></td>
                <td class="table__td table__td--num mono">
                    <?= (int) $r['total_pedidos'] ?>
                    <?php if ((int) $r['pedidos_pendientes'] > 0): ?>
                        <small class="muted">· <?= (int) $r['pedidos_pendientes'] ?> pend.</small>
                    <?php endif; ?>
                </td>
                <td class="table__td table__td--num mono"><?= $h($money((float) $r['total_comprado'])) ?></td>
                <td class="table__td table__td--num mono"><?= $h($money((float) $r['total_pagado'])) ?></td>
                <td class="table__td table__td--num mono">
                    <strong class="<?= $deuda > 0 ? 'text-rose' : 'text-ok' ?>"><?= $h($money($deuda)) ?></strong>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($datos): ?>
        <tfoot>
            <tr class="table__row table__row--totals">
                <td class="table__td" colspan="3"><strong>Totales</strong></td>
                <td class="table__td table__td--num mono"><strong><?= (int) $totales['total_productos'] ?></strong></td>
                <td class="table__td table__td--num mono"><strong><?= $h($money((float) $totales['valor_inventario'])) ?></strong></td>
                <td class="table__td table__td--num mono"><strong><?= (int) $totales['total_pedidos'] ?></strong></td>
                <td class="table__td table__td--num mono"><strong><?= $h($money((float) $totales['total_comprado'])) ?></strong></td>
                <td class="table__td table__td--num mono"><strong><?= $h($money((float) $totales['total_pagado'])) ?></strong></td>
                <td class="table__td table__td--num mono">
                    <strong class="text-rose"><?= $h($money((float) $totales['deuda_activa'])) ?></strong>
                </td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</section>
