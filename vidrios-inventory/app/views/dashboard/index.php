<?php
/** @var int   $totalProductos */
/** @var int   $stockBajo */
/** @var float $valorInventario */
/** @var int   $totalCategorias */
/** @var int   $totalProveedores */
/** @var int   $movHoy */
/** @var int   $movSemana */
/** @var int   $movPrevSem */
/** @var float $movTrendPct */
/** @var int   $entradasSemana */
/** @var int   $salidasSemana */
/** @var array $serie14 */
/** @var array $valorPorCategoria */
/** @var array $topProductos */
/** @var array $ultimosMovimientos */
/** @var array $topStockBajo */

$h     = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$money = static fn(float $n): string  => 'S/. ' . number_format($n, 0, ',', '.');

// Saludo según la hora local (America/Bogota)
$hora    = (int) date('H');
$saludo  = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$nombre  = (string) ($_SESSION['usuario']['nombre'] ?? 'invitado');
$primer  = explode(' ', trim($nombre))[0] ?? $nombre;

$mesesEs = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$diaSem  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'][(int) date('w')];
$fechaLarga = ucfirst($diaSem) . ', ' . (int) date('j') . ' de ' . $mesesEs[(int) date('n') - 1] . ' de ' . date('Y');

// ─── Cálculos para el chart de barras (14 días) ──────────────────────────────
$maxBar = 1;
foreach ($serie14 as $d) {
    $maxBar = max($maxBar, $d['entradas'], $d['salidas']);
}
$barChartW = 720;
$barChartH = 220;
$barLeft   = 36;
$barBottom = 30;
$barTop    = 16;
$plotW     = $barChartW - $barLeft - 12;
$plotH     = $barChartH - $barTop - $barBottom;
$slotW     = $plotW / count($serie14);
$barW      = ($slotW - 6) / 2;

// ─── Cálculos para el donut ──────────────────────────────────────────────────
$totalValor = 0.0;
foreach ($valorPorCategoria as $v) {
    $totalValor += (float) $v['valor'];
}
$donutR     = 78;
$donutInner = 50;
$donutCx    = 100;
$donutCy    = 100;
$paletteCat = ['#f5b94a', '#6cb6c0', '#d97a7a', '#b89bd9', '#8fc18f', '#e6a87c', '#7fb1e0', '#c8932e'];

// Helper: arco SVG entre dos ángulos (en radianes), sentido horario
$arcPath = static function (float $cx, float $cy, float $r, float $a1, float $a2): string {
    $x1 = $cx + $r * cos($a1);
    $y1 = $cy + $r * sin($a1);
    $x2 = $cx + $r * cos($a2);
    $y2 = $cy + $r * sin($a2);
    $large = (($a2 - $a1) > M_PI) ? 1 : 0;
    return sprintf('M %f %f A %f %f 0 %d 1 %f %f', $x1, $y1, $r, $r, $large, $x2, $y2);
};

$trendUp     = $movTrendPct > 0;
$trendIcon   = $trendUp ? '▲' : ($movTrendPct < 0 ? '▼' : '·');
$trendClass  = $trendUp ? 'is-up' : ($movTrendPct < 0 ? 'is-down' : 'is-flat');
$trendLabel  = ($movTrendPct === 0.0)
    ? 'Sin cambio respecto a la semana anterior'
    : sprintf('%s%.0f%% vs. semana anterior', $movTrendPct > 0 ? '+' : '', $movTrendPct);
?>

<header class="dash-hero">
    <div class="dash-hero__copy">
        <p class="dash-hero__date"><?= $h($fechaLarga) ?></p>
        <h1 class="dash-hero__title">
            <?= $h($saludo) ?>, <em><?= $h($primer) ?></em>.
        </h1>
        <p class="dash-hero__lead">
            El taller registra <strong><?= (int) $totalProductos ?></strong> piezas activas
            <?= $stockBajo > 0
                ? ' y <strong class="dash-hero__alert">' . (int) $stockBajo . '</strong> con stock crítico.'
                : ' y todo el stock está sobre el mínimo.' ?>
        </p>
    </div>
    <div class="dash-hero__art" aria-hidden="true">
        <svg viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice">
            <defs>
                <linearGradient id="heroFade" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0"   stop-color="#f5b94a" stop-opacity=".35"/>
                    <stop offset="1"   stop-color="#f5b94a" stop-opacity="0"/>
                </linearGradient>
            </defs>
            <g stroke="rgba(245,231,212,.18)" stroke-width=".8" fill="none">
                <path d="M30 30 L150 18 L160 150 L40 168 Z" fill="url(#heroFade)"/>
                <path d="M150 18 L290 36 L300 170 L160 150 Z"/>
                <path d="M30 30 L40 168"/>
                <path d="M150 18 L160 150"/>
                <line x1="40"  y1="168" x2="290" y2="36"  stroke="rgba(245,185,74,.45)"/>
                <line x1="30"  y1="30"  x2="160" y2="150" stroke="rgba(245,185,74,.25)"/>
                <line x1="150" y1="18"  x2="300" y2="170" stroke="rgba(108,182,192,.35)"/>
            </g>
            <g fill="#f5b94a" opacity=".75">
                <circle cx="30"  cy="30"  r="2.5"/>
                <circle cx="150" cy="18"  r="2.5"/>
                <circle cx="290" cy="36"  r="2.5"/>
                <circle cx="40"  cy="168" r="2.5"/>
                <circle cx="160" cy="150" r="2.5"/>
                <circle cx="300" cy="170" r="2.5"/>
            </g>
        </svg>
    </div>
</header>

<section class="metrics-grid">
    <article class="metric metric--main">
        <span class="metric__label">Valor del inventario</span>
        <span class="metric__value mono"><?= $h($money($valorInventario)) ?></span>
        <span class="metric__sub">Suma de stock × precio de compra</span>
    </article>

    <article class="metric">
        <span class="metric__label">Productos activos</span>
        <span class="metric__value mono"><?= (int) $totalProductos ?></span>
        <span class="metric__sub"><?= (int) $totalCategorias ?> categorías · <?= (int) $totalProveedores ?> proveedores</span>
    </article>

    <article class="metric <?= $stockBajo > 0 ? 'metric--alert' : 'metric--ok' ?>">
        <span class="metric__label">Stock crítico</span>
        <span class="metric__value mono"><?= (int) $stockBajo ?></span>
        <?php if ($stockBajo > 0): ?>
            <a class="metric__link" href="<?= BASE_URL ?>/reporte/stockBajo">Ver detalle →</a>
        <?php else: ?>
            <span class="metric__sub">Todo en orden ✓</span>
        <?php endif; ?>
    </article>

    <article class="metric">
        <span class="metric__label">Movimientos hoy</span>
        <span class="metric__value mono"><?= (int) $movHoy ?></span>
        <span class="metric__sub">
            <span class="trend trend--<?= $h($trendClass) ?>">
                <?= $h($trendIcon) ?> <?= $h($trendLabel) ?>
            </span>
        </span>
    </article>
</section>

<section class="dashboard-grid">
    <!-- ─── Barras: movimientos por día (14 días) ─────────────────────── -->
    <div class="card card--panel card--span">
        <header class="card__head card__head--row">
            <div>
                <h3 class="card__title">Movimientos por día</h3>
                <span class="card__sub">Unidades movidas en los últimos 14 días · entradas vs. salidas</span>
            </div>
            <div class="legend">
                <span class="legend__item legend__item--in">Entradas <strong class="mono">+<?= (int) $entradasSemana ?></strong> esta sem.</span>
                <span class="legend__item legend__item--out">Salidas <strong class="mono">−<?= (int) $salidasSemana ?></strong> esta sem.</span>
            </div>
        </header>

        <?php if ($maxBar <= 1): ?>
            <p class="empty-state empty-state--inline">Aún no hay movimientos suficientes para graficar. Registra una entrada o salida para verlo en acción.</p>
        <?php else: ?>
            <div class="chart-wrap">
                <svg class="chart chart--bars" viewBox="0 0 <?= $barChartW ?> <?= $barChartH ?>" preserveAspectRatio="none" role="img" aria-label="Movimientos por día">
                    <!-- Líneas de cuadrícula (4) -->
                    <?php for ($i = 0; $i <= 4; $i++):
                        $y = $barTop + ($plotH * $i / 4);
                        $val = $maxBar * (1 - $i / 4);
                    ?>
                        <line x1="<?= $barLeft ?>" y1="<?= $y ?>" x2="<?= $barChartW - 12 ?>" y2="<?= $y ?>"
                              class="chart__grid"/>
                        <text x="<?= $barLeft - 6 ?>" y="<?= $y + 4 ?>" class="chart__axis-label" text-anchor="end">
                            <?= (int) round($val) ?>
                        </text>
                    <?php endfor; ?>

                    <!-- Barras -->
                    <?php foreach ($serie14 as $i => $d):
                        $cx = $barLeft + $slotW * $i + $slotW / 2;
                        $hE = ($d['entradas'] / $maxBar) * $plotH;
                        $hS = ($d['salidas']  / $maxBar) * $plotH;
                        $yE = $barTop + $plotH - $hE;
                        $yS = $barTop + $plotH - $hS;
                        $xE = $cx - $barW - 1;
                        $xS = $cx + 1;
                        $delay = $i * 35;
                        $diaCorto = (int) date('j', strtotime($d['fecha']));
                        $mesCorto = date('M', strtotime($d['fecha']));
                    ?>
                        <?php if ($d['entradas'] > 0): ?>
                            <rect class="chart__bar chart__bar--in"
                                  x="<?= $xE ?>" y="<?= $yE ?>"
                                  width="<?= $barW ?>" height="<?= $hE ?>"
                                  rx="2"
                                  style="animation-delay: <?= $delay ?>ms; transform-origin: <?= $xE + $barW / 2 ?>px <?= $barTop + $plotH ?>px;">
                                <title>+<?= (int) $d['entradas'] ?> el <?= $h($d['fecha']) ?></title>
                            </rect>
                        <?php endif; ?>
                        <?php if ($d['salidas'] > 0): ?>
                            <rect class="chart__bar chart__bar--out"
                                  x="<?= $xS ?>" y="<?= $yS ?>"
                                  width="<?= $barW ?>" height="<?= $hS ?>"
                                  rx="2"
                                  style="animation-delay: <?= $delay + 60 ?>ms; transform-origin: <?= $xS + $barW / 2 ?>px <?= $barTop + $plotH ?>px;">
                                <title>−<?= (int) $d['salidas'] ?> el <?= $h($d['fecha']) ?></title>
                            </rect>
                        <?php endif; ?>
                        <?php if ($i % 2 === 0 || $i === count($serie14) - 1): ?>
                            <text x="<?= $cx ?>" y="<?= $barChartH - 12 ?>"
                                  class="chart__axis-label" text-anchor="middle">
                                <?= $diaCorto ?>
                            </text>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <!-- Línea base -->
                    <line x1="<?= $barLeft ?>" y1="<?= $barTop + $plotH ?>"
                          x2="<?= $barChartW - 12 ?>" y2="<?= $barTop + $plotH ?>"
                          class="chart__axis"/>
                </svg>
            </div>
        <?php endif; ?>
    </div>

    <!-- ─── Donut: valor por categoría ────────────────────────────────── -->
    <div class="card card--panel">
        <header class="card__head">
            <h3 class="card__title">Valor por categoría</h3>
            <span class="card__sub">Composición del inventario en pesos</span>
        </header>

        <?php if ($totalValor <= 0 || !$valorPorCategoria): ?>
            <p class="empty-state empty-state--inline">Sin datos suficientes para graficar.</p>
        <?php else: ?>
            <div class="donut-wrap">
                <svg class="chart chart--donut" viewBox="0 0 200 200" role="img" aria-label="Valor por categoría">
                    <?php
                    $a = -M_PI / 2; // empieza arriba
                    $segIndex = 0;
                    foreach ($valorPorCategoria as $cat):
                        $pct = (float) $cat['valor'] / $totalValor;
                        if ($pct <= 0) continue;
                        $a2 = $a + $pct * 2 * M_PI;
                        $color = $paletteCat[$segIndex % count($paletteCat)];
                        $delay = $segIndex * 90;
                        // Calcular path como anillo: usamos stroke con dasharray para animar
                        $circ = 2 * M_PI * $donutR;
                        $segLen = $circ * $pct;
                        $offset = $circ * (($a + M_PI / 2) / (2 * M_PI));
                    ?>
                        <circle cx="<?= $donutCx ?>" cy="<?= $donutCy ?>" r="<?= $donutR ?>"
                                fill="none" stroke="<?= $h($color) ?>"
                                stroke-width="<?= $donutR - $donutInner ?>"
                                stroke-dasharray="<?= $segLen ?> <?= $circ ?>"
                                stroke-dashoffset="<?= -$offset ?>"
                                class="chart__donut-seg"
                                style="animation-delay: <?= $delay ?>ms; transform-origin: <?= $donutCx ?>px <?= $donutCy ?>px;">
                            <title><?= $h($cat['categoria']) ?> · <?= $h($money((float) $cat['valor'])) ?> (<?= number_format($pct * 100, 1) ?>%)</title>
                        </circle>
                    <?php
                        $a = $a2;
                        $segIndex++;
                    endforeach;
                    ?>
                    <text x="<?= $donutCx ?>" y="<?= $donutCy - 4 ?>" text-anchor="middle"
                          class="chart__donut-label">Total</text>
                    <text x="<?= $donutCx ?>" y="<?= $donutCy + 14 ?>" text-anchor="middle"
                          class="chart__donut-value mono"><?= $h($money($totalValor)) ?></text>
                </svg>
                <ul class="donut-legend">
                    <?php $segIndex = 0; foreach ($valorPorCategoria as $cat):
                        $pct = $totalValor > 0 ? ((float) $cat['valor'] / $totalValor) * 100 : 0;
                        $color = $paletteCat[$segIndex % count($paletteCat)];
                        $segIndex++;
                    ?>
                        <li class="donut-legend__item">
                            <span class="donut-legend__dot" style="background: <?= $h($color) ?>;"></span>
                            <span class="donut-legend__name"><?= $h($cat['categoria']) ?></span>
                            <span class="donut-legend__pct mono"><?= number_format($pct, 1) ?>%</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <!-- ─── Top productos más movidos ─────────────────────────────────── -->
    <div class="card card--panel">
        <header class="card__head">
            <h3 class="card__title">Top productos · 30 días</h3>
            <span class="card__sub">Más movidos por número de transacciones</span>
        </header>
        <?php if (!$topProductos): ?>
            <p class="empty-state empty-state--inline">Aún no hay actividad registrada en el último mes.</p>
        <?php else: ?>
            <ul class="ranking">
                <?php $rank = 1; $maxUds = max(array_column($topProductos, 'unidades_movidas')); foreach ($topProductos as $tp):
                    $w = $maxUds > 0 ? round(((int) $tp['unidades_movidas'] / $maxUds) * 100) : 0;
                ?>
                    <li class="ranking__item">
                        <span class="ranking__num mono"><?= $rank++ ?></span>
                        <div class="ranking__body">
                            <div class="ranking__head">
                                <strong class="mono"><?= $h($tp['codigo']) ?></strong>
                                <span class="ranking__name"><?= $h($tp['nombre']) ?></span>
                            </div>
                            <div class="ranking__meta">
                                <small><?= $h($tp['categoria_nombre'] ?? '—') ?></small>
                                <small class="mono"><?= (int) $tp['num_movimientos'] ?> mov · <?= (int) $tp['unidades_movidas'] ?> uds</small>
                            </div>
                            <div class="ranking__bar"><span class="ranking__fill" style="width: <?= $w ?>%"></span></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- ─── Stock más crítico ─────────────────────────────────────────── -->
    <div class="card card--panel">
        <header class="card__head">
            <h3 class="card__title">Stock más crítico</h3>
            <span class="card__sub">Top 5 con mayor faltante relativo</span>
        </header>
        <?php if (!$topStockBajo): ?>
            <p class="empty-state empty-state--inline">Sin productos en estado crítico. ✓</p>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach ($topStockBajo as $p):
                    $rel = (int) $p['stock_minimo'] > 0
                        ? max(0.0, min(1.0, (int) $p['stock_actual'] / (int) $p['stock_minimo']))
                        : 0.0;
                    $faltante = max(0, (int) $p['stock_minimo'] - (int) $p['stock_actual']);
                ?>
                    <li class="mini-list__item">
                        <div>
                            <strong class="mono"><?= $h($p['codigo']) ?></strong>
                            <span><?= $h($p['nombre']) ?></span>
                            <small><?= $h($p['categoria_nombre'] ?? '—') ?> · faltan <?= $faltante ?></small>
                            <div class="critical-bar"><span class="critical-bar__fill" style="width: <?= round($rel * 100) ?>%"></span></div>
                        </div>
                        <span class="stock-pill stock-pill--alert mono">
                            <?= (int) $p['stock_actual'] ?> / <?= (int) $p['stock_minimo'] ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- ─── Últimos movimientos ───────────────────────────────────────── -->
    <div class="card card--panel card--span">
        <header class="card__head card__head--row">
            <div>
                <h3 class="card__title">Últimos movimientos</h3>
                <span class="card__sub">Las 5 transacciones más recientes</span>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= BASE_URL ?>/movimiento/historial">Ver historial completo →</a>
        </header>
        <?php if (!$ultimosMovimientos): ?>
            <p class="empty-state empty-state--inline">Aún no hay movimientos registrados.</p>
        <?php else: ?>
            <table class="table table--compact">
                <thead>
                <tr>
                    <th class="table__th">Fecha</th>
                    <th class="table__th">Producto</th>
                    <th class="table__th">Tipo</th>
                    <th class="table__th table__th--num">Cant.</th>
                    <th class="table__th">Usuario</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ultimosMovimientos as $m):
                    $tipo = (string) $m['tipo'];
                    $tipoLabel = ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste'][$tipo] ?? $tipo;
                ?>
                    <tr>
                        <td class="table__td mono"><?= $h($m['created_at']) ?></td>
                        <td class="table__td">
                            <strong class="mono"><?= $h($m['producto_codigo']) ?></strong>
                            <span class="cell-product__sub"><?= $h($m['producto_nombre']) ?></span>
                        </td>
                        <td class="table__td"><span class="mov mov--<?= $h($tipo) ?>"><?= $h($tipoLabel) ?></span></td>
                        <td class="table__td table__td--num mono">
                            <?= $tipo === 'salida' ? '−' : ($tipo === 'entrada' ? '+' : '=') ?><?= (int) $m['cantidad'] ?>
                        </td>
                        <td class="table__td"><?= $h($m['usuario_nombre'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
