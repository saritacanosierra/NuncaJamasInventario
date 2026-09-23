<?php
$pageTitle = 'Historial de Compras';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-clock-history"></i> Historial de Compras</h2>
        <a href="<?php echo BASE_URL; ?>index.php?action=clientes" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <h4><?php echo htmlspecialchars($cliente['nombre_completo']); ?></h4>
            <p class="mb-0"><strong>Cédula/NIT:</strong> <?php echo htmlspecialchars($cliente['cedula_nit']); ?></p>
            <p class="mb-0"><strong>Teléfono:</strong> <?php echo htmlspecialchars($cliente['telefono'] ?? 'N/A'); ?></p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($compras)): ?>
                <p class="text-center text-muted">Este cliente no tiene compras registradas</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Fecha</th>
                                <th>Vendedor</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($compras as $compra): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($compra['numero_factura']); ?></code></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($compra['fecha_venta'])); ?></td>
                                    <td><?php echo htmlspecialchars($compra['vendedor']); ?></td>
                                    <td><strong><?php echo pesos($compra['total']); ?></strong></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=factura&id=<?php echo $compra['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Ver Factura
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

