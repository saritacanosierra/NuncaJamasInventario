<?php
$pageTitle = 'Historial de Compras';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($cliente) || !is_array($cliente)) {
    $cliente = [
        'id' => 0,
        'nombre_completo' => '',
        'cedula_nit' => '',
        'telefono' => '',
    ];
}
if (!isset($saldoFiado) || !is_numeric($saldoFiado)) {
    $saldoFiado = 0;
}
if (!isset($cuentasFiado) || !is_array($cuentasFiado)) {
    $cuentasFiado = [];
}
if (!isset($facturasAbiertas) || !is_array($facturasAbiertas)) {
    $facturasAbiertas = [];
}
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
            <?php if (tienePermiso('clientes_fiado:view')): ?>
            <p class="mb-0 mt-2"><strong>Debe:</strong> <?php echo pesos($saldoFiado); ?></p>
            <?php if ($saldoFiado > 0 && !empty($enlaceFiado)): ?>
            <a class="btn btn-primary mt-3" href="<?php echo htmlspecialchars($enlaceFiado); ?>" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> Avisar por WhatsApp
            </a>
            <?php elseif ($saldoFiado > 0): ?>
            <p class="mb-0 mt-2">Agrega un celular de 10 dígitos para avisarle el saldo.</p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (tienePermiso('clientes_fiado:view') && (int) $cliente['id'] !== 1): ?>
    <div class="card mb-4" id="abonos">
        <div class="card-header"><h5 class="mb-0">Abonos</h5></div>
        <div class="card-body">
            <?php if (tienePermiso('clientes_fiado:create') && $saldoFiado > 0): ?>
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=clientes&method=abonar" class="row g-3 mb-3">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="cliente_id" value="<?php echo (int) $cliente['id']; ?>">
                <div class="col-md-3">
                    <label class="form-label" for="venta_id">Factura</label>
                    <select class="form-select" id="venta_id" name="venta_id" required>
                        <?php foreach ($facturasAbiertas as $factura): ?>
                        <option value="<?php echo (int) $factura['id']; ?>">
                            <?php echo htmlspecialchars($factura['numero_factura']); ?> · falta <?php echo pesos($factura['saldo']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="monto">Abono</label>
                    <input type="number" class="form-control" id="monto" name="monto" min="1" step="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="nota">Nota</label>
                    <input type="text" class="form-control" id="nota" name="nota">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Registrar abono</button>
                </div>
            </form>
            <?php endif; ?>
            <?php if (empty($cuentasFiado)): ?>
            <p class="mb-0 text-muted">Este cliente no tiene facturas fiadas.</p>
            <?php else: ?>
                <?php foreach ($cuentasFiado as $cuenta): ?>
                <div class="mb-3">
                    <strong><?php echo htmlspecialchars($cuenta['numero_factura']); ?></strong>
                    · total <?php echo pesos($cuenta['total']); ?>
                    · abonado <?php echo pesos($cuenta['abonado']); ?>
                    · falta <?php echo pesos($cuenta['saldo']); ?>
                    <?php if (empty($cuenta['abonos'])): ?>
                    <p class="mb-0 text-muted">Sin abonos en esta factura.</p>
                    <?php else: ?>
                    <ul class="mb-0">
                        <?php foreach ($cuenta['abonos'] as $abono): ?>
                        <li><?php echo date('d/m/Y', strtotime($abono['creado_en'])); ?> · <?php echo pesos($abono['monto']); ?><?php echo $abono['nota'] !== '' && $abono['nota'] !== null ? ' · ' . htmlspecialchars($abono['nota']) : ''; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
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
                                <th>Abonado</th>
                                <th>Falta</th>
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
                                    <?php
                                    $cuenta = $cuentasFiado[$compra['id']] ?? null;
                                    $debe = $cuenta && $cuenta['saldo'] > 0;
                                    ?>
                                    <?php if (!$debe): ?>
                                    <td colspan="2"><span class="badge tono-teal">Pagado</span></td>
                                    <?php else: ?>
                                    <td>
                                        <span class="badge tono-rosa"><?php echo pesos($cuenta['abonado']); ?></span>
                                    </td>
                                    <td><span class="badge tono-rosa"><?php echo pesos($cuenta['saldo']); ?></span></td>
                                    <?php endif; ?>
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

