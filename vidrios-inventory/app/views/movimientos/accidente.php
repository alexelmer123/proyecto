<?php
/** @var string $motivo */ /** @var array $form */ /** @var array $errores */
/** @var array  $productos */
$accion = BASE_URL . '/movimiento/registrarAccidente';
$submit = 'Registrar accidente';
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Salidas · Accidentes</p>
        <h1 class="page-head__title">Registrar accidente</h1>
        <p class="page-head__caption">Rotura, daño u otro siniestro que retira producto del inventario.</p>
    </div>
    <a href="<?= BASE_URL ?>/movimiento/historial?motivo=accidente" class="btn btn--ghost">← Accidentes</a>
</header>

<?php require __DIR__ . '/_form_salida.php'; ?>
