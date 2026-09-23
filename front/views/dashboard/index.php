<?php
$pageTitle = 'Dashboard';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<!-- Verificar carga de CSS -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>front/public/css/dashboard.css?v=<?php echo time(); ?>">

<div class="main-container">
    <h2 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h2>
    
    <!-- Métricas principales -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="metric-card metric-card-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2">Inventario Total</h6>
                        <h3 class="mb-0"><?php echo pesos($inventario_total ?? 0); ?></h3>
                    </div>
                    <i class="bi bi-box-seam display-6"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card metric-card-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2">Productos por Agotarse</h6>
                        <h3 class="mb-0"><?php echo count($productos_agotarse ?? []); ?></h3>
                    </div>
                    <i class="bi bi-exclamation-triangle display-6"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card metric-card-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2">Ventas del Mes</h6>
                        <h3 class="mb-0"><?php echo pesos($ventas_mes['total_ingresos'] ?? 0); ?></h3>
                    </div>
                    <i class="bi bi-cart-check display-6"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card metric-card-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2">Gastos del Mes</h6>
                        <h3 class="mb-0"><?php echo pesos($gastos_mes['total'] ?? 0); ?></h3>
                    </div>
                    <i class="bi bi-cash-stack display-6"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas de ventas -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info">
                    <i class="bi bi-calendar-day"></i> Ventas del Día
                </div>
                <div class="card-body">
                    <h4><?php echo pesos($ventas_dia['total_ingresos'] ?? 0); ?></h4>
                    <p class="mb-0 text-muted"><?php echo $ventas_dia['total_ventas'] ?? 0; ?> ventas</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info">
                    <i class="bi bi-calendar-week"></i> Ventas de la Semana
                </div>
                <div class="card-body">
                    <h4><?php echo pesos($ventas_semana['total_ingresos'] ?? 0); ?></h4>
                    <p class="mb-0 text-muted"><?php echo $ventas_semana['total_ventas'] ?? 0; ?> ventas</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info">
                    <i class="bi bi-graph-up"></i> Promedio por Venta
                </div>
                <div class="card-body">
                    <h4><?php echo pesos($ventas_mes['promedio_venta'] ?? 0); ?></h4>
                    <p class="mb-0 text-muted">Mes actual</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Punto de equilibrio -->
    <div class="row g-3 mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary">
                    <i class="bi bi-calculator"></i> Punto de Equilibrio
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Gastos Totales:</strong><br>
                            <span class="h5"><?php echo pesos($punto_equilibrio['gastos'] ?? 0); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Margen Promedio:</strong><br>
                            <span class="h5"><?php echo number_format($punto_equilibrio['margen_promedio'] ?? 0, 1); ?>%</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Punto de Equilibrio:</strong><br>
                            <span class="h5"><?php echo pesos($punto_equilibrio['punto_equilibrio'] ?? 0); ?></span>
                        </div>
                            <div class="col-md-3">
                                <strong>Ventas Actuales del Mes:</strong><br>
                                <span class="h5">
                                    <?php echo pesos($punto_equilibrio['ventas_actuales'] ?? 0); ?>
                                </span>
                                <?php 
                                $ventasActuales = $punto_equilibrio['ventas_actuales'] ?? 0;
                                $puntoEquilibrio = $punto_equilibrio['punto_equilibrio'] ?? 0;
                                $porcentaje = $puntoEquilibrio > 0 ? ($ventasActuales / $puntoEquilibrio) * 100 : 0;
                                ?>
                                <br>
                                <small class="text-muted">
                                    <?php echo number_format($porcentaje, 1); ?>% del punto de equilibrio
                                </small>
                            </div>
                    </div>
                    <hr>
                    <div class="row ganancia-par">
                        <div class="col-md-6">
                            <div class="p-3 rounded ganancia-box <?php echo ($punto_equilibrio['ganancia_actual'] ?? 0) >= 0 ? 'ganancia-positiva' : 'ganancia-negativa'; ?>">
                                <strong><i class="bi bi-cash-coin"></i> <?php echo ($punto_equilibrio['ganancia_actual'] ?? 0) >= 0 ? 'Ganancia' : 'Pérdida'; ?> Actual:</strong><br>
                                <span class="h4 <?php echo ($punto_equilibrio['ganancia_actual'] ?? 0) >= 0 ? 'ganancia-valor-positiva' : 'ganancia-valor-negativa'; ?>">
                                    <?php 
                                    $gananciaActual = $punto_equilibrio['ganancia_actual'] ?? 0;
                                    if ($gananciaActual < 0) {
                                        echo pesos(-(abs($gananciaActual)));
                                    } else {
                                        echo pesos($gananciaActual);
                                    }
                                    ?>
                                </span>
                                <br>
                                <small class="text-muted texto-desglose">
                                    <strong>Desglose del cálculo:</strong><br>
                                    <?php 
                                    $ventasActuales = $punto_equilibrio['ventas_actuales'] ?? 0;
                                    $contribucion = $punto_equilibrio['contribucion_actual'] ?? 0;
                                    $gastos = $punto_equilibrio['gastos'] ?? 0;
                                    $puntoEquilibrio = $punto_equilibrio['punto_equilibrio'] ?? 0;
                                    $faltante = max(0, $puntoEquilibrio - $ventasActuales);
                                    ?>
                                    • Ventas actuales del mes: <strong><?php echo pesos($ventasActuales); ?></strong><br>
                                    • Contribución (<?php echo number_format($punto_equilibrio['margen_promedio'] ?? 0, 1); ?>% de ventas): <strong><?php echo pesos($contribucion); ?></strong><br>
                                    • Gastos del mes: <strong><?php echo pesos($gastos); ?></strong><br>
                                    • <strong>Resultado:</strong> <?php echo ($gananciaActual >= 0) ? 'Ganancia' : 'Pérdida'; ?> de <strong><?php echo pesos(abs($gananciaActual)); ?></strong><br>
                                    <?php if ($ventasActuales < $puntoEquilibrio): ?>
                                    <span class="alerta-faltante">
                                        ⚠️ Faltan <?php echo pesos($faltante); ?> para llegar al punto de equilibrio
                                    </span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded ganancia-proyeccion">
                                <?php 
                                $gastos = $punto_equilibrio['gastos'] ?? 0;
                                $ventasMes = $punto_equilibrio['ventas_actuales'] ?? 0;
                                $gananciaNeta = $ventasMes - $gastos;
                                $esPositivo = $gananciaNeta >= 0;
                                ?>
                                <strong><i class="bi bi-<?php echo $esPositivo ? 'trophy' : 'target'; ?>"></i> <?php echo $esPositivo ? 'Ganancia Neta Actual' : 'Te falta para cubrir gastos'; ?>:</strong><br>
                                <span class="h4 ganancia-proyeccion-valor <?php echo $esPositivo ? '' : 'ganancia-valor-negativa'; ?>">
                                    <?php echo pesos(abs($gananciaNeta)); ?>
                                </span>
                                <br>
                                <small class="text-muted texto-desglose">
                                    <strong>Desglose:</strong><br>
                                    • Ventas del mes: <strong><?php echo pesos($ventasMes); ?></strong><br>
                                    • Gastos del mes: <strong><?php echo pesos($gastos); ?></strong><br>
                                    • Resultado: <strong><?php echo pesos($gananciaNeta); ?></strong>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficas -->
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart"></i> Ventas por Día (Últimos 30 días)
                </div>
                <div class="card-body">
                    <canvas id="ventasChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-exclamation-triangle"></i> Productos con Stock Bajo
                </div>
                <div class="card-body card-body-scroll-400">
                    <?php if (empty($productos_agotarse ?? [])): ?>
                        <p class="text-center text-muted">No hay productos con stock bajo</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($productos_agotarse as $producto): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong><br>
                                    <small class="text-muted">
                                        Stock: <?php echo $producto['stock']; ?> | 
                                        Mínimo: <?php echo $producto['stock_minimo']; ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-success">
                    <i class="bi bi-trophy"></i> Productos Más Vendidos
                </div>
                <div class="card-body card-body-scroll-400">
                    <?php if (empty($productos_mas_vendidos ?? [])): ?>
                        <p class="text-center text-muted">No hay productos vendidos aún</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($productos_mas_vendidos as $producto): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong><br>
                                    <small class="text-muted">
                                        <span class="badge bg-primary"><?php echo $producto['total_vendido']; ?> unidades</span>
                                        <?php if (!empty($producto['categoria_nombre'])): ?>
                                            | <?php echo htmlspecialchars($producto['categoria_nombre']); ?>
                                        <?php endif; ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Botón flotante para reabrir el contador -->
<button id="btnAbrirContador" class="btn-abrir-contador" title="Ver Meta del Día">
    <i class="bi bi-bullseye"></i>
    <span class="btn-badge" id="badgeContador">$0</span>
</button>

<!-- Contador flotante de meta diaria -->
<div id="contadorMeta" class="contador-meta-flotante">
    <div class="contador-meta-glow"></div>
    <div class="contador-meta-header">
        <div class="header-title">
            <i class="bi bi-bullseye header-icon"></i>
            <span>Meta del Día</span>
        </div>
        <button type="button" class="btn-close-custom" id="cerrarContador" aria-label="Cerrar">
            <i class="bi bi-x-circle"></i>
        </button>
    </div>
    <div class="contador-meta-body">
        <div class="meta-diaria-info">
            <div class="meta-numero-container">
                <div class="meta-numero">
                    <span id="productosVendidosHoy" class="numero-grande">$0</span>
                    <span class="separador">/</span>
                    <span id="metaDiaria" class="numero-meta">$0</span>
                </div>
                <div class="meta-badge-mini" id="metaBadgeMini">
                    <i class="bi bi-fire"></i>
                </div>
            </div>
            <div class="meta-label">
                <i class="bi bi-cash-coin"></i>
                <span>Ventas de hoy</span>
            </div>
            <div class="progreso-container">
                <div class="progreso-bar" id="progresoBar">
                    <div class="progreso-fill" id="progresoFill">
                        <div class="progreso-shine"></div>
                    </div>
                </div>
                <div class="progreso-info">
                    <span class="progreso-porcentaje" id="progresoPorcentaje">0%</span>
                    <span class="progreso-estado" id="progresoEstado">En progreso</span>
                </div>
            </div>
        </div>
        <div class="meta-mes-info">
            <div class="meta-mes-item">
                <div class="meta-mes-icon">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div class="meta-mes-text">
                    <span class="meta-mes-label">Faltan para el mes</span>
                    <span class="meta-mes-value" id="valorFaltanteMes">$0</span>
                </div>
            </div>
            <div class="meta-mes-item">
                <div class="meta-mes-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="meta-mes-text">
                    <span class="meta-mes-label">Días hábiles</span>
                    <span class="meta-mes-value" id="diasHabilesRestantes">0</span>
                    <span class="meta-mes-unit">restantes</span>
                </div>
            </div>
        </div>
        <div id="metaAlcanzadaBadge" class="meta-alcanzada-badge">
            <div class="badge-content">
                <i class="bi bi-trophy"></i>
                <div>
                    <strong>¡Meta alcanzada!</strong>
                    <small>Sigue así, estás haciendo un excelente trabajo</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
<script>
// Gráfica de ventas
const ventasData = <?php echo json_encode($ventas_por_dia ?? []); ?>;
const labels = ventasData.length > 0 ? ventasData.map(v => v.fecha) : [];
const data = ventasData.length > 0 ? ventasData.map(v => parseFloat(v.total || 0)) : [];

const ctx = document.getElementById('ventasChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Ventas ($)',
            data: data,
            borderColor: 'rgb(151, 207, 207)',
            backgroundColor: 'rgba(151, 207, 207, 0.35)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Contador de meta diaria
let metaAlcanzadaAnterior = false;
let intervaloMeta = null;

// Función para formatear valores monetarios
function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(valor);
}

// Variable para prevenir llamadas simultáneas
let actualizandoMeta = false;

function actualizarMetaDiaria() {
    // Prevenir llamadas simultáneas
    if (actualizandoMeta) {
        console.log('Actualización de meta ya en progreso, omitiendo...');
        return;
    }
    
    actualizandoMeta = true;
    
    // Obtener BASE_URL con fallback
    const baseUrl = window.BASE_URL || '<?php echo BASE_URL; ?>' || '';
    const url = baseUrl + 'index.php?action=dashboard&method=getMetaDiaria';
    
    fetch(url)
        .then(response => {
            // Primero obtener el texto de la respuesta para verificar si es HTML
            return response.text().then(text => {
                // Verificar si la respuesta es HTML (error de PHP)
                if (text.trim().startsWith('<') || text.includes('<br') || text.includes('<b>')) {
                    console.error('Respuesta HTML recibida:', text);
                    throw new Error('Error del servidor: La respuesta contiene HTML en lugar de JSON. Esto generalmente indica un error de conexión a la base de datos o un error de PHP. Verifique la configuración de la base de datos.');
                }
                
                // Intentar parsear como JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error al parsear JSON:', text);
                    throw new Error('Error: La respuesta del servidor no es JSON válido. ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Actualizar valores monetarios
                document.getElementById('productosVendidosHoy').textContent = formatearMoneda(data.ventas_hoy);
                document.getElementById('metaDiaria').textContent = formatearMoneda(data.meta_diaria);
                document.getElementById('valorFaltanteMes').textContent = formatearMoneda(data.valor_faltante_mes);
                document.getElementById('diasHabilesRestantes').textContent = data.dias_habiles_restantes;
                
                // Actualizar badge del botón flotante
                const btnAbrir = document.getElementById('btnAbrirContador');
                const badgeContador = document.getElementById('badgeContador');
                if (btnAbrir && badgeContador) {
                    badgeContador.textContent = formatearMoneda(data.ventas_hoy);
                    // Si el contador está cerrado, mostrar el botón con animación
                    if (document.getElementById('contadorMeta').style.display === 'none') {
                        btnAbrir.style.display = 'flex';
                    }
                }
                
                // Actualizar barra de progreso
                const progresoFill = document.getElementById('progresoFill');
                const progresoPorcentaje = document.getElementById('progresoPorcentaje');
                const porcentaje = Math.min(100, data.progreso_dia);
                
                progresoFill.style.width = porcentaje + '%';
                progresoPorcentaje.textContent = Math.round(porcentaje) + '%';
                
                // Cambiar color según progreso y actualizar estado
                const metaBadge = document.getElementById('metaAlcanzadaBadge');
                const progresoEstado = document.getElementById('progresoEstado');
                const metaBadgeMini = document.getElementById('metaBadgeMini');
                
                if (porcentaje >= 100) {
                    progresoFill.classList.add('progreso-completo');
                    progresoFill.classList.remove('progreso-alto', 'progreso-medio');
                    metaBadge.style.display = 'block';
                    progresoEstado.textContent = '¡Completado!';
                    progresoEstado.style.color = '#97cfcf';
                    metaBadgeMini.style.display = 'flex';
                } else {
                    metaBadge.style.display = 'none';
                    if (porcentaje >= 75) {
                        progresoFill.classList.remove('progreso-completo', 'progreso-medio');
                        progresoFill.classList.add('progreso-alto');
                        progresoEstado.textContent = 'Casi ';
                        progresoEstado.style.color = '#5e5552';
                        metaBadgeMini.style.display = 'flex';
                    } else if (porcentaje >= 50) {
                        progresoFill.classList.remove('progreso-completo', 'progreso-alto');
                        progresoFill.classList.add('progreso-medio');
                        progresoEstado.textContent = 'A mitad';
                        progresoEstado.style.color = '#5e5552';
                        metaBadgeMini.style.display = 'flex';
                    } else {
                        progresoFill.classList.remove('progreso-completo', 'progreso-alto', 'progreso-medio');
                        progresoEstado.textContent = 'En progreso';
                        progresoEstado.style.color = '#6d6562';
                        metaBadgeMini.style.display = porcentaje > 0 ? 'flex' : 'none';
                    }
                }
                
                // Animación de confeti cuando se alcanza la meta
                if (data.meta_alcanzada && !metaAlcanzadaAnterior) {
                    lanzarConfeti();
                    metaAlcanzadaAnterior = true;
                } else if (!data.meta_alcanzada) {
                    metaAlcanzadaAnterior = false;
                }
            } else {
                console.error('Error en respuesta:', data.error || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('Error al actualizar meta diaria:', error);
            // No mostrar alerta para evitar interrumpir la experiencia del usuario
            // Solo registrar en consola
        })
        .finally(() => {
            // Permitir nuevas llamadas después de un breve delay
            setTimeout(() => {
                actualizandoMeta = false;
            }, 1000);
        });
}

function lanzarConfeti() {
    // Verificar que la librería esté cargada
    if (typeof confetti === 'undefined') {
        console.warn('La librería confetti no está cargada');
        return;
    }
    
    const duration = 3000;
    const end = Date.now() + duration;
    const colors = ['#fcd1d1', '#ece2e1', '#d3e0dc', '#aee1e1', '#97cfcf'];
    
    (function frame() {
        confetti({
            particleCount: 2,
            angle: 60,
            spread: 55,
            origin: { x: 0 },
            colors: colors
        });
        confetti({
            particleCount: 2,
            angle: 120,
            spread: 55,
            origin: { x: 1 },
            colors: colors
        });
        
        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    }());
}

// Cerrar contador
document.getElementById('cerrarContador').addEventListener('click', function() {
    document.getElementById('contadorMeta').style.display = 'none';
    document.getElementById('btnAbrirContador').style.display = 'flex';
});

// Abrir contador
document.getElementById('btnAbrirContador').addEventListener('click', function() {
    document.getElementById('contadorMeta').style.display = 'block';
    document.getElementById('btnAbrirContador').style.display = 'none';
    // Actualizar datos al abrir
    actualizarMetaDiaria();
});

// Función para inicializar actualizaciones
function iniciarActualizacionesMeta() {
    // Limpiar intervalo anterior si existe
    if (intervaloMeta) {
        clearInterval(intervaloMeta);
    }
    
    // Actualizar inmediatamente
    actualizarMetaDiaria();
    
    // Actualizar cada 30 segundos
    intervaloMeta = setInterval(actualizarMetaDiaria, 30000);
}

// Iniciar actualizaciones cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarActualizacionesMeta);
} else {
    iniciarActualizacionesMeta();
}

// Actualizar también cuando la página gana foco (por si se procesó una venta en otra pestaña)
let ultimaActualizacionFoco = 0;
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Prevenir actualizaciones muy frecuentes (mínimo 5 segundos entre actualizaciones por foco)
        const ahora = Date.now();
        if (ahora - ultimaActualizacionFoco > 5000) {
            actualizarMetaDiaria();
            ultimaActualizacionFoco = ahora;
        }
    }
});
</script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

