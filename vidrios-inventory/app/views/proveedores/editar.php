<?php
/** @var array $form */ /** @var array $errores */
/** @var array $paises */ /** @var array $departamentos */
/** @var array $provincias */ /** @var array $distritos */ /** @var array $ciudades */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';
$id    = (int) ($form['id'] ?? 0);
$selId = static fn(?int $current, $optId): string =>
    ((int) $current === (int) $optId) ? 'selected' : '';
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Suministro · Edición</p>
        <h1 class="page-head__title"><?= $h($form['nombre'] ?? '') ?></h1>
    </div>
    <a href="<?= BASE_URL ?>/proveedor/index" class="btn btn--ghost">← Volver</a>
</header>

<form method="post" action="<?= BASE_URL ?>/proveedor/editar/<?= $id ?>" class="form form--card">
    <fieldset class="form__group">
        <legend class="form__legend">Identidad</legend>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Nombre *</span>
                <input class="field__input" name="nombre" value="<?= $h($form['nombre'] ?? '') ?>" required autofocus>
                <?= $err('nombre') ?>
            </label>
            <label class="field">
                <span class="field__label">Persona de contacto</span>
                <input class="field__input" name="contacto" value="<?= $h($form['contacto'] ?? '') ?>">
            </label>
        </div>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Teléfono</span>
                <input class="field__input mono" name="telefono" value="<?= $h($form['telefono'] ?? '') ?>">
            </label>
            <label class="field">
                <span class="field__label">Email</span>
                <input class="field__input mono" type="email" name="email" value="<?= $h($form['email'] ?? '') ?>">
                <?= $err('email') ?>
            </label>
        </div>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Productos que provee</legend>
        <label class="field">
            <span class="field__label">Descripción de lo que vende</span>
            <textarea class="field__input" name="descripcion_productos" rows="3"
                      placeholder="Ej.: vidrios templados claros, espejos plateados, perfiles de aluminio…"><?= $h($form['descripcion_productos'] ?? '') ?></textarea>
        </label>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Ubicación</legend>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">País</span>
                <select class="field__input" name="pais_id" data-cascade="departamento_id" data-cascade-url="<?= BASE_URL ?>/geografia/departamentos">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($paises as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $selId($form['pais_id'] ?? null, $p['id']) ?>><?= $h($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field__label">Departamento</span>
                <select class="field__input" name="departamento_id" data-cascade="provincia_id" data-cascade-url="<?= BASE_URL ?>/geografia/provincias" <?= empty($departamentos) ? 'disabled' : '' ?>>
                    <option value="">— Selecciona —</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= $selId($form['departamento_id'] ?? null, $d['id']) ?>><?= $h($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form__row form__row--3">
            <label class="field">
                <span class="field__label">Provincia</span>
                <select class="field__input" name="provincia_id" data-cascade="distrito_id" data-cascade-url="<?= BASE_URL ?>/geografia/distritos" <?= empty($provincias) ? 'disabled' : '' ?>>
                    <option value="">—</option>
                    <?php foreach ($provincias as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $selId($form['provincia_id'] ?? null, $p['id']) ?>><?= $h($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field__label">Distrito</span>
                <select class="field__input" name="distrito_id" data-cascade="ciudad_id" data-cascade-url="<?= BASE_URL ?>/geografia/ciudades" <?= empty($distritos) ? 'disabled' : '' ?>>
                    <option value="">—</option>
                    <?php foreach ($distritos as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= $selId($form['distrito_id'] ?? null, $d['id']) ?>><?= $h($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field__label">Ciudad</span>
                <select class="field__input" name="ciudad_id" <?= empty($ciudades) ? 'disabled' : '' ?>>
                    <option value="">—</option>
                    <?php foreach ($ciudades as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $selId($form['ciudad_id'] ?? null, $c['id']) ?>><?= $h($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label class="field">
            <span class="field__label">Dirección detallada</span>
            <textarea class="field__input" name="direccion" rows="2"><?= $h($form['direccion'] ?? '') ?></textarea>
        </label>
    </fieldset>

    <div class="form__actions">
        <a href="<?= BASE_URL ?>/proveedor/index" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--primary">Guardar cambios</button>
    </div>
</form>
