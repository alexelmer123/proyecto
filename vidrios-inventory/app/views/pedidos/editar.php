<?php
/** @var array $form */ /** @var array $errores */ /** @var array $proveedores */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';
$id = (int) ($form['id'] ?? 0);
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Compras · Edición</p>
        <h1 class="page-head__title">Pedido <?= $h($form['numero']) ?></h1>
        <p class="page-head__caption">Estado actual: <span class="estado estado--<?= $h((string) $form['estado']) ?>"><?= $h(ucfirst((string) $form['estado'])) ?></span></p>
    </div>
    <a href="<?= BASE_URL ?>/pedido/index" class="btn btn--ghost">← Volver</a>
</header>

<form method="post" action="<?= BASE_URL ?>/pedido/editar/<?= $id ?>" class="form form--card">
    <div class="form__row form__row--2">
        <label class="field">
            <span class="field__label">Proveedor *</span>
            <select class="field__input" name="proveedor_id" required>
                <option value="">— Selecciona —</option>
                <?php foreach ($proveedores as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                        <?= (int) ($form['proveedor_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= $h($p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $err('proveedor_id') ?>
        </label>
        <label class="field">
            <span class="field__label">Fecha del pedido *</span>
            <input class="field__input mono" type="date" name="fecha_pedido"
                   value="<?= $h((string) $form['fecha_pedido']) ?>" required>
            <?= $err('fecha_pedido') ?>
        </label>
    </div>

    <div class="form__row form__row--3">
        <label class="field">
            <span class="field__label">Fecha de entrega</span>
            <input class="field__input mono" type="date" name="fecha_entrega"
                   value="<?= $h((string) ($form['fecha_entrega'] ?? '')) ?>">
        </label>
        <label class="field">
            <span class="field__label">Total *</span>
            <input class="field__input mono" type="number" step="0.01" min="0"
                   name="total" value="<?= $h((string) $form['total']) ?>" required>
            <?= $err('total') ?>
        </label>
        <label class="field">
            <span class="field__label">Pagado</span>
            <input class="field__input mono" type="number" step="0.01" min="0"
                   name="pagado" value="<?= $h((string) $form['pagado']) ?>">
            <?= $err('pagado') ?>
        </label>
    </div>

    <label class="field">
        <span class="field__label">Observación</span>
        <textarea class="field__input" name="observacion" rows="2"><?= $h((string) ($form['observacion'] ?? '')) ?></textarea>
    </label>

    <div class="form__actions">
        <a href="<?= BASE_URL ?>/pedido/index" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--primary">Guardar cambios</button>
    </div>
</form>
