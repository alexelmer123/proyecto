<?php
/** @var array $form */ /** @var array $errores */
/** @var array $paises */ /** @var array $departamentos */
/** @var array $provincias */ /** @var array $distritos */ /** @var array $ciudades */
$id          = (int) ($form['id'] ?? 0);
$action      = BASE_URL . '/proveedor/editar/' . $id;
$submitLabel = 'Guardar cambios';
$h           = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Suministro · Edición</p>
        <h1 class="page-head__title"><?= $h($form['nombre'] ?? '') ?></h1>
    </div>
    <a href="<?= BASE_URL ?>/proveedor/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell form-shell--lg">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
