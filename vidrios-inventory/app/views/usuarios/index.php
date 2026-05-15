<?php
/** @var array $usuarios */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Seguridad</p>
        <h1 class="page-head__title">Usuarios del sistema</h1>
        <p class="page-head__caption"><?= count($usuarios) ?> cuentas activas.</p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/rol/index" class="btn btn--ghost">
            <?= icon('shield', 16) ?>
            <span>Roles</span>
        </a>
        <a href="<?= BASE_URL ?>/usuario/crear" class="btn btn--primary">
            <?= icon('plus', 16) ?>
            <span>Nuevo usuario</span>
        </a>
    </div>
</header>

<?php if (!$usuarios): ?>
    <p class="catalog-empty">No hay usuarios registrados todavía.</p>
<?php else: ?>
    <div class="table-shell">
        <table class="table">
            <thead>
                <tr>
                    <th class="table__th">Usuario</th>
                    <th class="table__th">Email</th>
                    <th class="table__th">Rol</th>
                    <th class="table__th">Último acceso</th>
                    <th class="table__th table__th--actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u):
                    $extras    = (int) ($u['extras_count'] ?? 0);
                    $rolNombre = (string) ($u['rol_nombre'] ?? $u['rol'] ?? '');
                ?>
                    <tr class="table__row">
                        <td class="table__td"><strong><?= $h($u['nombre']) ?></strong></td>
                        <td class="table__td mono"><?= $h($u['email']) ?></td>
                        <td class="table__td">
                            <?php if ($extras > 0): ?>
                                <span class="permisos-badge permisos-badge--custom" title="<?= $extras ?> permiso(s) extra sobre el rol «<?= $h($rolNombre) ?>»">
                                    Personalizado
                                    <small class="permisos-badge__extras">+<?= $extras ?></small>
                                </span>
                            <?php elseif ($rolNombre !== ''): ?>
                                <span class="permisos-badge">
                                    Rol: <strong><?= $h(ucfirst($rolNombre)) ?></strong>
                                </span>
                            <?php else: ?>
                                <span class="permisos-badge permisos-badge--none">Sin rol</span>
                            <?php endif; ?>
                        </td>
                        <td class="table__td mono">
                            <?= $u['ultimo_acceso'] ? $h((string) $u['ultimo_acceso']) : '—' ?>
                        </td>
                        <td class="table__td table__td--actions">
                            <a class="iconbtn" href="<?= BASE_URL ?>/usuario/editar/<?= (int) $u['id'] ?>" title="Editar">
                                <?= icon('edit', 16) ?>
                            </a>
                            <a class="iconbtn iconbtn--danger" href="<?= BASE_URL ?>/usuario/eliminar/<?= (int) $u['id'] ?>"
                               data-confirm="¿Archivar al usuario «<?= $h($u['nombre']) ?>»?"
                               title="Archivar">
                                <?= icon('archive', 16) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
