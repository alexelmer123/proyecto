<?php
/** @var array $form */ /** @var array $errores */
/** @var array $todosLosPermisos */ /** @var array $permisosSeleccionados */
/** @var bool  $esEdicion */
$action      = BASE_URL . '/rol/crear';
$submitLabel = 'Crear rol';
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Seguridad · Roles</p>
        <h1 class="page-head__title">Nuevo rol</h1>
        <p class="page-head__caption">Define un grupo de permisos reutilizable para los usuarios.</p>
    </div>
    <a href="<?= BASE_URL ?>/rol/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell form-shell--lg">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
