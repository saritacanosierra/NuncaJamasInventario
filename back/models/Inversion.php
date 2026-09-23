<?php
/**
 * Modelo de Inversión
 */

class Inversion {
    private $conn;
    private $table = 'inversiones';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear inversión
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (concepto, monto, categoria, fecha, descripcion, usuario_id) 
                  VALUES 
                  (:concepto, :monto, :categoria, :fecha, :descripcion, :usuario_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':concepto', $data['concepto']);
        $stmt->bindParam(':monto', $data['monto']);
        $stmt->bindParam(':categoria', $data['categoria']);
        $stmt->bindParam(':fecha', $data['fecha']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':usuario_id', $data['usuario_id']);
        
        return $stmt->execute();
    }
    
    /**
     * Actualizar inversión
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                  concepto = :concepto,
                  monto = :monto,
                  categoria = :categoria,
                  fecha = :fecha,
                  descripcion = :descripcion
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':concepto', $data['concepto']);
        $stmt->bindParam(':monto', $data['monto']);
        $stmt->bindParam(':categoria', $data['categoria']);
        $stmt->bindParam(':fecha', $data['fecha']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        
        return $stmt->execute();
    }
    
    /**
     * Eliminar inversión
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Obtener inversión por ID
     */
    public function getById($id) {
        $query = "SELECT i.*, u.nombre as usuario_nombre 
                  FROM " . $this->table . " i
                  LEFT JOIN usuarios u ON i.usuario_id = u.id
                  WHERE i.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Listar inversiones con filtros
     */
    public function getAll($filters = []) {
        try {
            $query = "SELECT i.*, u.nombre as usuario_nombre 
                      FROM " . $this->table . " i
                      LEFT JOIN usuarios u ON i.usuario_id = u.id
                      WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['fecha_desde'])) {
                $query .= " AND i.fecha >= :fecha_desde";
                $params[':fecha_desde'] = $filters['fecha_desde'];
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $query .= " AND i.fecha <= :fecha_hasta";
                $params[':fecha_hasta'] = $filters['fecha_hasta'];
            }
            
            if (!empty($filters['categoria'])) {
                $query .= " AND i.categoria = :categoria";
                $params[':categoria'] = $filters['categoria'];
            }
            
            $query .= " ORDER BY i.fecha DESC, i.id DESC";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Si la tabla no existe, retornar array vacío
            if ($e->getCode() == '42S02') {
                return [];
            }
            throw $e;
        }
    }
    
    /**
     * Obtener total de inversiones por periodo
     */
    public function getTotalPorPeriodo($fechaInicio, $fechaFin) {
        try {
            $query = "SELECT 
                        COALESCE(SUM(monto), 0) as total,
                        COALESCE(COUNT(*), 0) as cantidad
                      FROM " . $this->table . "
                      WHERE fecha >= :fecha_inicio AND fecha <= :fecha_fin";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha_inicio', $fechaInicio);
            $stmt->bindParam(':fecha_fin', $fechaFin);
            $stmt->execute();
            
            $resultado = $stmt->fetch();
            return $resultado ?: ['total' => 0, 'cantidad' => 0];
        } catch(PDOException $e) {
            // Si la tabla no existe, retornar valores por defecto
            if ($e->getCode() == '42S02') {
                return ['total' => 0, 'cantidad' => 0];
            }
            throw $e;
        }
    }
    
    /**
     * Obtener inversiones por categoría
     */
    public function getInversionesPorCategoria($fechaInicio, $fechaFin) {
        try {
            $query = "SELECT 
                        categoria,
                        SUM(monto) as total,
                        COUNT(*) as cantidad
                      FROM " . $this->table . "
                      WHERE fecha >= :fecha_inicio AND fecha <= :fecha_fin
                      GROUP BY categoria
                      ORDER BY total DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha_inicio', $fechaInicio);
            $stmt->bindParam(':fecha_fin', $fechaFin);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Si la tabla no existe, retornar array vacío
            if ($e->getCode() == '42S02') {
                return [];
            }
            throw $e;
        }
    }
    
    /**
     * Obtener inversiones agrupadas por mes
     */
    public function getInversionesPorMes() {
        try {
            $query = "SELECT 
                        DATE_FORMAT(fecha, '%Y-%m') as mes,
                        DATE_FORMAT(fecha, '%M %Y') as mes_nombre,
                        SUM(monto) as total,
                        COUNT(*) as cantidad
                      FROM " . $this->table . "
                      GROUP BY DATE_FORMAT(fecha, '%Y-%m'), DATE_FORMAT(fecha, '%M %Y')
                      ORDER BY mes DESC
                      LIMIT 12";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            if ($e->getCode() == '42S02') {
                return [];
            }
            throw $e;
        }
    }
    
    /**
     * Obtener inversiones agrupadas por año
     */
    public function getInversionesPorAnio() {
        try {
            $query = "SELECT 
                        YEAR(fecha) as anio,
                        SUM(monto) as total,
                        COUNT(*) as cantidad
                      FROM " . $this->table . "
                      GROUP BY YEAR(fecha)
                      ORDER BY anio DESC
                      LIMIT 10";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            if ($e->getCode() == '42S02') {
                return [];
            }
            throw $e;
        }
    }
}