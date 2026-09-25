<?php

trait ProduccionRegistros {
    /**
     * Crear registro diario
     */
    public function crearRegistro($data) {
        try {
            $query = "INSERT INTO " . $this->tableRegistros . " 
                      (operaria_nombre, fecha, turno, meta_dia, maquina_asignada, usuario_id) 
                      VALUES 
                      (:operaria_nombre, :fecha, :turno, :meta_dia, :maquina_asignada, :usuario_id)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':operaria_nombre', $data['operaria_nombre']);
            $stmt->bindParam(':fecha', $data['fecha']);
            $stmt->bindParam(':turno', $data['turno']);
            $stmt->bindParam(':meta_dia', $data['meta_dia']);
            $stmt->bindParam(':maquina_asignada', $data['maquina_asignada']);
            $stmt->bindParam(':usuario_id', $data['usuario_id']);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla registros_produccion no existe. Ejecuta crear_tabla_produccion.sql en phpMyAdmin.');
                throw new Exception('Las tablas de producción no existen. Por favor, ejecuta el script crear_tabla_produccion.sql en phpMyAdmin.');
            }
            error_log('Error en Produccion::crearRegistro(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener registro por ID
     */
    public function getRegistroById($id) {
        try {
            $query = "SELECT * FROM " . $this->tableRegistros . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Error en Produccion::getRegistroById(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener registro del día actual (método legacy - mantener para compatibilidad)
     */
    public function getRegistroDelDia($fecha, $usuarioId) {
        try {
            $query = "SELECT * FROM " . $this->tableRegistros . " 
                      WHERE fecha = :fecha AND usuario_id = :usuario_id 
                      ORDER BY fecha_creacion DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':usuario_id', $usuarioId);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla registros_produccion no existe. Ejecuta crear_tabla_produccion.sql en phpMyAdmin.');
                return false;
            }
            error_log('Error en Produccion::getRegistroDelDia(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener registro por operaria específica
     */
    public function getRegistroPorOperaria($fecha, $operariaNombre, $usuarioId, $cualquierUsuario = false) {
        try {
            if ($cualquierUsuario) {
                $query = "SELECT r.* FROM " . $this->tableRegistros . " r
                          WHERE r.fecha = :fecha AND LOWER(r.operaria_nombre) = LOWER(:operaria_nombre)
                          ORDER BY (SELECT COUNT(*) FROM " . $this->tableOperaciones . " o WHERE o.registro_id = r.id) DESC, r.id ASC
                          LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':fecha', $fecha);
                $stmt->bindParam(':operaria_nombre', $operariaNombre);
            } else {
                $query = "SELECT * FROM " . $this->tableRegistros . " 
                          WHERE fecha = :fecha AND operaria_nombre = :operaria_nombre AND usuario_id = :usuario_id 
                          ORDER BY fecha_creacion DESC LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':fecha', $fecha);
                $stmt->bindParam(':operaria_nombre', $operariaNombre);
                $stmt->bindParam(':usuario_id', $usuarioId);
            }
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla registros_produccion no existe. Ejecuta crear_tabla_produccion.sql en phpMyAdmin.');
                return false;
            }
            error_log('Error en Produccion::getRegistroPorOperaria(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todas las operarias del día con sus registros completos
     */
    public function getOperariasDelDia($fecha, $usuarioId, $soloPropios = true) {
        try {
            // Si soloPropios es false (administrador), mostrar todos los registros sin filtrar por usuario_id
            if ($soloPropios) {
                // Obtener el registro más reciente de cada operaria para la fecha especificada (solo del usuario)
                $query = "SELECT r1.* 
                          FROM " . $this->tableRegistros . " r1
                          INNER JOIN (
                              SELECT operaria_nombre, MAX(fecha_creacion) as max_fecha
                              FROM " . $this->tableRegistros . "
                              WHERE fecha = :fecha AND usuario_id = :usuario_id
                              GROUP BY operaria_nombre
                          ) r2 ON r1.operaria_nombre = r2.operaria_nombre 
                              AND r1.fecha_creacion = r2.max_fecha
                              AND r1.fecha = :fecha2
                              AND r1.usuario_id = :usuario_id2
                          ORDER BY r1.operaria_nombre ASC";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':fecha', $fecha);
                $stmt->bindParam(':fecha2', $fecha);
                $stmt->bindParam(':usuario_id', $usuarioId);
                $stmt->bindParam(':usuario_id2', $usuarioId);
            } else {
                $query = "SELECT r.*,
                                 (SELECT COUNT(*) FROM " . $this->tableOperaciones . " o WHERE o.registro_id = r.id) AS n_ops
                          FROM " . $this->tableRegistros . " r
                          WHERE r.fecha = :fecha
                          ORDER BY n_ops DESC, r.id ASC";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':fecha', $fecha);
            }
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$soloPropios) {
                $vistos = [];
                $unicos = [];
                foreach ($result as $fila) {
                    $clave = strtolower($fila['operaria_nombre']);
                    if (isset($vistos[$clave])) {
                        continue;
                    }
                    $vistos[$clave] = true;
                    unset($fila['n_ops']);
                    $unicos[] = $fila;
                }
                usort($unicos, function ($a, $b) {
                    return strcasecmp($a['operaria_nombre'], $b['operaria_nombre']);
                });
                $result = $unicos;
            }
            error_log('getOperariasDelDia - Fecha: ' . $fecha . ', Usuario: ' . $usuarioId . ', SoloPropios: ' . ($soloPropios ? 'true' : 'false') . ', Resultados: ' . count($result));
            return $result;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla registros_produccion no existe. Ejecuta crear_tabla_produccion.sql en phpMyAdmin.');
                return [];
            }
            error_log('Error en Produccion::getOperariasDelDia(): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener todos los registros del día
     */
    public function getAllRegistrosDelDia($fecha, $usuarioId) {
        try {
            $query = "SELECT * FROM " . $this->tableRegistros . " 
                      WHERE fecha = :fecha AND usuario_id = :usuario_id 
                      ORDER BY operaria_nombre ASC, fecha_creacion DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':usuario_id', $usuarioId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla registros_produccion no existe. Ejecuta crear_tabla_produccion.sql en phpMyAdmin.');
                return [];
            }
            error_log('Error en Produccion::getAllRegistrosDelDia(): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar registro
     */
    public function actualizarRegistro($id, $data) {
        try {
            $query = "UPDATE " . $this->tableRegistros . " SET 
                      operaria_nombre = :operaria_nombre,
                      turno = :turno,
                      meta_dia = :meta_dia,
                      maquina_asignada = :maquina_asignada,
                      tiempo_total_trabajado = :tiempo_total_trabajado,
                      tiempo_perdido_retrocesos = :tiempo_perdido_retrocesos,
                      piezas_producidas = :piezas_producidas,
                      eficiencia_promedio = :eficiencia_promedio
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':operaria_nombre', $data['operaria_nombre']);
            $stmt->bindParam(':turno', $data['turno']);
            $stmt->bindParam(':meta_dia', $data['meta_dia']);
            $stmt->bindParam(':maquina_asignada', $data['maquina_asignada']);
            $stmt->bindParam(':tiempo_total_trabajado', $data['tiempo_total_trabajado']);
            $stmt->bindParam(':tiempo_perdido_retrocesos', $data['tiempo_perdido_retrocesos']);
            $stmt->bindParam(':piezas_producidas', $data['piezas_producidas']);
            $stmt->bindParam(':eficiencia_promedio', $data['eficiencia_promedio']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error en Produccion::actualizarRegistro(): ' . $e->getMessage());
            return false;
        }
    }
    
}
