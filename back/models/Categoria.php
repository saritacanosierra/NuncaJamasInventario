<?php
/**
 * Modelo de Categoría
 */

class Categoria {
    private $conn;
    private $table = 'categorias';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Listar todas las categorías activas
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE activa = 1 
                  ORDER BY nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
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
     * Verificar si existe una categoría activa por nombre (case-insensitive)
     */
    public function existePorNombre($nombre, $excluirId = null) {
        // Normalizar el nombre (trim y convertir a minúsculas para comparación)
        $nombreNormalizado = trim(strtolower($nombre));
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE LOWER(TRIM(nombre)) = :nombre AND activa = 1";
        
        if ($excluirId !== null) {
            $query .= " AND id != :excluir_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombreNormalizado);
        
        if ($excluirId !== null) {
            $stmt->bindParam(':excluir_id', $excluirId);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Crear nueva categoría
     */
    public function create($data) {
        // Normalizar el nombre antes de verificar
        $nombre = trim($data['nombre']);
        
        if (empty($nombre)) {
            throw new Exception('El nombre de la categoría no puede estar vacío');
        }
        
        // Verificar si ya existe una categoría activa con ese nombre (case-insensitive)
        if ($this->existePorNombre($nombre)) {
            throw new Exception('Ya existe una categoría activa con ese nombre');
        }
        
        $query = "INSERT INTO " . $this->table . " (nombre, descripcion, activa) 
                  VALUES (:nombre, :descripcion, 1)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $descripcion = trim($data['descripcion'] ?? '');
        $stmt->bindParam(':descripcion', $descripcion);
        
        try {
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            // Si hay un error de duplicado a nivel de BD (por si acaso la restricción UNIQUE existe)
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            if ($errorCode == 23000 || strpos($errorMessage, 'Duplicate') !== false || 
                strpos($errorMessage, 'duplicate') !== false || strpos($errorMessage, 'UNIQUE') !== false) {
                throw new Exception('Ya existe una categoría con ese nombre');
            }
            // Re-lanzar la excepción si no es un error de duplicado
            throw $e;
        }
        
        return false;
    }
    
    /**
     * Obtener categorías con conteo de productos
     * Si hay duplicados, solo muestra la primera (por ID)
     */
    public function getAllWithCount() {
        // Primero obtener todas las categorías activas con sus productos
        $query = "SELECT c.*, 
                         COUNT(p.id) as total_productos
                  FROM " . $this->table . " c
                  LEFT JOIN productos p ON c.id = p.categoria_id
                  WHERE c.activa = 1
                  GROUP BY c.id
                  ORDER BY c.nombre, c.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $categorias = $stmt->fetchAll();
        
        // Si hay duplicados por nombre, mantener solo la primera (menor ID)
        $nombresVistos = [];
        $categoriasUnicas = [];
        
        foreach ($categorias as $categoria) {
            $nombreNormalizado = strtolower(trim($categoria['nombre']));
            if (!isset($nombresVistos[$nombreNormalizado])) {
                $nombresVistos[$nombreNormalizado] = true;
                $categoriasUnicas[] = $categoria;
            }
        }
        
        return $categoriasUnicas;
    }
    
    /**
     * Eliminar categoría (marcar como inactiva)
     */
    public function delete($id) {
        // Verificar si tiene productos asociados
        $query = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            return ['success' => false, 'error' => 'No se puede eliminar la categoría porque tiene ' . $result['total'] . ' productos asociados'];
        }
        
        // Marcar como inactiva en lugar de eliminar
        $query = "UPDATE " . $this->table . " SET activa = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Categoría eliminada exitosamente'];
        }
        
        return ['success' => false, 'error' => 'Error al eliminar la categoría'];
    }
}