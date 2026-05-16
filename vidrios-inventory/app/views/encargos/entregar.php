<?php
/** @var array $encargo */
/** @var array $items */
/** @var array $errores */
/** @var array $unidades */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

// Render helper: pinta UNA fila (cantidad + motivo + N medidas dimensionales
// según la unidad). $idx puede ser entero (fila inicial) o "__IDX__" (template).
$renderRow = function (string|int $idx, int $productoId, array $medidasCfg, string $paso) use ($h): string {
    ob_start(); ?>
    <div class="mermas-row" data-merma-row>
        <input type="hidden" name="mermas[<?= $h((string) $idx) ?>][producto_id]" value="<?= (int) $productoId ?>">
        <label class="field">
            <span class="field__label">Cantidad</span>
            <input class="field__input mono" type="number" min="0" step="<?= $h($paso) ?>"
                   name="mermas[<?= $h((string) $idx) ?>][cantidad]" placeholder="0">
        </label>
        <label class="field">
            <span class="field__label">Motivo</span>
            <select class="field__input" name="mermas[<?= $h((string) $idx) ?>][motivo]">
                <option value="merma">Merma</option>
                <option value="accidente">Accidente / rotura</option>
                <option value="retazo">Retazo aprovechable</option>
            </select>
        </label>
        <?php foreach ($medidasCfg as $m): ?>
            <label class="field">
                <span class="field__label"><?= $h($m['label']) ?></span>
                <input class="field__input mono" type="number" min="0" step="0.01"
                       name="mermas[<?= $h((string) $idx) ?>][medidas][<?= $h($m['key']) ?>]"
                       placeholder="<?= $h($m['placeholder'] ?? '') ?>">
            </label>
        <?php endforeach; ?>
        <button type="button" class="mermas-row__remove" data-remove-merma-row
                aria-label="Quitar fila">×</button>
    </div>
<?php
    return (string) ob_get_clean();
};

// 1ª pasada: contamos cuántas filas iniciales tendrá el form (una por cada
// item que admite mermas). Ese conteo es el siguiente índice libre que JS
// usará para añadir filas clonadas.
$nextIdx = 0;
foreach ($items as $it) {
    $cfg = $unidades[(string) ($it['producto_unidad'] ?? '')] ?? null;
    if (($cfg['permite_mermas'] ?? false) === true) {
        $nextIdx++;
    }
}
?>
<form method="post" action="<?= BASE_URL ?>/encargo/entregar/<?= (int) $encargo['id'] ?>"
      class="form form--modal" novalidate
      data-encargo-mermas data-merma-next-idx="<?= (int) $nextIdx ?>">
    <?php if (!empty($errores['general'])): ?>
        <p class="modal__error"><?= $h($errores['general']) ?></p>
    <?php endif; ?>

    <p class="form__hint">
        Para cada producto puedes anotar varias filas (p. ej. una merma <em>y</em> un retazo).
        Las <strong>mermas y accidentes</strong> descuentan stock; los <strong>retazos</strong> solo
        se anotan. Las medidas del retazo van en cm.
    </p>

    <?php
    // 2ª pasada: render real. Reusamos un contador propio que arranca en 0 y va
    // dando índice a cada fila inicial.
    $rowIdx = 0;
    foreach ($items as $i => $it):
        $unidad     = (string) ($it['producto_unidad'] ?? '');
        $cfg        = $unidades[$unidad] ?? null;
        $permite    = $cfg['permite_mermas'] ?? false;
        $paso       = $cfg['paso'] ?? '1';
        $medidasCfg = $cfg['medidas_merma'] ?? [];
        $cantidadOk = fmt_cantidad($it['cantidad']);
        $pid        = (int) $it['producto_id'];
    ?>
        <fieldset class="form__group" data-encargo-item="<?= (int) $i ?>">
            <legend class="form__legend">
                <strong class="mono"><?= $h($it['producto_codigo']) ?></strong>
                · <?= $h($it['producto_nombre']) ?>
                <small>(<?= $h($cantidadOk) ?> <?= $h($unidad) ?>)</small>
            </legend>

            <?php if ($permite): ?>
                <div class="mermas-list" data-mermas-rows-list>
                    <?= $renderRow($rowIdx, $pid, $medidasCfg, $paso) ?>
                    <?php $rowIdx++; ?>
                </div>

                <template data-mermas-row-template
                          data-producto-id="<?= $pid ?>"
                          data-paso="<?= $h($paso) ?>">
                    <?= $renderRow('__IDX__', $pid, $medidasCfg, $paso) ?>
                </template>

                <button type="button" class="btn btn--ghost btn--sm" data-add-merma-row>
                    + Agregar otra fila para este producto
                </button>
            <?php else: ?>
                <p class="form__hint">
                    La unidad <strong><?= $h($unidad ?: 'sin definir') ?></strong> no admite mermas ni retazos.
                </p>
            <?php endif; ?>
        </fieldset>
    <?php endforeach; ?>

    <div class="modal__foot modal__foot--inline">
        <button type="button" class="btn btn--ghost" data-modal-cancel>Cancelar</button>
        <button type="submit" class="btn btn--primary">
            <?= icon('check', 16) ?> <span>Confirmar entrega</span>
        </button>
    </div>
</form>
