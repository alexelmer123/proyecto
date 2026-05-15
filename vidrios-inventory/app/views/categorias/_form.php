<?php
/** @var array  $form */ /** @var array $errores */ /** @var string $action */
/** @var string $submitLabel */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';
?>
<form method="post" action="<?= $h($action) ?>" class="form form--modal">
    <label class="field">
        <span class="field__label">Nombre *</span>
        <input class="field__input" name="nombre" value="<?= $h($form['nombre'] ?? '') ?>" required autofocus maxlength="120">
        <?= $err('nombre') ?>
    </label>
    <label class="field">
        <span class="field__label">Descripción</span>
        <textarea class="field__input" name="descripcion" rows="3" maxlength="500"><?= $h($form['descripcion'] ?? '') ?></textarea>
        <small class="field__hint">Opcional — qué tipo de cristal o piezas agrupa esta familia.</small>
    </label>
    <div class="modal__foot modal__foot--inline">
        <button type="button" class="btn btn--ghost" data-modal-cancel>Cancelar</button>
        <button type="submit" class="btn btn--primary"><?= $h($submitLabel ?? 'Guardar') ?></button>
    </div>
</form>
