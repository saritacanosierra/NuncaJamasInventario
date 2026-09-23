<?php
/**
 * Controlador de Clientes
 */

class ClienteController {
    private $clienteModel;
    
    public function __construct($db) {
        $this->clienteModel = new Cliente($db);
    }
    
    /**
     * Listar clientes
     */
    public function index() {
        requireAuth();
        
        $busqueda = $_GET['busqueda'] ?? '';
        
        if (!empty($busqueda)) {
            $clientes = $this->clienteModel->search($busqueda);
        } else {
            $clientes = $this->clienteModel->getAll();
        }
        
        require_once BASE_DIR . '/front/views/clientes/index.php';
    }
    
    /**
     * Guardar nuevo cliente
     */
    public function store() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=clientes');
        }
        exigir_csrf_redirect('index.php?action=clientes');
        
        $data = [
            'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
            'cedula_nit' => trim($_POST['cedula_nit'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];
        
        if (empty($data['nombre_completo']) || empty($data['cedula_nit'])) {
            $_SESSION['error'] = 'El nombre y cédula/NIT son obligatorios';
            redirect('index.php?action=clientes');
        }
        
        $id = $this->clienteModel->create($data);
        
        if ($id) {
            $_SESSION['success'] = 'Cliente creado exitosamente';
            redirect('index.php?action=clientes');
        } else {
            $_SESSION['error'] = 'Error al crear el cliente. Verifique que la cédula/NIT no esté duplicada';
            redirect('index.php?action=clientes');
        }
    }
    
    /**
     * Obtener cliente por ID (API para modal)
     */
    public function getCliente() {
        // Iniciar output buffering al inicio para capturar cualquier warning/notice
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de cliente inválido']);
            exit;
        }
        
        try {
            // Verificar que el modelo tenga conexión válida
            if (!$this->clienteModel) {
                throw new Exception('Modelo de cliente no inicializado');
            }
            
            $cliente = $this->clienteModel->getById($id);
            
            if ($cliente) {
                echo json_encode(['success' => true, 'cliente' => $cliente]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
            }
        } catch (PDOException $e) {
            ob_clean();
            error_log('Error de base de datos en getCliente: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'error' => 'Error de conexión a la base de datos. Verifique la configuración.'
            ]);
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en getCliente: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al cargar el cliente: ' . $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Actualizar cliente
     */
    public function update() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=clientes');
        }
        exigir_csrf_redirect('index.php?action=clientes');
        
        $id = intval($_POST['id'] ?? 0);
        $cliente = $this->clienteModel->getById($id);
        
        if (!$cliente) {
            $_SESSION['error'] = 'Cliente no encontrado';
            redirect('index.php?action=clientes');
        }
        
        $data = [
            'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
            'cedula_nit' => trim($_POST['cedula_nit'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];
        
        if ($this->clienteModel->update($id, $data)) {
            $_SESSION['success'] = 'Cliente actualizado exitosamente';
            redirect('index.php?action=clientes');
        } else {
            $_SESSION['error'] = 'Error al actualizar el cliente';
            redirect('index.php?action=clientes&method=edit&id=' . $id);
        }
    }
    
    /**
     * Eliminar cliente
     */
    public function delete() {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=clientes');
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id == 1) {
            $_SESSION['error'] = 'No se puede eliminar el cliente general';
            redirect('index.php?action=clientes');
        }

        $cliente = $this->clienteModel->getById($id);
        if (!$cliente) {
            $_SESSION['error'] = 'Cliente no encontrado';
            redirect('index.php?action=clientes');
        }
        $esperado = trim($cliente['cedula_nit'] ?? '') !== '' ? $cliente['cedula_nit'] : ($cliente['nombre_completo'] ?? '');
        if (!codigo_eliminacion_valido($esperado)) {
            $_SESSION['error'] = 'La confirmación no coincide. No se eliminó.';
            redirect('index.php?action=clientes');
        }
        
        if ($this->clienteModel->delete($id)) {
            $_SESSION['success'] = 'Cliente eliminado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar el cliente';
        }
        
        redirect('index.php?action=clientes');
    }
    
    /**
     * Ver historial de compras del cliente
     */
    public function historial() {
        requireAuth();
        
        $id = intval($_GET['id'] ?? 0);
        $cliente = $this->clienteModel->getById($id);
        
        if (!$cliente) {
            $_SESSION['error'] = 'Cliente no encontrado';
            redirect('index.php?action=clientes');
        }
        
        $compras = $this->clienteModel->getHistorialCompras($id);
        
        require_once BASE_DIR . '/front/views/clientes/historial.php';
    }
    
    /**
     * Buscar cliente (API para ventas y búsqueda en tiempo real)
     */
    public function buscar() {
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo
        ob_clean();
        header('Content-Type: application/json');
        
        $termino = $_GET['termino'] ?? '';
        
        try {
            if (empty($termino)) {
                // Si no hay término, devolver todos los clientes
                $clientes = $this->clienteModel->getAll();
                echo json_encode(['success' => true, 'clientes' => $clientes]);
            } else {
                $clientes = $this->clienteModel->search($termino);
                echo json_encode(['success' => true, 'clientes' => $clientes]);
            }
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en buscar: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al buscar clientes']);
        }
        
        exit;
    }

    /**
     * Crear cliente rápido (API para punto de venta)
     * Mismos campos que el formulario de clientes, pero vía AJAX
     */
    public function crearRapido() {
        // Iniciar output buffering al inicio para capturar cualquier warning/notice
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();

        // Limpiar cualquier output previo (warnings, notices, etc.) antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');

        $nombre   = trim($_POST['nombre'] ?? '');
        $cedula   = trim($_POST['cedula_nit'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: '';
        $direccion = trim($_POST['direccion'] ?? '');
        $fechaNacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
        $observaciones   = trim($_POST['observaciones'] ?? '');

        if ($nombre === '' || $cedula === '') {
            echo json_encode(['success' => false, 'error' => 'El nombre y la cédula/NIT son obligatorios']);
            exit;
        }

        try {
            $data = [
                'nombre_completo' => $nombre,
                'cedula_nit'      => $cedula,
                'telefono'        => $telefono,
                'email'           => $email ?: '',
                'direccion'       => $direccion,
                'fecha_nacimiento'=> $fechaNacimiento,
                'observaciones'   => $observaciones
            ];

            $id = $this->clienteModel->create($data);

            if ($id) {
                $cliente = $this->clienteModel->getById($id);
                echo json_encode([
                    'success' => true,
                    'cliente' => $cliente ?: [
                        'id' => $id,
                        'nombre_completo' => $nombre,
                        'cedula_nit' => $cedula,
                        'telefono' => $telefono,
                        'email' => $email ?: '',
                        'direccion' => $direccion
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No se pudo crear el cliente. Verifique que la cédula/NIT no esté duplicada.']);
            }
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en crearRapido: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al crear el cliente']);
        }
        
        exit;
    }
}