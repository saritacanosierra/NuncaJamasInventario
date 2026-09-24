<?php
$pageTitle = 'Kardex';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($producto) || !is_array($producto)) {
    $producto = null;
}
$nombresTipo = [
    'venta' => 'Venta',
    'compra' => 'Compra',
    'cambio_sale' => 'Cambio, vuelve al inventario',
    'cambio_entra' => 'Cambio, sale del inventario',
    'anulacion' => 'Anulación',
];
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <h2 class="mb-0"><i class="bi bi-journal-text"></i> Kardex</h2>
        <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>index.php?action=compras">
            <i class="bi bi-arrow-left"></i> Compras
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="row g-3">
                <input type="hidden" name="action" value="compras">
                <input type="hidden" name="method" value="kardex">
                <div class="col-md-4">
                    <label class="form-label" for="producto_id">Id de la prenda</label>
                    <input type="number" class="form-control" name="producto_id" id="producto_id" min="0"
                           value="<?php echo is_array($producto) ? (int) $producto['id'] : 0; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Ver</button>
                </div>
                <?php if (is_array($producto)): ?>
                <div class="col-12">
                    <p class="mb-0"><?php echo htmlspecialchars($producto['nombre'] . ' · talla ' . $producto['talla'] . ' · stock ' . $producto['stock']); ?></p>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Prenda</th>
                            <th>Movimiento</th>
                            <th>Cantidad</th>
                            <th>Quedó</th>
                            <th>Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                        <tr><td colspan="6" class="text-center text-muted">No hay movimientos<?php echo $producto ? ' de esta prenda' : ''; ?>. Las ventas y compras nuevas quedan aquí.</td></tr>
                        <?php else: ?>
                        <?php foreach ($movimientos as $movimiento): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($movimiento['creado_en'])); ?></td>
                            <td><?php echo htmlspecialchars($movimiento['producto_nombre'] . ' ' . $movimiento['talla']); ?></td>
                            <td><?php echo htmlspecialchars($nombresTipo[$movimiento['tipo']] ?? $movimiento['tipo']); ?></td>
                            <td><?php echo (int) $movimiento['cantidad'] > 0 ? '+' . (int) $movimiento['cantidad'] : (int) $movimiento['cantidad']; ?></td>
                            <td><?php echo (int) $movimiento['stock_nuevo']; ?></td>
                            <td><?php echo htmlspecialchars($movimiento['nota'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
