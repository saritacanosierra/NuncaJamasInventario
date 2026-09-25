<?php
$pageTitle = 'Caja';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($fecha) || !is_string($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}
if (!isset($cerrado)) {
    $cerrado = null;
}
if (!isset($resumen) || !is_array($resumen)) {
    $resumen = [
        'ventas_efectivo' => 0,
        'gastos' => 0,
        'compras_caja' => 0,
        'ventas_otros' => 0,
        'ventas_fiado' => 0,
        'ventas_contra_entrega' => 0,
    ];
}
$cajaDestino = 'caja';
$fechaCaja = $fecha;
$cierreRegistro = $cerrado;
$cierreResumen = $resumen;
$cierreHistorial = $historial ?? [];
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <h2 class="mb-0"><i class="bi bi-safe"></i> Cierre de caja<?php $ayuda = 'Cuenta el efectivo de un día y ciérralo una sola vez. Sigue los cuatro pasos. El fiado y la contra entrega no están en el cajón.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <?php if (puedeVerModulo('gastos')): ?>
        <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>index.php?action=gastos">
            <i class="bi bi-arrow-left"></i> Gastos
        </a>
        <?php endif; ?>
    </div>
    <p class="text-muted mb-4">Cierra el cajón de un día, una sola vez. Sigue los pasos en orden.</p>

    <?php require BASE_DIR . '/front/views/components/cierre_caja.php'; ?>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
