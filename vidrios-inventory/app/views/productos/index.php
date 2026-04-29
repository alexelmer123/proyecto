<?php
/** @var array $productos */
/** @var array $categorias */
/** @var string $q */
/** @var ?int $categoriaId */
/** @var Paginator $paginator */
/** @var array $extrasUrl */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Catálogo</p>
        <h1 class="page-head__title">Productos en taller</h1>
        <p class="page-head__caption"><?= (int) $paginator->total ?> piezas activas en el inventario.</p>
    </div>
    <a href="<?= BASE_URL ?>/producto/crear" class="btn btn--primary">
        <span>+ Nuevo producto</span>
    </a>
</header>

<form method="get" action="<?= BASE_URL ?>/producto/index" class="filters">
    <label class="field field--search">
        <span class="field__label">Buscar</span>
        <input type="search" name="q" value="<?= $h($q) ?>"
               class="field__input" placeholder="Nombre o código…">
    </label>
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
    <button type="submit" class="btn btn--ghost">Filtrar</button>
</form>

<?php if (!$productos): ?>
    <p class="catalog-empty">Sin productos que coincidan con el filtro.</p>
<?php else: ?>
    <section class="catalog-grid">
        <?php foreach ($productos as $p):
            $bajo = (int) $p['stock_actual'] <= (int) $p['stock_minimo'];
            $detalleUrl = BASE_URL . '/producto/editar/' . (int) $p['id'];
        ?>
            <article class="catalog-card<?= $bajo ? ' is-critical' : '' ?>">
                <a class="catalog-card__cover" href="<?= $detalleUrl ?>" aria-label="Ver detalles de <?= $h($p['nombre']) ?>">
                    <?php if (!empty($p['imagen'])): ?>
                        <img src="<?= $h($p['imagen']) ?>" alt="<?= $h($p['nombre']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="catalog-card__cover-ph" data-color="<?= (int) $p['id'] % 6 ?>">
                            <?= $h(mb_strtoupper(mb_substr((string) $p['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                        </span>
                    <?php endif; ?>
                </a>

                <div class="catalog-card__actions">
                    <a class="iconbtn" href="<?= BASE_URL ?>/producto/ajustarStock/<?= (int) $p['id'] ?>" title="Ajustar stock">⇅</a>
                    <a class="iconbtn" href="<?= BASE_URL ?>/producto/editar/<?= (int) $p['id'] ?>" title="Editar">✎</a>
                    <a class="iconbtn iconbtn--danger" href="<?= BASE_URL ?>/producto/eliminar/<?= (int) $p['id'] ?>"
                       data-confirm="¿Archivar el producto «<?= $h($p['nombre']) ?>»? El stock quedará oculto del catálogo."
                       title="Archivar">×</a>
                </div>

                <div class="catalog-card__body">
                    <p class="catalog-card__code">
                        <span class="catalog-card__code-icon" aria-hidden="true">📌</span>
                        <?= $h($p['codigo']) ?>
                    </p>
                    <h3 class="catalog-card__name"><?= $h($p['nombre']) ?></h3>
                    <p class="catalog-card__price">
                        S/. <?= number_format((float) $p['precio_venta'], 2, '.', ',') ?>
                    </p>
                    <span class="catalog-card__stock <?= $bajo ? 'catalog-card__stock--alert' : '' ?>">
                        <span aria-hidden="true">📦</span> Stock: <?= (int) $p['stock_actual'] ?>
                    </span>
                    <a class="catalog-card__detail" href="<?= $detalleUrl ?>">
                        <span aria-hidden="true">👆</span> Haz clic para ver más detalles
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?= $paginator->render(BASE_URL . '/producto/index', $extrasUrl) ?>
