<?php
/** @var array $categorias */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Clasificación</p>
        <h1 class="page-head__title">Categorías</h1>
        <p class="page-head__caption">Familias y sub-tipos de cristal.</p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/categoria/exportar" class="btn btn--ghost">↓ Exportar CSV</a>
        <a href="<?= BASE_URL ?>/categoria/crear" class="btn btn--primary">+ Nueva categoría</a>
    </div>
</header>

<section class="cards-grid">
    <?php if (!$categorias): ?>
        <div class="empty-state">Aún no hay categorías. Crea la primera.</div>
    <?php endif; ?>

    <?php foreach ($categorias as $c): ?>
        <article class="card card--cat<?= ((int)$c['estado'] === 0 ? ' is-disabled' : '') ?>">
            <header class="card__head">
                <h3 class="card__title"><?= $h($c['nombre']) ?></h3>
                <span class="badge badge--neutral mono"><?= (int) ($c['total_productos'] ?? 0) ?> ítems</span>
            </header>
            <p class="card__desc"><?= $h($c['descripcion'] ?: 'Sin descripción.') ?></p>
            <footer class="card__foot">
                <a class="btn btn--ghost btn--sm" href="<?= BASE_URL ?>/categoria/editar/<?= (int) $c['id'] ?>">Editar</a>
                <a class="btn btn--danger btn--sm" href="<?= BASE_URL ?>/categoria/eliminar/<?= (int) $c['id'] ?>"
                   data-confirm="¿Desactivar la categoría «<?= $h($c['nombre']) ?>»?">Archivar</a>
            </footer>
        </article>
    <?php endforeach; ?>
</section>
