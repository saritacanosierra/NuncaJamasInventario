<?php
$pageTitle = 'Historial de Ventas';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-clock-history"></i> Historial de Ventas</h2>
        <div>
            <button type="button" class="btn btn-outline-info me-2" data-bs-toggle="modal" data-bs-target="#modalHistorialVentas">
                <i class="bi bi-bar-chart"></i> Historial
            </button>
            <a href="<?php echo BASE_URL; ?>index.php?action=ventas" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nueva Venta
            </a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>index.php?action=ventas&method=historial" class="row g-3" id="formFiltros">
                <input type="hidden" name="action" value="ventas">
                <input type="hidden" name="method" value="historial">
                <div class="col-md-4">
                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" name="fecha_desde" id="fecha_desde"
                           value="<?php echo htmlspecialchars($_GET['fecha_desde'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" name="fecha_hasta" id="fecha_hasta"
                           value="<?php echo htmlspecialchars($_GET['fecha_hasta'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="buscar_cliente_historial" class="form-label">Cliente</label>
                    <input type="text" class="form-control" name="buscar_cliente_historial" 
                           id="buscar_cliente_historial" 
                           placeholder="Buscar por nombre o cédula..."
                           value="<?php 
                               if (isset($_GET['cliente_id']) && !empty($_GET['cliente_id'])) {
                                   $clienteSeleccionado = null;
                                   foreach ($clientes as $cliente) {
                                       if ($cliente['id'] == $_GET['cliente_id']) {
                                           $clienteSeleccionado = $cliente;
                                           break;
                                       }
                                   }
                                   if ($clienteSeleccionado) {
                                       echo htmlspecialchars($clienteSeleccionado['nombre_completo'] . ' - ' . $clienteSeleccionado['cedula_nit']);
                                   }
                               }
                           ?>">
                    <input type="hidden" name="cliente_id" id="cliente_id_historial" 
                           value="<?php echo htmlspecialchars($_GET['cliente_id'] ?? ''); ?>">
                    <div id="resultados_cliente_historial" class="mt-2"></div>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=historial" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de ventas -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Subtotal</th>
                            <th>Descuento</th>
                            <th>Total</th>
                            <th>Método Pago</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventas)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No se encontraron ventas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ventas as $venta): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($venta['numero_factura']); ?></code></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                                    <td><?php echo htmlspecialchars($venta['cliente_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['vendedor']); ?></td>
                                    <td><?php echo pesos($venta['subtotal']); ?></td>
                                    <td><?php echo pesos($venta['descuento']); ?></td>
                                    <td><strong><?php echo pesos($venta['total']); ?></strong></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($venta['metodo_pago']); ?></span></td>
                                    <td>
                                        <?php if (tienePermiso('ventas_factura:view')): ?>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=factura&id=<?php echo $venta['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Ver factura">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                        <?php endif; ?>
                                        <?php if (tienePermiso('ventas_historial:edit')): ?>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=edit&id=<?php echo $venta['id']; ?>" 
                                           class="btn btn-sm btn-outline-warning" title="Editar venta">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                        <?php endif; ?>
                                        <?php if (tienePermiso('ventas_historial:delete')): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-icono btnEliminarVenta"
                                                data-id="<?php echo (int) $venta['id']; ?>"
                                                data-codigo="<?php echo htmlspecialchars($venta['numero_factura']); ?>"
                                                title="Eliminar venta">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (!empty($venta['con_domicilio']) && tienePermiso('ventas_rotulo:view')): ?>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=rotuloEnvio&id=<?php echo $venta['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="Imprimir rótulo de envío" target="_blank">
                                                <i class="bi bi-truck"></i> Rótulo
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEliminarVenta" tabindex="-1" aria-labelledby="modalEliminarVentaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=ventas&method=delete" id="formEliminarVenta">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" id="eliminar_venta_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarVentaLabel">Eliminar venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Esta acción devuelve el stock y borra la factura <strong id="eliminar_venta_codigo_texto"></strong>.</p>
                    <p class="mb-2">Escribe el código de la venta para confirmar.</p>
                    <label for="eliminar_venta_codigo" class="form-label">Código de la venta</label>
                    <input type="text" class="form-control" name="codigo_venta" id="eliminar_venta_codigo" autocomplete="off" required>
                    <p class="text-danger small mt-2 d-none" id="eliminar_venta_aviso">El código no coincide.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmarEliminarVenta" disabled>Eliminar venta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let codigoVentaEsperado = '';

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btnEliminarVenta');
    if (!btn) return;
    codigoVentaEsperado = btn.getAttribute('data-codigo') || '';
    document.getElementById('eliminar_venta_id').value = btn.getAttribute('data-id') || '';
    document.getElementById('eliminar_venta_codigo_texto').textContent = codigoVentaEsperado;
    const campo = document.getElementById('eliminar_venta_codigo');
    campo.value = '';
    document.getElementById('eliminar_venta_aviso').classList.add('d-none');
    document.getElementById('btnConfirmarEliminarVenta').disabled = true;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEliminarVenta')).show();
});

document.getElementById('eliminar_venta_codigo').addEventListener('input', function() {
    const coincide = this.value.trim().toLowerCase() === codigoVentaEsperado.trim().toLowerCase() && this.value.trim() !== '';
    document.getElementById('btnConfirmarEliminarVenta').disabled = !coincide;
    document.getElementById('eliminar_venta_aviso').classList.toggle('d-none', this.value.trim() === '' || coincide);
});

document.getElementById('formEliminarVenta').addEventListener('submit', function(e) {
    const escrito = document.getElementById('eliminar_venta_codigo').value.trim().toLowerCase();
    if (escrito === '' || escrito !== codigoVentaEsperado.trim().toLowerCase()) {
        e.preventDefault();
        document.getElementById('eliminar_venta_aviso').classList.remove('d-none');
    }
});

// Búsqueda de cliente en historial
let timeoutClienteHistorial;
document.getElementById('buscar_cliente_historial').addEventListener('input', function() {
    clearTimeout(timeoutClienteHistorial);
    const termino = this.value.trim();
    
    if (termino.length < 2) {
        document.getElementById('resultados_cliente_historial').innerHTML = '';
        document.getElementById('cliente_id_historial').value = '';
        return;
    }
    
    timeoutClienteHistorial = setTimeout(() => {
        buscarClienteHistorial(termino);
    }, 500);
});

function buscarClienteHistorial(termino) {
    fetch(`${BASE_URL}index.php?action=clientes&method=buscar&termino=${encodeURIComponent(termino)}`)
        .then(r => r.text())
        .then(txt => {
            console.log('Respuesta buscarCliente (texto crudo):', txt);
            let data;
            try {
                data = JSON.parse(txt);
            } catch (e) {
                console.error('No se pudo parsear JSON en buscarCliente:', e);
                document.getElementById('resultados_cliente_historial').innerHTML =
                    '<p class="text-danger">Error al buscar clientes. Revisa la consola.</p>';
                return;
            }

            let html = '';
            if (data.success && data.clientes && data.clientes.length > 0) {
                html = '<div class="list-group historial-scroll">';
                data.clientes.forEach(c => {
                    const nombre = (c.nombre_completo || '').replace(/'/g, "\\'");
                    html += `<a href="#" class="list-group-item list-group-item-action" 
                                onclick="seleccionarClienteHistorial(${c.id}, '${nombre}', '${c.cedula_nit}'); return false;">
                            <strong>${c.nombre_completo}</strong> - ${c.cedula_nit}
                        </a>`;
                });
                html += '</div>';
            } else {
                html = '<p class="text-muted">No se encontraron clientes con ese dato.</p>';
            }
            document.getElementById('resultados_cliente_historial').innerHTML = html;
        })
        .catch(err => {
            console.error('Error al buscar clientes (fetch):', err);
            document.getElementById('resultados_cliente_historial').innerHTML =
                '<p class="text-danger">Error al buscar clientes. Revisa la consola.</p>';
        });
}

function seleccionarClienteHistorial(id, nombre, cedula) {
    document.getElementById('cliente_id_historial').value = id;
    document.getElementById('buscar_cliente_historial').value = nombre + ' - ' + cedula;
    document.getElementById('resultados_cliente_historial').innerHTML = '';
}

// Limpiar búsqueda si el campo está vacío
document.getElementById('buscar_cliente_historial').addEventListener('blur', function() {
    setTimeout(() => {
        if (this.value.trim() === '') {
            document.getElementById('cliente_id_historial').value = '';
            document.getElementById('resultados_cliente_historial').innerHTML = '';
        }
    }, 200);
});
</script>

<!-- Modal Historial de Ventas -->
<div class="modal fade" id="modalHistorialVentas" tabindex="-1" aria-labelledby="modalHistorialVentasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHistorialVentasLabel">
                    <i class="bi bi-bar-chart"></i> Historial de Ventas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-4" id="historialVentasTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="mes-ventas-tab" data-bs-toggle="tab" data-bs-target="#mes-ventas" type="button" role="tab">
                            <i class="bi bi-calendar-month"></i> Por Mes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="anio-ventas-tab" data-bs-toggle="tab" data-bs-target="#anio-ventas" type="button" role="tab">
                            <i class="bi bi-calendar-year"></i> Por Año
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="historialVentasTabContent">
                    <!-- Tab Por Mes -->
                    <div class="tab-pane fade show active" id="mes-ventas" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Mes</th>
                                        <th class="text-end">Total Ventas</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-end">Descuentos</th>
                                        <th class="text-center">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaVentasMes">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab Por Año -->
                    <div class="tab-pane fade" id="anio-ventas" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Año</th>
                                        <th class="text-end">Total Ventas</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-end">Descuentos</th>
                                        <th class="text-center">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaVentasAnio">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Función para convertir mes a español
function mesEnEspanol(mesAnio) {
    if (!mesAnio) return '';
    
    const meses = {
        'January': 'Enero', 'February': 'Febrero', 'March': 'Marzo', 'April': 'Abril',
        'May': 'Mayo', 'June': 'Junio', 'July': 'Julio', 'August': 'Agosto',
        'September': 'Septiembre', 'October': 'Octubre', 'November': 'Noviembre', 'December': 'Diciembre'
    };
    
    // Si viene en formato "Month Year" (ej: "January 2024")
    if (mesAnio.includes(' ')) {
        const partes = mesAnio.split(' ');
        const mes = partes[0];
        const anio = partes[1];
        return meses[mes] ? `${meses[mes]} ${anio}` : mesAnio;
    }
    
    // Si viene en formato "YYYY-MM" (ej: "2024-01")
    if (mesAnio.match(/^\d{4}-\d{2}$/)) {
        const [anio, mes] = mesAnio.split('-');
        const mesesNum = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        const mesIndex = parseInt(mes) - 1;
        return `${mesesNum[mesIndex]} ${anio}`;
    }
    
    return mesAnio;
}

// Función para escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Cargar historial al abrir el modal
document.getElementById('modalHistorialVentas').addEventListener('show.bs.modal', function() {
    cargarHistorialVentas();
});

function cargarHistorialVentas() {
    fetch(`${BASE_URL}index.php?action=ventas&method=obtenerHistorial`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Ventas por mes
                let htmlVentasMes = '';
                if (data.ventas_por_mes && data.ventas_por_mes.length > 0) {
                    data.ventas_por_mes.forEach(item => {
                        const mesNombre = mesEnEspanol(item.mes_nombre || item.mes);
                        htmlVentasMes += `
                            <tr>
                                <td><strong>${escapeHtml(mesNombre)}</strong></td>
                                <td class="text-end"><strong class="text-success">$${parseFloat(item.total || 0).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</strong></td>
                                <td class="text-end">$${parseFloat(item.subtotal || 0).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
                                <td class="text-end text-danger">$${parseFloat(item.descuento || 0).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
                                <td class="text-center"><span class="badge bg-primary">${item.cantidad || 0}</span></td>
                            </tr>
                        `;
                    });
                } else {
                    htmlVentasMes = '<tr><td colspan="5" class="text-center text-muted">No hay datos</td></tr>';
                }
                document.getElementById('tablaVentasMes').innerHTML = htmlVentasMes;
                
                // Ventas por año
                let htmlVentasAnio = '';
                if (data.ventas_por_anio && data.ventas_por_anio.length > 0) {
                    data.ventas_por_anio.forEach(item => {
                        htmlVentasAnio += `
                            <tr>
                                <td><strong>${item.anio || ''}</strong></td>
                                <td class="text-end"><strong class="text-success">$${parseFloat(item.total || 0).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</strong></td>
                                <td class="text-end">$${parseFloat(item.subtotal || 0).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
                                <td class="text-end text-danger">$${parseFloat(item.descuento || 0).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
                                <td class="text-center"><span class="badge bg-primary">${item.cantidad || 0}</span></td>
                            </tr>
                        `;
                    });
                } else {
                    htmlVentasAnio = '<tr><td colspan="5" class="text-center text-muted">No hay datos</td></tr>';
                }
                document.getElementById('tablaVentasAnio').innerHTML = htmlVentasAnio;
            } else {
                console.error('Error al cargar historial');
            }
        })
        .catch(error => {
            console.error('Error al cargar historial:', error);
            document.getElementById('tablaVentasMes').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar datos</td></tr>';
            document.getElementById('tablaVentasAnio').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar datos</td></tr>';
        });
}
</script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

