<?php
/** @var array $form */ /** @var array $errores */
/** @var array $categorias */ /** @var array $proveedores */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';
$id = (int) ($form['id'] ?? 0);
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Catálogo · Edición</p>
        <h1 class="page-head__title"><?= $h($form['nombre'] ?? 'Editar producto') ?></h1>
        <p class="page-head__caption mono">Código <?= $h($form['codigo'] ?? '') ?></p>
    </div>
    <a href="<?= BASE_URL ?>/producto/index" class="btn btn--ghost">← Volver</a>
</header>

<form method="post" action="<?= BASE_URL ?>/producto/editar/<?= $id ?>" enctype="multipart/form-data" class="form form--card" novalidate>
    <fieldset class="form__group">
        <legend class="form__legend">Identidad</legend>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Código *</span>
                <input class="field__input mono" name="codigo" value="<?= $h($form['codigo'] ?? '') ?>" required>
                <?= $err('codigo') ?>
            </label>
            <label class="field">
                <span class="field__label">Nombre *</span>
                <input class="field__input" name="nombre" value="<?= $h($form['nombre'] ?? '') ?>" required>
                <?= $err('nombre') ?>
            </label>
        </div>
        <label class="field">
            <span class="field__label">Descripción</span>
            <textarea class="field__input" name="descripcion" rows="2"><?= $h($form['descripcion'] ?? '') ?></textarea>
        </label>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Clasificación</legend>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Categoría *</span>
                <select class="field__input" name="categoria_id" required>
                    <option value="">— Selecciona —</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"
                            <?= ((int)($form['categoria_id'] ?? 0)) === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= $h($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $err('categoria_id') ?>
            </label>
            <label class="field">
                <span class="field__label">Proveedor</span>
                <select class="field__input" name="proveedor_id">
                    <option value="">— Sin proveedor —</option>
                    <?php foreach ($proveedores as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"
                            <?= ((int)($form['proveedor_id'] ?? 0)) === (int) $p['id'] ? 'selected' : '' ?>>
                            <?= $h($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Dimensiones</legend>
        <div class="form__row form__row--4">
            <label class="field">
                <span class="field__label">Unidad</span>
                <input class="field__input" name="unidad" value="<?= $h($form['unidad'] ?? 'm²') ?>">
            </label>
            <label class="field">
                <span class="field__label">Ancho (mm)</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="ancho" value="<?= $h((string)($form['ancho'] ?? '')) ?>">
                <?= $err('ancho') ?>
            </label>
            <label class="field">
                <span class="field__label">Alto (mm)</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="alto" value="<?= $h((string)($form['alto'] ?? '')) ?>">
                <?= $err('alto') ?>
            </label>
            <label class="field">
                <span class="field__label">Grosor (mm)</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="grosor" value="<?= $h((string)($form['grosor'] ?? '')) ?>">
                <?= $err('grosor') ?>
            </label>
        </div>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Precios y stock</legend>
        <div class="form__row form__row--3">
            <label class="field">
                <span class="field__label">Precio compra *</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="precio_compra" value="<?= $h((string)($form['precio_compra'] ?? '0')) ?>" required>
                <?= $err('precio_compra') ?>
            </label>
            <label class="field">
                <span class="field__label">Precio venta *</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="precio_venta" value="<?= $h((string)($form['precio_venta'] ?? '0')) ?>" required>
                <?= $err('precio_venta') ?>
            </label>
            <label class="field">
                <span class="field__label">Stock mínimo *</span>
                <input class="field__input mono" type="number" min="1"
                       name="stock_minimo" value="<?= $h((string)($form['stock_minimo'] ?? '1')) ?>" required>
                <?= $err('stock_minimo') ?>
            </label>
        </div>
        <p class="form__hint">
            ⓘ El stock actual no se modifica desde aquí. Usa
            <a href="<?= BASE_URL ?>/producto/ajustarStock/<?= $id ?>">ajustar stock</a>
            o registra un movimiento.
        </p>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Imagen</legend>
        <?php if (!empty($form['imagen'])): ?>
            <p class="form__hint">Imagen actual:
                <a href="<?= $h($form['imagen']) ?>" target="_blank" rel="noopener"><?= $h($form['imagen']) ?></a>
            </p>
        <?php endif; ?>
        <label class="field">
            <span class="field__label">Reemplazar imagen <small>(JPG/PNG/WebP, máx. 4 MB)</small></span>
            <input class="field__input" type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
            <?= $err('imagen') ?>
        </label>
    </fieldset>

    <div class="form__actions">
        <a href="<?= BASE_URL ?>/producto/index" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--primary">Guardar cambios</button>
    </div>
</form>
