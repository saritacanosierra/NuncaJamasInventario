<?php
$pageTitle = 'Quienes deben';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($deudores) || !is_array($deudores)) {
    $deudores = [];
}
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-wallet2"></i> Quienes deben</h2>
            <p class="mb-0 text-muted">Cada fila es una factura fiada. El abono queda asociado a esa factura.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>index.php?action=clientes" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Clientes
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php require BASE_DIR . '/front/views/components/tabla_deudas.php'; ?>
        </div>
    </div>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
