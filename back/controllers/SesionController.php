<?php
/**
 * Sesión actual para el cliente.
 */

class SesionController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function me() {
        requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        $rol = new Rol($this->db);
        $resuelto = $rol->resolverUsuario((int) $_SESSION['usuario_id']);
        if (!$resuelto) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'El usuario no tiene un rol asignado']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'user' => $resuelto['user'],
            'role' => $resuelto['role'],
            'final_permissions' => $resuelto['final_permissions'],
        ]);
        exit;
    }
}
