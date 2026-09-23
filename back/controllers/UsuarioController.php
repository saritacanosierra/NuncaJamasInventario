<?php
/**
 * Controlador de Usuarios
 * Solo accesible para administradores
 */

class UsuarioController {
    private $usuarioModel;
    
    public function __construct($db) {
        $this->usuarioModel = new Usuario($db);
    }
    
    /**
     * Listar usuarios
     */
    public function index() {
        requireAuth();
        
        if (!isAdmin()) {
            $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
            redirect('index.php?action=dashboard');
        }
        
        $usuarios = $this->usuarioModel->getAll();
        
        require_once BASE_DIR . '/front/views/usuarios/index.php';
    }
    
    /**
     * Guardar nuevo usuario
     */
    public function store() {
        requireAuth();
        
        if (!isAdmin()) {
            $_SESSION['error'] = 'No tiene permisos para realizar esta acción';
            redirect('index.php?action=dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=usuarios');
        }
        
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'password' => $_POST['password'] ?? '',
            'rol' => $_POST['rol'] ?? 'cajero',
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        // Validaciones
        if (empty($data['nombre']) || empty($data['email']) || empty($data['password'])) {
            $_SESSION['error'] = 'El nombre, email y contraseña son obligatorios';
            redirect('index.php?action=usuarios');
        }
        
        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no es válido';
            redirect('index.php?action=usuarios');
        }
        
        // Validar rol
        $rolesPermitidos = ['admin', 'cajero', 'operario'];
        if (!in_array($data['rol'], $rolesPermitidos)) {
            $_SESSION['error'] = 'El rol seleccionado no es válido';
            redirect('index.php?action=usuarios');
        }
        
        // Verificar si el email ya existe
        if ($this->usuarioModel->emailExiste($data['email'])) {
            $_SESSION['error'] = 'El email ya está registrado';
            redirect('index.php?action=usuarios');
        }
        
        $id = $this->usuarioModel->create($data);
        
        if ($id) {
            $_SESSION['success'] = 'Usuario creado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al crear el usuario';
        }
        
        redirect('index.php?action=usuarios');
    }
    
    /**
     * Obtener usuario por ID (API para modal)
     */
    public function getUsuario() {
        // Iniciar output buffering para capturar cualquier error
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        if (!isAdmin()) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No tiene permisos']);
            exit;
        }
        
        // Limpiar cualquier output previo
        ob_clean();
        header('Content-Type: application/json');
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de usuario inválido']);
            exit;
        }
        
        try {
            // Verificar que el modelo tenga conexión válida
            if (!$this->usuarioModel) {
                throw new Exception('Modelo de usuario no inicializado');
            }
            
            $usuario = $this->usuarioModel->getById($id);
            
            if ($usuario) {
                // No devolver la contraseña
                unset($usuario['password']);
                echo json_encode(['success' => true, 'usuario' => $usuario]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
            }
        } catch (PDOException $e) {
            ob_clean();
            error_log('Error de base de datos en getUsuario: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'error' => 'Error de conexión a la base de datos. Verifique la configuración.'
            ]);
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en getUsuario: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al cargar el usuario: ' . $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Actualizar usuario
     */
    public function update() {
        requireAuth();
        
        if (!isAdmin()) {
            $_SESSION['error'] = 'No tiene permisos para realizar esta acción';
            redirect('index.php?action=dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=usuarios');
        }
        
        $id = intval($_POST['id'] ?? 0);
        $usuario = $this->usuarioModel->getById($id);
        
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            redirect('index.php?action=usuarios');
        }
        
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'rol' => $_POST['rol'] ?? 'cajero',
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        // Validaciones
        if (empty($data['nombre']) || empty($data['email'])) {
            $_SESSION['error'] = 'El nombre y email son obligatorios';
            redirect('index.php?action=usuarios');
        }
        
        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no es válido';
            redirect('index.php?action=usuarios');
        }
        
        // Validar rol
        $rolesPermitidos = ['admin', 'cajero', 'operario'];
        if (!in_array($data['rol'], $rolesPermitidos)) {
            $_SESSION['error'] = 'El rol seleccionado no es válido';
            redirect('index.php?action=usuarios');
        }
        
        // Verificar si el email ya existe en otro usuario
        if ($this->usuarioModel->emailExiste($data['email'], $id)) {
            $_SESSION['error'] = 'El email ya está registrado en otro usuario';
            redirect('index.php?action=usuarios');
        }
        
        // Si se proporciona una nueva contraseña, actualizarla
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        if ($this->usuarioModel->update($id, $data)) {
            $_SESSION['success'] = 'Usuario actualizado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al actualizar el usuario';
        }
        
        redirect('index.php?action=usuarios');
    }
    
    /**
     * Eliminar usuario
     */
    public function delete() {
        requireAuth();
        
        if (!isAdmin()) {
            $_SESSION['error'] = 'No tiene permisos para realizar esta acción';
            redirect('index.php?action=dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=usuarios');
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        // No permitir eliminar el propio usuario
        if ($id == $_SESSION['usuario_id']) {
            $_SESSION['error'] = 'No puede eliminar su propio usuario';
            redirect('index.php?action=usuarios');
        }
        
        $usuario = $this->usuarioModel->getById($id);
        
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            redirect('index.php?action=usuarios');
        }
        
        if ($this->usuarioModel->delete($id)) {
            $_SESSION['success'] = 'Usuario eliminado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar el usuario';
        }
        
        redirect('index.php?action=usuarios');
    }
}