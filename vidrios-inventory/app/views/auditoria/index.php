<?php
/** @var array     $registros */
/** @var Paginator $paginator */
/** @var array     $filtros */
/** @var array     $entidades */
/** @var array     $acciones */
$h = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$selectedEntidad = $filtros['entidad'] ?? '';
$selectedAccion  = $filtros['accion']  ?? '';
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Seguridad</p>
        <h1 class="page-head__title">Bitácora de auditoría</h1>
        <p class="page-head__caption">Registro de cambios y accesos al sistema. <?= (int) $paginator->total ?> entradas en total.</p>
    </div>
    <?php
    $qsAud = http_build_query(array_filter([
        'entidad' => $filtros['entidad'] ?? '',
        'accion'  => $filtros['accion']  ?? '',
    ], static fn($v) => $v !== ''));
    ?>
    <a href="<?= BASE_URL ?>/auditoria/exportar<?= $qsAud ? '?' . $qsAud : '' ?>" class="btn btn--ghost">↓ Exportar CSV</a>
</header>

<form method="get" action="<?= BASE_URL ?>/auditoria/index" class="filters">
    <label class="field">
        <span class="field__label">Entidad</span>
        <select name="entidad" class="field__input">
            <option value="">Todas</option>
            <?php foreach ($entidades as $e): ?>
                <option value="<?= $h($e) ?>" <?= $selectedEntidad === $e ? 'selected' : '' ?>><?= $h(ucfirst($e)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span class="field__label">Acción</span>
        <select name="accion" class="field__input">
            <option value="">Todas</option>
            <?php foreach ($acciones as $a): ?>
                <option value="<?= $h($a) ?>" <?= $selectedAccion === $a ? 'selected' : '' ?>><?= $h($a) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="btn btn--ghost">Filtrar</button>
</form>

<section class="table-shell">
    <table class="table" id="tabla-auditoria">
        <thead>
        <tr>
            <th class="table__th">Fecha</th>
            <th class="table__th">Usuario</th>
            <th class="table__th">Acción</th>
            <th class="table__th">Entidad</th>
            <th class="table__th">ID</th>
            <th class="table__th">Descripción</th>
            <th class="table__th">IP</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$registros): ?>
            <tr><td colspan="7" class="table__empty">No hay registros para los filtros aplicados.</td></tr>
        <?php endif; ?>
        <?php foreach ($registros as $r): ?>
            <tr class="table__row">
                <td class="table__td mono"><?= $h($r['created_at']) ?></td>
                <td class="table__td">
                    <?= $h($r['usuario_nombre'] ?? '—') ?>
                    <?php if (!empty($r['usuario_email'])): ?>
                        <span class="cell-product__sub mono"><?= $h($r['usuario_email']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="table__td">
                    <span class="mov mov--<?= $h($r['accion']) ?>"><?= $h($r['accion']) ?></span>
                </td>
                <td class="table__td"><?= $h($r['entidad']) ?></td>
                <td class="table__td mono"><?= $h($r['entidad_id'] ?? '—') ?></td>
                <td class="table__td"><?= $h($r['descripcion'] ?? '') ?></td>
                <td class="table__td mono"><?= $h($r['ip'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?= $paginator->render(BASE_URL . '/auditoria/index', $filtros) ?>
