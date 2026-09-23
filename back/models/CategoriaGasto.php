<?php
/**
 * Modelo de Categoría de Gasto
 */

class CategoriaGasto {
    private $conn;
    private $table = 'categorias_gastos';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Listar todas las categorías
     */
    public function getAll() {
        try {
            $query = "SELECT * FROM " . $this->table . " ORDER BY nombre";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Si la tabla no existe, retornar categorías por defecto
            if ($e->getCode() == '42S02') {
                return [
                    ['id' => 1, 'nombre' => 'Servicios', 'descripcion' => 'Gastos relacionados con servicios'],
                    ['id' => 2, 'nombre' => 'Insumos', 'descripcion' => 'Gastos en insumos y materiales'],
                    ['id' => 3, 'nombre' => 'Administrativos', 'descripcion' => 'Gastos administrativos'],
                    ['id' => 4, 'nombre' => 'Marketing', 'descripcion' => 'Gastos de marketing y publicidad'],
                    ['id' => 5, 'nombre' => 'Otros', 'descripcion' => 'Otros gastos']
                ];
            }
            throw $e;
        }
    }
    
    /**
     * Obtener categoría por ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Crear nueva categoría
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (nombre, descripcion) 
                  VALUES (:nombre, :descripcion)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar categoría
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                  nombre = :nombre,
                  descripcion = :descripcion
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        
        return $stmt->execute();
    }
    
    /**
     * Eliminar categoría
     */
    public function delete($id) {
        // Verificar si tiene gastos asociados
        $query = "SELECT COUNT(*) as total FROM gastos WHERE categoria = (SELECT nombre FROM " . $this->table . " WHERE id = :id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            return ['success' => false, 'error' => 'No se puede eliminar la categoría porque tiene ' . $result['total'] . ' gastos asociados'];
        }
        
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Categoría eliminada exitosamente'];
        }
        
        return ['success' => false, 'error' => 'Error al eliminar la categoría'];
    }
}