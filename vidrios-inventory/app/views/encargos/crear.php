<?php
/** @var array $form */ /** @var array $items */ /** @var array $errores */
/** @var array $productos */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';

// Si no hay items previos (primera carga), mostrar una fila vacía.
$filasIniciales = $items !== [] ? $items : [['producto_id' => 0, 'cantidad' => 1, 'precio_unitario' => '']];

// Helper para renderizar una opción <option> con su data-stock
$renderOpts = static function (array $productos, int $selectedId = 0) use ($h): string {
    $out = '<option value="">— Selecciona producto —</option>';
    foreach ($productos as $p) {
        $sel = $selectedId === (int) $p['id'] ? ' selected' : '';
        $stock = (int) $p['stock_actual'];
        $precio = (float) ($p['precio_venta'] ?? 0);
        $out .= '<option value="' . (int) $p['id'] . '"'
              . ' data-stock="' . $stock . '"'
              . ' data-precio="' . number_format($precio, 2, '.', '') . '"'
              . $sel . '>'
              . $h($p['codigo'] . ' · ' . $p['nombre']) . ' [stock: ' . $stock . ']'
              . '</option>';
    }
    return $out;
};
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Salidas · Encargos</p>
        <h1 class="page-head__title">Nuevo encargo</h1>
        <p class="page-head__caption">Registra un encargo del cliente. Cada producto se descontará del stock.</p>
    </div>
    <a href="<?= BASE_URL ?>/encargo/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="form-shell form-shell--lg">
    <form method="post" action="<?= BASE_URL ?>/encargo/crear" class="form form--modal" novalidate>
        <?php if (!empty($errores['general'])): ?>
            <p class="modal__error"><?= $h($errores['general']) ?></p>
        <?php endif; ?>

        <fieldset class="form__group">
            <legend class="form__legend">Datos del cliente</legend>
            <div class="form__row form__row--2">
                <label class="field">
                    <span class="field__label">Cliente *</span>
                    <input class="field__input" name="cliente" required
                           value="<?= $h($form['cliente'] ?? '') ?>"
                           placeholder="Nombre o razón social">
                    <?= $err('cliente') ?>
                </label>
                <label class="field">
                    <span class="field__label">Teléfono</span>
                    <input class="field__input mono" name="telefono" type="tel"
                           value="<?= $h($form['telefono'] ?? '') ?>"
                           placeholder="+51 9XX XXX XXX">
                </label>
            </div>
            <div class="form__row form__row--2">
                <label class="field">
                    <span class="field__label">Fecha de entrega *</span>
                    <input class="field__input mono" name="fecha_entrega" type="date" required
                           value="<?= $h($form['fecha_entrega'] ?? '') ?>">
                    <?= $err('fecha_entrega') ?>
                </label>
                <label class="field">
                    <span class="field__label">Lugar de entrega</span>
                    <input class="field__input" name="lugar_entrega"
                           value="<?= $h($form['lugar_entrega'] ?? '') ?>"
                           placeholder="Dirección o referencia de entrega">
                </label>
            </div>
            <label class="field">
                <span class="field__label">Detalles del encargo</span>
                <textarea class="field__input" name="detalles" rows="3"
                          placeholder="Especificaciones, medidas, acabados, color, instalación, etc."><?= $h($form['detalles'] ?? '') ?></textarea>
            </label>
        </fieldset>

        <fieldset class="form__group">
            <legend class="form__legend">Productos del encargo</legend>
            <p class="form__hint">
                Añade los productos que se usarán. Cada uno descontará del stock al guardar.
            </p>
            <?php if (!empty($errores['items'])): ?>
                <p class="modal__error"><?= $h($errores['items']) ?></p>
            <?php endif; ?>

            <div class="encargo-items" data-encargo-items>
                <?php foreach ($filasIniciales as $i => $row):
                    $pid    = (int) ($row['producto_id'] ?? 0);
                    $qty    = (int) ($row['cantidad']    ?? 1);
                    $precio = $row['precio_unitario']    ?? '';
                ?>
                    <div class="encargo-item" data-encargo-item>
                        <div class="encargo-item__cell encargo-item__cell--product">
                            <label class="field field--inline">
                                <span class="field__label">Producto *</span>
                                <select class="field__input" name="items[<?= $i ?>][producto_id]" data-item-producto required>
                                    <?= $renderOpts($productos, $pid) ?>
                                </select>
                            </label>
                        </div>
                        <div class="encargo-item__cell">
                            <label class="field field--inline">
                                <span class="field__label">Cantidad *</span>
                                <input class="field__input mono" type="number" min="1"
                                       name="items[<?= $i ?>][cantidad]" value="<?= (int) $qty ?>"
                                       data-item-cantidad required>
                            </label>
                        </div>
                        <div class="encargo-item__cell">
                            <label class="field field--inline">
                                <span class="field__label">Precio unit. (S/.)</span>
                                <input class="field__input mono" type="number" step="0.01" min="0"
                                       name="items[<?= $i ?>][precio_unitario]"
                                       value="<?= $h((string) $precio) ?>"
                                       data-item-precio>
                            </label>
                        </div>
                        <div class="encargo-item__cell encargo-item__cell--subtotal">
                            <span class="field__label">Subtotal</span>
                            <span class="encargo-item__subtotal mono" data-item-subtotal>S/. 0.00</span>
                        </div>
                        <button type="button" class="iconbtn iconbtn--danger" data-encargo-item-remove title="Quitar">
                            <?= icon('close', 14) ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="encargo-items__foot">
                <button type="button" class="btn btn--ghost" data-encargo-item-add>
                    <?= icon('plus', 14) ?>
                    <span>Añadir producto</span>
                </button>
                <div class="encargo-items__total">
                    <span class="form__hint">Total estimado:</span>
                    <strong class="mono" data-encargo-total>S/. 0.00</strong>
                </div>
            </div>

            <!-- Plantilla para nuevas filas (renderiza una fila base sin index; el JS reasigna). -->
            <template data-encargo-item-template>
                <div class="encargo-item" data-encargo-item>
                    <div class="encargo-item__cell encargo-item__cell--product">
                        <label class="field field--inline">
                            <span class="field__label">Producto *</span>
                            <select class="field__input" data-item-producto required>
                                <?= $renderOpts($productos) ?>
                            </select>
                        </label>
                    </div>
                    <div class="encargo-item__cell">
                        <label class="field field--inline">
                            <span class="field__label">Cantidad *</span>
                            <input class="field__input mono" type="number" min="1"
                                   value="1" data-item-cantidad required>
                        </label>
                    </div>
                    <div class="encargo-item__cell">
                        <label class="field field--inline">
                            <span class="field__label">Precio unit. (S/.)</span>
                            <input class="field__input mono" type="number" step="0.01" min="0"
                                   data-item-precio>
                        </label>
                    </div>
                    <div class="encargo-item__cell encargo-item__cell--subtotal">
                        <span class="field__label">Subtotal</span>
                        <span class="encargo-item__subtotal mono" data-item-subtotal>S/. 0.00</span>
                    </div>
                    <button type="button" class="iconbtn iconbtn--danger" data-encargo-item-remove title="Quitar">
                        <?= icon('close', 14) ?>
                    </button>
                </div>
            </template>
        </fieldset>

        <div class="modal__foot modal__foot--inline">
            <a href="<?= BASE_URL ?>/encargo/index" class="btn btn--ghost">Cancelar</a>
            <button type="submit" class="btn btn--primary">
                <?= icon('check', 16) ?>
                <span>Registrar encargo</span>
            </button>
        </div>
    </form>
</div>
