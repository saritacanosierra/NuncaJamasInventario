<?php
/**
 * Controlador de Producción
 */

class ProduccionController {
    private $produccionModel;
    
    public function __construct($db) {
        try {
            require_once BASE_DIR . '/back/models/Produccion.php';
            $this->produccionModel = new Produccion($db);
        } catch (Exception $e) {
            error_log('Error al inicializar ProduccionController: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Mostrar vista principal de producción
     */
    public function index() {
        // Capturar errores fatales
        set_error_handler(function($severity, $message, $file, $line) {
            error_log("Error fatal en ProduccionController::index() - Severidad: $severity, Mensaje: $message, Archivo: $file, Línea: $line");
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        
        try {
            requireAuth();
            
            // Verificar que el modelo esté inicializado
            if (!isset($this->produccionModel) || !$this->produccionModel) {
                throw new Exception('El modelo de producción no está inicializado');
            }
            
            // Verificar que el operario tenga nombre configurado
            if (!ve_produccion_ajena()) {
                $nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
                if (empty($nombreUsuario) || trim($nombreUsuario) === '') {
                    $_SESSION['error'] = 'Error: El nombre del usuario operario no está configurado. Por favor, contacte al administrador para configurar su nombre en el sistema.';
                }
            }
            
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $operariaSeleccionada = $_GET['operaria'] ?? '';
            
            // Si es operario, solo puede ver su propia operaria
            if (!ve_produccion_ajena()) {
                $nombreUsuario = trim($_SESSION['usuario_nombre'] ?? '');
                if (!empty($nombreUsuario)) {
                    $operariaSeleccionada = $nombreUsuario;
                }
            }
            
            // Obtener todas las operarias del día
            // Si es administrador, mostrar todos los registros. Si es operario, solo los suyos.
            try {
                $soloPropios = !ve_produccion_ajena();
                $operariasDelDia = $this->produccionModel->getOperariasDelDia($fecha, $_SESSION['usuario_id'], $soloPropios);
            } catch (PDOException $e) {
                error_log('Error de base de datos obteniendo operarias del día: ' . $e->getMessage());
                error_log('SQL State: ' . $e->getCode());
                // Verificar si es porque las tablas no existen
                if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Table") !== false) {
                    $_SESSION['error'] = 'Error: Las tablas de producción no existen en la base de datos. Por favor, ejecuta el script crear_tabla_produccion.sql en phpMyAdmin.';
                    redirect('index.php?action=dashboard');
                    return;
                }
                $operariasDelDia = [];
            } catch (Exception $e) {
                error_log('Error obteniendo operarias del día: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                $operariasDelDia = [];
            }
            
            // Si es operario, filtrar solo su operaria
            if (!ve_produccion_ajena()) {
                $nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
                if (!empty($nombreUsuario)) {
                    $operariasDelDia = array_filter($operariasDelDia, function($op) use ($nombreUsuario) {
                        return isset($op['operaria_nombre']) && $op['operaria_nombre'] === $nombreUsuario;
                    });
                    // Reindexar el array después del filtro
                    $operariasDelDia = array_values($operariasDelDia);
                }
            }
            
            // Si hay una operaria seleccionada, obtener su registro
            $registro = null;
            $operaciones = [];
            $retrocesos = [];
            $tiempoPerdido = 0;
            
            if ($operariaSeleccionada) {
                try {
                    $registro = $this->produccionModel->getRegistroPorOperaria($fecha, $operariaSeleccionada, $_SESSION['usuario_id'], ve_produccion_ajena());
                    
                    if ($registro) {
                        $operaciones = $this->produccionModel->getOperaciones($registro['id']);
                        $retrocesos = $this->produccionModel->getRetrocesos($registro['id']);
                        $tiempoPerdido = $this->produccionModel->getTiempoPerdidoRetrocesos($registro['id']);
                    }
                } catch (Exception $e) {
                    error_log('Error obteniendo registro de operaria: ' . $e->getMessage());
                }
            }
            
            // Verificar si el día está finalizado
            $diaFinalizado = false;
            if (method_exists($this->produccionModel, 'diaFinalizado')) {
                try {
                    // Verificar si el día está finalizado (por cualquier usuario, especialmente administradores)
                    $diaFinalizado = $this->produccionModel->diaFinalizado($fecha);
                } catch (Exception $e) {
                    error_log('Error verificando día finalizado: ' . $e->getMessage());
                    $diaFinalizado = false;
                }
            }
            
            // Asegurar que todas las variables estén definidas
            $operariasDelDia = $operariasDelDia ?? [];
            $registro = $registro ?? null;
            $operaciones = $operaciones ?? [];
            $retrocesos = $retrocesos ?? [];
            $tiempoPerdido = $tiempoPerdido ?? 0;
            $diaFinalizado = $diaFinalizado ?? false;
            $fecha = $fecha ?? date('Y-m-d');
            $operariaSeleccionada = $operariaSeleccionada ?? '';
            
            // Verificar que la vista existe
            $vistaPath = BASE_DIR . '/front/views/produccion/index.php';
            if (!file_exists($vistaPath)) {
                error_log("Error: La vista $vistaPath no existe");
                $_SESSION['error'] = 'Error: La vista de producción no se encuentra';
                redirect(permisos_url_inicio());
                return;
            }
            
            require_once $vistaPath;
            
        } catch (ErrorException $e) {
            error_log('Error fatal en ProduccionController::index(): ' . $e->getMessage());
            error_log('Archivo: ' . $e->getFile() . ', Línea: ' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());
            restore_error_handler();
            $_SESSION['error'] = 'Error interno del servidor. Por favor, contacte al administrador.';
            redirect('index.php?action=dashboard');
            return;
        } catch (Exception $e) {
            error_log('Excepción en ProduccionController::index(): ' . $e->getMessage());
            error_log('Archivo: ' . $e->getFile() . ', Línea: ' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());
            restore_error_handler();
            $_SESSION['error'] = 'Error al cargar la página de producción: ' . htmlspecialchars($e->getMessage());
            redirect('index.php?action=dashboard');
            return;
        } catch (Error $e) {
            error_log('Error fatal (PHP Error) en ProduccionController::index(): ' . $e->getMessage());
            error_log('Archivo: ' . $e->getFile() . ', Línea: ' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());
            restore_error_handler();
            $_SESSION['error'] = 'Error fatal del servidor. Por favor, contacte al administrador.';
            redirect('index.php?action=dashboard');
            return;
        } finally {
            restore_error_handler();
        }
    }
    
    /**
     * Crear o actualizar registro diario (API)
     */
    public function guardarRegistro() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $operariaNombre = trim($_POST['operaria_nombre'] ?? '');
        
        if (empty($operariaNombre)) {
            echo json_encode(['success' => false, 'error' => 'El nombre de la operaria es obligatorio']);
            exit;
        }
        
        // Sin permiso de jornada ajena, el día solo se abre con el propio nombre
        if (!ve_produccion_ajena() || !tienePermiso('produccion_jornada:create')) {
            $nombreUsuario = trim($_SESSION['usuario_nombre'] ?? '');
            if (empty($nombreUsuario) || strtolower($nombreUsuario) !== strtolower($operariaNombre)) {
                echo json_encode(['success' => false, 'error' => 'Solo puede crear o editar registros con su propio nombre']);
                exit;
            }
            
            // Verificar si ya existe un registro para este operario en esta fecha
        $registroExistente = $this->produccionModel->getRegistroPorOperaria($fecha, $operariaNombre, $_SESSION['usuario_id'], ve_produccion_ajena());
            
            // Si ya existe un registro, permitir actualizarlo pero no crear uno nuevo con otro nombre
            if ($registroExistente && $registroExistente['operaria_nombre'] !== $operariaNombre) {
                echo json_encode(['success' => false, 'error' => 'Ya existe un registro para este día. Solo puede actualizar su registro existente.']);
                exit;
            }
        } else {
            // Para no-operarios, buscar registro existente normalmente
            $registroExistente = $this->produccionModel->getRegistroPorOperaria($fecha, $operariaNombre, $_SESSION['usuario_id'], ve_produccion_ajena());
        }
        
        $data = [
            'operaria_nombre' => $operariaNombre,
            'fecha' => $fecha,
            'turno' => $_POST['turno'] ?? 'mañana',
            'meta_dia' => intval($_POST['meta_dia'] ?? 0),
            'maquina_asignada' => $_POST['maquina_asignada'] ?? '',
            'tiempo_total_trabajado' => intval($_POST['tiempo_total_trabajado'] ?? 0),
            'tiempo_perdido_retrocesos' => intval($_POST['tiempo_perdido_retrocesos'] ?? 0),
            'piezas_producidas' => intval($_POST['piezas_producidas'] ?? 0),
            'eficiencia_promedio' => floatval($_POST['eficiencia_promedio'] ?? 0)
        ];
        
        if ($registroExistente) {
            $result = $this->produccionModel->actualizarRegistro($registroExistente['id'], $data);
            $registroId = $registroExistente['id'];
        } else {
            $data['usuario_id'] = $_SESSION['usuario_id'];
            $registroId = $this->produccionModel->crearRegistro($data);
            $result = $registroId !== false;
        }
        
        if ($result) {
            $this->recalcularEstadisticas($registroId);
            $registro = $this->produccionModel->getRegistroById($registroId);
            echo json_encode(['success' => true, 'registro' => $registro, 'message' => 'Registro guardado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar el registro']);
        }
        exit;
    }
    
    /**
     * Guardar operación (API)
     */
    public function guardarOperacion() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $operariaNombre = trim($_POST['operaria_nombre'] ?? '');
        
        if (empty($operariaNombre)) {
            echo json_encode(['success' => false, 'error' => 'El nombre de la operaria es obligatorio']);
            exit;
        }
        
        // Si es operario, solo puede trabajar con operaciones de su nombre
        if (!ve_produccion_ajena()) {
            $nombreUsuario = trim($_SESSION['usuario_nombre'] ?? '');
            if (strtolower($operariaNombre) !== strtolower($nombreUsuario)) {
                echo json_encode(['success' => false, 'error' => 'Solo puede agregar operaciones para su propio nombre']);
                exit;
            }
        }
        
        $registro = $this->produccionModel->getRegistroPorOperaria($fecha, $operariaNombre, $_SESSION['usuario_id'], ve_produccion_ajena());
        
        if (!$registro) {
            echo json_encode(['success' => false, 'error' => 'Debe crear primero el registro del día para esta operaria']);
            exit;
        }
        
        $data = [
            'registro_id' => $registro['id'],
            'codigo_operacion' => trim($_POST['codigo_operacion'] ?? ''),
            'nombre_operacion' => trim($_POST['nombre_operacion'] ?? ''),
            'maquina_usada' => $_POST['maquina_usada'] ?? '',
            'hora_inicio' => $_POST['hora_inicio'] ?? date('Y-m-d H:i:s'),
            'hora_fin' => $_POST['hora_fin'] ?? null,
            'tiempo_total_minutos' => floatval($_POST['tiempo_total_minutos'] ?? 0),
            'piezas_producidas' => intval($_POST['piezas_producidas'] ?? 0),
            'tiempo_estandar_por_pieza' => floatval($_POST['tiempo_estandar_por_pieza'] ?? 0),
            'eficiencia' => floatval($_POST['eficiencia'] ?? 0),
            'cantidad_pausas' => intval($_POST['cantidad_pausas'] ?? 0),
            'tiempo_pausas_minutos' => floatval($_POST['tiempo_pausas_minutos'] ?? 0)
        ];
        
        if (empty($data['nombre_operacion'])) {
            echo json_encode(['success' => false, 'error' => 'El nombre de la operación es obligatorio']);
            exit;
        }
        
        // Verificar si el día está finalizado
        if ($this->produccionModel->diaFinalizado($fecha)) {
            echo json_encode(['success' => false, 'error' => 'No se puede modificar operaciones de un día que ya está finalizado']);
            exit;
        }
        
        $operacionId = $_POST['operacion_id'] ?? null;
        
        if ($operacionId) {
            $result = $this->produccionModel->actualizarOperacion($operacionId, $data);
        } else {
            $operacionId = $this->produccionModel->crearOperacion($data);
            $result = $operacionId !== false;
        }
        
        if ($result) {
            // Si es una actualización, registrar en historial
            if ($operacionId && isset($_POST['operacion_id']) && !empty($_POST['operacion_id'])) {
                // Ya se registró en el modelo actualizarOperacion
            } else if ($operacionId) {
                // Si es nueva, ya se registró el inicio en crearOperacion
            }
            
            // Recalcular estadísticas del registro
            $this->recalcularEstadisticas($registro['id']);
            echo json_encode(['success' => true, 'operacion_id' => $operacionId, 'message' => 'Operación guardada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar la operación']);
        }
        exit;
    }
    
    /**
     * Obtener historial completo de una operación (API)
     */
    public function getHistorialOperacion() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $operacionId = $_GET['operacion_id'] ?? 0;
        
        if (empty($operacionId)) {
            echo json_encode(['success' => false, 'error' => 'ID de operación requerido']);
            exit;
        }
        
        $historial = $this->produccionModel->getHistorialCompletoOperacion($operacionId);
        echo json_encode(['success' => true, 'historial' => $historial]);
        exit;
    }
    
    /**
     * Eliminar operación (API)
     */
    public function eliminarOperacion() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        // Obtener la fecha de la operación para verificar si el día está finalizado
        $operacionId = $_POST['operacion_id'] ?? ($_POST['id'] ?? 0);
        if ($operacionId) {
            $operacion = $this->produccionModel->getOperacionById($operacionId);
            if ($operacion) {
                $registro = $this->produccionModel->getRegistroById($operacion['registro_id']);
                if ($registro) {
                    $fecha = $registro['fecha'];
                    if ($this->produccionModel->diaFinalizado($fecha)) {
                        echo json_encode(['success' => false, 'error' => 'No se puede eliminar operaciones de un día que ya está finalizado']);
                        exit;
                    }
                }
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $id = intval($_POST['id'] ?? 0);
        $operacionBorrar = $this->produccionModel->getOperacionById($id);
        if (!$operacionBorrar) {
            echo json_encode(['success' => false, 'error' => 'Operación no encontrada']);
            exit;
        }
        $esperado = trim($operacionBorrar['codigo_operacion'] ?? '') !== ''
            ? $operacionBorrar['codigo_operacion']
            : ($operacionBorrar['nombre_operacion'] ?? '');
        if (!codigo_eliminacion_valido($esperado)) {
            echo json_encode(['success' => false, 'error' => 'La confirmación no coincide. No se eliminó.']);
            exit;
        }
        
        if ($this->produccionModel->eliminarOperacion($id)) {
            echo json_encode(['success' => true, 'message' => 'Operación eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar la operación']);
        }
        exit;
    }
    
    /**
     * Guardar retroceso (API)
     */
    public function guardarRetroceso() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $operariaNombre = trim($_POST['operaria_nombre'] ?? '');
        
        if (empty($operariaNombre)) {
            echo json_encode(['success' => false, 'error' => 'El nombre de la operaria es obligatorio']);
            exit;
        }
        
        // Verificar si el día está finalizado
        if ($this->produccionModel->diaFinalizado($fecha)) {
            echo json_encode(['success' => false, 'error' => 'No se pueden agregar retrocesos a un día que ya está finalizado']);
            exit;
        }
        
        // Buscar registro específico de la operaria
        $registro = $this->produccionModel->getRegistroPorOperaria($fecha, $operariaNombre, $_SESSION['usuario_id'], ve_produccion_ajena());
        
        if (!$registro) {
            echo json_encode(['success' => false, 'error' => 'Debe crear primero el registro del día para esta operaria']);
            exit;
        }
        
        $data = [
            'registro_id' => $registro['id'],
            'operacion_id' => !empty($_POST['operacion_id']) ? intval($_POST['operacion_id']) : null,
            'tipo_defecto' => $_POST['tipo_defecto'] ?? '',
            'maquina' => $_POST['maquina'] ?? '',
            'minutos_perdidos' => intval($_POST['minutos_perdidos'] ?? 0),
            'accion_correctiva' => trim($_POST['accion_correctiva'] ?? '')
        ];
        
        if (empty($data['tipo_defecto'])) {
            echo json_encode(['success' => false, 'error' => 'El tipo de defecto es obligatorio']);
            exit;
        }
        
        $retrocesoId = $this->produccionModel->crearRetroceso($data);
        
        if ($retrocesoId) {
            // Recalcular estadísticas del registro
            $this->recalcularEstadisticas($registro['id']);
            echo json_encode(['success' => true, 'retroceso_id' => $retrocesoId, 'message' => 'Retroceso registrado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al registrar el retroceso']);
        }
        exit;
    }
    
    /**
     * Obtener operaciones del día (API)
     */
    public function getOperaciones() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        // Si se proporciona registro_id, usarlo directamente
        if (!empty($_GET['registro_id'])) {
            $registroId = intval($_GET['registro_id']);
            $operaciones = $this->produccionModel->getOperaciones($registroId);
            echo json_encode(['success' => true, 'operaciones' => $operaciones]);
            exit;
        }
        
        // Si no, buscar por fecha y operaria
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $operariaNombre = $_GET['operaria'] ?? '';
        
        if (empty($operariaNombre)) {
            echo json_encode(['success' => true, 'operaciones' => []]);
            exit;
        }
        
        $registro = $this->produccionModel->getRegistroPorOperaria($fecha, $operariaNombre, $_SESSION['usuario_id'], ve_produccion_ajena());
        
        if (!$registro) {
            echo json_encode(['success' => true, 'operaciones' => []]);
            exit;
        }
        
        $operaciones = $this->produccionModel->getOperaciones($registro['id']);
        echo json_encode(['success' => true, 'operaciones' => $operaciones]);
        exit;
    }
    
    /**
     * Obtener todas las operarias del día (API)
     */
    public function getOperariasDelDia() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $soloPropios = !ve_produccion_ajena();
        $operarias = $this->produccionModel->getOperariasDelDia($fecha, $_SESSION['usuario_id'], $soloPropios);
        
        echo json_encode(['success' => true, 'operarias' => $operarias]);
        exit;
    }
    
    /**
     * Obtener retrocesos de un registro (API)
     */
    public function getRetrocesos() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $registroId = $_GET['registro_id'] ?? 0;
        
        if (empty($registroId)) {
            echo json_encode(['success' => true, 'retrocesos' => []]);
            exit;
        }
        
        $retrocesos = $this->produccionModel->getRetrocesos($registroId);
        echo json_encode(['success' => true, 'retrocesos' => $retrocesos]);
        exit;
    }
    
    /**
     * Recalcular estadísticas del registro
     */
    private function recalcularEstadisticas($registroId) {
        $operaciones = $this->produccionModel->getOperaciones($registroId);
        $retrocesos = $this->produccionModel->getRetrocesos($registroId);
        
        $tiempoTotal = 0;
        $piezasTotal = 0;
        $eficienciaTotal = 0;
        $tiempoPerdido = 0;
        
        foreach ($operaciones as $op) {
            $tiempoTotal += $op['tiempo_total_minutos'];
            $piezasTotal += $op['piezas_producidas'];
            $eficienciaTotal += $op['eficiencia'];
        }
        
        foreach ($retrocesos as $ret) {
            $tiempoPerdido += intval($ret['minutos_perdidos'] ?? 0);
        }
        
        $eficienciaPromedio = count($operaciones) > 0 ? $eficienciaTotal / count($operaciones) : 0;
        
        $registro = $this->produccionModel->getRegistroById($registroId);
        if ($registro) {
            $this->produccionModel->actualizarRegistro($registroId, [
                'operaria_nombre' => $registro['operaria_nombre'],
                'turno' => $registro['turno'],
                'meta_dia' => $registro['meta_dia'],
                'maquina_asignada' => $registro['maquina_asignada'],
                'tiempo_total_trabajado' => $tiempoTotal,
                'tiempo_perdido_retrocesos' => $tiempoPerdido,
                'piezas_producidas' => $piezasTotal,
                'eficiencia_promedio' => $eficienciaPromedio
            ]);
        }
    }
    
    /**
     * Buscar operarias por coincidencias (API)
     */
    public function buscarOperarias() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $busqueda = $_GET['busqueda'] ?? '';
        $usuarioBusqueda = ve_produccion_ajena() ? null : $_SESSION['usuario_id'];
        $operarias = $this->produccionModel->getOperariasUnicas($busqueda, $usuarioBusqueda);
        
        echo json_encode(['success' => true, 'operarias' => $operarias]);
        exit;
    }
    
    /**
     * Generar código único de operación (API)
     */
    public function generarCodigoOperacion() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $codigo = $this->produccionModel->generarCodigoOperacion($fecha);
        
        echo json_encode(['success' => true, 'codigo_operacion' => $codigo]);
        exit;
    }
    
    /**
     * Obtener resumen del día (API)
     */
    public function getResumenDia() {
        // Verificar autenticación sin redirección para APIs
        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $soloPropios = !ve_produccion_ajena();
            $resumen = $this->produccionModel->getResumenDia($fecha, $_SESSION['usuario_id'], $soloPropios);
            
            $response = [
                'success' => true,
                'total_operarias' => $resumen['total_operarias'] ?? 0,
                'total_operaciones' => $resumen['total_operaciones'] ?? 0,
                'prendas_terminadas' => $resumen['prendas_terminadas'] ?? 0,
                'prendas_empezadas' => $resumen['prendas_empezadas'] ?? 0,
                'tiempo_total_minutos' => $resumen['tiempo_total_minutos'] ?? 0
            ];
            
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener el resumen: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Finalizar día y guardar bitácora (API)
     */
    public function finalizarDia() {
        // Verificar autenticación sin redirección para APIs
        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $prendasTerminadas = intval($_POST['prendas_terminadas'] ?? 0);
        $prendasEmpezadas = intval($_POST['prendas_empezadas'] ?? 0);
        $tiempoTotalMinutos = floatval($_POST['tiempo_total_minutos'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');
        
        // Validar datos
        if ($prendasTerminadas < 0 || $prendasEmpezadas < 0) {
            echo json_encode(['success' => false, 'error' => 'Los valores de prendas no pueden ser negativos']);
            exit;
        }

        if ($this->produccionModel->diaFinalizado($fecha)) {
            echo json_encode(['success' => false, 'error' => 'Este día ya está cerrado. Solo se puede cerrar una vez.']);
            exit;
        }
        
        try {
            error_log('Intentando finalizar día - Fecha: ' . $fecha . ', Usuario: ' . $_SESSION['usuario_id']);
            error_log('Datos: prendas_terminadas=' . $prendasTerminadas . ', prendas_empezadas=' . $prendasEmpezadas . ', tiempo=' . $tiempoTotalMinutos);
            
            $result = $this->produccionModel->guardarCierreDia($fecha, $_SESSION['usuario_id'], $observaciones, $prendasTerminadas, $prendasEmpezadas, $tiempoTotalMinutos);
            
            if ($result) {
                error_log('Día finalizado exitosamente');
                echo json_encode(['success' => true, 'message' => 'Día finalizado exitosamente']);
            } else {
                error_log('Error: guardarCierreDia retornó false');
                $errorMsg = 'Error al guardar el cierre del día. Verifique los logs del servidor para más detalles.';
                echo json_encode(['success' => false, 'error' => $errorMsg]);
            }
        } catch (Exception $e) {
            error_log('Excepción en finalizarDia: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            echo json_encode([
                'success' => false, 
                'error' => 'Error al guardar el cierre del día: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Abre de nuevo un día cerrado. Solo quien puede cerrar el día.
     */
    public function reabrirDia() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();

        $fecha = $_POST['fecha'] ?? date('Y-m-d');

        if (!$this->produccionModel->diaFinalizado($fecha)) {
            echo json_encode(['success' => false, 'error' => 'Este día ya está abierto.']);
            exit;
        }

        if ($this->produccionModel->reabrirDia($fecha)) {
            echo json_encode(['success' => true, 'message' => 'El día quedó abierto de nuevo.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo abrir el día.']);
        }
        exit;
    }
    
    /**
     * Verificar si un día está finalizado (API)
     */
    public function verificarDiaFinalizado() {
        // Verificar autenticación sin redirección para APIs
        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            // Verificar si el día está finalizado (por cualquier usuario, especialmente administradores)
            $finalizado = $this->produccionModel->diaFinalizado($fecha);
            
            echo json_encode(['success' => true, 'finalizado' => $finalizado]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al verificar estado: ' . $e->getMessage(),
                'finalizado' => false
            ]);
        }
        exit;
    }
    
    /**
     * Dashboard de operaciones
     */
    public function dashboardOperaciones() {
        requireAuth();
        
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        
        // Obtener datos para el dashboard
        $verTodas = ve_produccion_ajena();
        $resumenGeneral = $this->produccionModel->getResumenGeneral($fechaInicio, $fechaFin, $_SESSION['usuario_id'], $verTodas);
        $operariasRendimiento = $this->produccionModel->getRendimientoOperarias($fechaInicio, $fechaFin, $_SESSION['usuario_id'], $verTodas);
        $bitacoraDias = $this->produccionModel->getBitacoraDias($fechaInicio, $fechaFin, $_SESSION['usuario_id'], $verTodas);
        
        require_once BASE_DIR . '/front/views/produccion/dashboard_operaciones.php';
    }
}