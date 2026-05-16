<?php
/** @var string $motivo */ /** @var string $accion */ /** @var string $submit */
/** @var array  $form */ /** @var array $errores */
/** @var array  $productos */
$h   = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';

// Configuración visual por motivo
$cfg = [
    'venta'     => ['icon' => 'tag',     'color' => 'success', 'verbo' => 'Registrar venta'],
    'encargo'   => ['icon' => 'clock',   'color' => 'info',    'verbo' => 'Registrar encargo'],
    'accidente' => ['icon' => 'alert',   'color' => 'danger',  'verbo' => 'Registrar accidente'],
    'merma'     => ['icon' => 'archive', 'color' => 'warn',    'verbo' => 'Registrar merma'],
][$motivo] ?? ['icon' => 'arrow-up', 'color' => 'danger', 'verbo' => 'Registrar salida'];
?>
<form method="post" action="<?= $h($accion) ?>" class="form form--card form--narrow" novalidate>
    <?php if (!empty($errores['general'])): ?>
        <p class="modal__error"><?= $h($errores['general']) ?></p>
    <?php endif; ?>

    <label class="field">
        <span class="field__label">Producto *</span>
        <select class="field__input" name="producto_id" required data-stock-target="#stockInfo">
            <option value="">— Selecciona —</option>
            <?php foreach ($productos as $p): ?>
                <option value="<?= (int) $p['id'] ?>"
                    <?= (int) $form['producto_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= $h($p['codigo'] . ' · ' . $p['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= $err('producto_id') ?>
    </label>

    <div id="stockInfo" class="stock-info">Selecciona un producto para ver el stock actual.</div>

    <label class="field">
        <span class="field__label" data-cantidad-label>Cantidad a retirar *</span>
        <input class="field__input mono" type="number" min="1" step="1" name="cantidad" required
               value="<?= $h((string) ($form['cantidad'] ?? 1)) ?>"
               data-cantidad-input>
        <?= $err('cantidad') ?>
    </label>

    <?php if ($motivo === 'venta'): ?>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Cliente</span>
                <input class="field__input" name="cliente"
                       value="<?= $h($form['cliente'] ?? '') ?>"
                       placeholder="Nombre o razón social del comprador">
            </label>
            <label class="field">
                <span class="field__label">Total de la venta (S/.)</span>
                <input class="field__input mono" type="number" step="0.01" min="0" name="total"
                       value="<?= $h($form['total'] ?? '') ?>"
                       placeholder="0.00">
                <?= $err('total') ?>
            </label>
        </div>
    <?php endif; ?>

    <?php if ($motivo === 'encargo'): ?>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Cliente *</span>
                <input class="field__input" name="cliente"
                       value="<?= $h($form['cliente'] ?? '') ?>"
                       placeholder="A quién se entrega el encargo" required>
                <?= $err('cliente') ?>
            </label>
            <label class="field">
                <span class="field__label">Fecha de entrega *</span>
                <input class="field__input mono" type="date" name="fecha_entrega"
                       value="<?= $h($form['fecha_entrega'] ?? '') ?>" required>
                <?= $err('fecha_entrega') ?>
            </label>
        </div>
    <?php endif; ?>

    <?php if ($motivo === 'accidente' || $motivo === 'merma'): ?>
        <label class="field">
            <span class="field__label">Evidencia *</span>
            <textarea class="field__input" name="evidencia" rows="3"
                      placeholder="<?= $motivo === 'accidente'
                          ? 'Describe el accidente: cómo ocurrió, dónde, responsable…'
                          : 'Describe la merma: causa (vencimiento, deterioro, robo, etc.) y contexto.' ?>"
                      required><?= $h($form['evidencia'] ?? '') ?></textarea>
            <?= $err('evidencia') ?>
        </label>
    <?php endif; ?>

    <label class="field">
        <span class="field__label">Observación</span>
        <textarea class="field__input" name="observacion" rows="2"
                  placeholder="Notas adicionales, número de orden, etc."><?= $h($form['observacion'] ?? '') ?></textarea>
    </label>

    <?php if (in_array($motivo, ['venta', 'accidente'], true)):
        $mermasForm = $form['mermas'] ?? [];
        if (!is_array($mermasForm) || $mermasForm === []) {
            $mermasForm = [['cantidad' => '', 'motivo' => 'merma', 'observacion' => '']];
        }
        $motivosMerma = [
            'merma'     => 'Merma',
            'accidente' => 'Accidente / rotura',
            'retazo'    => 'Retazo aprovechable (no descuenta stock)',
        ];
    ?>
        <section class="mermas-section" data-mermas-wrapper hidden>
            <header class="mermas-section__head">
                <div>
                    <h3 class="mermas-section__title">Mermas y retazos generados</h3>
                    <p class="mermas-section__hint">
                        Captura aquí lo que se perdió, rompió o quedó como retazo. Las
                        <strong>mermas y accidentes</strong> descuentan stock extra; los
                        <strong>retazos</strong> sólo se anotan como referencia.
                    </p>
                </div>
                <button type="button" class="btn btn--ghost btn--sm" data-add-merma>+ Agregar fila</button>
            </header>

            <div class="mermas-list" data-mermas-list>
                <?php foreach ($mermasForm as $i => $m): ?>
                    <div class="mermas-row" data-merma-row>
                        <label class="field">
                            <span class="field__label">Cantidad</span>
                            <input class="field__input mono" type="number" min="0" step="0.01"
                                   name="mermas[<?= (int) $i ?>][cantidad]"
                                   value="<?= $h((string) ($m['cantidad'] ?? '')) ?>"
                                   placeholder="0"
                                   data-merma-cantidad>
                        </label>
                        <label class="field">
                            <span class="field__label">Motivo</span>
                            <select class="field__input" name="mermas[<?= (int) $i ?>][motivo]">
                                <?php foreach ($motivosMerma as $v => $lbl): ?>
                                    <option value="<?= $h($v) ?>"
                                        <?= ($m['motivo'] ?? 'merma') === $v ? 'selected' : '' ?>>
                                        <?= $h($lbl) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field mermas-row__obs">
                            <span class="field__label">Detalle</span>
                            <input class="field__input" type="text"
                                   name="mermas[<?= (int) $i ?>][observacion]"
                                   value="<?= $h((string) ($m['observacion'] ?? '')) ?>"
                                   placeholder="Ej. retazo 20×30, esquina rota, sobrante irregular…">
                        </label>
                        <button type="button" class="mermas-row__remove" data-remove-merma
                                aria-label="Quitar fila">×</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <template data-merma-template>
                <div class="mermas-row" data-merma-row>
                    <label class="field">
                        <span class="field__label">Cantidad</span>
                        <input class="field__input mono" type="number" min="0" step="0.01"
                               name="mermas[__IDX__][cantidad]" placeholder="0"
                               data-merma-cantidad>
                    </label>
                    <label class="field">
                        <span class="field__label">Motivo</span>
                        <select class="field__input" name="mermas[__IDX__][motivo]">
                            <?php foreach ($motivosMerma as $v => $lbl): ?>
                                <option value="<?= $h($v) ?>"><?= $h($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field mermas-row__obs">
                        <span class="field__label">Detalle</span>
                        <input class="field__input" type="text"
                               name="mermas[__IDX__][observacion]"
                               placeholder="Ej. retazo 20×30, esquina rota, sobrante irregular…">
                    </label>
                    <button type="button" class="mermas-row__remove" data-remove-merma aria-label="Quitar fila">×</button>
                </div>
            </template>

            <?php if (!empty($errores['mermas'])): ?>
                <small class="field__error"><?= $h($errores['mermas']) ?></small>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="form__actions">
        <a href="<?= BASE_URL ?>/movimiento/historial" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--<?= $cfg['color'] ?>">
            <?= icon($cfg['icon'], 16) ?>
            <span><?= $h($submit ?? $cfg['verbo']) ?></span>
        </button>
    </div>
</form>
