<?php
$pageTitle = 'Factura';
require_once BASE_DIR . '/front/views/layout/header.php';

// Ruta del logo
$logoPath = BASE_URL . 'front/public/img/logo nunca jamas.png';
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
                    <tr class="table-success factura-total-row">
                        <td colspan="3" class="text-end"><strong class="h5">TOTAL:</strong></td>
                        <td class="text-end"><strong class="h4"><?php echo pesos($venta['total']); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir Factura
            </button>
            <?php 
            if ($conDomicilio): 
            ?>
                <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=rotuloEnvio&id=<?php echo $venta['id']; ?>" 
                   class="btn btn-info" target="_blank">
                    <i class="bi bi-truck"></i> Imprimir Rótulo de Envío
                </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=ventas" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Nueva Venta
            </a>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&family=Poppins:wght@300;400;600;700&display=swap');

.factura-container {
    max-width: 900px;
    margin: 20px auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.factura-header {
    background: linear-gradient(135deg, #fcd1d1 0%, #aee1e1 100%);
    padding: 30px 40px;
    color: #3c3534;
}

.factura-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.factura-logo-section {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex-shrink: 0;
}

.factura-title-section {
    flex: 1;
    text-align: center;
}

.factura-empresa-data {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    flex-shrink: 0;
    text-align: right;
}

.logo-factura {
    width: 120px;
    height: 120px;
    object-fit: contain;
    background: white;
    border-radius: 100px;
    padding: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}


.factura-empresa-data i {
    margin-right: 8px;
    font-size: 14px;
    opacity: 1;
    vertical-align: middle;
    color: #000000;
}

.factura-empresa-data small {
    color: #000000;
    font-size: 12px;
    display: block;
}

.factura-title {
    font-size: 32px;
    font-weight: 700;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.factura-subtitle {
    margin: 5px 0 0 0;
    font-size: 14px;
    opacity: 0.9;
}

.factura-body {
    padding: 30px 40px;
}

.factura-info-box-compact {
    background: #f4f0ef;
    padding: 15px 20px;
    border-radius: 8px;
    border-left: 3px solid #fcd1d1;
    height: 100%;
    font-size: 14px;
}

.factura-info-title-compact {
    font-size: 13px;
    font-weight: 700;
    color: #fcd1d1;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding-bottom: 8px;
    border-bottom: 2px solid #fcd1d1;
}

.factura-numero-compact {
    font-size: 18px;
    font-weight: 700;
    color: #3c3534;
}

.factura-info-box-compact p {
    margin-bottom: 6px;
    line-height: 1.5;
}

.factura-info-box-compact small {
    font-size: 13px;
    color: #6d6562;
}

.badge-sm {
    font-size: 11px;
    padding: 4px 10px;
}

.factura-table-container {
    margin-top: 30px;
}

.factura-table {
    margin-bottom: 0;
}

.factura-table thead {
    background: linear-gradient(135deg, #fcd1d1 0%, #aee1e1 100%);
    color: #3c3534;
}

.factura-table thead th {
    border: none;
    padding: 15px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.5px;
}

.factura-table tbody td {
    padding: 15px;
    vertical-align: middle;
}

.factura-table tfoot td {
    padding: 15px;
    font-size: 16px;
}

.factura-total-row {
    background: linear-gradient(135deg, #d3e0dc 0%, #aee1e1 100%) !important;
}

.factura-total-row td {
    font-size: 20px;
    padding: 20px 15px !important;
}

.info-domicilio {
    background-color: #aee1e1;
    border: 1px solid #aee1e1;
    border-radius: 0.375rem;
    padding: 1rem;
    color: #3e6464;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

@media print {
    @page {
        margin: 0.5cm;
        size: A4;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    body {
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .navbar, .btn, .card-header, .no-print, .main-container > *:not(.factura-container) {
        display: none !important;
    }
    
    .main-container {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
    
    .factura-container {
        max-width: 100% !important;
        margin: 0 auto !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
        border-radius: 10px !important;
        page-break-after: avoid;
        background: #fff !important;
    }
    
    .factura-header {
        background: linear-gradient(135deg, #fcd1d1 0%, #aee1e1 100%) !important;
        padding: 30px 40px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
        page-break-after: avoid;
    }
    
    .factura-header-top {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 20px !important;
    }
    
    .factura-logo-section {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        flex-shrink: 0 !important;
    }
    
    .logo-factura {
        width: 120px !important;
        height: 120px !important;
        object-fit: contain !important;
        background: white !important;
        border-radius: 100px !important;
        padding: 10px !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        display: block !important;
    }
    
    .factura-title-section {
        flex: 1 !important;
        text-align: center !important;
    }
    
    .factura-empresa-data {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-end !important;
        flex-shrink: 0 !important;
        text-align: right !important;
        color: #000000 !important;
        font-size: 13px !important;
        line-height: 1.8 !important;
    }
    
    .factura-empresa-data i {
        margin-right: 8px !important;
        font-size: 14px !important;
        opacity: 1 !important;
        vertical-align: middle !important;
        display: inline !important;
        color: #000000 !important;
    }
    
    .factura-empresa-data small {
        color: #000000 !important;
        font-size: 12px !important;
        display: block !important;
        text-align: right !important;
    }
    
    .factura-title {
        font-size: 32px !important;
        font-weight: 700 !important;
        margin: 0 !important;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2) !important;
        color: #3c3534 !important;
    }
    
    .factura-subtitle {
        margin: 5px 0 0 0 !important;
        font-size: 14px !important;
        opacity: 0.9 !important;
        color: #3c3534 !important;
    }
    
    .factura-body {
        padding: 30px 40px !important;
        background: #fff !important;
    }
    
    .factura-body {
        padding: 30px 40px !important;
        background: #fff !important;
    }
    
    .row {
        display: flex !important;
        flex-wrap: wrap !important;
        margin-right: -15px !important;
        margin-left: -15px !important;
    }
    
    .col-md-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding-right: 15px !important;
        padding-left: 15px !important;
    }
    
    .factura-info-box-compact {
        background: #f4f0ef !important;
        border-left: 3px solid #fcd1d1 !important;
        padding: 15px 20px !important;
        border-radius: 8px !important;
        height: 100% !important;
        font-size: 14px !important;
        page-break-inside: avoid;
    }
    
    .factura-info-title-compact {
        font-size: 13px !important;
        font-weight: 700 !important;
        color: #fcd1d1 !important;
        margin-bottom: 12px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        padding-bottom: 8px !important;
        border-bottom: 2px solid #fcd1d1 !important;
    }
    
    .factura-numero-compact {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #3c3534 !important;
    }
    
    .factura-info-box-compact p {
        margin-bottom: 6px !important;
        line-height: 1.5 !important;
    }
    
    .factura-info-box-compact small {
        font-size: 13px !important;
        color: #6d6562 !important;
    }
    
    .badge-sm {
        font-size: 11px !important;
        padding: 4px 10px !important;
    }
    
    .badge {
        background-color: #aee1e1 !important;
        color: #000 !important;
        padding: 4px 10px !important;
        border-radius: 0.25rem !important;
        font-size: 11px !important;
        display: inline-block !important;
    }
    
    .badge.bg-success {
        background-color: #97cfcf !important;
        color: #3c3534 !important;
    }
    
    .badge.bg-warning {
        background-color: #5e5552 !important;
        color: #000 !important;
    }
    
    .factura-table-container {
        margin-top: 30px !important;
    }
    
    .factura-table {
        margin-bottom: 0 !important;
    }
    
    .factura-table thead {
        background: linear-gradient(135deg, #fcd1d1 0%, #aee1e1 100%) !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    .factura-table thead th {
        color: #3c3534 !important;
        border: none !important;
        padding: 15px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        font-size: 13px !important;
        letter-spacing: 0.5px !important;
    }
    
    .factura-table tbody td {
        padding: 15px !important;
        vertical-align: middle !important;
    }
    
    .factura-table tfoot td {
        padding: 15px !important;
        font-size: 16px !important;
    }
    
    .factura-total-row {
        background: linear-gradient(135deg, #d3e0dc 0%, #aee1e1 100%) !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    .factura-total-row td {
        font-size: 20px !important;
        padding: 20px 15px !important;
    }
    
    .badge {
        background-color: #aee1e1 !important;
        color: #000 !important;
        padding: 4px 10px !important;
        border-radius: 0.25rem !important;
        font-size: 11px !important;
        display: inline-block !important;
    }
    
    .info-domicilio {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        background-color: #aee1e1 !important;
        border: 1px solid #aee1e1 !important;
        border-radius: 0.375rem !important;
        padding: 1rem !important;
        color: #3e6464 !important;
        page-break-inside: avoid;
    }
}

@media (max-width: 768px) {
    .factura-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .factura-title-section {
        text-align: center;
    }
}
</style>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
