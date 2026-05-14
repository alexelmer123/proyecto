<?php
/** @var array $roles */
/** @var array $todosLosPermisos */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Seguridad</p>
        <h1 class="page-head__title">Roles y permisos</h1>
        <p class="page-head__caption">Cada rol agrupa un conjunto de permisos. Solo lectura.</p>
    </div>
</header>

<section class="cards-grid">
    <?php foreach ($roles as $r): ?>
        <article class="card card--cat">
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
        </article>
    <?php endforeach; ?>
</section>

<section class="card card--panel" style="margin-top:2rem;">
    <header class="card__head">
        <h3 class="card__title">Catálogo completo de permisos</h3>
        <span class="card__sub">Lista de los <?= count($todosLosPermisos) ?> códigos disponibles en el sistema.</span>
    </header>
    <table class="table table--compact">
        <thead>
        <tr>
            <th class="table__th">Módulo</th>
            <th class="table__th">Código</th>
            <th class="table__th">Nombre</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($todosLosPermisos as $p): ?>
            <tr>
                <td class="table__td mono"><?= $h($p['modulo']) ?></td>
                <td class="table__td mono"><?= $h($p['codigo']) ?></td>
                <td class="table__td"><?= $h($p['nombre']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
