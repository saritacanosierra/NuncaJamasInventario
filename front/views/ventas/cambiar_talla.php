<?php
$pageTitle = 'Cambio de talla';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($venta) || !is_array($venta)) {
    $venta = [
        'id' => 0,
        'numero_factura' => '',
        'cliente_nombre' => '',
        'detalles' => [],
    ];
}
if (!isset($venta['detalles']) || !is_array($venta['detalles'])) {
    $venta['detalles'] = [];
}
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-arrow-left-right"></i> Cambio de talla<?php $ayuda = 'La talla vendida vuelve al inventario y sale la talla nueva. El valor de la factura no cambia.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=historial" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Historial
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-1"><strong>Factura:</strong> <?php echo htmlspecialchars($venta['numero_factura']); ?></p>
            <p class="mb-0"><strong>Cliente:</strong> <?php echo htmlspecialchars($venta['cliente_nombre']); ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=ventas&method=cambiarTalla&id=<?php echo (int) $venta['id']; ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="venta_id" value="<?php echo (int) $venta['id']; ?>">
                <input type="hidden" name="producto_entra_id" id="producto_entra_id" value="">
                <input type="hidden" name="talla_entra_id" id="talla_entra_id" value="">
                <div class="mb-3">
                    <label class="form-label" for="detalle_id">Prenda vendida</label>
                    <select class="form-select" name="detalle_id" id="detalle_id" required>
                        <?php foreach ($venta['detalles'] as $detalle): ?>
                        <option value="<?php echo (int) $detalle['id']; ?>" data-cantidad="<?php echo (int) $detalle['cantidad']; ?>">
                            <?php echo htmlspecialchars($detalle['producto_nombre'] . ' · talla ' . $detalle['talla'] . ' · ' . $detalle['cantidad']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cantidad">Cantidad a cambiar</label>
                    <input type="number" class="form-control" name="cantidad" id="cantidad" min="1" value="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="buscar_talla">Talla nueva</label>
                    <input type="text" class="form-control" id="buscar_talla" placeholder="Nombre o código de la prenda que se lleva">
                    <div id="resultados_talla" class="mt-2"></div>
                    <p class="mt-2 mb-0" id="talla_elegida">Todavía no eliges la talla nueva.</p>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Cambiar talla</button>
            </form>
        </div>
    </div>
</div>

<script>window.BASE_URL = <?php echo json_encode(BASE_URL); ?>;</script>
<script src="<?php echo BASE_URL; ?>front/public/js/ventas-cambio.js?v=2"></script>
<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
