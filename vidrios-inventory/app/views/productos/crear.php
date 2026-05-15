<?php
/** @var array $form */ /** @var array $errores */
/** @var array $categorias */ /** @var array $proveedores */
$action      = BASE_URL . '/producto/crear';
$submitLabel = 'Crear producto';
$esEdicion   = false;
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Catálogo</p>
        <h1 class="page-head__title">Nuevo producto</h1>
        <p class="page-head__caption">Define la pieza, sus dimensiones y los umbrales de stock.</p>
    </div>
    <a href="<?= BASE_URL ?>/producto/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell form-shell--lg">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
