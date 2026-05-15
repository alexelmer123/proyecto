<?php
/** @var array $form */ /** @var array $errores */
/** @var array $categorias */ /** @var array $proveedores */
$id          = (int) ($form['id'] ?? 0);
$action      = BASE_URL . '/producto/editar/' . $id;
$submitLabel = 'Guardar cambios';
$esEdicion   = true;
$h           = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Catálogo · Edición</p>
        <h1 class="page-head__title"><?= $h($form['nombre'] ?? 'Editar producto') ?></h1>
        <p class="page-head__caption mono">Código <?= $h($form['codigo'] ?? '') ?></p>
    </div>
    <a href="<?= BASE_URL ?>/producto/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell form-shell--lg">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
