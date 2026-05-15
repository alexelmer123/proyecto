<?php
/** @var array  $form */ /** @var array $errores */
/** @var array  $todosLosPermisos */ /** @var array $permisosSeleccionados */
/** @var string $action */ /** @var string $submitLabel */
/** @var bool   $esEdicion */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$err = static fn(string $k) => isset($errores[$k])
    ? '<small class="field__error">' . htmlspecialchars($errores[$k], ENT_QUOTES, 'UTF-8') . '</small>'
    : '';
$seleccionados = array_flip(array_map('intval', $permisosSeleccionados ?? []));

// Agrupar permisos por módulo
$grupos = [];
foreach ($todosLosPermisos as $p) {
    $grupos[$p['modulo']][] = $p;
}
ksort($grupos);
?>
<form method="post" action="<?= $h($action) ?>" class="form form--modal" novalidate>
    <?php if (!empty($errores['general'])): ?>
        <p class="modal__error"><?= $h($errores['general']) ?></p>
    <?php endif; ?>

    <fieldset class="form__group">
        <legend class="form__legend">Datos del rol</legend>
        <div class="form__row form__row--2">
            <label class="field">
                <span class="field__label">Nombre *</span>
                <input class="field__input" name="nombre" value="<?= $h($form['nombre'] ?? '') ?>" required
                       placeholder="gerente, almacenero, ventas…">
                <?= $err('nombre') ?>
            </label>
            <label class="field">
                <span class="field__label">Estado</span>
                <label class="switch">
                    <input type="checkbox" name="activo" value="1" <?= !empty($form['activo']) ? 'checked' : '' ?>>
                    <span class="switch__track"><span class="switch__thumb"></span></span>
                    <span class="switch__label">Activo</span>
                </label>
            </label>
        </div>
        <label class="field">
            <span class="field__label">Descripción</span>
            <textarea class="field__input" name="descripcion" rows="2"
                      placeholder="Resumen de qué hace este rol…"><?= $h($form['descripcion'] ?? '') ?></textarea>
        </label>
    </fieldset>

    <fieldset class="form__group">
        <legend class="form__legend">Permisos</legend>
        <p class="form__hint">
            Marca los permisos que tendrá este rol. Se aplicarán a todos los usuarios asignados.
        </p>

        <div class="permisos-grid">
            <?php foreach ($grupos as $modulo => $items): ?>
                <section class="permisos-grid__module" data-permisos-modulo>
                    <header class="permisos-grid__module-head">
                        <h4 class="permisos-grid__module-title"><?= $h(ucfirst((string) $modulo)) ?></h4>
                        <button type="button" class="btn btn--ghost btn--xs" data-permisos-toggle-modulo>
                            Marcar todos
                        </button>
                    </header>
                    <div class="permisos-grid__items">
                        <?php foreach ($items as $p): $pid = (int) $p['id']; $checked = isset($seleccionados[$pid]); ?>
                            <label class="permiso-check">
                                <input type="checkbox" name="permisos[]" value="<?= $pid ?>"
                                       <?= $checked ? 'checked' : '' ?>>
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
        <a href="<?= BASE_URL ?>/rol/index" class="btn btn--ghost">Cancelar</a>
        <button type="submit" class="btn btn--primary"><?= $h($submitLabel ?? 'Guardar') ?></button>
    </div>
</form>
