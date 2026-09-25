<?php
$pageTitle = 'Gastos';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($categorias) || !is_array($categorias)) {
    $categorias = [];
}
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <h2 class="mb-0"><i class="bi bi-cash-stack"></i> Gastos e inversiones<?php $ayuda = 'Un gasto es plata que sale y no vuelve, como el arriendo. Una inversión es plata que queda en el negocio, como telas o mercancía. Historial descarga el mes o el año en PDF. Cierre de caja cuenta el efectivo del día.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <div class="d-flex align-items-center gap-2">
            <?php if (tienePermiso('gastos_categorias:view')): ?>
            <button type="button" class="btn btn-outline-secondary btn-icono" data-bs-toggle="modal" data-bs-target="#modalCategoriasGastos" title="Categorías">
                <i class="bi bi-tags"></i>
            </button>
            <?php endif; ?>
            <?php if (tienePermiso('caja:view') && tienePermiso('caja_cierre:view')): ?>
            <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>index.php?action=caja">
                <i class="bi bi-safe"></i> Cierre de caja
            </a>
            <?php endif; ?>
            <?php if (tienePermiso('gastos_historial:view')): ?>
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalHistorial">
                <i class="bi bi-clock-history"></i> Historial
            </button>
            <?php endif; ?>
            <?php if (tienePermiso('compras_registro:view') || tienePermiso('compras:view')): ?>
            <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>index.php?action=compras">
                <i class="bi bi-bag-plus"></i> Compras de producto
            </a>
            <?php endif; ?>
            <?php if (tienePermiso('gastos_inversiones:create')): ?>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevaInversion">
                <i class="bi bi-plus-circle"></i> Nueva inversión
            </button>
            <?php endif; ?>
            <?php if (tienePermiso('gastos_registro:create')): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoGasto">
                <i class="bi bi-plus-circle"></i> Nuevo gasto
            </button>
            <?php endif; ?>
        </div>
    </div>
    <!-- Resumen -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card bg-danger">
                <div class="card-body">
                    <h5><i class="bi bi-cash-stack"></i> Total de Gastos del Periodo<?php $ayuda = 'Suma de los gastos del filtro de arriba. No incluye inversiones ni compras de mercancía.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h5>
                    <h2><?php echo pesos($totalGastos['total'] ?? 0); ?></h2>
                    <small><?php echo $totalGastos['cantidad'] ?? 0; ?> gastos registrados</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success">
                <div class="card-body">
                    <h5><i class="bi bi-graph-up"></i> Total de Inversiones del Periodo<?php $ayuda = 'Suma de las inversiones del filtro, incluidas las compras de producto pagadas en este periodo.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h5>
                    <h2><?php echo pesos($totalInversiones['total'] ?? 0); ?></h2>
                    <small><?php echo $totalInversiones['cantidad'] ?? 0; ?> inversiones registradas</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>index.php?action=gastos" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" name="fecha_desde" 
                           value="<?php echo htmlspecialchars($_GET['fecha_desde'] ?? date('Y-m-01')); ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" name="fecha_hasta" 
                           value="<?php echo htmlspecialchars($_GET['fecha_hasta'] ?? date('Y-m-t')); ?>">
                </div>
                <div class="col-md-4">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php 
                        $categoriaFiltro = $_GET['categoria'] ?? '';
                        foreach ($categorias as $cat): 
                        ?>
                            <option value="<?php echo htmlspecialchars($cat['nombre']); ?>" 
                                    <?php echo ($categoriaFiltro == $cat['nombre']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php
                        $hayCategoriaProducto = false;
                        foreach ($categorias as $cat) {
                            if (strtolower($cat['nombre']) === 'producto') {
                                $hayCategoriaProducto = true;
                            }
                        }
                        if (!$hayCategoriaProducto):
                        ?>
                            <option value="Producto" <?php echo ($categoriaFiltro == 'Producto') ? 'selected' : ''; ?>>Producto</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Gráficas -->
    <div class="row g-3 mb-4">
        <?php if (!empty($gastosPorCategoria)): ?>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pie-chart"></i> Gastos por Categoría
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="gastosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($inversionesPorCategoria)): ?>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pie-chart"></i> Inversiones por Categoría
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="inversionesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (tienePermiso('gastos_registro:view')): ?>
    <!-- Tabla de gastos -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Gastos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Categoría</th>
                            <th>Monto</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gastos)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No se encontraron gastos</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($gastos as $gasto): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($gasto['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($gasto['concepto']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($gasto['categoria']); ?></span></td>
                                    <td><strong><?php echo pesos($gasto['monto']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($gasto['usuario_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (tienePermiso('gastos_registro:edit')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-icono" 
                                                title="Editar" 
                                                onclick="editarGasto(<?php echo $gasto['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (tienePermiso('gastos_registro:delete')): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=gastos&method=delete" class="d-inline form-doble-eliminar" data-titulo="Eliminar gasto" data-detalle="Se borra el gasto y no se puede recuperar." data-codigo="<?php echo htmlspecialchars($gasto['concepto']); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $gasto['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icono" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php require BASE_DIR . '/front/views/components/paginacion.php'; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (tienePermiso('gastos_inversiones:view')): ?>
    <!-- Tabla de inversiones -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Inversiones</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Categoría</th>
                            <th>Monto</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inversiones)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No se encontraron inversiones</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inversiones as $inversion): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($inversion['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($inversion['concepto']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($inversion['categoria']); ?></span></td>
                                    <td><strong><?php echo pesos($inversion['monto']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($inversion['usuario_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (tienePermiso('gastos_inversiones:edit')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-icono" 
                                                title="Editar" 
                                                onclick="editarInversion(<?php echo $inversion['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (tienePermiso('gastos_inversiones:delete')): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=gastos&method=deleteInversion" class="d-inline form-doble-eliminar" data-titulo="Eliminar inversión" data-detalle="Se borra la inversión y no se puede recuperar." data-codigo="<?php echo htmlspecialchars($inversion['concepto']); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $inversion['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icono" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php require BASE_DIR . '/front/views/components/paginacion.php'; ?>
        </div>
    </div>
</div>
    <?php endif; ?>

<?php if (!empty($gastosPorCategoria)): ?>
<script>
const gastosData = <?php echo json_encode($gastosPorCategoria); ?>;
const labels = gastosData.map(g => g.categoria);
const data = gastosData.map(g => parseFloat(g.total));

const ctx = document.getElementById('gastosChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: [
                '#fcd1d1',
                '#e7a3a8',
                '#d6848a',
                '#ece2e1',
                '#7a5555'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 10,
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php if (!empty($inversionesPorCategoria)): ?>
<script>
const inversionesData = <?php echo json_encode($inversionesPorCategoria); ?>;
const labelsInversiones = inversionesData.map(i => i.categoria);
const dataInversiones = inversionesData.map(i => parseFloat(i.total));

const ctxInversiones = document.getElementById('inversionesChart').getContext('2d');
new Chart(ctxInversiones, {
    type: 'pie',
    data: {
        labels: labelsInversiones,
        datasets: [{
            data: dataInversiones,
            backgroundColor: [
                '#97cfcf',
                '#d3e0dc',
                '#6ea898',
                '#aee1e1',
                '#3e6464'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 10,
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<!-- Modal Nuevo Gasto -->
<div class="modal fade" id="modalNuevoGasto" tabindex="-1" aria-labelledby="modalNuevoGastoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoGastoLabel">
                    <i class="bi bi-plus-circle"></i> Nuevo Gasto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoGasto">
                    <input type="hidden" id="gasto_id" name="id" value="">
                    <div class="mb-3">
                        <label for="concepto" class="form-label">Concepto *</label>
                        <input type="text" class="form-control" id="concepto" name="concepto" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="monto" class="form-label">Monto *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="monto" name="monto" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoria" class="form-label">Categoría *</label>
                        <select class="form-select" id="categoria" name="categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <?php if (strtolower($cat['nombre']) === 'producto') { continue; } ?>
                                <option value="<?php echo htmlspecialchars($cat['nombre']); ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fecha" class="form-label">Fecha *</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarGasto">
                    <i class="bi bi-save"></i> <span id="btnGuardarGastoTexto">Guardar Gasto</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Inversión -->
<div class="modal fade" id="modalNuevaInversion" tabindex="-1" aria-labelledby="modalNuevaInversionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaInversionLabel">
                    <i class="bi bi-plus-circle"></i> Nueva Inversión
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaInversion">
                    <input type="hidden" id="inversion_id" name="id" value="">
                    <div class="mb-3">
                        <label for="inversion_concepto" class="form-label">Concepto *</label>
                        <input type="text" class="form-control" id="inversion_concepto" name="concepto" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="inversion_monto" class="form-label">Monto *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="inversion_monto" name="monto" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="inversion_categoria" class="form-label">Categoría *</label>
                        <select class="form-select" id="inversion_categoria" name="categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <?php if (strtolower($cat['nombre']) === 'producto') { continue; } ?>
                                <option value="<?php echo htmlspecialchars($cat['nombre']); ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">La mercancía se anota en Compras de producto. Entra al inventario y también suma aquí como inversión.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="inversion_fecha" class="form-label">Fecha *</label>
                        <input type="date" class="form-control" id="inversion_fecha" name="fecha" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="inversion_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="inversion_descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardarInversion">
                    <i class="bi bi-save"></i> <span id="btnGuardarInversionTexto">Guardar Inversión</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Historial -->
<div class="modal fade" id="modalHistorial" tabindex="-1" aria-labelledby="modalHistorialLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHistorialLabel">
                    <i class="bi bi-clock-history"></i> Historial del mes<?php $ayuda = 'Elige un mes o el año completo. Cada botón descarga un PDF que no se puede editar: gastos, ingresos, inversiones, todos los movimientos o las prendas vendidas.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="alcance_descarga">Periodo</label>
                                <select class="form-select" id="alcance_descarga">
                                    <option value="mes">Un mes</option>
                                    <option value="anio">Año completo</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="campo_mes_descarga">
                                <label class="form-label" for="mes_descarga">Mes</label>
                                <input type="month" class="form-control" id="mes_descarga" value="<?php echo date('Y-m'); ?>">
                            </div>
                            <div class="col-md-3" id="campo_anio_descarga" hidden>
                                <label class="form-label" for="anio_descarga">Año</label>
                                <input type="number" class="form-control" id="anio_descarga" min="2000" max="2100" value="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <p class="text-muted mt-3 mb-3">Cada venta suma su total, aunque sea fiado. El abono es el dinero que después entró de esa deuda. El precio de las prendas vendidas separa la base y el IVA del 19 %. Puedes bajar un mes o el año completo, en PDF.</p>
                        <div class="d-flex flex-wrap gap-2" id="descargasMes">
                            <a class="btn btn-outline-danger" data-tipo="gastos" href="<?php echo BASE_URL; ?>index.php?action=gastos&amp;method=descargar&amp;tipo=gastos&amp;mes=<?php echo date('Y-m'); ?>"><i class="bi bi-download"></i> Gastos</a>
                            <a class="btn btn-outline-success" data-tipo="ingresos" href="<?php echo BASE_URL; ?>index.php?action=gastos&amp;method=descargar&amp;tipo=ingresos&amp;mes=<?php echo date('Y-m'); ?>"><i class="bi bi-download"></i> Ingresos</a>
                            <a class="btn btn-outline-primary" data-tipo="inversion" href="<?php echo BASE_URL; ?>index.php?action=gastos&amp;method=descargar&amp;tipo=inversion&amp;mes=<?php echo date('Y-m'); ?>"><i class="bi bi-download"></i> Inversiones</a>
                            <a class="btn btn-outline-secondary" data-tipo="movimientos" href="<?php echo BASE_URL; ?>index.php?action=gastos&amp;method=descargar&amp;tipo=movimientos&amp;mes=<?php echo date('Y-m'); ?>"><i class="bi bi-download"></i> Todos los movimientos</a>
                            <a class="btn btn-outline-info" data-tipo="productos" href="<?php echo BASE_URL; ?>index.php?action=gastos&amp;method=descargar&amp;tipo=productos&amp;mes=<?php echo date('Y-m'); ?>"><i class="bi bi-download"></i> Productos vendidos</a>
                        </div>
                    </div>
                </div>
                <ul class="nav nav-tabs mb-4" id="historialTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="mes-tab" data-bs-toggle="tab" data-bs-target="#mes" type="button" role="tab">
                            <i class="bi bi-calendar-month"></i> Por Mes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="anio-tab" data-bs-toggle="tab" data-bs-target="#anio" type="button" role="tab">
                            <i class="bi bi-calendar-year"></i> Por Año
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="historialTabContent">
                    <!-- Tab Por Mes -->
                    <div class="tab-pane fade show active" id="mes" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4">
                                <h6 class="text-danger"><i class="bi bi-cash-stack"></i> Gastos por Mes</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Mes</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaGastosMes">
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Cargando...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-success"><i class="bi bi-graph-up"></i> Inversiones por Mes</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Mes</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaInversionesMes">
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Cargando...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-primary"><i class="bi bi-receipt"></i> Ventas por Mes</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Mes</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaVentasMes">
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Cargando...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Por Año -->
                    <div class="tab-pane fade" id="anio" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4">
                                <h6 class="text-danger"><i class="bi bi-cash-stack"></i> Gastos por Año</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Año</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaGastosAnio">
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Cargando...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-success"><i class="bi bi-graph-up"></i> Inversiones por Año</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Año</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaInversionesAnio">
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Cargando...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-primary"><i class="bi bi-receipt"></i> Ventas por Año</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Año</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaVentasAnio">
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Cargando...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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

<!-- Modal Categorías de Gastos -->
<div class="modal fade" id="modalCategoriasGastos" tabindex="-1" aria-labelledby="modalCategoriasGastosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCategoriasGastosLabel">
                    <i class="bi bi-tags"></i> Gestión de Categorías de Gastos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-primary" id="btnNuevaCategoriaGasto">
                        <i class="bi bi-plus-circle"></i> Nueva Categoría
                    </button>
                </div>
                
                <div id="formCategoriaGasto" class="card mb-3">
                    <div class="card-body">
                        <form id="formNuevaCategoriaGasto">
                            <input type="hidden" id="categoria_gasto_id" name="id" value="">
                            <div class="mb-3">
                                <label for="categoria_gasto_nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="categoria_gasto_nombre" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="categoria_gasto_descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="categoria_gasto_descripcion" name="descripcion" rows="2"></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="btnGuardarCategoriaGasto">
                                    <i class="bi bi-save"></i> Guardar
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarCategoriaGasto">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaCategoriasGastos">
                            <tr>
                                <td colspan="3" class="text-center text-muted">Cargando categorías...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Pasar BASE_URL al JavaScript -->
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

<!-- JavaScript del módulo de gastos (debe cargarse después de Bootstrap) -->
<script src="<?php echo BASE_URL; ?>front/public/js/gastos.js?v=6"></script>

