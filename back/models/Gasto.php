<?php
/**
 * Modelo de Gasto
 */

class Gasto {
    private $conn;
    private $table = 'gastos';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear gasto
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (concepto, monto, categoria, fecha, descripcion, usuario_id) 
                  VALUES 
                  (:concepto, :monto, :categoria, :fecha, :descripcion, :usuario_id)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':concepto', $data['concepto']);
            $stmt->bindValue(':monto', $data['monto']);
            $stmt->bindValue(':categoria', $data['categoria']);
            $stmt->bindValue(':fecha', $data['fecha']);
            $stmt->bindValue(':descripcion', $data['descripcion']);
            $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error en Gasto::create(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar gasto
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
     * Eliminar gasto
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Obtener gasto por ID
     */
    public function getById($id) {
        $query = "SELECT g.*, u.nombre as usuario_nombre 
                  FROM " . $this->table . " g
                  LEFT JOIN usuarios u ON g.usuario_id = u.id
                  WHERE g.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Listar gastos con filtros
     */
    public function getAll($filters = []) {
        $query = "SELECT g.*, u.nombre as usuario_nombre 
                  FROM " . $this->table . " g
                  LEFT JOIN usuarios u ON g.usuario_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['fecha_desde'])) {
            $query .= " AND g.fecha >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }
        
        if (!empty($filters['fecha_hasta'])) {
            $query .= " AND g.fecha <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }
        
        if (!empty($filters['categoria'])) {
            $query .= " AND g.categoria = :categoria";
            $params[':categoria'] = $filters['categoria'];
        }
        
        $query .= " ORDER BY g.fecha DESC, g.id DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener total de gastos por periodo
     */
    public function getTotalPorPeriodo($fechaInicio, $fechaFin) {
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
    }
    
    /**
     * Obtener gastos por categoría
     */
    public function getGastosPorCategoria($fechaInicio, $fechaFin) {
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
    }
    
    /**
     * Obtener gastos agrupados por mes
     */
    public function getGastosPorMes() {
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
     * Obtener gastos agrupados por año
     */
    public function getGastosPorAnio() {
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