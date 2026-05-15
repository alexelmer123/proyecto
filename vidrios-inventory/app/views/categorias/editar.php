<?php
/** @var array $form */ /** @var array $errores */
$id          = (int) ($form['id'] ?? 0);
$action      = BASE_URL . '/categoria/editar/' . $id;
$submitLabel = 'Guardar cambios';
$h           = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Clasificación · Edición</p>
        <h1 class="page-head__title"><?= $h($form['nombre'] ?? '') ?></h1>
    </div>
    <a href="<?= BASE_URL ?>/categoria/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell">
    <?php require __DIR__ . '/_form.php'; ?>
</div>
