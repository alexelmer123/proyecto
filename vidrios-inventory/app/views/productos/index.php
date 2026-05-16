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
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/producto/exportar?q=<?= urlencode($q) ?>&categoria=<?= (int) $categoriaId ?>" class="btn btn--ghost">
            <?= icon('download', 16) ?>
            <span>Exportar CSV</span>
        </a>
        <button type="button"
                class="btn btn--primary"
                data-modal-src="<?= BASE_URL ?>/producto/crear"
                data-modal-title="Nuevo producto"
                data-modal-kicker="Catálogo"
                data-modal-caption="Define la pieza, sus dimensiones y los umbrales de stock."
                data-modal-size="lg">
            <?= icon('plus', 16) ?>
            <span>Nuevo producto</span>
        </button>
    </div>
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
        <?php foreach ($productos as $p): ?>
            <?php require __DIR__ . '/_card.php'; ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?= $paginator->render(BASE_URL . '/producto/index', $extrasUrl) ?>
