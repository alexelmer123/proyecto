<?php
/** @var array  $form */ /** @var array $errores */
/** @var array  $roles */
/** @var array  $todosLosPermisos */
/** @var array  $permisosDelRol */ /** @var array $permisosExtra */
/** @var string $action */ /** @var string $submitLabel */
/** @var bool   $esEdicion */
$h   = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';
$idsRol    = array_flip(array_map('intval', $permisosDelRol ?? []));
$idsExtra  = array_flip(array_map('intval', $permisosExtra ?? []));
$rolActual = (int) ($form['rol_id'] ?? 0);

// Agrupar permisos por módulo
$grupos = [];
foreach ($todosLosPermisos as $p) {
    $grupos[$p['modulo']][] = $p;
}
ksort($grupos);

// Nombre del rol seleccionado (para badge inicial)
$nombreRolSel = '';
foreach ($roles as $r) {
    if ((int) $r['id'] === $rolActual) { $nombreRolSel = (string) $r['nombre']; break; }
}
$hasExtras = !empty($idsExtra);
?>
<form method="post" action="<?= $h($action) ?>" class="form form--modal"
      data-usuario-form
      data-permisos-rol-url="<?= BASE_URL ?>/usuario/permisosDeRol"
      novalidate>
    <?php if (!empty($errores['general'])): ?>
        <p class="modal__error"><?= $h($errores['general']) ?></p>
    <?php endif; ?>

    <fieldset class="form__group">
        <legend class="form__legend">Datos del usuario</legend>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Nombre *</span>
                <input class="field__input" name="nombre" value="<?= $h($form['nombre'] ?? '') ?>" required
                       placeholder="Nombre y apellidos">
                <?= $err('nombre') ?>
            </label>
            <label class="field">
                <span class="field__label">Email *</span>
                <input class="field__input mono" type="email" name="email"
                       value="<?= $h($form['email'] ?? '') ?>" required
                       placeholder="usuario@empresa.com">
                <?= $err('email') ?>
            </label>
        </div>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">
                    Contraseña <?= $esEdicion ? '<small>(dejar vacío para no cambiar)</small>' : '*' ?>
                </span>
                <input class="field__input mono" type="password" name="password" autocomplete="new-password"
                       <?= $esEdicion ? '' : 'required' ?>>
                <?= $err('password') ?>
            </label>
            <?php if ($esEdicion): ?>
                <label class="field">
                    <span class="field__label">Estado</span>
                    <label class="switch">
                        <input type="checkbox" name="activo" value="1" <?= !empty($form['activo']) ? 'checked' : '' ?>>
                        <span class="switch__track"><span class="switch__thumb"></span></span>
                        <span class="switch__label">Activo</span>
                    </label>
                </label>
            <?php endif; ?>
        </div>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Rol y permisos</legend>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Rol *</span>
                <select class="field__input" name="rol_id" required data-rol-select>
                    <option value="">— Selecciona un rol —</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int) $r['id'] ?>" <?= $rolActual === (int) $r['id'] ? 'selected' : '' ?>>
                            <?= $h(ucfirst((string) $r['nombre'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $err('rol_id') ?>
            </label>
            <div class="field">
                <span class="field__label">Etiqueta del usuario</span>
                <span class="permisos-badge<?= $hasExtras ? ' permisos-badge--custom' : '' ?>"
                      data-permisos-badge
                      data-rol-name="<?= $h($nombreRolSel) ?>">
                    <?php if ($hasExtras): ?>
                        Personalizado
                    <?php elseif ($nombreRolSel !== ''): ?>
                        Rol: <strong><?= $h(ucfirst($nombreRolSel)) ?></strong>
                    <?php else: ?>
                        Sin rol asignado
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <p class="form__hint">
            <span class="legend-dot legend-dot--role"></span> Permiso heredado del rol &nbsp;·&nbsp;
            <span class="legend-dot legend-dot--extra"></span> Permiso extra asignado al usuario
        </p>

        <div class="permisos-grid" data-permisos-grid>
            <?php foreach ($grupos as $modulo => $items): ?>
                <section class="permisos-grid__module">
                    <header class="permisos-grid__module-head">
                        <h4 class="permisos-grid__module-title"><?= $h(ucfirst((string) $modulo)) ?></h4>
                    </header>
                    <div class="permisos-grid__items">
                        <?php foreach ($items as $p):
                            $pid       = (int) $p['id'];
                            $isFromRol = isset($idsRol[$pid]);
                            $isExtra   = !$isFromRol && isset($idsExtra[$pid]);
                            $classes   = 'permiso-check';
                            if ($isFromRol) $classes .= ' is-from-role';
                            if ($isExtra)   $classes .= ' is-extra';
                        ?>
                            <label class="<?= $classes ?>" data-permiso-id="<?= $pid ?>">
                                <input type="checkbox" name="permisos_extra[]" value="<?= $pid ?>"
                                       data-permiso-input
                                       <?= $isFromRol ? 'checked disabled' : '' ?>
                                       <?= $isExtra   ? 'checked' : '' ?>>
                                <span class="permiso-check__box"></span>
                                <span class="permiso-check__body">
                                    <span class="permiso-check__name"><?= $h($p['nombre']) ?></span>
                                    <span class="permiso-check__code mono"><?= $h($p['codigo']) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="modal__foot modal__foot--inline">
        <a href="<?= BASE_URL ?>/usuario/index" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--primary"><?= $h($submitLabel ?? 'Guardar') ?></button>
    </div>
</form>
