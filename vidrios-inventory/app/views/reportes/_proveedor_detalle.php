<?php
/** @var array $proveedor */
/** @var array $productos */
/** @var array $pedidos */
$h     = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$money = static fn(float $v): string  => 'S/. ' . number_format($v, 0, ',', '.');

$ubicacion = trim(($proveedor['ciudad'] ?? '') . (!empty($proveedor['pais']) ? ', ' . $proveedor['pais'] : ''), ', ');
$deuda = (float) $proveedor['deuda_activa'];

$estadoBadges = [
    'pendiente' => 'badge--warn',
    'deuda'     => 'badge--alert',
    'pagado'    => 'badge--ok',
];
?>
<div class="proveedor-detalle">
    <div class="proveedor-detalle__head">
        <div>
            <h3 class="proveedor-detalle__name"><?= $h($proveedor['nombre']) ?></h3>
            <?php if (!empty($proveedor['descripcion_productos'])): ?>
                <p class="proveedor-detalle__desc"><?= $h($proveedor['descripcion_productos']) ?></p>
            <?php endif; ?>
        </div>
        <?php if ((int) $proveedor['estado'] === 0): ?>
            <span class="badge badge--alert">Inactivo</span>
        <?php endif; ?>
    </div>

    <div class="proveedor-detalle__contact">
        <?php if (!empty($proveedor['email'])): ?>
            <span class="mono"><?= $h($proveedor['email']) ?></span>
        <?php endif; ?>
        <?php if (!empty($proveedor['telefono'])): ?>
            <span class="mono"><?= $h($proveedor['telefono']) ?></span>
        <?php endif; ?>
        <?php if ($ubicacion !== ''): ?>
            <span><?= $h($ubicacion) ?></span>
        <?php endif; ?>
        <?php if (!empty($proveedor['direccion'])): ?>
            <small class="muted"><?= $h($proveedor['direccion']) ?></small>
        <?php endif; ?>
    </div>

    <section class="metrics-grid metrics-grid--compact">
        <article class="metric">
            <span class="metric__label">Productos activos</span>
            <span class="metric__value mono"><?= (int) $proveedor['total_productos'] ?></span>
        </article>
        <article class="metric">
            <span class="metric__label">Valor en inventario</span>
            <span class="metric__value mono"><?= $h($money((float) $proveedor['valor_inventario'])) ?></span>
        </article>
        <article class="metric">
            <span class="metric__label">Pedidos</span>
            <span class="metric__value mono"><?= (int) $proveedor['total_pedidos'] ?></span>
            <?php if ((int) $proveedor['pedidos_pendientes'] > 0): ?>
                <span class="metric__sub"><?= (int) $proveedor['pedidos_pendientes'] ?> pendientes</span>
            <?php endif; ?>
        </article>
        <article class="metric <?= $deuda > 0 ? 'metric--alert' : 'metric--ok' ?>">
            <span class="metric__label">Deuda activa</span>
            <span class="metric__value mono"><?= $h($money($deuda)) ?></span>
            <span class="metric__sub">comprado <?= $h($money((float) $proveedor['total_comprado'])) ?></span>
        </article>
    </section>

    <details class="proveedor-detalle__section" open>
        <summary>
            Productos que provee
            <span class="muted">· <?= count($productos) ?></span>
        </summary>
        <?php if (!$productos): ?>
            <p class="muted">Este proveedor no tiene productos activos asociados.</p>
        <?php else: ?>
            <div class="table-shell table-shell--inset">
                <table class="table">
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
                    <?php foreach ($productos as $p):
                        $bajo = (float) $p['stock_actual'] <= (float) $p['stock_minimo']; ?>
                        <tr class="table__row<?= $bajo ? ' is-critical' : '' ?>">
                            <td class="table__td mono"><?= $h($p['codigo']) ?></td>
                            <td class="table__td"><strong><?= $h($p['nombre']) ?></strong></td>
                            <td class="table__td"><?= $h($p['categoria_nombre'] ?? '—') ?></td>
                            <td class="table__td table__td--num mono"><?= $h(fmt_cantidad($p['stock_actual'])) ?></td>
                            <td class="table__td table__td--num mono"><?= $h($money((float) $p['precio_compra'])) ?></td>
                            <td class="table__td table__td--num mono"><strong><?= $h($money((float) $p['valor_total'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </details>

    <details class="proveedor-detalle__section">
        <summary>
            Últimos pedidos
            <span class="muted">· <?= count($pedidos) ?></span>
        </summary>
        <?php if (!$pedidos): ?>
            <p class="muted">No hay pedidos registrados para este proveedor.</p>
        <?php else: ?>
            <div class="table-shell table-shell--inset">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="table__th">Número</th>
                        <th class="table__th">Fecha</th>
                        <th class="table__th table__th--num">Total</th>
                        <th class="table__th table__th--num">Pagado</th>
                        <th class="table__th table__th--num">Saldo</th>
                        <th class="table__th">Estado</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pedidos as $ped):
                        $saldo  = (float) $ped['saldo'];
                        $estado = (string) $ped['estado'];
                        $cls    = $estadoBadges[$estado] ?? 'badge--neutral';
                    ?>
                        <tr class="table__row">
                            <td class="table__td mono"><?= $h($ped['numero']) ?></td>
                            <td class="table__td mono"><small><?= $h(substr((string) $ped['fecha_pedido'], 0, 10)) ?></small></td>
                            <td class="table__td table__td--num mono"><?= $h($money((float) $ped['total'])) ?></td>
                            <td class="table__td table__td--num mono"><?= $h($money((float) $ped['pagado'])) ?></td>
                            <td class="table__td table__td--num mono">
                                <strong class="<?= $saldo > 0 ? 'text-rose' : 'text-ok' ?>"><?= $h($money($saldo)) ?></strong>
                            </td>
                            <td class="table__td"><span class="badge <?= $cls ?>"><?= $h(ucfirst($estado)) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </details>

    <div class="modal__foot modal__foot--inline">
        <button type="button" class="btn btn--ghost" data-modal-cancel>Cerrar</button>
        <a href="<?= BASE_URL ?>/proveedor/editar/<?= (int) $proveedor['id'] ?>" class="btn btn--ghost">
            <?= icon('edit', 16) ?>
            <span>Editar proveedor</span>
        </a>
        <a href="<?= BASE_URL ?>/pedido/index?proveedor=<?= (int) $proveedor['id'] ?>" class="btn btn--primary">
            <?= icon('truck', 16) ?>
            <span>Ver pedidos</span>
        </a>
    </div>
</div>
