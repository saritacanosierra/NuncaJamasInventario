<?php
/**
 * Controlador de Usuarios
 */

class UsuarioController {
    private $usuarioModel;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->usuarioModel = new Usuario($db);
    }

    public function index() {
        requireAuth();
        $usuarios = $this->usuarioModel->getAll();
        $roles = (new Rol($this->db))->getAll();
        require_once BASE_DIR . '/front/views/usuarios/index.php';
    }

    public function store() {
        requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=usuarios');
        }
        exigir_csrf_redirect('index.php?action=usuarios');

        $rol = $this->rolEnviado();
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'rol' => $rol['key'] ?? '',
            'role_id' => $rol['id'] ?? 0,
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        if ($data['nombre'] === '' || $data['email'] === '' || $data['password'] === '') {
            $_SESSION['error'] = 'El nombre, email y contraseña son obligatorios';
            redirect('index.php?action=usuarios');
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no es válido';
            redirect('index.php?action=usuarios');
        }
        if (!$rol) {
            $_SESSION['error'] = 'El rol seleccionado no es válido';
            redirect('index.php?action=usuarios');
        }
        if ($this->usuarioModel->emailExiste($data['email'])) {
            $_SESSION['error'] = 'El email ya está registrado';
            redirect('index.php?action=usuarios');
        }

        $id = $this->usuarioModel->create($data);
        if (!$id) {
            $_SESSION['error'] = 'Error al crear el usuario';
            redirect('index.php?action=usuarios');
        }

        [$extras, $revocados] = $this->listasPermisos();
        if (!$this->guardarExcepciones((int) $id, $extras, $revocados)) {
            $_SESSION['error'] = 'El usuario se creó, pero no se guardaron las excepciones';
            redirect('index.php?action=usuarios');
        }

        $_SESSION['success'] = 'Usuario creado exitosamente';
        redirect('index.php?action=usuarios');
    }

    public function getUsuario() {
        ob_start();

        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de usuario inválido']);
            exit;
        }

        try {
            $usuario = $this->usuarioModel->getById($id);
            if (!$usuario) {
                echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
                exit;
            }
            unset($usuario['password']);
            $rolModel = new Rol($this->db);
            $overrides = $rolModel->overridesDeUsuario($id);
            $roleSlugs = !empty($usuario['role_id']) ? $rolModel->slugsDeRol($usuario['role_id']) : [];
            echo json_encode([
                'success' => true,
                'usuario' => $usuario,
                'extra' => $overrides['extra'],
                'revocado' => $overrides['revocado'],
                'role_slugs' => $roleSlugs,
            ]);
        } catch (Exception $e) {
            error_log('Error en getUsuario: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error al cargar el usuario']);
        }
        exit;
    }

    public function update() {
        requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=usuarios');
        }
        exigir_csrf_redirect('index.php?action=usuarios');

        $id = intval($_POST['id'] ?? 0);
        $usuario = $this->usuarioModel->getById($id);
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            redirect('index.php?action=usuarios');
        }

        $rol = $this->rolEnviado();
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'rol' => $rol['key'] ?? '',
            'role_id' => $rol['id'] ?? 0,
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        if ($data['nombre'] === '' || $data['email'] === '') {
            $_SESSION['error'] = 'El nombre y email son obligatorios';
            redirect('index.php?action=usuarios');
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no es válido';
            redirect('index.php?action=usuarios');
        }
        if (!$rol) {
            $_SESSION['error'] = 'El rol seleccionado no es válido';
            redirect('index.php?action=usuarios');
        }
        if ($this->usuarioModel->emailExiste($data['email'], $id)) {
            $_SESSION['error'] = 'El email ya está registrado en otro usuario';
            redirect('index.php?action=usuarios');
        }

        [$extras, $revocados] = $this->listasPermisos();
        $avisoPropio = false;
        if ($id === (int) $_SESSION['usuario_id']) {
            $slugsRol = $this->rolModel()->slugsDeRol($rol['id']);
            $finales = resolveFinalPermissionSet($slugsRol, $extras, $revocados);
            if (!in_array('usuarios_lista:edit', $finales, true)) {
                $revocados = array_values(array_filter($revocados, function ($slug) {
                    return $slug !== 'usuarios_lista:edit';
                }));
                if (!in_array('usuarios_lista:edit', $slugsRol, true)) {
                    $extras[] = 'usuarios_lista:edit';
                }
                $avisoPropio = true;
            }
        }

        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }

        if (!$this->usuarioModel->update($id, $data) || !$this->guardarExcepciones($id, $extras, $revocados)) {
            $_SESSION['error'] = 'Error al actualizar el usuario';
            redirect('index.php?action=usuarios');
        }

        if ($id === (int) $_SESSION['usuario_id']) {
            permisos_cargar_en_sesion($this->db, $id);
        }
        $_SESSION['success'] = 'Usuario actualizado exitosamente';
        if ($avisoPropio) {
            $_SESSION['success'] .= ' Se conservó su permiso para editar usuarios.';
        }
        redirect('index.php?action=usuarios');
    }

    public function delete() {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=usuarios');
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id == $_SESSION['usuario_id']) {
            $_SESSION['error'] = 'No puede eliminar su propio usuario';
            redirect('index.php?action=usuarios');
        }

        $usuario = $this->usuarioModel->getById($id);
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            redirect('index.php?action=usuarios');
        }
        if (!codigo_eliminacion_valido($usuario['nombre'] ?? '')) {
            $_SESSION['error'] = 'La confirmación no coincide. No se eliminó.';
            redirect('index.php?action=usuarios');
        }

        if ($this->usuarioModel->delete($id)) {
            $_SESSION['success'] = 'Usuario eliminado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar el usuario';
        }
        redirect('index.php?action=usuarios');
    }

    private function rolModel() {
        return new Rol($this->db);
    }

    private function rolEnviado() {
        $key = trim($_POST['rol'] ?? '');
        if ($key === '') {
            return null;
        }
        return $this->rolModel()->getByKey($key) ?: null;
    }

    private function listaPost($campo) {
        $valor = $_POST[$campo] ?? [];
        return is_array($valor) ? $valor : [];
    }

    private function listasPermisos() {
        $json = $_POST['permisos_json'] ?? '';
        if (is_string($json) && $json !== '') {
            $data = json_decode($json, true);
            if (is_array($data)) {
                $extra = (isset($data['extra']) && is_array($data['extra'])) ? $data['extra'] : [];
                $revocado = (isset($data['revocado']) && is_array($data['revocado'])) ? $data['revocado'] : [];
                return [$extra, $revocado];
            }
        }
        return [$this->listaPost('extra'), $this->listaPost('revocado')];
    }

    private function guardarExcepciones($userId, $extras = null, $revocados = null) {
        if ($extras === null) {
            $extras = $this->listaPost('extra');
        }
        if ($revocados === null) {
            $revocados = $this->listaPost('revocado');
        }
        return $this->rolModel()->guardarOverrides($userId, $extras, $revocados);
    }
}
