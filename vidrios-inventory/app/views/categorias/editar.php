<?php
/** @var array $form */ /** @var array $errores */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';
$id = (int) ($form['id'] ?? 0);
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Clasificación · Edición</p>
        <h1 class="page-head__title"><?= $h($form['nombre'] ?? '') ?></h1>
    </div>
    <a href="<?= BASE_URL ?>/categoria/index" class="btn btn--ghost">← Volver</a>
</header>

<form method="post" action="<?= BASE_URL ?>/categoria/editar/<?= $id ?>" class="form form--card form--narrow">
    <label class="field">
        <span class="field__label">Nombre *</span>
        <input class="field__input" name="nombre" value="<?= $h($form['nombre'] ?? '') ?>" required autofocus>
        <?= $err('nombre') ?>
    </label>
    <label class="field">
        <span class="field__label">Descripción</span>
        <textarea class="field__input" name="descripcion" rows="3"><?= $h($form['descripcion'] ?? '') ?></textarea>
    </label>
    <div class="form__actions">
        <a href="<?= BASE_URL ?>/categoria/index" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--primary">Guardar</button>
    </div>
</form>
