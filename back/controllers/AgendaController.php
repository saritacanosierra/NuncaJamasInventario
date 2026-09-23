<?php
/**
 * Controlador de Agenda y Tareas
 */

class AgendaController {
    private $tareaModel;
    
    public function __construct($db) {
        require_once BASE_DIR . '/back/models/Tarea.php';
        $this->tareaModel = new Tarea($db);
    }
    
    /**
     * Mostrar agenda principal
     */
    public function index() {
        requireAuth();
        
        // Obtener fecha actual o fecha seleccionada
        $fechaSeleccionada = $_GET['fecha'] ?? date('Y-m-d');
        $mesSeleccionado = $_GET['mes'] ?? date('Y-m');
        
        // Obtener tareas del mes
        list($year, $month) = explode('-', $mesSeleccionado);
        $tareasMes = $this->tareaModel->getByMonth($year, $month, $_SESSION['usuario_id']);
        
        // Obtener tareas del día seleccionado
        $tareasDia = $this->tareaModel->getByDate($fechaSeleccionada, $_SESSION['usuario_id']);
        
        // Obtener estadísticas
        $estadisticas = $this->tareaModel->getEstadisticas($_SESSION['usuario_id']);
        
        require_once BASE_DIR . '/front/views/agenda/index.php';
    }
    
    /**
     * Crear nueva tarea (API)
     */
    public function store() {
        // Limpiar cualquier output previo
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        requireAuth();
        
        // Limpiar output antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $data = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'hora' => $_POST['hora'] ?? null,
            'prioridad' => $_POST['prioridad'] ?? 'media',
            'estado' => 'pendiente',
            'categoria' => trim($_POST['categoria'] ?? ''),
            'usuario_id' => $_SESSION['usuario_id']
        ];
        
        if (empty($data['titulo'])) {
            echo json_encode(['success' => false, 'error' => 'El título es obligatorio']);
            exit;
        }
        
        try {
            $id = $this->tareaModel->create($data);
            
            if ($id && $id > 0) {
                $tarea = $this->tareaModel->getById($id);
                if ($tarea) {
                    echo json_encode(['success' => true, 'tarea' => $tarea, 'message' => 'Tarea creada exitosamente']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Tarea creada pero no se pudo recuperar']);
                }
            } else {
                // Log del error para depuración
                error_log('Error al crear tarea: create() retornó false o 0');
                echo json_encode(['success' => false, 'error' => 'Error al crear la tarea. Verifique los logs del servidor.']);
            }
        } catch (PDOException $e) {
            ob_clean();
            error_log('Error de BD al crear tarea: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error de base de datos al crear la tarea']);
        } catch (Exception $e) {
            ob_clean();
            error_log('Error al crear tarea: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al crear la tarea: ' . $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Obtener tarea por ID (API)
     */
    public function getTarea() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $id = intval($_GET['id'] ?? 0);
        $tarea = $this->tareaModel->getById($id);
        
        if ($tarea && $tarea['usuario_id'] == $_SESSION['usuario_id']) {
            echo json_encode(['success' => true, 'tarea' => $tarea]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Tarea no encontrada']);
        }
        exit;
    }
    
    /**
     * Actualizar tarea (API)
     */
    public function update() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $id = intval($_POST['id'] ?? 0);
        $tarea = $this->tareaModel->getById($id);
        
        if (!$tarea || $tarea['usuario_id'] != $_SESSION['usuario_id']) {
            echo json_encode(['success' => false, 'error' => 'Tarea no encontrada']);
            exit;
        }
        
        $data = [
            'titulo' => trim($_POST['titulo'] ?? $tarea['titulo']),
            'descripcion' => trim($_POST['descripcion'] ?? $tarea['descripcion']),
            'fecha' => $_POST['fecha'] ?? $tarea['fecha'],
            'hora' => $_POST['hora'] ?? $tarea['hora'],
            'prioridad' => $_POST['prioridad'] ?? $tarea['prioridad'],
            'estado' => $_POST['estado'] ?? $tarea['estado'],
            'categoria' => trim($_POST['categoria'] ?? $tarea['categoria'])
        ];
        
        if (empty($data['titulo'])) {
            echo json_encode(['success' => false, 'error' => 'El título es obligatorio']);
            exit;
        }
        
        if ($this->tareaModel->update($id, $data)) {
            $tareaActualizada = $this->tareaModel->getById($id);
            echo json_encode(['success' => true, 'tarea' => $tareaActualizada, 'message' => 'Tarea actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar la tarea']);
        }
        exit;
    }
    
    /**
     * Marcar tarea como completada (API)
     */
    public function completar() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $id = intval($_POST['id'] ?? 0);
        $tarea = $this->tareaModel->getById($id);
        
        if (!$tarea || $tarea['usuario_id'] != $_SESSION['usuario_id']) {
            echo json_encode(['success' => false, 'error' => 'Tarea no encontrada']);
            exit;
        }
        
        if ($this->tareaModel->completar($id)) {
            echo json_encode(['success' => true, 'message' => 'Tarea completada']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al completar la tarea']);
        }
        exit;
    }
    
    /**
     * Eliminar tarea (API)
     */
    public function delete() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            echo json_encode(['success' => false, 'error' => 'La solicitud no es válida. Recargue la página e intente de nuevo.']);
            exit;
        }
        
        $id = intval($_POST['id'] ?? 0);
        $tarea = $this->tareaModel->getById($id);
        
        if (!$tarea || $tarea['usuario_id'] != $_SESSION['usuario_id']) {
            echo json_encode(['success' => false, 'error' => 'Tarea no encontrada']);
            exit;
        }
        if (!codigo_eliminacion_valido($tarea['titulo'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'La confirmación no coincide. No se eliminó.']);
            exit;
        }
        
        if ($this->tareaModel->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Tarea eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar la tarea']);
        }
        exit;
    }
    
    /**
     * Obtener tareas por fecha (API para calendario)
     */
    public function getTareasPorFecha() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $tareas = $this->tareaModel->getByDate($fecha, $_SESSION['usuario_id']);
        
        echo json_encode(['success' => true, 'tareas' => $tareas]);
        exit;
    }
}