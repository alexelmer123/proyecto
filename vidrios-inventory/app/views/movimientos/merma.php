<?php
/** @var string $motivo */ /** @var array $form */ /** @var array $errores */
/** @var array  $productos */
$accion = BASE_URL . '/movimiento/registrarMerma';
$submit = 'Registrar merma';
?>
<header class="page-head">
    <div>
        <p class="page-head__kicker">Salidas · Mermas</p>
        <h1 class="page-head__title">Registrar merma</h1>
        <p class="page-head__caption">Pérdida por deterioro, vencimiento, robo o cualquier causa no productiva.</p>
    </div>
    <a href="<?= BASE_URL ?>/movimiento/historial?motivo=merma" class="btn btn--ghost">← Mermas</a>
</header>

<?php require __DIR__ . '/_form_salida.php'; ?>
