<?php
/**
 * Controlador de la matriz de roles.
 */

class RolController {
    private $rolModel;

    public function __construct($db) {
        $this->rolModel = new Rol($db);
    }

    public function index() {
        requireAuth();
        $roles = $this->rolModel->getAll();
        $seleccionado = intval($_GET['id'] ?? 0);
        if ($seleccionado <= 0 && !empty($roles)) {
            $seleccionado = (int) $roles[0]['id'];
        }
        $rol = $seleccionado > 0 ? $this->rolModel->getById($seleccionado) : null;
        $asignados = $rol ? $this->rolModel->slugsDeRol($rol['id']) : [];
        require_once BASE_DIR . '/front/views/configuracion/roles.php';
    }

    public function guardar() {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=roles');
        }
        exigir_csrf_redirect('index.php?action=roles');

        $id = intval($_POST['id'] ?? 0);
        $rol = $this->rolModel->getById($id);
        if (!$rol) {
            $_SESSION['error'] = 'Rol no encontrado';
            redirect('index.php?action=roles');
        }

        $slugs = filtrarSlugsValidos($_POST['slugs'] ?? []);
        if ((int) $rol['id'] === (int) ($_SESSION['usuario_role_id'] ?? 0)) {
            $overrides = $this->rolModel->overridesDeUsuario((int) $_SESSION['usuario_id']);
            $finales = resolveFinalPermissionSet($slugs, $overrides['extra'], $overrides['revocado']);
            if (!in_array('roles_matriz:edit', $finales, true)) {
                $_SESSION['error'] = 'No puede quitarse el permiso de editar roles.';
                redirect('index.php?action=roles&id=' . $id);
            }
        }

        if ($this->rolModel->guardarPermisos($id, $slugs)) {
            if ((int) $rol['id'] === (int) ($_SESSION['usuario_role_id'] ?? 0)) {
                permisos_cargar_en_sesion($GLOBALS['db'], (int) $_SESSION['usuario_id']);
            }
            $_SESSION['success'] = 'Permisos del rol ' . $rol['name'] . ' guardados.';
        } else {
            $_SESSION['error'] = 'No se pudieron guardar los permisos del rol';
        }
        redirect('index.php?action=roles&id=' . $id);
    }

    public function crear() {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=roles');
        }
        exigir_csrf_redirect('index.php?action=roles');

        $nombre = trim($_POST['name'] ?? '');
        $key = strtolower(trim($_POST['key'] ?? ''));
        if ($nombre === '' || !preg_match('/^[a-z][a-z0-9_]{1,40}$/', $key)) {
            $_SESSION['error'] = 'El nombre es obligatorio y la clave debe usar letras minúsculas, números o guion bajo.';
            redirect('index.php?action=roles');
        }
        if ($this->rolModel->getByKey($key)) {
            $_SESSION['error'] = 'Ya existe un rol con esa clave';
            redirect('index.php?action=roles');
        }

        $id = $this->rolModel->crear($key, $nombre);
        $_SESSION['success'] = 'Rol creado. Marque sus permisos y guarde.';
        redirect('index.php?action=roles&id=' . $id);
    }

    public function slugs() {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        $rol = $this->rolModel->getById($id);
        if (!$rol) {
            echo json_encode(['success' => false, 'error' => 'Rol no encontrado']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'role' => ['id' => (int) $rol['id'], 'key' => $rol['key'], 'name' => $rol['name']],
            'slugs' => $this->rolModel->slugsDeRol($rol['id']),
        ]);
        exit;
    }
}
