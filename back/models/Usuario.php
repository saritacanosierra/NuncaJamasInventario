<?php
/**
 * Modelo de Usuario
 */

class Usuario {
    private $conn;
    private $table = 'usuarios';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Autenticar usuario
     */
    public function login($email, $password) {
        $query = "SELECT id, nombre, email, password, rol, activo 
                  FROM " . $this->table . " 
                  WHERE email = :email AND activo = 1 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $usuario = $stmt->fetch();
        if (!$usuario) {
            return false;
        }

        $guardada = (string) $usuario['password'];
        $esHash = password_get_info($guardada)['algo'] !== null;
        $valida = $esHash
            ? password_verify($password, $guardada)
            : hash_equals($guardada, (string) $password);

        if (!$valida) {
            return false;
        }

        if (!$esHash) {
            $this->guardarHash((int) $usuario['id'], $password);
        }

        unset($usuario['password']);
        return $usuario;
    }

    private function guardarHash($id, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE " . $this->table . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':password', $hash);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getById($id) {
        try {
            $query = "SELECT id, nombre, email, rol, role_id, activo 
                      FROM " . $this->table . " 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Error en Usuario::getById(): ' . $e->getMessage());
            throw $e;
        }
    }

    public function getActivoPorEmail($email) {
        $query = "SELECT id, nombre, email
                  FROM " . $this->table . "
                  WHERE email = :email AND activo = 1
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function cambiarClave($id, $password) {
        $this->guardarHash((int) $id, $password);
    }
    
    /**
     * Listar todos los usuarios
     */
    public function getAll() {
        $query = "SELECT u.id, u.nombre, u.email, u.rol, u.role_id, u.activo, u.fecha_creacion,
                         r.name AS rol_nombre
                  FROM " . $this->table . " u
                  LEFT JOIN roles r ON r.id = u.role_id
                  ORDER BY u.nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Crear usuario
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (nombre, email, password, rol, role_id, activo) 
                  VALUES 
                  (:nombre, :email, :password, :rol, :role_id, :activo)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindValue(':password', $hash);
            $stmt->bindParam(':rol', $data['rol']);
            $stmt->bindValue(':role_id', $data['role_id'], PDO::PARAM_INT);
            $stmt->bindParam(':activo', $data['activo']);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Error en Usuario::create(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar usuario
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                  nombre = :nombre,
                  email = :email,
                  rol = :rol,
                  role_id = :role_id,
                  activo = :activo";
        
        // Si se proporciona una nueva contraseña, incluirla en la actualización
        if (isset($data['password']) && !empty($data['password'])) {
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':rol', $data['rol']);
            $stmt->bindValue(':role_id', $data['role_id'], PDO::PARAM_INT);
            $stmt->bindParam(':activo', $data['activo']);
            
            if (isset($data['password']) && !empty($data['password'])) {
                $hash = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt->bindValue(':password', $hash);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error en Usuario::update(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error en Usuario::delete(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si un email ya existe
     */
    public function emailExiste($email, $excludeId = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE email = :email";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['total'] > 0;
    }
}