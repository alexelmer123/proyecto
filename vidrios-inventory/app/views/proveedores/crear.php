<?php
/** @var array $form */ /** @var array $errores */ /** @var array $paises */
$action      = BASE_URL . '/proveedor/crear';
$submitLabel = 'Crear proveedor';
$departamentos = [];
$provincias    = [];
$distritos     = [];
$ciudades      = [];
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Suministro</p>
        <h1 class="page-head__title">Nuevo proveedor</h1>
    </div>
    <a href="<?= BASE_URL ?>/proveedor/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell form-shell--lg">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
