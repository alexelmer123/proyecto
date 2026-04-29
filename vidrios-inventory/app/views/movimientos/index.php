<?php
/** @var array $movimientos */ /** @var array $productos */ /** @var array $filtro */
/** @var Paginator $paginator */ /** @var array $extrasUrl */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Bitácora</p>
        <h1 class="page-head__title">Historial de movimientos</h1>
        <p class="page-head__caption"><?= (int) $paginator->total ?> movimientos en total (paginados de a <?= (int) $paginator->perPage ?>).</p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/movimiento/registrarEntrada" class="btn btn--primary">+ Entrada</a>
        <a href="<?= BASE_URL ?>/movimiento/registrarSalida"  class="btn btn--ghost">− Salida</a>
    </div>
</header>

<form method="get" action="<?= BASE_URL ?>/movimiento/historial" class="filters">
    <label class="field">
        <span class="field__label">Producto</span>
        <select name="producto" class="field__input">
            <option value="">Todos</option>
            <?php foreach ($productos as $p): ?>
                <option value="<?= (int) $p['id'] ?>"
                    <?= ((int) ($filtro['productoId'] ?? 0)) === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= $h($p['codigo'] . ' · ' . $p['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span class="field__label">Tipo</span>
        <select name="tipo" class="field__input">
            <?php foreach (['' => 'Todos', 'entrada' => 'Entradas', 'salida' => 'Salidas', 'ajuste' => 'Ajustes'] as $v => $lbl): ?>
                <option value="<?= $v ?>" <?= ($filtro['tipo'] ?? '') === $v ? 'selected' : '' ?>><?= $h($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span class="field__label">Desde</span>
        <input type="date" name="desde" value="<?= $h($filtro['desde'] ?? '') ?>" class="field__input mono">
    </label>
    <label class="field">
        <span class="field__label">Hasta</span>
        <input type="date" name="hasta" value="<?= $h($filtro['hasta'] ?? '') ?>" class="field__input mono">
    </label>
    <button type="submit" class="btn btn--ghost">Aplicar</button>
</form>

<section class="table-shell">
    <table class="table" id="tabla-movimientos">
        <thead>
        <tr>
            <th class="table__th">Fecha</th>
            <th class="table__th">Producto</th>
            <th class="table__th">Tipo</th>
            <th class="table__th table__th--num">Cant.</th>
            <th class="table__th table__th--num">Anterior → Nuevo</th>
            <th class="table__th">Usuario</th>
            <th class="table__th">Observación</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$movimientos): ?>
            <tr><td colspan="7" class="table__empty">No hay movimientos para el filtro aplicado.</td></tr>
        <?php endif; ?>
        <?php foreach ($movimientos as $m):
            $tipo = (string) $m['tipo'];
            $tipoLabel = ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste'][$tipo] ?? $tipo;
            $cls = 'mov mov--' . $tipo;
        ?>
            <tr class="table__row">
                <td class="table__td mono"><?= $h($m['created_at']) ?></td>
                <td class="table__td">
                    <strong class="mono"><?= $h($m['producto_codigo']) ?></strong>
                    <span class="cell-product__sub"><?= $h($m['producto_nombre']) ?></span>
                </td>
                <td class="table__td"><span class="<?= $cls ?>"><?= $h($tipoLabel) ?></span></td>
                <td class="table__td table__td--num mono">
                    <?= $tipo === 'salida' ? '−' : ($tipo === 'entrada' ? '+' : '=') ?><?= (int) $m['cantidad'] ?>
                </td>
                <td class="table__td table__td--num mono">
                    <?= (int) $m['stock_anterior'] ?> → <strong><?= (int) $m['stock_nuevo'] ?></strong>
                </td>
                <td class="table__td"><?= $h($m['usuario_nombre'] ?? '—') ?></td>
                <td class="table__td"><?= $h($m['observacion'] ?: '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?= $paginator->render(BASE_URL . '/movimiento/historial', $extrasUrl) ?>
