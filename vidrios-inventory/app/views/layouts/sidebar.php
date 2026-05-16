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
<div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>
<aside class="sidebar" id="appSidebar">
    <nav class="sidebar__nav" aria-label="Navegación principal">
        <p class="sidebar__section">Inicio</p>
        <a class="sidebar__link<?= $is('/dashboard') ?>" href="<?= BASE_URL ?>/dashboard/index">
            <span class="sidebar__icon" aria-hidden="true"><?= icon('dashboard', 18) ?></span>
            <span class="sidebar__label">Tablero</span>
        </a>

        <details class="sidebar__group"<?= $groupOpen(['/producto', '/categoria', '/proveedor', '/retazo']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Inventario</span>
                <span class="sidebar__caret" aria-hidden="true"><?= icon('chevron-down', 14) ?></span>
            </summary>
            <a class="sidebar__link<?= $is('/producto') ?>" href="<?= BASE_URL ?>/producto/index">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('package', 18) ?></span>
                <span class="sidebar__label">Catálogo</span>
            </a>
            <a class="sidebar__link<?= $is('/categoria') ?>" href="<?= BASE_URL ?>/categoria/index">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('layers', 18) ?></span>
                <span class="sidebar__label">Categorías</span>
            </a>
            <a class="sidebar__link<?= $is('/proveedor') ?>" href="<?= BASE_URL ?>/proveedor/index">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('truck', 18) ?></span>
                <span class="sidebar__label">Proveedores</span>
            </a>
            <a class="sidebar__link<?= $is('/retazo') ?>" href="<?= BASE_URL ?>/retazo/index">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('archive', 18) ?></span>
                <span class="sidebar__label">Retazos</span>
            </a>
        </details>

        <details class="sidebar__group"<?= $groupOpen(['/pedido', '/movimiento/registrarentrada']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Entradas</span>
                <span class="sidebar__caret" aria-hidden="true"><?= icon('chevron-down', 14) ?></span>
            </summary>
            <a class="sidebar__link<?= $is('/pedido') ?>" href="<?= BASE_URL ?>/pedido/index">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('cart', 18) ?></span>
                <span class="sidebar__label">Pedidos</span>
            </a>
            <a class="sidebar__link<?= $is('/movimiento/registrarentrada') ?>" href="<?= BASE_URL ?>/movimiento/registrarEntrada">
                <span class="sidebar__icon sidebar__icon--in" aria-hidden="true"><?= icon('arrow-down', 18) ?></span>
                <span class="sidebar__label">Registrar entrada</span>
            </a>
        </details>

        <details class="sidebar__group"<?= $groupOpen(['/movimiento/registrarventa', '/encargo', '/movimiento/registraraccidente', '/movimiento/registrarmerma']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Salidas</span>
                <span class="sidebar__caret" aria-hidden="true"><?= icon('chevron-down', 14) ?></span>
            </summary>
            <a class="sidebar__link<?= $is('/movimiento/registrarventa') ?>" href="<?= BASE_URL ?>/movimiento/registrarVenta">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('tag', 18) ?></span>
                <span class="sidebar__label">Ventas</span>
            </a>
            <a class="sidebar__link<?= $is('/encargo') ?>" href="<?= BASE_URL ?>/encargo/index">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('clock', 18) ?></span>
                <span class="sidebar__label">Encargos</span>
            </a>
            <a class="sidebar__link<?= $is('/movimiento/registraraccidente') ?>" href="<?= BASE_URL ?>/movimiento/registrarAccidente">
                <span class="sidebar__icon sidebar__icon--alert" aria-hidden="true"><?= icon('alert', 18) ?></span>
                <span class="sidebar__label">Accidentes</span>
            </a>
            <a class="sidebar__link<?= $is('/movimiento/registrarmerma') ?>" href="<?= BASE_URL ?>/movimiento/registrarMerma">
                <span class="sidebar__icon sidebar__icon--out" aria-hidden="true"><?= icon('archive', 18) ?></span>
                <span class="sidebar__label">Mermas</span>
            </a>
        </details>

        <details class="sidebar__group"<?= $groupOpen(['/reporte', '/movimiento/historial']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Reportes</span>
                <span class="sidebar__caret" aria-hidden="true"><?= icon('chevron-down', 14) ?></span>
            </summary>
            <a class="sidebar__link<?= $is('/movimiento/historial') ?>" href="<?= BASE_URL ?>/movimiento/historial">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('clock', 18) ?></span>
                <span class="sidebar__label">Historial</span>
            </a>
            <a class="sidebar__link<?= $is('/reporte/stock') ?>" href="<?= BASE_URL ?>/reporte/stock">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('chart', 18) ?></span>
                <span class="sidebar__label">Stock</span>
            </a>
            <a class="sidebar__link<?= $is('/reporte/mermas') ?>" href="<?= BASE_URL ?>/reporte/mermas">
                <span class="sidebar__icon sidebar__icon--alert" aria-hidden="true"><?= icon('alert', 18) ?></span>
                <span class="sidebar__label">Mermas y accidentes</span>
            </a>
            <a class="sidebar__link<?= $is('/reporte/ventas') ?>" href="<?= BASE_URL ?>/reporte/ventas">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('tag', 18) ?></span>
                <span class="sidebar__label">Ventas del día</span>
            </a>
            <a class="sidebar__link<?= $is('/reporte/consolidadoproveedores') ?>" href="<?= BASE_URL ?>/reporte/consolidadoProveedores">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('truck', 18) ?></span>
                <span class="sidebar__label">Consolidado</span>
            </a>
        </details>

        <details class="sidebar__group"<?= $groupOpen(['/auditoria', '/rol', '/usuario']) ? ' open' : '' ?>>
            <summary class="sidebar__section sidebar__section--toggle">
                <span class="sidebar__section-label">Seguridad</span>
                <span class="sidebar__caret" aria-hidden="true"><?= icon('chevron-down', 14) ?></span>
            </summary>
            <a class="sidebar__link<?= $is('/auditoria') ?>" href="<?= BASE_URL ?>/auditoria/index">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('eye', 18) ?></span>
                <span class="sidebar__label">Auditoría</span>
            </a>
            <a class="sidebar__link<?= $is('/usuario') ?>" href="<?= BASE_URL ?>/usuario/index">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('users', 18) ?></span>
                <span class="sidebar__label">Usuarios</span>
            </a>
            <a class="sidebar__link<?= $is('/rol') ?>" href="<?= BASE_URL ?>/rol/index">
                <span class="sidebar__icon" aria-hidden="true"><?= icon('shield', 18) ?></span>
                <span class="sidebar__label">Roles</span>
            </a>
        </details>
    </nav>

    <footer class="sidebar__foot">
        <p class="sidebar__signature">
            Vidrios Centro Puno · v<?= htmlspecialchars(APP_VERSION, ENT_QUOTES, 'UTF-8') ?>
        </p>
    </footer>
</aside>
