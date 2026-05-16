<?php
/** @var array      $retazos */
/** @var array      $productos */
/** @var array      $filtro */
/** @var Paginator  $paginator */
/** @var array      $extrasUrl */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$productoId = $filtro['productoId'] ?? null;
$origen     = (string) ($filtro['origen'] ?? '');
$estado     = (string) ($filtro['estado'] ?? '');
$desde      = (string) ($filtro['desde']  ?? '');
$hasta      = (string) ($filtro['hasta']  ?? '');

// Formatea las medidas del retazo como "ancho×alto cm" o "longitud cm".
$fmtMedidas = static function (array $r) use ($h): string {
    $valores = [];
    if (!empty($r['ancho']))    $valores[] = rtrim(rtrim(number_format((float) $r['ancho'],    2, '.', ''), '0'), '.');
    if (!empty($r['alto']))     $valores[] = rtrim(rtrim(number_format((float) $r['alto'],     2, '.', ''), '0'), '.');
    if (!empty($r['longitud'])) $valores[] = rtrim(rtrim(number_format((float) $r['longitud'], 2, '.', ''), '0'), '.');
    return $valores === [] ? '—' : $h(implode('×', $valores) . ' cm');
};
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Inventario</p>
        <h1 class="page-head__title">Retazos disponibles</h1>
        <p class="page-head__caption">
            Sobrantes aprovechables generados al vender o entregar encargos.
            Estos retazos <strong>no descuentan stock</strong>; sólo quedan registrados
            para reutilizarlos en cortes futuros.
        </p>
    </div>
    <a href="<?= BASE_URL ?>/movimiento/historial" class="btn btn--ghost">← Historial</a>
</header>

<form method="get" action="<?= BASE_URL ?>/retazo/index" class="filters">
    <label class="field">
        <span class="field__label">Producto</span>
        <select name="producto" class="field__input">
            <option value="">Todos</option>
            <?php foreach ($productos as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $productoId === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= $h($p['codigo'] . ' · ' . $p['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span class="field__label">Origen</span>
        <select name="origen" class="field__input">
            <option value="">Todos</option>
            <option value="salida"  <?= $origen === 'salida'  ? 'selected' : '' ?>>Salida (venta/accidente)</option>
            <option value="encargo" <?= $origen === 'encargo' ? 'selected' : '' ?>>Encargo</option>
        </select>
    </label>
    <label class="field">
        <span class="field__label">Estado</span>
        <select name="estado" class="field__input">
            <option value="">Todos</option>
            <option value="disponible"  <?= $estado === 'disponible'  ? 'selected' : '' ?>>Disponibles</option>
            <option value="aprovechado" <?= $estado === 'aprovechado' ? 'selected' : '' ?>>Aprovechados</option>
        </select>
    </label>
    <label class="field">
        <span class="field__label">Desde</span>
        <input type="date" name="desde" value="<?= $h($desde) ?>" class="field__input mono">
    </label>
    <label class="field">
        <span class="field__label">Hasta</span>
        <input type="date" name="hasta" value="<?= $h($hasta) ?>" class="field__input mono">
    </label>
    <button type="submit" class="btn btn--ghost">Filtrar</button>
</form>

<?php if (!$retazos): ?>
    <p class="catalog-empty">No hay retazos que coincidan con el filtro.</p>
<?php else: ?>
<div class="table-shell">
    <table class="table">
        <thead>
            <tr>
                <th class="table__th">Fecha</th>
                <th class="table__th">Producto</th>
                <th class="table__th">Categoría</th>
                <th class="table__th table__th--num">Cantidad</th>
                <th class="table__th table__th--num">Medidas</th>
                <th class="table__th">Origen</th>
                <th class="table__th">Detalle</th>
                <th class="table__th">Estado</th>
                <th class="table__th">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($retazos as $r):
                $aprov  = (int) $r['aprovechado'] === 1;
            ?>
                <tr class="table__row<?= $aprov ? ' is-muted' : '' ?>" data-entity-id="retazo:<?= (int) $r['id'] ?>">
                    <td class="table__td mono"><?= $h($r['created_at']) ?></td>
                    <td class="table__td">
                        <strong class="mono"><?= $h($r['producto_codigo']) ?></strong>
                        <span class="cell-product__sub"><?= $h($r['producto_nombre']) ?></span>
                    </td>
                    <td class="table__td"><?= $h($r['categoria_nombre'] ?? '—') ?></td>
                    <td class="table__td table__td--num mono">
                        <?= $h(fmt_cantidad($r['cantidad'])) ?> <?= $h($r['producto_unidad'] ?? '') ?>
                    </td>
                    <td class="table__td table__td--num mono"><?= $fmtMedidas($r) ?></td>
                    <td class="table__td">
                        <span class="badge badge--<?= $r['origen'] === 'salida' ? 'info' : 'success' ?>">
                            <?= $h(ucfirst((string) $r['origen'])) ?>
                            <?php if (!empty($r['origen_id'])): ?> #<?= (int) $r['origen_id'] ?><?php endif; ?>
                        </span>
                    </td>
                    <td class="table__td"><small><?= $h($r['observacion'] ?? '') ?></small></td>
                    <td class="table__td">
                        <?php if ($aprov): ?>
                            <span class="badge badge--neutral">Aprovechado</span>
                        <?php else: ?>
                            <span class="badge badge--success">Disponible</span>
                        <?php endif; ?>
                    </td>
                    <td class="table__td">
                        <a class="iconbtn" href="<?= BASE_URL ?>/retazo/aprovechar/<?= (int) $r['id'] ?>"
                           title="<?= $aprov ? 'Volver a disponible' : 'Marcar como aprovechado' ?>">
                            <?= icon($aprov ? 'rotate' : 'check', 16) ?>
                        </a>
                        <a class="iconbtn iconbtn--danger" href="<?= BASE_URL ?>/retazo/eliminar/<?= (int) $r['id'] ?>"
                           data-confirm="¿Eliminar este retazo? No se puede deshacer."
                           title="Eliminar">
                            <?= icon('trash', 16) ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?= $paginator->render(BASE_URL . '/retazo/index', $extrasUrl) ?>
