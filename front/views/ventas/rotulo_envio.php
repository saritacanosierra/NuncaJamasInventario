<?php
$pageTitle = 'Rótulo de Envío';
require_once BASE_DIR . '/front/views/layout/header.php';

// Obtener dirección del cliente
$clienteDireccion = $venta['cliente_direccion'] ?? '';

// Determinar si está pagado basado en el campo pago_contra_entrega
$pagoContraEntrega = isset($venta['pago_contra_entrega']) && ($venta['pago_contra_entrega'] == 1 || $venta['pago_contra_entrega'] === '1' || $venta['pago_contra_entrega'] === true);
$pagado = !$pagoContraEntrega;

// Ruta del logo
$logoPath = BASE_URL . 'front/public/img/logo nunca jamas.png';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&family=Poppins:wght@300;400;600;700&display=swap');

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    font-family: 'Poppins', sans-serif;
    padding: 20px;
}

.rotulo-container {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    overflow: hidden;
    position: relative;
}

.rotulo-header {
    background: linear-gradient(135deg, #FF69B4 0%, #FFB6C1 100%);
    padding: 30px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.rotulo-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    z-index: 1;
}

.logo-img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    background: white;
    border-radius: 100px;
    padding: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.logo-text {
    font-family: 'Dancing Script', cursive;
    font-size: 42px;
    font-weight: 700;
    color: #fff;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.titulo-rotulo {
    font-family: 'Dancing Script', cursive;
    font-size: 48px;
    color: #fff;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    position: relative;
    z-index: 1;
}

.rotulo-body {
    padding: 40px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.datos-destinatario {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 30px;
    border-radius: 15px;
    border: 2px solid #FF69B4;
    box-shadow: 0 4px 15px rgba(255, 105, 180, 0.1);
}

.datos-remitente {
    background: linear-gradient(135deg, #fff5f7 0%, #ffeef2 100%);
    padding: 30px;
    border-radius: 15px;
    border: 2px solid #FFB6C1;
    box-shadow: 0 4px 15px rgba(255, 182, 193, 0.1);
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #FF69B4;
    margin-bottom: 25px;
    padding-bottom: 10px;
    border-bottom: 2px solid #FF69B4;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.dato-item {
    margin-bottom: 20px;
}

.dato-label {
    font-size: 11px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
    display: block;
}

.dato-value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    padding: 8px 0;
    border-bottom: 2px dotted #ddd;
    min-height: 35px;
    display: flex;
    align-items: center;
}

.estado-pago {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #FF69B4;
}

.estado-pago-title {
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 15px;
    letter-spacing: 0.5px;
}

.checkbox-pago {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.checkbox-pago input[type="checkbox"] {
    width: 28px;
    height: 28px;
    border: 3px solid #FF69B4;
    border-radius: 6px;
    cursor: pointer;
    appearance: none;
    position: relative;
    background: #fff;
    transition: all 0.3s ease;
}

.checkbox-pago input[type="checkbox"]:checked {
    background: #FF69B4;
    border-color: #FF69B4;
}

.checkbox-pago input[type="checkbox"]:checked::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 20px;
    font-weight: bold;
}

.checkbox-pago label {
    font-weight: 700;
    color: #333;
    font-size: 16px;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.remitente-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
}

.remitente-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #FF69B4;
    color: white;
    border-radius: 50%;
    font-size: 16px;
    flex-shrink: 0;
}

.remitente-text {
    color: #333;
    font-size: 15px;
    font-weight: 500;
}

.remitente-direccion {
    margin-top: 15px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
    color: #555;
    font-size: 14px;
    line-height: 1.6;
    border-left: 4px solid #FF69B4;
}

.decorative-elements {
    position: absolute;
    z-index: 0;
    pointer-events: none;
}

.shape-1 {
    top: 20px;
    right: 20px;
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    border-radius: 20px;
    transform: rotate(15deg);
    opacity: 0.15;
}

.shape-2 {
    bottom: 20px;
    left: 20px;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #87CEEB 0%, #4682B4 100%);
    border-radius: 50%;
    opacity: 0.15;
}

.btn-print-rotulo {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    background: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
    border: none;
    color: white;
    padding: 18px 30px;
    border-radius: 50px;
    box-shadow: 0 6px 25px rgba(255, 105, 180, 0.4);
    font-weight: 600;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s ease;
}

.btn-print-rotulo:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(255, 105, 180, 0.5);
}

@media print {
    @page {
        margin: 0;
        size: A4;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
        padding: 20px !important;
        margin: 0;
    }
    
    .navbar, .btn, .card-header, .no-print, .btn-print-rotulo {
        display: none !important;
    }
    
    .rotulo-container {
        box-shadow: 0 10px 40px rgba(0,0,0,0.1) !important;
        border-radius: 20px !important;
        max-width: 900px !important;
        margin: 0 auto !important;
        background: #fff !important;
        page-break-inside: avoid;
    }
    
    .rotulo-header {
        background: linear-gradient(135deg, #FF69B4 0%, #FFB6C1 100%) !important;
        padding: 30px 40px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .rotulo-body {
        padding: 40px !important;
        gap: 40px !important;
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
    }
    
    .datos-destinatario {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        border: 2px solid #FF69B4 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .datos-remitente {
        background: linear-gradient(135deg, #fff5f7 0%, #ffeef2 100%) !important;
        border: 2px solid #FFB6C1 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .decorative-elements {
        display: block !important;
        opacity: 0.15 !important;
    }
    
    .logo-img {
        background: white !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    }
    
    .checkbox-pago input[type="checkbox"]:checked {
        background: #FF69B4 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .remitente-icon {
        background: #FF69B4 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

@media (max-width: 768px) {
    .rotulo-body {
        grid-template-columns: 1fr;
    }
    
    .rotulo-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .logo-container {
        justify-content: center;
    }
}
</style>

<div class="rotulo-container">
    <div class="decorative-elements shape-1"></div>
    <div class="decorative-elements shape-2"></div>
    
    <div class="rotulo-header">
        <div class="logo-container">
            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo Nunca Jamás" class="logo-img" 
                 onerror="this.style.display='none'; this.nextElementSibling.classList.add('d-flex');">
            <div class="logo-fallback">
                <span class="logo-fallback-rotulo-icon">⭐</span>
                <span class="logo-text">Nunca Jamás</span>
            </div>
            <span class="logo-text d-none">Nunca Jamás</span>
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
            
            <div class="estado-pago">
                <div class="estado-pago-title">Estado de Pago</div>
                <div class="checkbox-pago">
                    <input type="checkbox" id="pagado" <?php echo $pagado ? 'checked' : ''; ?> disabled>
                    <label for="pagado">Pagado</label>
                </div>
                <div class="checkbox-pago">
                    <input type="checkbox" id="por-pagar" <?php echo !$pagado ? 'checked' : ''; ?> disabled>
                    <label for="por-pagar">Por Pagar</label>
                </div>
            </div>
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
