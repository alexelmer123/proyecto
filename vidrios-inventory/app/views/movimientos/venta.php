<?php
/** @var string $motivo */ /** @var array $form */ /** @var array $errores */
/** @var array  $productos */
$accion = BASE_URL . '/movimiento/registrarVenta';
$submit = 'Registrar venta';
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Salidas · Ventas</p>
        <h1 class="page-head__title">Registrar venta</h1>
        <p class="page-head__caption">Venta directa de un producto al cliente. Descuenta el stock.</p>
    </div>
    <a href="<?= BASE_URL ?>/movimiento/historial?motivo=venta" class="btn btn--ghost">← Ventas registradas</a>
</header>

<?php require __DIR__ . '/_form_salida.php'; ?>
