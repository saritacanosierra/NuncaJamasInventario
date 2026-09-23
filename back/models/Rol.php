<?php
/**
 * Roles, permisos y excepciones por usuario.
 */

class Rol {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $stmt = $this->conn->query('SELECT id, `key`, name FROM roles ORDER BY name');
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare('SELECT id, `key`, name FROM roles WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getByKey($key) {
        $stmt = $this->conn->prepare('SELECT id, `key`, name FROM roles WHERE `key` = :key LIMIT 1');
        $stmt->bindValue(':key', $key);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function slugsDeRol($roleId) {
        $stmt = $this->conn->prepare(
            'SELECT p.slug
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id
             ORDER BY p.slug'
        );
        $stmt->bindValue(':role_id', (int) $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'slug');
    }

    public function overridesDeUsuario($userId) {
        $stmt = $this->conn->prepare(
            'SELECT p.slug, o.value
             FROM user_permission_overrides o
             INNER JOIN permissions p ON p.id = o.permission_id
             WHERE o.user_id = :user_id'
        );
        $stmt->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
        $stmt->execute();
        $extra = [];
        $revocado = [];
        foreach ($stmt->fetchAll() as $fila) {
            if ((int) $fila['value'] === 1) {
                $extra[] = $fila['slug'];
            } else {
                $revocado[] = $fila['slug'];
            }
        }
        return ['extra' => $extra, 'revocado' => $revocado];
    }

    public function resolverUsuario($userId) {
        $stmt = $this->conn->prepare(
            'SELECT u.id, u.nombre, u.email, u.activo, u.role_id,
                    r.id AS role_real_id, r.`key` AS role_key, r.name AS role_name
             FROM usuarios u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->bindValue(':id', (int) $userId, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch();
        if (!$usuario || empty($usuario['role_real_id'])) {
            return null;
        }

        $overrides = $this->overridesDeUsuario($userId);
        $finales = resolveFinalPermissionSet(
            $this->slugsDeRol($usuario['role_real_id']),
            $overrides['extra'],
            $overrides['revocado']
        );

        return [
            'user' => [
                'id' => (int) $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'activo' => (int) $usuario['activo'],
            ],
            'role' => [
                'id' => (int) $usuario['role_real_id'],
                'key' => $usuario['role_key'],
                'name' => $usuario['role_name'],
            ],
            'final_permissions' => $finales,
        ];
    }

    public function guardarPermisos($roleId, array $slugs) {
        $slugs = filtrarSlugsValidos($slugs);
        $this->conn->beginTransaction();
        try {
            $borrar = $this->conn->prepare('DELETE FROM role_permissions WHERE role_id = :role_id');
            $borrar->bindValue(':role_id', (int) $roleId, PDO::PARAM_INT);
            $borrar->execute();

            $insertar = $this->conn->prepare(
                'INSERT INTO role_permissions (role_id, permission_id)
                 SELECT :role_id, id FROM permissions WHERE slug = :slug'
            );
            foreach ($slugs as $slug) {
                $insertar->bindValue(':role_id', (int) $roleId, PDO::PARAM_INT);
                $insertar->bindValue(':slug', $slug);
                $insertar->execute();
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Error al guardar permisos del rol: ' . $e->getMessage());
            return false;
        }
    }

    public function guardarOverrides($userId, array $extras, array $revocados) {
        $extras = filtrarSlugsValidos($extras);
        $revocados = filtrarSlugsValidos($revocados);
        [$extras, $revocados] = normalizarOverrides($extras, $revocados);

        $this->conn->beginTransaction();
        try {
            $borrar = $this->conn->prepare('DELETE FROM user_permission_overrides WHERE user_id = :user_id');
            $borrar->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
            $borrar->execute();

            $insertar = $this->conn->prepare(
                'INSERT INTO user_permission_overrides (user_id, permission_id, value)
                 SELECT :user_id, id, :value FROM permissions WHERE slug = :slug'
            );
            foreach ($extras as $slug) {
                $insertar->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
                $insertar->bindValue(':value', 1, PDO::PARAM_INT);
                $insertar->bindValue(':slug', $slug);
                $insertar->execute();
            }
            foreach ($revocados as $slug) {
                $insertar->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
                $insertar->bindValue(':value', 0, PDO::PARAM_INT);
                $insertar->bindValue(':slug', $slug);
                $insertar->execute();
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Error al guardar excepciones: ' . $e->getMessage());
            return false;
        }
    }

    public function crear($key, $name) {
        $stmt = $this->conn->prepare('INSERT INTO roles (`key`, name) VALUES (:key, :name)');
        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        return (int) $this->conn->lastInsertId();
    }
}
