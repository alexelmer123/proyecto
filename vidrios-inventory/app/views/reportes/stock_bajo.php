<?php
/** @var array $productos */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Reporte</p>
        <h1 class="page-head__title">Stock crítico</h1>
        <p class="page-head__caption"><?= count($productos) ?> productos en o por debajo del mínimo.</p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/reporte/exportarStockBajo" class="btn btn--ghost">↓ Exportar CSV</a>
        <a href="<?= BASE_URL ?>/movimiento/registrarEntrada" class="btn btn--primary">+ Registrar entrada</a>
    </div>
</header>

<section class="table-shell">
    <table class="table" id="tabla-stock-bajo">
        <thead>
        <tr>
            <th class="table__th">Código</th>
            <th class="table__th">Producto</th>
            <th class="table__th">Categoría</th>
            <th class="table__th table__th--num">Stock</th>
            <th class="table__th table__th--num">Mínimo</th>
            <th class="table__th table__th--num">Faltante</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$productos): ?>
            <tr><td colspan="6" class="table__empty">Sin productos en estado crítico. ✓</td></tr>
        <?php endif; ?>
        <?php foreach ($productos as $p):
            $faltante = max(0, (int) $p['stock_minimo'] - (int) $p['stock_actual']); ?>
            <tr class="table__row is-critical">
                <td class="table__td mono"><?= $h($p['codigo']) ?></td>
                <td class="table__td"><strong><?= $h($p['nombre']) ?></strong></td>
                <td class="table__td"><?= $h($p['categoria_nombre'] ?? '—') ?></td>
                <td class="table__td table__td--num mono"><?= (int) $p['stock_actual'] ?></td>
                <td class="table__td table__td--num mono"><?= (int) $p['stock_minimo'] ?></td>
                <td class="table__td table__td--num mono"><strong><?= $faltante ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
