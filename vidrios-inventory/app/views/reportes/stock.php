<?php
/** @var array       $productos */
/** @var float       $totalValor */
/** @var int         $totalCriticos */
/** @var bool        $soloCriticos */
/** @var int|null    $categoriaId */
/** @var int|null    $proveedorId */
/** @var array       $categorias */
/** @var array       $proveedores */
$h     = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$money = static fn(float $v): string  => 'S/. ' . number_format($v, 0, ',', '.');

$qs = http_build_query(array_filter([
    'criticos'  => $soloCriticos ? '1' : null,
    'categoria' => $categoriaId,
    'proveedor' => $proveedorId,
]));
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Reporte</p>
        <h1 class="page-head__title">Stock</h1>
        <p class="page-head__caption">
            <?= count($productos) ?> productos ·
            <strong class="<?= $totalCriticos > 0 ? 'text-rose' : 'text-ok' ?>">
                <?= (int) $totalCriticos ?> crítico<?= $totalCriticos === 1 ? '' : 's' ?>
            </strong>
            · capital inmovilizado <strong class="mono"><?= $h($money($totalValor)) ?></strong>
        </p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/reporte/exportarStock<?= $qs !== '' ? '?' . $qs : '' ?>" class="btn btn--ghost">↓ Exportar CSV</a>
        <a href="<?= BASE_URL ?>/movimiento/registrarEntrada" class="btn btn--primary">+ Registrar entrada</a>
    </div>
</header>

<form method="get" action="<?= BASE_URL ?>/reporte/stock" class="filters">
    <label class="field">
        <span class="field__label">Categoría</span>
        <select name="categoria" class="field__input">
            <option value="">Todas</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $categoriaId === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= $h($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span class="field__label">Proveedor</span>
        <select name="proveedor" class="field__input">
            <option value="">Todos</option>
            <?php foreach ($proveedores as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $proveedorId === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= $h($p['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field field--inline">
        <input type="checkbox" name="criticos" value="1" <?= $soloCriticos ? 'checked' : '' ?>>
        <span class="field__label">Solo críticos</span>
    </label>
    <button type="submit" class="btn btn--ghost">Aplicar</button>
    <?php if ($soloCriticos || $categoriaId || $proveedorId): ?>
        <a href="<?= BASE_URL ?>/reporte/stock" class="btn btn--ghost btn--sm">Limpiar</a>
    <?php endif; ?>
</form>

<section class="table-shell">
    <table class="table" id="tabla-stock">
        <thead>
        <tr>
            <th class="table__th">Código</th>
            <th class="table__th">Producto</th>
            <th class="table__th">Categoría</th>
            <th class="table__th">Proveedor</th>
            <th class="table__th table__th--num">Stock</th>
            <th class="table__th table__th--num">Mínimo</th>
            <th class="table__th table__th--num">Faltante</th>
            <th class="table__th table__th--num">P. compra</th>
            <th class="table__th table__th--num">Valor</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$productos): ?>
            <tr><td colspan="9" class="table__empty">Sin productos que coincidan con el filtro.</td></tr>
        <?php endif; ?>
        <?php foreach ($productos as $p):
            $critico = (int) $p['es_critico'] === 1;
        ?>
            <tr class="table__row<?= $critico ? ' is-critical' : '' ?>">
                <td class="table__td mono"><?= $h($p['codigo']) ?></td>
                <td class="table__td"><strong><?= $h($p['nombre']) ?></strong></td>
                <td class="table__td"><?= $h($p['categoria_nombre'] ?? '—') ?></td>
                <td class="table__td"><?= $h($p['proveedor_nombre'] ?? '—') ?></td>
                <td class="table__td table__td--num mono">
                    <?php if ($critico): ?>
                        <strong class="text-rose"><?= $h(fmt_cantidad($p['stock_actual'])) ?></strong>
                    <?php else: ?>
                        <?= $h(fmt_cantidad($p['stock_actual'])) ?>
                    <?php endif; ?>
                </td>
                <td class="table__td table__td--num mono"><?= $h(fmt_cantidad($p['stock_minimo'])) ?></td>
                <td class="table__td table__td--num mono">
                    <?php if ((float) $p['faltante'] > 0): ?>
                        <strong><?= $h(fmt_cantidad($p['faltante'])) ?></strong>
                    <?php else: ?>
                        <small class="muted">—</small>
                    <?php endif; ?>
                </td>
                <td class="table__td table__td--num mono"><?= $h($money((float) $p['precio_compra'])) ?></td>
                <td class="table__td table__td--num mono"><strong><?= $h($money((float) $p['valor_total'])) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($productos): ?>
        <tfoot>
            <tr class="table__row table__row--totals">
                <td class="table__td" colspan="8" style="text-align:right;"><strong>Total valor</strong></td>
                <td class="table__td table__td--num mono"><strong><?= $h($money($totalValor)) ?></strong></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</section>
