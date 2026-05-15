<?php
/** @var int   $rolId */
/** @var array $form */ /** @var array $errores */
/** @var array $todosLosPermisos */ /** @var array $permisosSeleccionados */
/** @var bool  $esEdicion */
$action      = BASE_URL . '/rol/editar/' . (int) $rolId;
$submitLabel = 'Guardar cambios';
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Seguridad · Roles</p>
        <h1 class="page-head__title"><?= $h($form['nombre'] ?? 'Editar rol') ?></h1>
        <p class="page-head__caption">Ajusta los permisos del rol; afecta a todos los usuarios asignados.</p>
    </div>
    <a href="<?= BASE_URL ?>/rol/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell form-shell--lg">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
