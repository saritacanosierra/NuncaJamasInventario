<?php
/**
 * Modelo de Tarea (Agenda)
 */

class Tarea {
    private $conn;
    private $table = 'tareas';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear tarea
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (titulo, descripcion, fecha, hora, prioridad, estado, categoria, usuario_id) 
                  VALUES 
                  (:titulo, :descripcion, :fecha, :hora, :prioridad, :estado, :categoria, :usuario_id)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':titulo', $data['titulo']);
            $stmt->bindParam(':descripcion', $data['descripcion']);
            $stmt->bindParam(':fecha', $data['fecha']);
            $hora = !empty($data['hora']) ? $data['hora'] : null;
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':prioridad', $data['prioridad']);
            $stmt->bindParam(':estado', $data['estado']);
            $categoria = !empty($data['categoria']) ? $data['categoria'] : null;
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':usuario_id', $data['usuario_id']);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Error en Tarea::create(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener tarea por ID
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla tareas no existe. Ejecuta crear_tabla_tareas.sql en phpMyAdmin.');
                return false;
            }
            error_log('Error en Tarea::getById(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todas las tareas con filtros
     */
    public function getAll($filters = []) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
            $params = [];
            
            if (!empty($filters['usuario_id'])) {
                $query .= " AND usuario_id = :usuario_id";
                $params[':usuario_id'] = $filters['usuario_id'];
            }
            
            if (!empty($filters['fecha'])) {
                $query .= " AND fecha = :fecha";
                $params[':fecha'] = $filters['fecha'];
            }
            
            if (!empty($filters['fecha_desde'])) {
                $query .= " AND fecha >= :fecha_desde";
                $params[':fecha_desde'] = $filters['fecha_desde'];
            }
            
            if (!empty($filters['fecha_hasta'])) {
                $query .= " AND fecha <= :fecha_hasta";
                $params[':fecha_hasta'] = $filters['fecha_hasta'];
            }
            
            if (!empty($filters['estado'])) {
                $query .= " AND estado = :estado";
                $params[':estado'] = $filters['estado'];
            }
            
            if (!empty($filters['prioridad'])) {
                $query .= " AND prioridad = :prioridad";
                $params[':prioridad'] = $filters['prioridad'];
            }
            
            $query .= " ORDER BY fecha ASC, hora ASC, prioridad DESC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Si la tabla no existe, retornar array vacío
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla tareas no existe. Ejecuta crear_tabla_tareas.sql en phpMyAdmin.');
                return [];
            }
            error_log('Error en Tarea::getAll(): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener tareas por mes
     */
    public function getByMonth($year, $month, $usuarioId) {
        $fechaInicio = sprintf('%04d-%02d-01', $year, $month);
        $fechaFin = date('Y-m-t', strtotime($fechaInicio));
        
        return $this->getAll([
            'usuario_id' => $usuarioId,
            'fecha_desde' => $fechaInicio,
            'fecha_hasta' => $fechaFin
        ]);
    }
    
    /**
     * Obtener tareas del día
     */
    public function getByDate($fecha, $usuarioId) {
        return $this->getAll([
            'usuario_id' => $usuarioId,
            'fecha' => $fecha
        ]);
    }
    
    /**
     * Actualizar tarea
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET titulo = :titulo, 
                      descripcion = :descripcion,
                      fecha = :fecha,
                      hora = :hora,
                      prioridad = :prioridad,
                      estado = :estado,
                      categoria = :categoria
                  WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':titulo', $data['titulo']);
            $stmt->bindParam(':descripcion', $data['descripcion']);
            $stmt->bindParam(':fecha', $data['fecha']);
            $hora = !empty($data['hora']) ? $data['hora'] : null;
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':prioridad', $data['prioridad']);
            $stmt->bindParam(':estado', $data['estado']);
            $categoria = !empty($data['categoria']) ? $data['categoria'] : null;
            $stmt->bindParam(':categoria', $categoria);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error en Tarea::update(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marcar tarea como completada
     */
    public function completar($id) {
        $query = "UPDATE " . $this->table . " SET estado = 'completada' WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Eliminar tarea
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Obtener estadísticas de tareas
     */
    public function getEstadisticas($usuarioId, $fechaInicio = null, $fechaFin = null) {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN prioridad = 'alta' AND estado = 'pendiente' THEN 1 ELSE 0 END) as urgentes
                      FROM " . $this->table . " 
                      WHERE usuario_id = :usuario_id";
            
            $params = [':usuario_id' => $usuarioId];
            
            if ($fechaInicio) {
                $query .= " AND fecha >= :fecha_inicio";
                $params[':fecha_inicio'] = $fechaInicio;
            }
            
            if ($fechaFin) {
                $query .= " AND fecha <= :fecha_fin";
                $params[':fecha_fin'] = $fechaFin;
            }
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result ?: ['total' => 0, 'completadas' => 0, 'pendientes' => 0, 'urgentes' => 0];
        } catch (PDOException $e) {
            // Si la tabla no existe, retornar estadísticas vacías
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla tareas no existe. Ejecuta crear_tabla_tareas.sql en phpMyAdmin.');
                return ['total' => 0, 'completadas' => 0, 'pendientes' => 0, 'urgentes' => 0];
            }
            error_log('Error en Tarea::getEstadisticas(): ' . $e->getMessage());
            return ['total' => 0, 'completadas' => 0, 'pendientes' => 0, 'urgentes' => 0];
        }
    }
}