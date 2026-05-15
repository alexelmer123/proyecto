<?php
/** @var int   $usuarioId */
/** @var array $form */ /** @var array $errores */
/** @var array $roles */ /** @var array $todosLosPermisos */
/** @var array $permisosDelRol */ /** @var array $permisosExtra */
/** @var bool  $esEdicion */
$action      = BASE_URL . '/usuario/editar/' . (int) $usuarioId;
$submitLabel = 'Guardar cambios';
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Seguridad · Usuarios</p>
        <h1 class="page-head__title"><?= $h($form['nombre'] ?? 'Editar usuario') ?></h1>
        <p class="page-head__caption mono"><?= $h($form['email'] ?? '') ?></p>
    </div>
    <a href="<?= BASE_URL ?>/usuario/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell form-shell--lg">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
