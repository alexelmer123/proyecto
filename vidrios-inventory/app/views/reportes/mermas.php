<?php
/** @var array  $agrupado */
/** @var array  $detalle */
/** @var array  $totales */
/** @var string $vista */
/** @var string $desde */
/** @var string $hasta */
/** @var string $motivo */
$h     = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$money = static fn(float $v): string  => 'S/. ' . number_format($v, 0, ',', '.');

$baseQs = static function (array $extra) use ($desde, $hasta, $motivo): string {
    $qs = http_build_query(array_filter(array_merge([
        'desde'  => $desde,
        'hasta'  => $hasta,
        'motivo' => $motivo,
    ], $extra)));
    return $qs !== '' ? '?' . $qs : '';
};

$labelMotivo = ['merma' => 'Merma', 'accidente' => 'Accidente / retazo'];
$exportQs = $baseQs(['vista' => $vista]);
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Reporte</p>
        <h1 class="page-head__title">Mermas y accidentes</h1>
        <p class="page-head__caption">
            Salidas adicionales con motivo merma o accidente (descuentan stock).
            <?php if ($vista === 'agrupado'): ?>
                <?= count($agrupado) ?> productos afectados.
            <?php else: ?>
                <?= count($detalle) ?> eventos en el rango.
            <?php endif; ?>
            <br><small>
                Los <strong>retazos aprovechables</strong> (que no descuentan stock) viven en
                <a href="<?= BASE_URL ?>/retazo/index">Inventario · Retazos</a>.
            </small>
        </p>
    </div>
    <a href="<?= BASE_URL ?>/reporte/exportarMermas<?= $h($exportQs) ?>" class="btn btn--ghost">↓ Exportar CSV</a>
</header>

<form method="get" action="<?= BASE_URL ?>/reporte/mermas" class="filters">
    <input type="hidden" name="vista" value="<?= $h($vista) ?>">
    <label class="field">
        <span class="field__label">Desde</span>
        <input type="date" name="desde" value="<?= $h($desde) ?>" class="field__input mono">
    </label>
    <label class="field">
        <span class="field__label">Hasta</span>
        <input type="date" name="hasta" value="<?= $h($hasta) ?>" class="field__input mono">
    </label>
    <label class="field">
        <span class="field__label">Motivo</span>
        <select name="motivo" class="field__input">
            <option value="">Todos</option>
            <?php foreach ($labelMotivo as $v => $lbl): ?>
                <option value="<?= $h($v) ?>" <?= $motivo === $v ? 'selected' : '' ?>><?= $h($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="btn btn--ghost">Aplicar</button>
</form>

<section class="metrics-grid" style="margin-top: var(--sp-3);">
    <article class="metric metric--alert">
        <span class="metric__label">Unidades perdidas</span>
        <span class="metric__value mono"><?= $h(fmt_cantidad($totales['total_perdido'])) ?></span>
        <span class="metric__sub"><?= (int) $totales['eventos'] ?> eventos</span>
    </article>
    <article class="metric">
        <span class="metric__label">Por motivo</span>
        <span class="metric__value mono" style="font-size: var(--fs-md);">
            Merma <?= $h(fmt_cantidad($totales['total_merma'])) ?>
            · Accidente <?= $h(fmt_cantidad($totales['total_accidente'])) ?>
        </span>
    </article>
</section>

<nav class="tabs" style="margin-top: var(--sp-3);">
    <a class="tabs__tab<?= $vista === 'agrupado' ? ' is-active' : '' ?>"
       href="<?= BASE_URL ?>/reporte/mermas<?= $h($baseQs(['vista' => 'agrupado'])) ?>">
        Agrupado por producto
    </a>
    <a class="tabs__tab<?= $vista === 'detalle' ? ' is-active' : '' ?>"
       href="<?= BASE_URL ?>/reporte/mermas<?= $h($baseQs(['vista' => 'detalle'])) ?>">
        Detalle cronológico
    </a>
</nav>

<?php if ($vista === 'agrupado'): ?>
    <section class="table-shell">
        <table class="table" id="tabla-mermas-agrupado">
            <thead>
            <tr>
                <th class="table__th">Código</th>
                <th class="table__th">Producto</th>
                <th class="table__th">Categoría</th>
                <th class="table__th table__th--num">Merma</th>
                <th class="table__th table__th--num">Accidente</th>
                <th class="table__th table__th--num">Total perdido</th>
                <th class="table__th table__th--num">Eventos</th>
                <th class="table__th">Último</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$agrupado): ?>
                <tr><td colspan="8" class="table__empty">Sin mermas registradas en el rango.</td></tr>
            <?php endif; ?>
            <?php foreach ($agrupado as $r): ?>
                <tr class="table__row">
                    <td class="table__td mono"><?= $h($r['codigo']) ?></td>
                    <td class="table__td"><strong><?= $h($r['nombre']) ?></strong></td>
                    <td class="table__td"><?= $h($r['categoria_nombre'] ?? '—') ?></td>
                    <td class="table__td table__td--num mono"><?= $h(fmt_cantidad($r['total_merma'])) ?></td>
                    <td class="table__td table__td--num mono"><?= $h(fmt_cantidad($r['total_accidente'])) ?></td>
                    <td class="table__td table__td--num mono"><strong><?= $h(fmt_cantidad($r['total_perdido'])) ?></strong></td>
                    <td class="table__td table__td--num mono"><?= (int) $r['eventos'] ?></td>
                    <td class="table__td mono"><small><?= $h(substr((string) ($r['ultimo_evento'] ?? ''), 0, 10)) ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php else: ?>
    <section class="table-shell">
        <table class="table" id="tabla-mermas-detalle">
            <thead>
            <tr>
                <th class="table__th">Fecha</th>
                <th class="table__th">Producto</th>
                <th class="table__th">Motivo</th>
                <th class="table__th table__th--num">Cantidad</th>
                <th class="table__th">Usuario</th>
                <th class="table__th">Observación</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$detalle): ?>
                <tr><td colspan="6" class="table__empty">Sin eventos en el rango.</td></tr>
            <?php endif; ?>
            <?php foreach ($detalle as $r): ?>
                <tr class="table__row">
                    <td class="table__td mono"><small><?= $h($r['created_at']) ?></small></td>
                    <td class="table__td">
                        <strong><?= $h($r['producto_nombre']) ?></strong>
                        <span class="cell-product__sub mono"><?= $h($r['producto_codigo']) ?></span>
                    </td>
                    <td class="table__td">
                        <span class="badge badge--<?= $r['motivo'] === 'merma' ? 'alert' : 'warn' ?>">
                            <?= $h($labelMotivo[$r['motivo']] ?? $r['motivo']) ?>
                        </span>
                    </td>
                    <td class="table__td table__td--num mono"><strong><?= $h(fmt_cantidad($r['cantidad'])) ?></strong></td>
                    <td class="table__td"><?= $h($r['usuario_nombre'] ?? '—') ?></td>
                    <td class="table__td"><small><?= $h($r['observacion'] ?? '') ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>
