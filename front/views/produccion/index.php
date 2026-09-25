<?php
$pageTitle = 'Producción';
require_once BASE_DIR . '/front/views/layout/header.php';

$fechaActual = date('Y-m-d');
$fecha = $_GET['fecha'] ?? $fechaActual;
?>

<div class="main-container<?php echo !ve_produccion_ajena() ? ' produccion-operario' : ''; ?>">
    <?php if (!ve_produccion_ajena()): ?>
    <div id="pisoOperario" class="piso-operario" aria-live="polite"></div>
    <?php endif; ?>
    <div class="produccion-admin-bar mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h2 class="mb-1"><i class="bi bi-gear-wide-connected"></i> Día de producción<?php $ayuda = 'Anota quién trabajó y qué hizo. Nueva operaria la suma al día y luego Guardar en el día. Nueva operación registra cada trabajo. Cerrar día de la empresa bloquea a todas y solo se hace una vez.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
            <p class="prod-ayuda">Para sumar a alguien: <strong>Nueva operaria</strong> y luego <strong>Guardar en el día</strong>. Cada trabajo se anota con <strong>Nueva operación</strong>. <strong>Cerrar día de la empresa</strong> bloquea a todas y solo se hace una vez.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <input type="date" id="fechaSeleccionada" class="form-control" value="<?php echo $fecha; ?>" 
                   onchange="cambiarFecha(this.value)" style="width: auto;">
            <?php if (tienePermiso('produccion_jornada:create') || tienePermiso('produccion_dashboard:view') || tienePermiso('produccion_cierre:decide') || tienePermiso('produccion_pago:view')): ?>
            <?php if (tienePermiso('produccion_jornada:create')): ?>
            <button class="btn btn-primary" id="btnNuevaOperaria">
                <i class="bi bi-plus-circle"></i> Nueva operaria
            </button>
            <?php endif; ?>
            <?php if (tienePermiso('produccion_dashboard:view')): ?>
            <button class="btn btn-info" onclick="window.location.href='<?php echo BASE_URL; ?>index.php?action=produccion&method=dashboardOperaciones'">
                <i class="bi bi-speedometer2"></i> Ver rendimiento
            </button>
            <?php endif; ?>
            <?php if (tienePermiso('produccion_pago:view')): ?>
            <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>index.php?action=produccion&method=pago">
                <i class="bi bi-cash-coin"></i> Pago por pieza
            </a>
            <?php endif; ?>
            <?php if (tienePermiso('produccion_cierre:decide')): ?>
            <button class="btn btn-outline-danger" id="btnFinalizarDia" onclick="abrirModalFinalizarDia()" <?php echo !empty($diaFinalizado) ? 'hidden' : ''; ?> title="Cierra el día de toda la empresa. No agrega operarias.">
                <i class="bi bi-lock"></i> Cerrar día de la empresa
            </button>
            <button class="btn btn-outline-primary" id="btnReabrirDia" onclick="reabrirDiaEmpresa()" <?php echo !empty($diaFinalizado) ? '' : 'hidden'; ?> title="Vuelve a permitir el registro de este día.">
                <i class="bi bi-unlock"></i> Abrir el día
            </button>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        </div>
        <div id="estadoDia" class="estado-dia <?php echo !empty($diaFinalizado) ? 'estado-cerrado' : 'estado-abierto'; ?>" role="status">
            <i class="bi <?php echo !empty($diaFinalizado) ? 'bi-lock' : 'bi-unlock'; ?>"></i>
            <div>
                <?php if (!empty($diaFinalizado)): ?>
                <strong>Día cerrado</strong>
                <span>Nadie puede registrar trabajo. Solo un administrador puede abrirlo de nuevo.</span>
                <?php else: ?>
                <strong>Día abierto</strong>
                <span>Las operarias pueden registrar su trabajo. Cerrar el día bloquea a toda la empresa y solo se hace una vez.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Pestañas de Operarias -->
    <div id="operariasTabs" class="mb-3"></div>
    
    <!-- Contenedor de datos de operarias (se llena dinámicamente) -->
    <div id="contenedorOperarias">
        <!-- Los datos de cada operaria se cargarán aquí dinámicamente -->
    </div>
</div>

<!-- Modal: Cronómetro de Operación -->
<div class="modal fade" id="modalCronometro" tabindex="-1" aria-labelledby="modalCronometroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCronometroLabel">
                    <i class="bi bi-stopwatch"></i> Registro de Tiempo - Operación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formOperacion">
                    <input type="hidden" id="operacion_id" name="operacion_id" value="">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="codigo_operacion" class="form-label">Código de Operación</label>
                            <input type="text" class="form-control" id="codigo_operacion" name="codigo_operacion" 
                                   placeholder="Ej: OP-001">
                        </div>
                        <div class="col-md-6">
                            <label for="nombre_operacion" class="form-label">Nombre de Operación *</label>
                            <input type="text" class="form-control" id="nombre_operacion" name="nombre_operacion" 
                                   placeholder="Ej: Bordado de logo" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="maquina_usada" class="form-label">Máquina Usada *</label>
                            <select class="form-select" id="maquina_usada" name="maquina_usada" required>
                                <option value="">Seleccione...</option>
                                <option value="Bordadora">Bordadora</option>
                                <option value="Recubridora">Recubridora</option>
                                <option value="Plana">Plana</option>
                                <option value="Fileteadora">Fileteadora</option>
                                <option value="Pulir">Pulir</option>
                                <option value="Detalles manuales">Detalles manuales</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="tiempo_estandar_por_pieza" class="form-label">Tiempo Estándar por Pieza (min)</label>
                            <input type="number" class="form-control" id="tiempo_estandar_por_pieza" 
                                   name="tiempo_estandar_por_pieza" step="0.01" min="0" placeholder="Ej: 2.5">
                        </div>
                    </div>
                    
                    <!-- Cronómetro -->
                    <div class="cronometro-container mb-4">
                        <div class="cronometro-display" id="cronometroDisplay">00:00:00</div>
                        <div class="cronometro-controls">
                            <button type="button" class="btn btn-success btn-lg" id="btnIniciar" onclick="iniciarCronometro()">
                                <i class="bi bi-play-fill"></i> Iniciar
                            </button>
                            <button type="button" class="btn btn-warning btn-lg" id="btnPausar" onclick="pausarCronometro()" style="display:none;">
                                <i class="bi bi-pause-fill"></i> Pausar
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg" id="btnReiniciar" onclick="reiniciarCronometro()" style="display:none;">
                                <i class="bi bi-arrow-clockwise"></i> Reiniciar
                            </button>
                            <button type="button" class="btn btn-danger btn-lg" id="btnFinalizar" onclick="finalizarCronometro()" style="display:none;">
                                <i class="bi bi-stop-fill"></i> Finalizar
                            </button>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Hora Inicio:</strong> <span id="horaInicioDisplay">-</span><br>
                                <strong>Hora Fin:</strong> <span id="horaFinDisplay">-</span>
                            </small>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="piezas_producidas" class="form-label">Piezas Producidas *</label>
                            <input type="number" class="form-control" id="piezas_producidas" name="piezas_producidas" 
                                   min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tiempo Total (minutos)</label>
                            <input type="text" class="form-control" id="tiempo_total_minutos" 
                                   name="tiempo_total_minutos" readonly>
                            <input type="hidden" id="hora_inicio" name="hora_inicio">
                            <input type="hidden" id="hora_fin" name="hora_fin">
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="cantidad_pausas" class="form-label">Cantidad de Pausas</label>
                            <input type="number" class="form-control" id="cantidad_pausas" name="cantidad_pausas" 
                                   value="0" min="0" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="tiempo_pausas" class="form-label">Tiempo Total de Pausas</label>
                            <input type="text" class="form-control" id="tiempo_pausas" name="tiempo_pausas" 
                                   value="0:00:00" readonly>
                            <input type="hidden" id="tiempo_pausas_minutos" name="tiempo_pausas_minutos" value="0">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarOperacion" onclick="guardarOperacion()" disabled>
                    <i class="bi bi-save"></i> Guardar Operación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registro de Retrocesos -->
<div class="modal fade" id="modalRetroceso" tabindex="-1" aria-labelledby="modalRetrocesoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalRetrocesoLabel">
                    <i class="bi bi-exclamation-triangle"></i> Registro de Retrocesos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formRetroceso">
                    <input type="hidden" id="retroceso_operacion_id" name="operacion_id" value="">
                    <div class="mb-3">
                        <label for="tipo_defecto" class="form-label">Tipo de Defecto *</label>
                        <select class="form-select" id="tipo_defecto" name="tipo_defecto" required>
                            <option value="">Seleccione...</option>
                            <option value="Hilo suelto">Hilo suelto</option>
                            <option value="Puntada corrida">Puntada corrida</option>
                            <option value="Fallo de máquina">Fallo de máquina</option>
                            <option value="Error humano">Error humano</option>
                            <option value="Desalineación">Desalineación</option>
                            <option value="Mancha">Mancha</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="retroceso_maquina" class="form-label">Máquina *</label>
                        <select class="form-select" id="retroceso_maquina" name="maquina" required>
                            <option value="">Seleccione...</option>
                            <option value="Bordadora">Bordadora</option>
                            <option value="Recubridora">Recubridora</option>
                            <option value="Plana">Plana</option>
                            <option value="Fileteadora">Fileteadora</option>
                            <option value="Pulir">Pulir</option>
                            <option value="Detalles manuales">Detalles manuales</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="minutos_perdidos" class="form-label">Minutos Perdidos *</label>
                        <input type="number" class="form-control" id="minutos_perdidos" name="minutos_perdidos" 
                               min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="accion_correctiva" class="form-label">Acción Correctiva</label>
                        <textarea class="form-control" id="accion_correctiva" name="accion_correctiva" rows="3" 
                                  placeholder="Describa la acción tomada para corregir el defecto"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="guardarRetroceso()">
                    <i class="bi bi-save"></i> Guardar Retroceso
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Seleccionar Operaria -->
<div class="modal fade" id="modalSeleccionarOperaria" tabindex="-1" aria-labelledby="modalSeleccionarOperariaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalSeleccionarOperariaLabel">
                    <i class="bi bi-people"></i> Seleccionar Operaria
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="buscarOperaria" class="form-label">Buscar operaria:</label>
                    <input type="text" class="form-control" id="buscarOperaria" 
                           placeholder="Escribe el nombre de la operaria..." 
                           autocomplete="off">
                    <small class="form-text text-muted">Escribe para buscar operarias existentes o crea una nueva</small>
                </div>
                <div id="listaOperarias" class="list-group" style="max-height: 300px; overflow-y: auto;">
                    <!-- Las operarias se cargarán aquí -->
                </div>
                <?php if (tienePermiso('produccion_jornada:create')): ?>
                <div class="mt-3">
                    <button type="button" class="btn btn-success w-100" id="btnCrearNuevaOperaria">
                        <i class="bi bi-plus-circle"></i> Crear Nueva Operaria
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Historial de Operación -->
<div class="modal fade" id="modalHistorialOperacion" tabindex="-1" aria-labelledby="modalHistorialOperacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalHistorialOperacionLabel">
                    <i class="bi bi-clock-history"></i> Historial de Cambios - Operación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historialOperacionContenido">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
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

<!-- Modal: Finalizar Día -->
<div class="modal fade" id="modalFinalizarDia" tabindex="-1" aria-labelledby="modalFinalizarDiaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFinalizarDiaLabel">
                    <i class="bi bi-lock"></i> Cerrar el día de la empresa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">Esto cierra el día de <strong>toda la empresa</strong> y solo se puede hacer una vez. No ingresa a una operaria. Para sumarla al día, usa <strong>Nueva operaria</strong> y luego <strong>Guardar en el día</strong>. Si te equivocas, un administrador puede abrir el día de nuevo.</div>
                <form id="formFinalizarDia">
                    <input type="hidden" id="fecha_cierre" name="fecha" value="<?php echo $fecha; ?>">
                    
                    <!-- Formulario de Datos -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="prendas_terminadas" class="form-label">
                                <i class="bi bi-check-circle text-success"></i> Prendas Terminadas *
                            </label>
                            <input type="number" class="form-control" id="prendas_terminadas" name="prendas_terminadas" 
                                   min="0" value="0" required>
                            <small class="form-text text-muted">Total de prendas completamente terminadas en el día</small>
                        </div>
                        <div class="col-md-6">
                            <label for="prendas_empezadas" class="form-label">
                                <i class="bi bi-hourglass-split text-warning"></i> Prendas Empezadas *
                            </label>
                            <input type="number" class="form-control" id="prendas_empezadas" name="prendas_empezadas" 
                                   min="0" value="0" required>
                            <small class="form-text text-muted">Prendas que se iniciaron pero no se terminaron</small>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="tiempo_total_horas" class="form-label">
                                <i class="bi bi-clock text-info"></i> Tiempo Total Invertido *
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="tiempo_total_horas" name="tiempo_total_horas" 
                                       min="0" max="23" value="0" placeholder="Horas" required>
                                <span class="input-group-text">:</span>
                                <input type="number" class="form-control" id="tiempo_total_minutos" name="tiempo_total_minutos" 
                                       min="0" max="59" value="0" placeholder="Minutos" required>
                                <span class="input-group-text">:</span>
                                <input type="number" class="form-control" id="tiempo_total_segundos" name="tiempo_total_segundos" 
                                       min="0" max="59" value="0" placeholder="Segundos" required>
                            </div>
                            <small class="form-text text-muted">Formato: Horas:Minutos:Segundos (ej: 8:30:00)</small>
                        </div>
                        <div class="col-md-6">
                            <label for="total_operarias" class="form-label">
                                <i class="bi bi-people text-primary"></i> Total Operarias
                            </label>
                            <input type="number" class="form-control" id="total_operarias" name="total_operarias" 
                                   min="0" value="0" readonly>
                            <small class="form-text text-muted">Se calcula automáticamente</small>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="total_operaciones" class="form-label">
                                <i class="bi bi-gear-wide-connected text-secondary"></i> Total Operaciones
                            </label>
                            <input type="number" class="form-control" id="total_operaciones" name="total_operaciones" 
                                   min="0" value="0" readonly>
                            <small class="form-text text-muted">Se calcula automáticamente</small>
                        </div>
                    </div>
                    
                    <!-- Observaciones / Bitácora -->
                    <div class="mb-3">
                        <label for="observaciones_cierre" class="form-label">
                            <i class="bi bi-journal-text"></i> Observaciones del Día (Bitácora)
                        </label>
                        <textarea class="form-control" id="observaciones_cierre" name="observaciones" 
                                  rows="5" placeholder="Registre aquí cualquier observación importante del día: problemas, logros, mejoras, etc."></textarea>
                        <small class="form-text text-muted">Este registro formará parte de la bitácora diaria de producción</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnGuardarCierreDia" onclick="guardarCierreDia()">
                    <i class="bi bi-lock"></i> Cerrar el día
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Pasar BASE_URL al JavaScript -->
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
    window.FECHA_ACTUAL = '<?php echo $fecha; ?>';
    window.DIA_FINALIZADO = <?php echo isset($diaFinalizado) && $diaFinalizado ? 'true' : 'false'; ?>;
    window.PRODUCCION_SOLO_PROPIOS = <?php echo ve_produccion_ajena() ? 'false' : 'true'; ?>;
    window.USUARIO_NOMBRE = '<?php echo htmlspecialchars(trim($_SESSION['usuario_nombre'] ?? '')); ?>';
    
    <?php if (!ve_produccion_ajena()): ?>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.USUARIO_NOMBRE || window.USUARIO_NOMBRE.trim() === '') {
            console.error('Error: El nombre del usuario operario no está configurado');
            mostrarAviso({
                titulo: 'Falta el nombre',
                mensaje: 'El nombre de la operaria no está configurado. Pide a la administradora que lo complete.'
            });
        }
    });
    <?php endif; ?>
</script>
<!-- JavaScript del módulo de producción -->
<?php
$scriptPartes = [
    'front/public/js/produccion/estado.js',
    'front/public/js/produccion/operarias.js',
    'front/public/js/produccion/operaciones.js',
    'front/public/js/produccion/registro.js',
    'front/public/js/produccion/cronometro.js',
    'front/public/js/produccion/guardado.js',
    'front/public/js/produccion/cierre.js',
];
$scriptVersion = '9';
require BASE_DIR . '/front/views/components/script_partes.php';
?>
<?php if (!ve_produccion_ajena()): ?>
<script src="<?php echo BASE_URL; ?>front/public/js/produccion-operario.js?v=5"></script>
<?php endif; ?>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
