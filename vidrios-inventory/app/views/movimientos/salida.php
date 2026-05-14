<?php
/** @var array $productos */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Movimientos</p>
        <h1 class="page-head__title">Registrar salida de inventario</h1>
        <p class="page-head__caption">Ventas, instalaciones, mermas y bajas. Se valida stock disponible.</p>
    </div>
    <a href="<?= BASE_URL ?>/movimiento/historial" class="btn btn--ghost">← Historial</a>
</header>

<form method="post" action="<?= BASE_URL ?>/movimiento/registrarSalida" class="form form--card form--narrow">
    <label class="field">
        <span class="field__label">Producto *</span>
        <select class="field__input" name="producto_id" required data-stock-target="#stockInfo">
            <option value="">— Selecciona —</option>
            <?php foreach ($productos as $p): ?>
                <option value="<?= (int) $p['id'] ?>">
                    <?= $h($p['codigo'] . ' · ' . $p['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <div id="stockInfo" class="stock-info">Selecciona un producto para ver el stock actual.</div>

    <label class="field">
        <span class="field__label">Cantidad a retirar *</span>
        <input class="field__input mono" type="number" min="1" name="cantidad" required value="1">
    </label>

    <label class="field">
        <span class="field__label">Observación</span>
        <textarea class="field__input" name="observacion" rows="2"
                  placeholder="Cliente, orden de trabajo, motivo de la merma…"></textarea>
    </label>

    <div class="form__actions">
        <a href="<?= BASE_URL ?>/movimiento/historial" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--danger">Registrar salida</button>
    </div>
</form>
