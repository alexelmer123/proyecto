<?php
/** @var array $roles */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Seguridad</p>
        <h1 class="page-head__title">Roles y permisos</h1>
        <p class="page-head__caption">Cada rol agrupa un conjunto de permisos. Asígnalos a tus usuarios.</p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/usuario/index" class="btn btn--ghost">
            <?= icon('users', 16) ?>
            <span>Usuarios</span>
        </a>
        <a href="<?= BASE_URL ?>/rol/crear" class="btn btn--primary">
            <?= icon('plus', 16) ?>
            <span>Nuevo rol</span>
        </a>
    </div>
</header>

<?php if (!$roles): ?>
    <p class="catalog-empty">Aún no hay roles creados.</p>
<?php else: ?>
    <section class="cards-grid">
        <?php foreach ($roles as $r): ?>
            <article class="card card--cat<?= (int) $r['activo'] === 0 ? ' is-disabled' : '' ?>">
                <header class="card__head">
                    <h3 class="card__title"><?= $h(ucfirst((string) $r['nombre'])) ?></h3>
                    <span class="badge badge--neutral mono"><?= (int) $r['total_permisos'] ?> permisos</span>
                </header>
                <p class="card__desc"><?= $h($r['descripcion'] ?: 'Sin descripción.') ?></p>

                <?php if ($r['permisos_grupos']): ?>
                    <div class="permisos-grupos">
                        <?php foreach ($r['permisos_grupos'] as $modulo => $codigos): ?>
                            <div class="permisos-grupo">
                                <span class="permisos-grupo__modulo"><?= $h($modulo) ?></span>
                                <div class="permisos-grupo__chips">
                                    <?php foreach ($codigos as $c): ?>
                                        <span class="chip mono"><?= $h($c) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state empty-state--inline">Este rol no tiene permisos asignados.</p>
                <?php endif; ?>

                <footer class="card__foot">
                    <a href="<?= BASE_URL ?>/rol/editar/<?= (int) $r['id'] ?>" class="btn btn--ghost btn--xs">
                        <?= icon('edit', 14) ?> <span>Editar</span>
                    </a>
                    <a href="<?= BASE_URL ?>/rol/eliminar/<?= (int) $r['id'] ?>"
                       class="btn btn--ghost btn--xs"
                       data-confirm="¿Archivar el rol «<?= $h($r['nombre']) ?>»?">
                        <?= icon('archive', 14) ?> <span>Archivar</span>
                    </a>
                </footer>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
