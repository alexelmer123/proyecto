<?php
/** @var array $producto */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Catálogo</p>
        <h1 class="page-head__title">Ajustar stock</h1>
        <p class="page-head__caption mono">
            <?= $h($producto['codigo']) ?> · <?= $h($producto['nombre']) ?>
        </p>
    </div>
    <a href="<?= BASE_URL ?>/producto/index" class="btn btn--ghost">← Volver</a>
</header>

<div class="card card--summary">
    <div class="card__metric">
        <span class="card__label">Stock actual</span>
        <span class="card__value mono"><?= (int) $producto['stock_actual'] ?></span>
    </div>
    <div class="card__metric">
        <span class="card__label">Stock mínimo</span>
        <span class="card__value mono"><?= (int) $producto['stock_minimo'] ?></span>
    </div>
    <div class="card__metric">
        <span class="card__label">Unidad</span>
        <span class="card__value"><?= $h($producto['unidad']) ?></span>
    </div>
</div>

<form method="post" action="<?= BASE_URL ?>/producto/ajustarStock/<?= (int) $producto['id'] ?>" class="form form--card form--narrow">
    <p class="form__hint">El stock se sustituirá por el valor que indiques. Se registrará un movimiento de tipo
        <strong>ajuste</strong> con stock anterior y nuevo.</p>

    <label class="field">
        <span class="field__label">Nuevo stock *</span>
        <input class="field__input mono" type="number" min="0" name="stock_nuevo"
               value="<?= (int) $producto['stock_actual'] ?>" required autofocus>
    </label>
    <label class="field">
        <span class="field__label">Observación</span>
        <textarea class="field__input" name="observacion" rows="2"
                  placeholder="Conteo físico, merma, recepción no facturada…"></textarea>
    </label>

    <div class="form__actions">
        <a href="<?= BASE_URL ?>/producto/index" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--primary">Aplicar ajuste</button>
    </div>
</form>
