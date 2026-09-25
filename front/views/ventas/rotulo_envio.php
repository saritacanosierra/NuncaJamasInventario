<?php
$pageTitle = 'Rótulo de Envío';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($venta) || !is_array($venta)) {
    $venta = [];
}
$venta += [
    'cliente_nombre' => '',
    'cliente_cedula' => '',
    'cliente_telefono' => '',
    'cliente_direccion' => '',
    'pago_contra_entrega' => 0,
    'metodo_pago' => '',
    'domicilio' => 0,
    'domicilio_contra_entrega' => 0,
    'observaciones_domicilio' => '',
];
if (!isset($remitente) || !is_array($remitente)) {
    $remitente = [];
}
$remitente += [
    'nombre' => '',
    'cedula' => '',
    'telefono' => '',
    'direccion' => '',
];

// Obtener dirección del cliente
$clienteDireccion = $venta['cliente_direccion'] ?? '';

// Determinar si está pagado basado en el campo pago_contra_entrega
$pagoContraEntrega = isset($venta['pago_contra_entrega']) && ($venta['pago_contra_entrega'] == 1 || $venta['pago_contra_entrega'] === '1' || $venta['pago_contra_entrega'] === true);
$esFiado = strcasecmp((string) ($venta['metodo_pago'] ?? ''), 'Fiado') === 0;
$pagado = !$pagoContraEntrega && !$esFiado;
$domicilioContra = !empty($venta['domicilio_contra_entrega']);

// Ruta del logo
$logoPath = BASE_URL . 'front/public/img/logo-nunca-jamas.jpg';
?>


<div class="rotulo-container">
    <div class="decorative-elements shape-1"></div>
    <div class="decorative-elements shape-2"></div>
    
    <div class="rotulo-header">
        <div class="logo-container">
            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo Nunca Jamás" class="logo-img">
        </div>
        <h1 class="titulo-rotulo">Datos de Envío</h1>
    </div>
    
    <div class="rotulo-body">
        <div class="datos-destinatario">
            <h2 class="section-title">Destinatario</h2>
            
            <div class="dato-item">
                <span class="dato-label">Nombre</span>
                <div class="dato-value"><?php echo htmlspecialchars($venta['cliente_nombre']); ?></div>
            </div>
            
            <div class="dato-item">
                <span class="dato-label">Dirección</span>
                <div class="dato-value"><?php echo htmlspecialchars($clienteDireccion ?: 'No especificada'); ?></div>
            </div>
            
            <div class="dato-item">
                <span class="dato-label">Cédula</span>
                <div class="dato-value"><?php echo htmlspecialchars($venta['cliente_cedula']); ?></div>
            </div>
            
            <div class="dato-item">
                <span class="dato-label">Teléfono</span>
                <div class="dato-value"><?php echo htmlspecialchars($venta['cliente_telefono'] ?: 'No especificado'); ?></div>
            </div>
            
            <?php if (!$esFiado): ?>
            <div class="estado-pago">
                <div class="estado-pago-title">Estado de Pago</div>
                <?php if ($pagado && $domicilioContra): ?>
                <div class="estado-sello estado-sello-pendiente">Pedido pagado. Cobrar el domicilio</div>
                <?php elseif ($pagado): ?>
                <div class="checkbox-pago">
                    <input type="checkbox" id="pagado" checked disabled>
                    <label for="pagado"><?php echo ((float) ($venta['domicilio'] ?? 0) > 0) ? 'Pagado, con el domicilio' : 'Pagado. Recogida o solo el producto'; ?></label>
                </div>
                <?php else: ?>
                <div class="estado-sello estado-sello-pendiente"><?php echo ((float) ($venta['domicilio'] ?? 0) > 0) ? 'Cobrar el pedido y el domicilio' : 'Cobrar el pedido'; ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($domicilioContra || !empty($venta['observaciones_domicilio'])): ?>
            <div class="estado-pago">
                <div class="estado-pago-title">Para el domiciliario</div>
                <?php if (!empty($venta['observaciones_domicilio'])): ?>
                <p class="mb-0"><?php echo htmlspecialchars($venta['observaciones_domicilio']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="datos-remitente">
            <h2 class="section-title section-title-remitente">Remitente</h2>
            
            <div class="remitente-item">
                <div class="remitente-icon">👤</div>
                <div class="remitente-text"><?php echo htmlspecialchars($remitente['nombre']); ?></div>
            </div>
            
            <div class="remitente-item">
                <div class="remitente-icon">📄</div>
                <div class="remitente-text"><?php echo htmlspecialchars($remitente['cedula']); ?></div>
            </div>
            
            <div class="remitente-item">
                <div class="remitente-icon">📞</div>
                <div class="remitente-text"><?php echo htmlspecialchars($remitente['telefono']); ?></div>
            </div>
            
            <div class="remitente-direccion">
                <strong>Dirección:</strong><br>
                <?php echo htmlspecialchars($remitente['direccion']); ?>
            </div>
        </div>
    </div>
</div>

<button class="btn-print-rotulo no-print" onclick="window.print()">
    <i class="bi bi-printer"></i> Imprimir Rótulo
</button>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
