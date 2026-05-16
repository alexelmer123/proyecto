<?php
/** @var array  $form */        /** @var array  $errores */
/** @var array  $categorias */  /** @var array  $proveedores */
/** @var array  $unidades */
/** @var string $action */      /** @var string $submitLabel */
/** @var bool   $esEdicion */
$h    = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err  = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';
$esEdicion = (bool) ($esEdicion ?? false);
$idActual  = (int) ($form['id'] ?? 0);

$unidades       = $unidades ?? [];
$unidadActual   = (string) ($form['unidad'] ?? 'm²');
$unidadDesconocida = $unidadActual !== '' && !isset($unidades[$unidadActual]);

// Mapa unidad → dims que se serializa al markup para que app.js controle
// la visibilidad de los inputs dimensionales sin duplicar la tabla.
$dimsMap = [];
foreach ($unidades as $value => $meta) {
    $dimsMap[$value] = $meta['dims'];
}
?>
<form method="post" action="<?= $h($action) ?>" enctype="multipart/form-data" class="form form--modal" novalidate
      data-producto-form
      data-unidad-dims='<?= $h(json_encode($dimsMap, JSON_UNESCAPED_UNICODE)) ?>'>
    <fieldset class="form__group">
        <legend class="form__legend">Identidad</legend>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Código <?= $esEdicion ? '*' : '<small>(opcional, se autogenera)</small>' ?></span>
                <input class="field__input mono" name="codigo" value="<?= $h($form['codigo'] ?? '') ?>"
                       placeholder="VID-00042" <?= $esEdicion ? 'required' : '' ?>>
                <?= $err('codigo') ?>
            </label>
            <label class="field">
                <span class="field__label">Nombre *</span>
                <input class="field__input" name="nombre" value="<?= $h($form['nombre'] ?? '') ?>" required
                       placeholder="Vidrio templado claro">
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
        <legend class="form__legend">Unidad y dimensiones</legend>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Unidad de medida *</span>
                <select class="field__input" name="unidad" required data-unidad-select>
                    <?php foreach ($unidades as $value => $meta): ?>
                        <option value="<?= $h((string) $value) ?>"
                            <?= $unidadActual === (string) $value ? 'selected' : '' ?>>
                            <?= $h((string) $meta['label']) ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($unidadDesconocida): ?>
                        <option value="<?= $h($unidadActual) ?>" selected>
                            <?= $h($unidadActual) ?> (legado)
                        </option>
                    <?php endif; ?>
                </select>
                <?= $err('unidad') ?>
            </label>
        </div>
        <div class="form__row form__row--4" data-dim-grid>
            <label class="field" data-dim="ancho" hidden>
                <span class="field__label">Ancho (mm)</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="ancho" value="<?= $h((string)($form['ancho'] ?? '')) ?>">
                <?= $err('ancho') ?>
            </label>
            <label class="field" data-dim="alto" hidden>
                <span class="field__label">Alto (mm)</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="alto" value="<?= $h((string)($form['alto'] ?? '')) ?>">
                <?= $err('alto') ?>
            </label>
            <label class="field" data-dim="grosor" hidden>
                <span class="field__label">Grosor (mm)</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="grosor" value="<?= $h((string)($form['grosor'] ?? '')) ?>">
                <?= $err('grosor') ?>
            </label>
            <label class="field" data-dim="longitud" hidden>
                <span class="field__label">Longitud (mm)</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="longitud" value="<?= $h((string)($form['longitud'] ?? '')) ?>">
                <?= $err('longitud') ?>
            </label>
            <label class="field" data-dim="diametro" hidden>
                <span class="field__label">Diámetro (mm)</span>
                <input class="field__input mono" type="number" step="0.01" min="0"
                       name="diametro" value="<?= $h((string)($form['diametro'] ?? '')) ?>">
                <?= $err('diametro') ?>
            </label>
            <p class="form__hint form__hint--info" data-dim-empty hidden>
                Esta unidad no requiere campos de dimensiones.
            </p>
        </div>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Precios y stock</legend>
        <div class="form__row <?= $esEdicion ? 'form__row--3' : 'form__row--4' ?>">
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
            <?php if (!$esEdicion): ?>
                <label class="field">
                    <span class="field__label">Stock inicial</span>
                    <input class="field__input mono" type="number" min="0" step="0.01"
                           name="stock_actual" value="<?= $h((string)($form['stock_actual'] ?? '0')) ?>">
                </label>
            <?php endif; ?>
            <label class="field">
                <span class="field__label">Stock mínimo *</span>
                <input class="field__input mono" type="number" min="0.01" step="0.01"
                       name="stock_minimo" value="<?= $h((string)($form['stock_minimo'] ?? '1')) ?>" required>
                <?= $err('stock_minimo') ?>
            </label>
        </div>
        <?php if ($esEdicion): ?>
            <p class="form__hint form__hint--info">
                <?= icon('info', 14) ?>
                El stock actual no se modifica desde aquí. Usa
                <a href="<?= BASE_URL ?>/producto/ajustarStock/<?= $idActual ?>">ajustar stock</a>
                o registra un movimiento.
            </p>
        <?php endif; ?>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Imagen</legend>
        <?php if ($esEdicion && !empty($form['imagen'])): ?>
            <div class="form__current-img" data-current-img>
                <img src="<?= $h($form['imagen']) ?>" alt="Imagen actual" class="form__current-img__thumb">
                <div class="form__current-img__meta">
                    <strong>Imagen actual</strong>
                    <small data-current-img-hint>Reemplázala subiendo una nueva abajo o quítala.</small>
                </div>
                <button type="button" class="iconbtn iconbtn--danger" title="Quitar imagen" data-quitar-imagen>
                    <?= icon('trash', 16) ?>
                </button>
                <input type="hidden" name="quitar_imagen" value="0">
            </div>
        <?php endif; ?>
        <div data-upload>
            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" id="producto-imagen-<?= $idActual ?>">
        </div>
        <?= $err('imagen') ?>
    </fieldset>

    <div class="modal__foot modal__foot--inline">
        <button type="button" class="btn btn--ghost" data-modal-cancel>Cancelar</button>
        <button type="submit" class="btn btn--primary"><?= $h($submitLabel ?? 'Guardar') ?></button>
    </div>
</form>
