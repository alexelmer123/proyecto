<?php
/** @var array $datos */ /** @var array $totales */
/** @var string $agrupacion */ /** @var string $desde */ /** @var string $hasta */
$h     = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$money = static fn(float $n): string  => 'S/. ' . number_format($n, 0, ',', '.');
$labels = ['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes'];
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Reporte</p>
        <h1 class="page-head__title">Ventas por período</h1>
        <p class="page-head__caption">
            Salidas de stock como ventas · agrupadas por <?= $h($labels[$agrupacion]) ?> ·
            del <?= $h($desde) ?> al <?= $h($hasta) ?>
        </p>
    </div>
    <a href="<?= BASE_URL ?>/reporte/exportarVentas?agrupacion=<?= urlencode($agrupacion) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>" class="btn btn--ghost">↓ Exportar CSV</a>
</header>

<form method="get" action="<?= BASE_URL ?>/reporte/ventas" class="filters">
    <label class="field">
        <span class="field__label">Agrupar por</span>
        <select name="agrupacion" class="field__input">
            <?php foreach ($labels as $v => $lbl): ?>
                <option value="<?= $h($v) ?>" <?= $agrupacion === $v ? 'selected' : '' ?>><?= $h($lbl) ?></option>
            <?php endforeach; ?>
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
    <button type="submit" class="btn btn--ghost">Aplicar</button>
</form>

<section class="metrics-grid" style="margin-top: var(--sp-3);">
    <article class="metric metric--main">
        <span class="metric__label">Ingreso total</span>
        <span class="metric__value mono"><?= $h($money((float) $totales['ingreso'])) ?></span>
        <span class="metric__sub"><?= (int) $totales['unidades'] ?> unidades · <?= (int) $totales['num_ventas'] ?> ventas</span>
    </article>
    <article class="metric">
        <span class="metric__label">Costo</span>
        <span class="metric__value mono"><?= $h($money((float) $totales['costo'])) ?></span>
    </article>
    <article class="metric metric--ok">
        <span class="metric__label">Utilidad</span>
        <span class="metric__value mono"><?= $h($money((float) $totales['utilidad'])) ?></span>
    </article>
</section>

<section class="table-shell">
    <table class="table" id="tabla-ventas-periodo">
        <thead>
        <tr>
            <th class="table__th">Período</th>
            <th class="table__th table__th--num">Ventas</th>
            <th class="table__th table__th--num">Unidades</th>
            <th class="table__th table__th--num">Ingreso</th>
            <th class="table__th table__th--num">Costo</th>
            <th class="table__th table__th--num">Utilidad</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$datos): ?>
            <tr><td colspan="6" class="table__empty">Sin ventas en el rango indicado.</td></tr>
        <?php endif; ?>
        <?php foreach ($datos as $r): ?>
            <tr class="table__row">
                <td class="table__td mono"><?= $h($r['periodo']) ?></td>
                <td class="table__td table__td--num mono"><?= (int) $r['num_ventas'] ?></td>
                <td class="table__td table__td--num mono"><?= (int) $r['unidades'] ?></td>
                <td class="table__td table__td--num mono"><?= $h($money((float) $r['ingreso'])) ?></td>
                <td class="table__td table__td--num mono"><?= $h($money((float) $r['costo'])) ?></td>
                <td class="table__td table__td--num mono">
                    <strong class="<?= (float) $r['utilidad'] >= 0 ? 'text-ok' : 'text-rose' ?>">
                        <?= $h($money((float) $r['utilidad'])) ?>
                    </strong>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
