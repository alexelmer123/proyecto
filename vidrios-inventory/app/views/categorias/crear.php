<?php
/** @var array $form */ /** @var array $errores */
$action      = BASE_URL . '/categoria/crear';
$submitLabel = 'Crear categoría';
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Clasificación</p>
        <h1 class="page-head__title">Nueva categoría</h1>
    </div>
    <a href="<?= BASE_URL ?>/categoria/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
