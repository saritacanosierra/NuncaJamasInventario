<?php
$pageTitle = 'Factura';
require_once BASE_DIR . '/front/views/layout/header.php';

// Ruta del logo
$logoPath = BASE_URL . 'front/public/img/logo-nunca-jamas.jpg';
if (!isset($remitente) || !is_array($remitente)) {
    $remitente = [];
}
$remitente += [
    'nombre' => '',
    'cedula' => '',
    'telefono' => '',
    'direccion' => '',
];
if (!isset($venta) || !is_array($venta)) {
    $venta = [];
}
$venta += [
    'id' => 0,
    'cliente_nombre' => '',
    'cliente_cedula' => '',
    'cliente_direccion' => '',
    'cliente_telefono' => '',
    'vendedor' => '',
    'numero_factura' => '',
    'fecha_venta' => date('Y-m-d H:i:s'),
    'metodo_pago' => '',
    'pago_contra_entrega' => 0,
    'con_domicilio' => 0,
    'observaciones_domicilio' => '',
    'detalles' => [],
    'subtotal' => 0,
    'descuento' => 0,
    'total' => 0,
];
if (!is_array($venta['detalles'])) {
    $venta['detalles'] = [];
}
if (!isset($resolucion) || !is_array($resolucion)) {
    $resolucion = null;
}
$razonFactura = !empty($resolucion['razon_social']) ? $resolucion['razon_social'] : $remitente['nombre'];
$nitFactura = !empty($resolucion['nit']) ? $resolucion['nit'] : $remitente['cedula'];
?>

<div class="factura-container">
    <div class="factura-header">
        <div class="factura-header-top">
            <div class="factura-logo-section">
                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo Nunca Jamás" 
                     class="logo-factura"
                     onerror="this.style.display='none'; this.nextElementSibling.classList.add('d-flex');">
             
            </div>
            <div class="factura-title-section">
                <h1 class="factura-title">FACTURA DE VENTA</h1>
                <p class="factura-subtitle">Ropa Infantil</p>
            </div>
            <div class="factura-empresa-data">
                <p class="mb-1"><small><?php echo htmlspecialchars($razonFactura); ?></small></p>
                <p class="mb-1"><small>NIT: <?php echo htmlspecialchars($nitFactura); ?></small></p>
                <?php if (!empty($resolucion)): ?>
                <p class="mb-1"><small>Resolución <?php echo htmlspecialchars($resolucion['numero']); ?> del <?php echo date('d/m/Y', strtotime($resolucion['fecha_desde'])); ?></small></p>
                <p class="mb-1"><small><?php echo htmlspecialchars($resolucion['prefijo']); ?> del <?php echo (int) $resolucion['desde_numero']; ?> al <?php echo (int) $resolucion['hasta_numero']; ?>, hasta el <?php echo date('d/m/Y', strtotime($resolucion['fecha_hasta'])); ?></small></p>
                <?php endif; ?>
                <p class="mb-1"><small><i class="bi bi-telephone"></i> Teléfono: <?php echo htmlspecialchars($remitente['telefono']); ?></small></p>
                <p class="mb-0"><small><i class="bi bi-geo-alt"></i> <?php echo nl2br(htmlspecialchars($remitente['direccion'])); ?></small></p>
            </div>
        </div>
    </div>
    
    <div class="factura-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="factura-info-box-compact">
                    <h6 class="factura-info-title-compact">DATOS DEL CLIENTE</h6>
                    <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($venta['cliente_nombre']); ?></p>
                    <p class="mb-1"><strong>Cédula/NIT:</strong> <?php echo htmlspecialchars($venta['cliente_cedula']); ?></p>
                    <?php if (!empty($venta['cliente_direccion'])): ?>
                        <p class="mb-1"><strong>Dirección:</strong> <?php echo htmlspecialchars($venta['cliente_direccion']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($venta['cliente_telefono'])): ?>
                        <p class="mb-1"><strong>Teléfono:</strong> <?php echo htmlspecialchars($venta['cliente_telefono']); ?></p>
                    <?php endif; ?>
                    <p class="mb-0"><strong>Vendedor:</strong> <?php echo htmlspecialchars($venta['vendedor']); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="factura-info-box-compact">
                    <h6 class="factura-info-title-compact">INFORMACIÓN DE LA FACTURA</h6>
                    <p class="mb-1"><strong>Número:</strong> <span class="factura-numero-compact"><?php echo htmlspecialchars($venta['numero_factura']); ?></span></p>
                    <p class="mb-1"><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></p>
                    <p class="mb-1"><strong>Método de Pago:</strong> <?php echo htmlspecialchars($venta['metodo_pago']); ?></p>
                    <?php if ((float) ($venta['pago_transferencia'] ?? 0) > 0 && (float) ($venta['pago_efectivo'] ?? 0) > 0): ?>
                    <p class="mb-1"><strong>Transferencia:</strong> <?php echo pesos($venta['pago_transferencia']); ?> · <strong>Efectivo:</strong> <?php echo pesos($venta['pago_efectivo']); ?></p>
                    <?php endif; ?>
                    <?php if ((float) ($venta['recibido'] ?? 0) > 0): ?>
                    <p class="mb-1"><strong>Recibí:</strong> <?php echo pesos($venta['recibido']); ?> · <strong>Devuelta:</strong> <?php echo pesos($venta['devuelta'] ?? 0); ?></p>
                    <?php endif; ?>
                    <?php 
                    $pagoContraEntrega = isset($venta['pago_contra_entrega']) && ($venta['pago_contra_entrega'] == 1 || $venta['pago_contra_entrega'] === '1' || $venta['pago_contra_entrega'] === true);
                    if ($pagoContraEntrega):
                    ?>
                        <p class="mb-1"><strong>Estado de Pago:</strong> <span class="badge bg-warning badge-sm">Pago Contra Entrega</span></p>
                    <?php else: ?>
                        <p class="mb-1"><strong>Estado de Pago:</strong> <span class="badge bg-success badge-sm">Pagado</span></p>
                    <?php endif; ?>
                    <?php 
                    $conDomicilio = isset($venta['con_domicilio']) && ($venta['con_domicilio'] == 1 || $venta['con_domicilio'] === '1' || $venta['con_domicilio'] === true);
                    if ($conDomicilio): 
                    ?>
                        <p class="mb-1"><strong>Domicilio:</strong> <span class="badge bg-info badge-sm">Con Domicilio</span></p>
                        <?php if (!empty($venta['observaciones_domicilio'])): ?>
                            <p class="mb-0"><strong>Observaciones:</strong> <small><?php echo htmlspecialchars($venta['observaciones_domicilio']); ?></small></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="factura-table-container">
            <table class="table table-bordered factura-table">
                <thead>
                    <tr>
                        <th>PRODUCTO</th>
                        <th>CANTIDAD</th>
                        <th>PRECIO UNIT.</th>
                        <th>SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($venta['detalles'] as $detalle): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($detalle['producto_nombre']); ?></strong><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($detalle['color']); ?> - 
                                    <?php echo htmlspecialchars($detalle['talla']); ?>
                                </small>
                            </td>
                            <td class="text-center"><?php echo $detalle['cantidad']; ?></td>
                            <td class="text-end"><?php echo pesos($detalle['precio_unitario']); ?></td>
                            <td class="text-end"><?php echo pesos($detalle['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php $domicilioPorCobrar = ((float) ($venta['domicilio'] ?? 0) > 0) && (!empty($venta['domicilio_contra_entrega']) || !empty($venta['pago_contra_entrega'])); ?>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                        <td class="text-end"><strong><?php echo pesos($venta['subtotal']); ?></strong></td>
                    </tr>
                    <?php if ($venta['descuento'] > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Descuento:</strong></td>
                            <td class="text-end"><strong><?php echo pesos(-($venta['descuento'])); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ((float) ($venta['iva'] ?? 0) > 0): ?>
                        <?php
                        $ivaSumado = round((float) $venta['subtotal'] - (float) $venta['descuento'] + (float) $venta['iva'] + (float) ($venta['domicilio'] ?? 0) + (float) ($venta['empaque'] ?? 0));
                        $ivaYaIncluido = abs($ivaSumado - round((float) $venta['total'])) > 1;
                        ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong><?php echo $ivaYaIncluido ? 'IVA incluido:' : 'IVA:'; ?></strong></td>
                            <td class="text-end"><strong><?php echo pesos($venta['iva']); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ((float) ($venta['domicilio'] ?? 0) > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong><?php echo $domicilioPorCobrar ? 'Domicilio (contra entrega):' : 'Domicilio:'; ?></strong></td>
                            <td class="text-end"><strong><?php echo pesos($venta['domicilio']); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ((float) ($venta['empaque'] ?? 0) > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Empaque:</strong></td>
                            <td class="text-end"><strong><?php echo pesos($venta['empaque']); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="table-success factura-total-row">
                        <td colspan="3" class="text-end"><strong class="h5">TOTAL:</strong></td>
                        <td class="text-end"><strong class="h4"><?php echo pesos($venta['total']); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="factura-acciones no-print">
            <div class="factura-acciones-fila">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Imprimir factura
                </button>
                <?php if ($conDomicilio): ?>
                <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=rotuloEnvio&id=<?php echo $venta['id']; ?>" class="btn btn-info" target="_blank">
                    <i class="bi bi-truck"></i> Imprimir rótulo
                </a>
                <?php endif; ?>
            </div>
            <a href="<?php echo BASE_URL; ?>index.php?action=ventas" class="btn btn-secondary factura-accion-volver">
                <i class="bi bi-arrow-left"></i> Nueva venta
            </a>
        </div>
    </div>
</div>


<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
