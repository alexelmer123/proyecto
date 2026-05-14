<?php
$activo = strtolower($_SERVER['REQUEST_URI'] ?? '');
$is = static fn(string $needle): string => str_contains($activo, $needle) ? ' is-active' : '';
$groupOpen = static function (array $needles) use ($activo): bool {
    foreach ($needles as $n) {
        if (str_contains($activo, $n)) {
            return true;
        }
    }
    return false;
};
?>
<aside class="sidebar">
    <nav class="sidebar__nav" aria-label="Navegación principal">
        <p class="sidebar__section">Inicio</p>
        <a class="sidebar__link<?= $is('/dashboard') ?>" href="<?= BASE_URL ?>/dashboard/index">
            <span class="sidebar__bullet" aria-hidden="true"></span>
            <span class="sidebar__label">Tablero</span>
            <span class="sidebar__shortcut">Resumen</span>
        </a>

        <details class="sidebar__group"<?= $groupOpen(['/producto', '/categoria', '/proveedor']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Inventario</span>
                <span class="sidebar__caret" aria-hidden="true"></span>
            </summary>
            <a class="sidebar__link<?= $is('/producto') ?>" href="<?= BASE_URL ?>/producto/index">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Catálogo</span>
                <span class="sidebar__shortcut">Productos</span>
            </a>
            <a class="sidebar__link<?= $is('/categoria') ?>" href="<?= BASE_URL ?>/categoria/index">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Categorías</span>
                <span class="sidebar__shortcut">Tipos</span>
            </a>
            <a class="sidebar__link<?= $is('/proveedor') ?>" href="<?= BASE_URL ?>/proveedor/index">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Proveedores</span>
                <span class="sidebar__shortcut">Suministro</span>
            </a>
        </details>

        <details class="sidebar__group"<?= $groupOpen(['/pedido']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Compras</span>
                <span class="sidebar__caret" aria-hidden="true"></span>
            </summary>
            <a class="sidebar__link<?= $is('/pedido') ?>" href="<?= BASE_URL ?>/pedido/index">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Pedidos</span>
                <span class="sidebar__shortcut">Proveedores</span>
            </a>
            <a class="sidebar__link<?= $is('/pedido/index?estado=deuda') ?>" href="<?= BASE_URL ?>/pedido/index?estado=deuda">
                <span class="sidebar__bullet sidebar__bullet--alert" aria-hidden="true"></span>
                <span class="sidebar__label">Deudas</span>
                <span class="sidebar__shortcut">Pendientes</span>
            </a>
        </details>

        <details class="sidebar__group"<?= $groupOpen(['/movimiento']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Movimientos</span>
                <span class="sidebar__caret" aria-hidden="true"></span>
            </summary>
            <a class="sidebar__link<?= $is('/movimiento/registrarentrada') ?>" href="<?= BASE_URL ?>/movimiento/registrarEntrada">
                <span class="sidebar__bullet sidebar__bullet--in" aria-hidden="true"></span>
                <span class="sidebar__label">Entrada</span>
                <span class="sidebar__shortcut">+ Stock</span>
            </a>
            <a class="sidebar__link<?= $is('/movimiento/registrarsalida') ?>" href="<?= BASE_URL ?>/movimiento/registrarSalida">
                <span class="sidebar__bullet sidebar__bullet--out" aria-hidden="true"></span>
                <span class="sidebar__label">Salida</span>
                <span class="sidebar__shortcut">− Stock</span>
            </a>
            <a class="sidebar__link<?= $is('/movimiento/historial') ?>" href="<?= BASE_URL ?>/movimiento/historial">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Historial</span>
                <span class="sidebar__shortcut">Bitácora</span>
            </a>
        </details>

        <details class="sidebar__group"<?= $groupOpen(['/reporte']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Reportes</span>
                <span class="sidebar__caret" aria-hidden="true"></span>
            </summary>
            <a class="sidebar__link<?= $is('/reporte/stockbajo') ?>" href="<?= BASE_URL ?>/reporte/stockBajo">
                <span class="sidebar__bullet sidebar__bullet--alert" aria-hidden="true"></span>
                <span class="sidebar__label">Stock crítico</span>
            </a>
            <a class="sidebar__link<?= $is('/reporte/valorinventario') ?>" href="<?= BASE_URL ?>/reporte/valorInventario">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Valor inventario</span>
            </a>
            <a class="sidebar__link<?= $is('/reporte/movimientosporperiodo') ?>" href="<?= BASE_URL ?>/reporte/movimientosPorPeriodo">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Por período</span>
            </a>
            <a class="sidebar__link<?= $is('/reporte/ventas') ?>" href="<?= BASE_URL ?>/reporte/ventas">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Ventas del día</span>
                <span class="sidebar__shortcut">Periodizado</span>
            </a>
            <a class="sidebar__link<?= $is('/reporte/consolidadoproveedores') ?>" href="<?= BASE_URL ?>/reporte/consolidadoProveedores">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Consolidado proveedores</span>
                <span class="sidebar__shortcut">Compras</span>
            </a>
        </details>

        <details class="sidebar__group"<?= $groupOpen(['/auditoria', '/rol']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Seguridad</span>
                <span class="sidebar__caret" aria-hidden="true"></span>
            </summary>
            <a class="sidebar__link<?= $is('/auditoria') ?>" href="<?= BASE_URL ?>/auditoria/index">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Auditoría</span>
                <span class="sidebar__shortcut">Bitácora</span>
            </a>
            <a class="sidebar__link<?= $is('/rol') ?>" href="<?= BASE_URL ?>/rol/index">
                <span class="sidebar__bullet" aria-hidden="true"></span>
                <span class="sidebar__label">Roles</span>
                <span class="sidebar__shortcut">Permisos</span>
            </a>
        </details>
    </nav>

    <footer class="sidebar__foot">
        <p class="sidebar__signature">
            Vidrios Centro Puno · v<?= htmlspecialchars(APP_VERSION, ENT_QUOTES, 'UTF-8') ?>
        </p>
        <p class="sidebar__quote">
            <em>“El cristal es luz disciplinada por la geometría.”</em>
        </p>
    </footer>
</aside>
