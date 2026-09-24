<?php
$pageTitle = 'Agenda';
require_once BASE_DIR . '/front/views/layout/header.php';

// Preparar datos para el calendario
$mesActual = date('Y-m');
$mesSeleccionado = $_GET['mes'] ?? $mesActual;
list($year, $month) = explode('-', $mesSeleccionado);
$fechaSeleccionada = $_GET['fecha'] ?? date('Y-m-d');

// Nombres de meses en español
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

// Obtener primer día del mes y número de días
$primerDia = date('w', strtotime("$year-$month-01"));
$diasEnMes = date('t', strtotime("$year-$month-01"));

// Organizar tareas por fecha
$tareasPorFecha = [];
if (!isset($tareasMes) || !is_array($tareasMes)) {
    $tareasMes = [];
}
foreach ($tareasMes as $tarea) {
    $fecha = $tarea['fecha'];
    if (!isset($tareasPorFecha[$fecha])) {
        $tareasPorFecha[$fecha] = [];
    }
    $tareasPorFecha[$fecha][] = $tarea;
}
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-calendar-check"></i> Agenda y Tareas</h2>
        <?php if (tienePermiso('agenda_tareas:create')): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
            <i class="bi bi-plus-circle"></i> Nueva Tarea
        </button>
        <?php endif; ?>
    </div>
    
    <!-- Estadísticas rápidas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6><i class="bi bi-list-check"></i> Total Tareas</h6>
                    <h3><?php echo $estadisticas['total'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6><i class="bi bi-check-circle"></i> Completadas</h6>
                    <h3><?php echo $estadisticas['completadas'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6><i class="bi bi-clock"></i> Pendientes</h6>
                    <h3><?php echo $estadisticas['pendientes'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6><i class="bi bi-exclamation-triangle"></i> Urgentes</h6>
                    <h3><?php echo $estadisticas['urgentes'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Calendario -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="<?php echo BASE_URL; ?>index.php?action=agenda&mes=<?php echo date('Y-m', strtotime("$year-$month-01 -1 month")); ?>" 
                           class="btn btn-sm btn-outline-secondary btn-icono">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <span class="mx-3"><strong><?php echo $meses[$month - 1] . ' ' . $year; ?></strong></span>
                        <a href="<?php echo BASE_URL; ?>index.php?action=agenda&mes=<?php echo date('Y-m', strtotime("$year-$month-01 +1 month")); ?>" 
                           class="btn btn-sm btn-outline-secondary btn-icono">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                    <a href="<?php echo BASE_URL; ?>index.php?action=agenda&mes=<?php echo date('Y-m'); ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-calendar-event"></i> Hoy
                    </a>
                </div>
                <div class="card-body">
                    <div class="calendario-mensual">
                        <!-- Días de la semana -->
                        <div class="calendario-header">
                            <?php foreach ($diasSemana as $dia): ?>
                                <div class="calendario-dia-header"><?php echo $dia; ?></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Días del mes -->
                        <div class="calendario-dias">
                            <?php
                            // Espacios vacíos antes del primer día
                            for ($i = 0; $i < $primerDia; $i++):
                            ?>
                                <div class="calendario-dia vacio"></div>
                            <?php endfor; ?>
                            
                            <?php
                            // Días del mes
                            for ($dia = 1; $dia <= $diasEnMes; $dia++):
                                $fechaCompleta = sprintf('%04d-%02d-%02d', $year, $month, $dia);
                                $esHoy = $fechaCompleta == date('Y-m-d');
                                $esSeleccionado = $fechaCompleta == $fechaSeleccionada;
                                $tareasDelDia = $tareasPorFecha[$fechaCompleta] ?? [];
                                $tareasPendientes = array_filter($tareasDelDia, fn($t) => $t['estado'] == 'pendiente');
                                $tareasCompletadas = array_filter($tareasDelDia, fn($t) => $t['estado'] == 'completada');
                                $tareasUrgentes = array_filter($tareasDelDia, fn($t) => $t['prioridad'] == 'alta' && $t['estado'] == 'pendiente');
                            ?>
                                <div class="calendario-dia <?php echo $esHoy ? 'hoy' : ''; ?> <?php echo $esSeleccionado ? 'seleccionado' : ''; ?>" 
                                     data-fecha="<?php echo $fechaCompleta; ?>"
                                     onclick="seleccionarFecha('<?php echo $fechaCompleta; ?>')">
                                    <div class="dia-numero"><?php echo $dia; ?></div>
                                    <?php if (count($tareasDelDia) > 0): ?>
                                        <div class="dia-tareas">
                                            <?php if (count($tareasUrgentes) > 0): ?>
                                                <span class="badge bg-danger" title="Tareas urgentes"><?php echo count($tareasUrgentes); ?></span>
                                            <?php endif; ?>
                                            <?php if (count($tareasPendientes) > 0): ?>
                                                <span class="badge bg-warning" title="Tareas pendientes"><?php echo count($tareasPendientes); ?></span>
                                            <?php endif; ?>
                                            <?php if (count($tareasCompletadas) > 0): ?>
                                                <span class="badge bg-success" title="Tareas completadas"><?php echo count($tareasCompletadas); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de tareas del día -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list-task"></i> Tareas del <?php echo date('d/m/Y', strtotime($fechaSeleccionada)); ?></h5>
                </div>
                <div class="card-body">
                    <div id="listaTareasDia">
                        <?php if (empty($tareasDia)): ?>
                            <p class="text-muted text-center">No hay tareas para este día</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($tareasDia as $tarea): ?>
                                    <div class="list-group-item tarea-item <?php echo $tarea['estado'] == 'completada' ? 'tarea-completada' : ''; ?>" 
                                         data-id="<?php echo $tarea['id']; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <input type="checkbox" class="form-check-input tarea-checkbox" 
                                                           <?php echo $tarea['estado'] == 'completada' ? 'checked' : ''; ?>
                                                           <?php echo tienePermiso('agenda_tareas:decide') ? '' : 'disabled'; ?>
                                                           onchange="toggleTarea(<?php echo $tarea['id']; ?>, this.checked)">
                                                    <h6 class="mb-0 tarea-titulo"><?php echo htmlspecialchars($tarea['titulo']); ?></h6>
                                                </div>
                                                <?php if (!empty($tarea['descripcion'])): ?>
                                                    <p class="text-muted small mb-1"><?php echo htmlspecialchars($tarea['descripcion']); ?></p>
                                                <?php endif; ?>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <?php if ($tarea['hora']): ?>
                                                        <span class="badge bg-info">
                                                            <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($tarea['hora'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="badge bg-<?php 
                                                        echo $tarea['prioridad'] == 'alta' ? 'danger' : ($tarea['prioridad'] == 'media' ? 'warning' : 'secondary'); 
                                                    ?>">
                                                        <?php echo ucfirst($tarea['prioridad']); ?>
                                                    </span>
                                                    <?php if ($tarea['categoria']): ?>
                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($tarea['categoria']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if (tienePermiso('agenda_tareas:edit') || tienePermiso('agenda_tareas:delete')): ?>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <?php if (tienePermiso('agenda_tareas:edit')): ?>
                                                <button class="btn btn-outline-primary btn-icono" onclick="editarTarea(<?php echo $tarea['id']; ?>)" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if (tienePermiso('agenda_tareas:delete')): ?>
                                                <button type="button" class="btn btn-outline-danger btn-icono" data-codigo="<?php echo htmlspecialchars($tarea['titulo']); ?>" onclick="eliminarTarea(<?php echo $tarea['id']; ?>, this)" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva/Editar Tarea -->
<div class="modal fade" id="modalNuevaTarea" tabindex="-1" aria-labelledby="modalNuevaTareaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaTareaLabel">
                    <i class="bi bi-plus-circle"></i> Nueva Tarea
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaTarea">
                    <input type="hidden" id="tarea_id" name="id" value="">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha" class="form-label">Fecha *</label>
                            <input type="date" class="form-control" id="fecha" name="fecha" 
                                   value="<?php echo $fechaSeleccionada; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hora" class="form-label">Hora (opcional)</label>
                            <input type="time" class="form-control" id="hora" name="hora">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="prioridad" class="form-label">Prioridad</label>
                            <select class="form-select" id="prioridad" name="prioridad">
                                <option value="baja">Baja</option>
                                <option value="media" selected>Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="categoria" class="form-label">Categoría (opcional)</label>
                            <input type="text" class="form-control" id="categoria" name="categoria" 
                                   placeholder="Ej: Personal, Trabajo, Urgente">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarTarea">
                    <i class="bi bi-save"></i> <span id="btnGuardarTareaTexto">Guardar Tarea</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Pasar BASE_URL al JavaScript -->
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
    window.FECHA_SELECCIONADA = '<?php echo $fechaSeleccionada; ?>';
</script>
<!-- JavaScript del módulo de agenda -->
<script src="<?php echo BASE_URL; ?>front/public/js/agenda.js?v=2"></script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

