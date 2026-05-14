<?php
/** @var array $datos */ /** @var string $agrupacion */ /** @var string $desde */ /** @var string $hasta */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$max = 0;
foreach ($datos as $d) {
    $max = max($max, (int) $d['total_entradas'], (int) $d['total_salidas']);
}
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Reporte</p>
        <h1 class="page-head__title">Movimientos por período</h1>
        <p class="page-head__caption">Entradas y salidas agrupadas por <?= $agrupacion === 'mes' ? 'mes' : 'día' ?>.</p>
    </div>
    <a href="<?= BASE_URL ?>/reporte/exportarMovimientosPorPeriodo?agrupacion=<?= urlencode($agrupacion) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>" class="btn btn--ghost">↓ Exportar CSV</a>
</header>

<form method="get" action="<?= BASE_URL ?>/reporte/movimientosPorPeriodo" class="filters">
    <label class="field">
        <span class="field__label">Agrupar por</span>
        <select name="agrupacion" class="field__input">
            <option value="dia" <?= $agrupacion === 'dia' ? 'selected' : '' ?>>Día</option>
            <option value="mes" <?= $agrupacion === 'mes' ? 'selected' : '' ?>>Mes</option>
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

<section class="table-shell">
    <table class="table" id="tabla-periodos">
        <thead>
        <tr>
            <th class="table__th">Período</th>
            <th class="table__th table__th--num">Entradas</th>
            <th class="table__th table__th--num">Salidas</th>
            <th class="table__th table__th--num">Movimientos</th>
            <th class="table__th">Distribución</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$datos): ?>
            <tr><td colspan="5" class="table__empty">Sin movimientos en el rango seleccionado.</td></tr>
        <?php endif; ?>
        <?php foreach ($datos as $d):
            $entW = $max > 0 ? round(((int) $d['total_entradas'] / $max) * 100) : 0;
            $salW = $max > 0 ? round(((int) $d['total_salidas']  / $max) * 100) : 0;
        ?>
            <tr class="table__row">
                <td class="table__td mono"><?= $h($d['periodo']) ?></td>
                <td class="table__td table__td--num mono">+<?= (int) $d['total_entradas'] ?></td>
                <td class="table__td table__td--num mono">−<?= (int) $d['total_salidas'] ?></td>
                <td class="table__td table__td--num mono"><?= (int) $d['total_movimientos'] ?></td>
                <td class="table__td">
                    <div class="bar">
                        <span class="bar__seg bar__seg--in"  style="width:<?= $entW ?>%"></span>
                        <span class="bar__seg bar__seg--out" style="width:<?= $salW ?>%"></span>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
