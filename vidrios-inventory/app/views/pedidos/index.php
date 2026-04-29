<?php
/** @var array     $pedidos */
/** @var Paginator $paginator */
/** @var ?string   $estado */
/** @var array     $resumen */
/** @var array     $extrasUrl */
$h     = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$money = static fn(float $n): string  => 'S/. ' . number_format($n, 0, ',', '.');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Compras</p>
        <h1 class="page-head__title">Pedidos a proveedores</h1>
        <p class="page-head__caption">
            <?= (int) $paginator->total ?> pedidos registrados ·
            deuda activa: <strong class="mono"><?= $h($money((float) $resumen['total_deuda'])) ?></strong>
        </p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/pedido/exportar<?= $estado ? '?estado=' . urlencode($estado) : '' ?>" class="btn btn--ghost">↓ Exportar CSV</a>
        <a href="<?= BASE_URL ?>/pedido/crear" class="btn btn--primary">+ Nuevo pedido</a>
    </div>
</header>

<section class="estado-tabs">
    <a class="estado-tab<?= $estado === null ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/pedido/index">
        Todos <span class="estado-tab__num mono"><?= (int) ($resumen['pendientes'] + $resumen['pagados'] + $resumen['deudas']) ?></span>
    </a>
    <a class="estado-tab estado-tab--pendiente<?= $estado === 'pendiente' ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/pedido/index?estado=pendiente">
        Pendientes <span class="estado-tab__num mono"><?= (int) $resumen['pendientes'] ?></span>
    </a>
    <a class="estado-tab estado-tab--pagado<?= $estado === 'pagado' ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/pedido/index?estado=pagado">
        Pagados <span class="estado-tab__num mono"><?= (int) $resumen['pagados'] ?></span>
    </a>
    <a class="estado-tab estado-tab--deuda<?= $estado === 'deuda' ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/pedido/index?estado=deuda">
        Deudas <span class="estado-tab__num mono"><?= (int) $resumen['deudas'] ?></span>
    </a>
</section>

<section class="table-shell">
    <table class="table" id="tabla-pedidos">
        <thead>
        <tr>
            <th class="table__th">Número</th>
            <th class="table__th">Proveedor</th>
            <th class="table__th">Fecha pedido</th>
            <th class="table__th">Entrega</th>
            <th class="table__th table__th--num">Total</th>
            <th class="table__th table__th--num">Pagado</th>
            <th class="table__th table__th--num">Saldo</th>
            <th class="table__th">Estado</th>
            <th class="table__th table__th--actions">Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$pedidos): ?>
            <tr><td colspan="9" class="table__empty">No hay pedidos para el filtro seleccionado.</td></tr>
        <?php endif; ?>
        <?php foreach ($pedidos as $p):
            $saldo = (float) $p['saldo'];
        ?>
            <tr class="table__row">
                <td class="table__td mono"><?= $h($p['numero']) ?></td>
                <td class="table__td"><strong><?= $h($p['proveedor_nombre'] ?? '—') ?></strong></td>
                <td class="table__td mono"><?= $h($p['fecha_pedido']) ?></td>
                <td class="table__td mono"><?= $h($p['fecha_entrega'] ?? '—') ?></td>
                <td class="table__td table__td--num mono"><?= $h($money((float) $p['total'])) ?></td>
                <td class="table__td table__td--num mono"><?= $h($money((float) $p['pagado'])) ?></td>
                <td class="table__td table__td--num mono">
                    <strong class="<?= $saldo > 0 ? 'text-rose' : 'text-ok' ?>"><?= $h($money($saldo)) ?></strong>
                </td>
                <td class="table__td">
                    <span class="estado estado--<?= $h((string) $p['estado']) ?>"><?= $h(ucfirst((string) $p['estado'])) ?></span>
                </td>
                <td class="table__td table__td--actions">
                    <?php if ($p['estado'] !== 'pagado'): ?>
                        <a class="iconbtn iconbtn--ok" href="<?= BASE_URL ?>/pedido/pagar/<?= (int) $p['id'] ?>"
                           data-confirm="¿Marcar el pedido <?= $h($p['numero']) ?> como pagado?"
                           title="Marcar como pagado">S/.</a>
                    <?php endif; ?>
                    <a class="iconbtn" href="<?= BASE_URL ?>/pedido/editar/<?= (int) $p['id'] ?>" title="Editar">✎</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?= $paginator->render(BASE_URL . '/pedido/index', $extrasUrl) ?>
