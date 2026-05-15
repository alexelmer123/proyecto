<?php
/** @var array $form */ /** @var array $errores */
/** @var array $roles */ /** @var array $todosLosPermisos */
/** @var array $permisosDelRol */ /** @var array $permisosExtra */
/** @var bool  $esEdicion */
$action      = BASE_URL . '/usuario/crear';
$submitLabel = 'Crear usuario';
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Seguridad · Usuarios</p>
        <h1 class="page-head__title">Nuevo usuario</h1>
        <p class="page-head__caption">Asigna un rol y, si necesitas, permisos extra individuales.</p>
    </div>
    <a href="<?= BASE_URL ?>/usuario/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell form-shell--lg">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
