<?php
$pageTitle = 'Dashboard de Operaciones';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>front/public/css/dashboard.css?v=<?php echo time(); ?>">

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-speedometer2"></i> Rendimiento del taller</h2>
        <div class="d-flex gap-2 align-items-center">
            <input type="date" id="fechaInicio" class="form-control" value="<?php echo $fechaInicio; ?>" 
                   onchange="actualizarDashboard()" style="width: auto;">
            <span class="mx-2">a</span>
            <input type="date" id="fechaFin" class="form-control" value="<?php echo $fechaFin; ?>" 
                   onchange="actualizarDashboard()" style="width: auto;">
            <button class="btn btn-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>index.php?action=produccion'">
                <i class="bi bi-arrow-left"></i> Volver a Producción
            </button>
        </div>
    </div>
    
    <!-- Resumen General -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="metric-card metric-card-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2">Personas</h6>
                        <h3 class="mb-0"><?php echo number_format($resumenGeneral['total_operarias'] ?? 0); ?></h3>
                    </div>
                    <i class="bi bi-people display-6"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card metric-card-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2">Trabajos</h6>
                        <h3 class="mb-0"><?php echo number_format($resumenGeneral['total_operaciones'] ?? 0); ?></h3>
                    </div>
                    <i class="bi bi-gear-wide-connected display-6"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card metric-card-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2">Piezas</h6>
                        <h3 class="mb-0"><?php echo number_format($resumenGeneral['total_piezas'] ?? 0); ?></h3>
                    </div>
                    <i class="bi bi-box-seam display-6"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="metric-card metric-card-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-2">Ritmo</h6>
                        <h3 class="mb-0"><?php echo number_format($resumenGeneral['eficiencia_promedio'] ?? 0, 2); ?>%</h3>
                    </div>
                    <i class="bi bi-graph-up display-6"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rendimiento de Operarias -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-person-check"></i> Cómo va cada operaria</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Operaria</th>
                            <th>Días Trabajados</th>
                            <th>Trabajos</th>
                            <th>Piezas</th>
                            <th>Piezas por día</th>
                            <th>Meta</th>
                            <th>Cumplió la meta</th>
                            <th>Ritmo</th>
                            <th>Trabajos en meta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($operariasRendimiento)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No hay datos disponibles para el período seleccionado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($operariasRendimiento as $operaria): ?>
                                <?php 
                                $metaDia = floatval($operaria['meta_dia'] ?? 0);
                                $promedioPiezasDia = floatval($operaria['promedio_piezas_dia'] ?? 0);
                                $porcentajeMeta = $metaDia > 0 ? ($promedioPiezasDia / $metaDia) * 100 : 0;
                                $eficiencia = floatval($operaria['eficiencia_promedio'] ?? 0);
                                $metaCumplida = intval($operaria['operaciones_meta_cumplida'] ?? 0);
                                $totalOperaciones = intval($operaria['total_operaciones'] ?? 0);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($operaria['operaria_nombre']); ?></strong></td>
                                    <td><?php echo number_format($operaria['dias_trabajados'] ?? 0); ?></td>
                                    <td><?php echo number_format($totalOperaciones); ?></td>
                                    <td><?php echo number_format($operaria['total_piezas'] ?? 0); ?></td>
                                    <td><?php echo number_format($promedioPiezasDia, 1); ?></td>
                                    <td><?php echo number_format($metaDia); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $porcentajeMeta >= 100 ? 'success' : ($porcentajeMeta >= 80 ? 'warning' : 'danger'); ?>">
                                            <?php echo number_format($porcentajeMeta, 1); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $eficiencia >= 100 ? 'success' : ($eficiencia >= 80 ? 'warning' : 'danger'); ?>">
                                            <?php echo number_format($eficiencia, 2); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $metaCumplida; ?> / <?php echo $totalOperaciones; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Bitácora de Días -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-journal-text"></i> Cierres del día</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Prendas Terminadas</th>
                            <th>Prendas Empezadas</th>
                            <th>Tiempo Total</th>
                            <th>Total Operarias</th>
                            <th>Total Operaciones</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bitacoraDias)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No hay cierres en el período seleccionado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bitacoraDias as $dia): ?>
                                <?php 
                                $tiempoMinutos = floatval($dia['tiempo_total_minutos'] ?? 0);
                                $horas = floor($tiempoMinutos / 60);
                                $minutos = floor($tiempoMinutos % 60);
                                $segundos = floor(($tiempoMinutos % 1) * 60);
                                $tiempoFormateado = $horas > 0 
                                    ? sprintf('%d:%02d:%02d', $horas, $minutos, $segundos)
                                    : sprintf('%d:%02d', $minutos, $segundos);
                                ?>
                                <tr>
                                    <td><strong><?php echo date('d/m/Y', strtotime($dia['fecha'])); ?></strong></td>
                                    <td>
                                        <?php if (intval($dia['finalizado'] ?? 1) === 1): ?>
                                            <span class="badge bg-danger">Cerrado</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Abierto de nuevo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($dia['prendas_terminadas'] ?? 0); ?></td>
                                    <td><?php echo number_format($dia['prendas_empezadas'] ?? 0); ?></td>
                                    <td><?php echo $tiempoFormateado; ?></td>
                                    <td><?php echo number_format($dia['total_operarias'] ?? 0); ?></td>
                                    <td><?php echo number_format($dia['total_operaciones'] ?? 0); ?></td>
                                    <td>
                                        <?php if (!empty($dia['observaciones'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info btn-icono btnVerObservaciones"
                                                    title="Ver"
                                                    data-fecha="<?php echo htmlspecialchars($dia['fecha']); ?>"
                                                    data-observaciones="<?php echo htmlspecialchars($dia['observaciones']); ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
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

<!-- Modal único para observaciones -->
<div class="modal fade" id="modalObservaciones" tabindex="-1" aria-labelledby="modalObservacionesLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalObservacionesLabel">Observaciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalObservacionesBody">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function actualizarDashboard() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    
    if (fechaInicio && fechaFin) {
        window.location.href = `<?php echo BASE_URL; ?>index.php?action=produccion&method=dashboardOperaciones&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
    }
}

// Manejar clic en botones de ver observaciones
document.addEventListener('DOMContentLoaded', function() {
    const botonesObservaciones = document.querySelectorAll('.btnVerObservaciones');
    const modalObservaciones = document.getElementById('modalObservaciones');
    const modalTitle = document.getElementById('modalObservacionesLabel');
    const modalBody = document.getElementById('modalObservacionesBody');
    
    botonesObservaciones.forEach(function(boton) {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const fecha = this.getAttribute('data-fecha');
            const observaciones = this.getAttribute('data-observaciones');
            
            // Formatear fecha
            const fechaObj = new Date(fecha);
            const fechaFormateada = fechaObj.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            
            // Actualizar contenido del modal
            modalTitle.textContent = 'Observaciones - ' + fechaFormateada;
            modalBody.innerHTML = '<p>' + observaciones.replace(/\n/g, '<br>') + '</p>';
            
            // Mostrar modal
            const modal = new bootstrap.Modal(modalObservaciones);
            modal.show();
        });
    });
});
</script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

