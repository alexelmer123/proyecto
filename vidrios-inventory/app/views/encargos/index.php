<?php
/** @var array $encargos */ /** @var string $estado */
/** @var Paginator $paginator */ /** @var array $extrasUrl */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$estadoLabels = ['pendiente' => 'Pendiente', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Salidas · Encargos</p>
        <h1 class="page-head__title">Encargos</h1>
        <p class="page-head__caption"><?= (int) $paginator->total ?> encargos registrados.</p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/encargo/crear" class="btn btn--primary">
            <?= icon('plus', 16) ?>
            <span>Nuevo encargo</span>
        </a>
    </div>
</header>

<form method="get" action="<?= BASE_URL ?>/encargo/index" class="filters">
    <label class="field">
        <span class="field__label">Estado</span>
        <select name="estado" class="field__input">
            <option value="">Todos</option>
            <?php foreach ($estadoLabels as $v => $lbl): ?>
                <option value="<?= $v ?>" <?= $estado === $v ? 'selected' : '' ?>><?= $h($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="btn btn--ghost">Filtrar</button>
</form>

<?php if (!$encargos): ?>
    <p class="catalog-empty">No hay encargos para mostrar.</p>
<?php else: ?>
    <section class="encargos-list">
        <?php foreach ($encargos as $e):
            $estadoActual = (string) $e['estado'];
            $totalValor   = (float) ($e['total_valor'] ?? 0);
        ?>
            <article class="encargo-card encargo-card--<?= $h($estadoActual) ?>">
                <header class="encargo-card__head">
                    <div class="encargo-card__meta">
                        <span class="encargo-card__code mono"><?= $h($e['codigo']) ?></span>
                        <span class="mov mov--<?= $h($estadoActual) ?>"><?= $h($estadoLabels[$estadoActual] ?? $estadoActual) ?></span>
                    </div>
                    <h3 class="encargo-card__cliente"><?= $h($e['cliente']) ?></h3>
                    <div class="encargo-card__sub">
                        <?php if (!empty($e['telefono'])): ?>
                            <span class="encargo-card__sub-item">
                                <?= icon('phone', 14) ?>
                                <span class="mono"><?= $h($e['telefono']) ?></span>
                            </span>
                        <?php endif; ?>
                        <span class="encargo-card__sub-item">
                            <?= icon('calendar', 14, 'icon--warn') ?>
                            <span class="mono"><?= $e['fecha_entrega'] ? $h((string) $e['fecha_entrega']) : 'sin fecha' ?></span>
                        </span>
                        <?php if (!empty($e['lugar_entrega'])): ?>
                            <span class="encargo-card__sub-item">
                                <?= icon('pin', 14) ?>
                                <span><?= $h($e['lugar_entrega']) ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="encargo-card__items">
                    <?php foreach ($e['items'] as $it): ?>
                        <div class="encargo-card__item">
                            <span class="mono"><?= (int) $it['cantidad'] ?> <?= $h($it['producto_unidad'] ?? 'u') ?></span>
                            <span class="encargo-card__item-name"><?= $h($it['producto_nombre']) ?></span>
                            <span class="mono encargo-card__item-code"><?= $h($it['producto_codigo']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <footer class="encargo-card__foot">
                    <div class="encargo-card__totals">
                        <span class="encargo-card__items-count">
                            <?= (int) $e['items_count'] ?> producto(s) · <?= (int) $e['total_unidades'] ?> unidades
                        </span>
                        <?php if ($totalValor > 0): ?>
                            <span class="encargo-card__valor mono">S/. <?= number_format($totalValor, 2, '.', ',') ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= BASE_URL ?>/encargo/detalle/<?= (int) $e['id'] ?>" class="btn btn--ghost btn--xs">
                        Ver detalle →
                    </a>
                </footer>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?= $paginator->render(BASE_URL . '/encargo/index', $extrasUrl) ?>
