<?php
/**
 * Modelo de Cliente
 */

class Cliente {
    private $conn;
    private $table = 'clientes';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear cliente
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (nombre_completo, cedula_nit, telefono, email, direccion, fecha_nacimiento, observaciones) 
                  VALUES 
                  (:nombre_completo, :cedula_nit, :telefono, :email, :direccion, :fecha_nacimiento, :observaciones)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':nombre_completo', $data['nombre_completo']);
            $stmt->bindParam(':cedula_nit', $data['cedula_nit']);
            $stmt->bindParam(':telefono', $data['telefono']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':direccion', $data['direccion']);
            
            // Manejar fecha_nacimiento: si está vacío o es null, usar null
            $fechaNac = !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null;
            $stmt->bindParam(':fecha_nacimiento', $fechaNac);
            
            $stmt->bindParam(':observaciones', $data['observaciones']);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Error en Cliente::create(): ' . $e->getMessage());
            error_log('Query: ' . $query);
            error_log('Data: ' . print_r($data, true));
            return false;
        }
    }
    
    /**
     * Actualizar cliente
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                  nombre_completo = :nombre_completo,
                  cedula_nit = :cedula_nit,
                  telefono = :telefono,
                  email = :email,
                  direccion = :direccion,
                  fecha_nacimiento = :fecha_nacimiento,
                  observaciones = :observaciones
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre_completo', $data['nombre_completo']);
        $stmt->bindParam(':cedula_nit', $data['cedula_nit']);
        $stmt->bindParam(':telefono', $data['telefono']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':direccion', $data['direccion']);
        $stmt->bindParam(':fecha_nacimiento', $data['fecha_nacimiento']);
        $stmt->bindParam(':observaciones', $data['observaciones']);
        
        return $stmt->execute();
    }
    
    /**
     * Eliminar cliente
     */
    public function delete($id) {
        // No permitir eliminar cliente general
        if ($id == 1) {
            return false;
        }
        
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Obtener cliente por ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Buscar clientes
     */
    public function search($term) {
        $query = "SELECT c.*, 
                         COUNT(v.id) as total_compras,
                         SUM(v.total) as total_gastado
                  FROM " . $this->table . " c
                  LEFT JOIN ventas v ON c.id = v.cliente_id
                  WHERE c.nombre_completo LIKE :term1 
                     OR c.cedula_nit LIKE :term2 
                     OR c.telefono LIKE :term3
                  GROUP BY c.id
                  ORDER BY c.nombre_completo
                  LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $like = '%' . $term . '%';
        $stmt->bindParam(':term1', $like);
        $stmt->bindParam(':term2', $like);
        $stmt->bindParam(':term3', $like);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Listar todos los clientes
     */
    public function getAll() {
        $query = "SELECT c.*, 
                         COUNT(v.id) as total_compras,
                         SUM(v.total) as total_gastado
                  FROM " . $this->table . " c
                  LEFT JOIN ventas v ON c.id = v.cliente_id
                  GROUP BY c.id
                  ORDER BY c.nombre_completo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener historial de compras del cliente
     */
    public function getHistorialCompras($clienteId) {
        $query = "SELECT v.*, u.nombre as vendedor
                  FROM ventas v
                  LEFT JOIN usuarios u ON v.usuario_id = u.id
                  WHERE v.cliente_id = :cliente_id
                  ORDER BY v.fecha_venta DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cliente_id', $clienteId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}