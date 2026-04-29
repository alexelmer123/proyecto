<?php
/** @var array $proveedores */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Suministro</p>
        <h1 class="page-head__title">Proveedores</h1>
        <p class="page-head__caption"><?= count($proveedores) ?> contactos registrados.</p>
    </div>
    <div class="page-head__actions">
        <a href="<?= BASE_URL ?>/proveedor/exportar" class="btn btn--ghost">↓ Exportar CSV</a>
        <a href="<?= BASE_URL ?>/proveedor/crear" class="btn btn--primary">+ Nuevo proveedor</a>
    </div>
</header>

<section class="table-shell">
    <table class="table" id="tabla-proveedores">
        <thead>
        <tr>
            <th class="table__th">Nombre</th>
            <th class="table__th">Provee</th>
            <th class="table__th">Ubicación</th>
            <th class="table__th">Contacto</th>
            <th class="table__th">Teléfono</th>
            <th class="table__th">Estado</th>
            <th class="table__th table__th--actions">Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$proveedores): ?>
            <tr><td colspan="7" class="table__empty">Aún no hay proveedores.</td></tr>
        <?php endif; ?>
        <?php foreach ($proveedores as $p):
            $ubic = trim(($p['ciudad_nombre'] ?? '') . (!empty($p['pais_nombre']) ? ', ' . $p['pais_nombre'] : ''), ', ');
        ?>
            <tr class="table__row<?= ((int)$p['estado'] === 0 ? ' is-disabled' : '') ?>">
                <td class="table__td">
                    <strong><?= $h($p['nombre']) ?></strong>
                    <?php if (!empty($p['email'])): ?>
                        <span class="cell-product__sub mono"><?= $h($p['email']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="table__td">
                    <?php if (!empty($p['descripcion_productos'])): ?>
                        <span class="cell-multiline"><?= $h(mb_strimwidth((string) $p['descripcion_productos'], 0, 90, '…', 'UTF-8')) ?></span>
                    <?php else: ?>
                        <small class="muted">—</small>
                    <?php endif; ?>
                </td>
                <td class="table__td"><?= $ubic !== '' ? $h($ubic) : '<small class="muted">—</small>' ?></td>
                <td class="table__td"><?= $h($p['contacto']) ?></td>
                <td class="table__td mono"><?= $h($p['telefono']) ?></td>
                <td class="table__td">
                    <span class="badge <?= (int)$p['estado'] === 1 ? 'badge--ok' : 'badge--neutral' ?>">
                        <?= (int)$p['estado'] === 1 ? 'Activo' : 'Archivado' ?>
                    </span>
                </td>
                <td class="table__td table__td--actions">
                    <a class="iconbtn" href="<?= BASE_URL ?>/proveedor/editar/<?= (int) $p['id'] ?>" title="Editar">✎</a>
                    <a class="iconbtn iconbtn--danger" href="<?= BASE_URL ?>/proveedor/eliminar/<?= (int) $p['id'] ?>"
                       data-confirm="¿Archivar al proveedor «<?= $h($p['nombre']) ?>»?"
                       title="Archivar">×</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
