<?php

trait ProduccionOperaciones {
    /**
     * Crear operación
     */
    public function crearOperacion($data) {
        try {
            // Verificar si los campos de pausas existen en la tabla
            $query = "INSERT INTO " . $this->tableOperaciones . " 
                      (registro_id, codigo_operacion, nombre_operacion, maquina_usada, 
                       hora_inicio, hora_fin, tiempo_total_minutos, piezas_producidas, 
                       tiempo_estandar_por_pieza, eficiencia";
            
            // Agregar campos de pausas si están disponibles
            $tienePausas = isset($data['cantidad_pausas']) || isset($data['tiempo_pausas_minutos']);
            if ($tienePausas) {
                $query .= ", cantidad_pausas, tiempo_pausas_minutos";
            }
            
            $query .= ") VALUES 
                      (:registro_id, :codigo_operacion, :nombre_operacion, :maquina_usada,
                       :hora_inicio, :hora_fin, :tiempo_total_minutos, :piezas_producidas,
                       :tiempo_estandar_por_pieza, :eficiencia";
            
            if ($tienePausas) {
                $query .= ", :cantidad_pausas, :tiempo_pausas_minutos";
            }
            
            $query .= ")";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':registro_id', $data['registro_id']);
            $stmt->bindParam(':codigo_operacion', $data['codigo_operacion']);
            $stmt->bindParam(':nombre_operacion', $data['nombre_operacion']);
            $stmt->bindParam(':maquina_usada', $data['maquina_usada']);
            $stmt->bindParam(':hora_inicio', $data['hora_inicio']);
            $horaFin = !empty($data['hora_fin']) ? $data['hora_fin'] : null;
            $stmt->bindParam(':hora_fin', $horaFin);
            $stmt->bindParam(':tiempo_total_minutos', $data['tiempo_total_minutos']);
            $stmt->bindParam(':piezas_producidas', $data['piezas_producidas']);
            $stmt->bindParam(':tiempo_estandar_por_pieza', $data['tiempo_estandar_por_pieza']);
            $stmt->bindParam(':eficiencia', $data['eficiencia']);
            
            if ($tienePausas) {
                $cantidadPausas = intval($data['cantidad_pausas'] ?? 0);
                $tiempoPausas = floatval($data['tiempo_pausas_minutos'] ?? 0);
                $stmt->bindParam(':cantidad_pausas', $cantidadPausas);
                $stmt->bindParam(':tiempo_pausas_minutos', $tiempoPausas);
            }
            
            if ($stmt->execute()) {
                $operacionId = $this->conn->lastInsertId();
                
                // Registrar inicio en historial
                $this->registrarHistorialOperacion($operacionId, 'inicio', [
                    'hora_inicio' => $data['hora_inicio'],
                    'hora_fin' => null,
                    'tiempo_total_minutos' => 0,
                    'piezas_producidas' => 0,
                    'eficiencia' => 0
                ]);
                
                return $operacionId;
            }
            return false;
        } catch (PDOException $e) {
            error_log('Error en Produccion::crearOperacion(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener operación por ID
     */
    public function getOperacionById($id) {
        try {
            $query = "SELECT * FROM " . $this->tableOperaciones . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error en Produccion::getOperacionById(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener operaciones de un registro
     */
    public function getOperaciones($registroId) {
        try {
            $query = "SELECT * FROM " . $this->tableOperaciones . " 
                      WHERE registro_id = :registro_id 
                      ORDER BY hora_inicio ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':registro_id', $registroId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Error en Produccion::getOperaciones(): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar operación
     */
    public function actualizarOperacion($id, $data) {
        try {
            $query = "UPDATE " . $this->tableOperaciones . " SET 
                      hora_fin = :hora_fin,
                      tiempo_total_minutos = :tiempo_total_minutos,
                      piezas_producidas = :piezas_producidas,
                      eficiencia = :eficiencia
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $horaFin = !empty($data['hora_fin']) ? $data['hora_fin'] : null;
            $stmt->bindParam(':hora_fin', $horaFin);
            $stmt->bindParam(':tiempo_total_minutos', $data['tiempo_total_minutos']);
            $stmt->bindParam(':piezas_producidas', $data['piezas_producidas']);
            $stmt->bindParam(':eficiencia', $data['eficiencia']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error en Produccion::actualizarOperacion(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar operación
     */
    public function eliminarOperacion($id) {
        try {
            $query = "DELETE FROM " . $this->tableOperaciones . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error en Produccion::eliminarOperacion(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear retroceso
     */
    public function crearRetroceso($data) {
        try {
            $query = "INSERT INTO " . $this->tableRetrocesos . " 
                      (registro_id, operacion_id, tipo_defecto, maquina, minutos_perdidos, accion_correctiva) 
                      VALUES 
                      (:registro_id, :operacion_id, :tipo_defecto, :maquina, :minutos_perdidos, :accion_correctiva)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':registro_id', $data['registro_id']);
            $operacionId = !empty($data['operacion_id']) ? $data['operacion_id'] : null;
            $stmt->bindParam(':operacion_id', $operacionId);
            $stmt->bindParam(':tipo_defecto', $data['tipo_defecto']);
            $stmt->bindParam(':maquina', $data['maquina']);
            $stmt->bindParam(':minutos_perdidos', $data['minutos_perdidos']);
            $stmt->bindParam(':accion_correctiva', $data['accion_correctiva']);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('Error en Produccion::crearRetroceso(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener retrocesos de un registro
     */
    public function getRetrocesos($registroId) {
        try {
            $query = "SELECT r.*, o.nombre_operacion 
                      FROM " . $this->tableRetrocesos . " r
                      LEFT JOIN " . $this->tableOperaciones . " o ON r.operacion_id = o.id
                      WHERE r.registro_id = :registro_id 
                      ORDER BY r.fecha_creacion DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':registro_id', $registroId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Error en Produccion::getRetrocesos(): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener tiempo total perdido en retrocesos
     */
    public function getTiempoPerdidoRetrocesos($registroId) {
        try {
            $query = "SELECT SUM(minutos_perdidos) as total FROM " . $this->tableRetrocesos . " 
                      WHERE registro_id = :registro_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':registro_id', $registroId);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log('Error en Produccion::getTiempoPerdidoRetrocesos(): ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Registrar cambio en historial de operación
     */
    public function registrarHistorialOperacion($operacionId, $tipoCambio, $data = []) {
        try {
            $query = "INSERT INTO " . $this->tableHistorial . " 
                      (operacion_id, tipo_cambio, hora_inicio, hora_fin, tiempo_total_minutos, 
                       piezas_producidas, eficiencia, observaciones) 
                      VALUES 
                      (:operacion_id, :tipo_cambio, :hora_inicio, :hora_fin, :tiempo_total_minutos,
                       :piezas_producidas, :eficiencia, :observaciones)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':operacion_id', $operacionId);
            $stmt->bindParam(':tipo_cambio', $tipoCambio);
            
            $horaInicio = $data['hora_inicio'] ?? null;
            $horaFin = $data['hora_fin'] ?? null;
            $tiempoTotal = $data['tiempo_total_minutos'] ?? 0;
            $piezas = $data['piezas_producidas'] ?? 0;
            $eficiencia = $data['eficiencia'] ?? 0;
            $observaciones = $data['observaciones'] ?? null;
            
            $stmt->bindParam(':hora_inicio', $horaInicio);
            $stmt->bindParam(':hora_fin', $horaFin);
            $stmt->bindParam(':tiempo_total_minutos', $tiempoTotal);
            $stmt->bindParam(':piezas_producidas', $piezas);
            $stmt->bindParam(':eficiencia', $eficiencia);
            $stmt->bindParam(':observaciones', $observaciones);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla historial_operaciones_produccion no existe. Ejecuta crear_tabla_historial_operaciones.sql en phpMyAdmin.');
                return false;
            }
            error_log('Error en Produccion::registrarHistorialOperacion(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener historial de una operación
     */
    public function getHistorialOperacion($operacionId) {
        try {
            $query = "SELECT * FROM " . $this->tableHistorial . " 
                      WHERE operacion_id = :operacion_id 
                      ORDER BY fecha_creacion ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':operacion_id', $operacionId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla historial_operaciones_produccion no existe. Ejecuta crear_tabla_historial_operaciones.sql en phpMyAdmin.');
                return [];
            }
            error_log('Error en Produccion::getHistorialOperacion(): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener historial completo de una operación (incluyendo retrocesos)
     */
    public function getHistorialCompletoOperacion($operacionId) {
        try {
            // Obtener historial de cambios
            $historial = $this->getHistorialOperacion($operacionId);
            
            // Obtener retrocesos asociados a esta operación
            $query = "SELECT r.id, r.operacion_id, r.tipo_defecto, r.maquina, r.minutos_perdidos, 
                             r.accion_correctiva, r.fecha_creacion, 'retroceso' as tipo_cambio 
                      FROM " . $this->tableRetrocesos . " r
                      WHERE r.operacion_id = :operacion_id 
                      ORDER BY r.fecha_creacion ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':operacion_id', $operacionId);
            $stmt->execute();
            $retrocesos = $stmt->fetchAll();
            
            // Combinar y ordenar por fecha
            $historialCompleto = array_merge($historial, $retrocesos);
            usort($historialCompleto, function($a, $b) {
                $fechaA = $a['fecha_creacion'] ?? $a['fecha_creacion'] ?? '';
                $fechaB = $b['fecha_creacion'] ?? $b['fecha_creacion'] ?? '';
                return strcmp($fechaA, $fechaB);
            });
            
            return $historialCompleto;
        } catch (PDOException $e) {
            error_log('Error en Produccion::getHistorialCompletoOperacion(): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener todas las operarias únicas (para autocompletado)
     */
    public function getOperariasUnicas($busqueda = '', $usuarioId = null) {
        try {
            $query = "SELECT DISTINCT operaria_nombre 
                      FROM " . $this->tableRegistros . " 
                      WHERE 1=1";
            
            $params = [];
            
            if (!empty($busqueda)) {
                $query .= " AND operaria_nombre LIKE :busqueda";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            if ($usuarioId !== null) {
                $query .= " AND usuario_id = :usuario_id";
                $params[':usuario_id'] = $usuarioId;
            }
            
            $query .= " ORDER BY operaria_nombre ASC";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('Tabla registros_produccion no existe. Ejecuta crear_tabla_produccion.sql en phpMyAdmin.');
                return [];
            }
            error_log('Error en Produccion::getOperariasUnicas(): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generar código único de operación
     * Formato: OP-YYYYMMDD-XXX (donde XXX es un número secuencial del día)
     */
    public function generarCodigoOperacion($fecha = null) {
        try {
            if ($fecha === null) {
                $fecha = date('Y-m-d');
            }
            
            // Formato de fecha: YYYYMMDD
            $fechaFormato = str_replace('-', '', $fecha);
            
            // Buscar el último código del día
            $query = "SELECT codigo_operacion 
                      FROM " . $this->tableOperaciones . " 
                      WHERE codigo_operacion LIKE :patron
                      ORDER BY codigo_operacion DESC 
                      LIMIT 1";
            
            $patron = 'OP-' . $fechaFormato . '-%';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':patron', $patron);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $numeroSecuencial = 1;
            
            if ($resultado && !empty($resultado['codigo_operacion'])) {
                // Extraer el número secuencial del último código
                $ultimoCodigo = $resultado['codigo_operacion'];
                $partes = explode('-', $ultimoCodigo);
                if (count($partes) === 3 && $partes[0] === 'OP' && $partes[1] === $fechaFormato) {
                    $numeroSecuencial = intval($partes[2]) + 1;
                }
            }
            
            // Generar el nuevo código con 3 dígitos
            $codigo = 'OP-' . $fechaFormato . '-' . str_pad($numeroSecuencial, 3, '0', STR_PAD_LEFT);
            
            return $codigo;
        } catch (PDOException $e) {
            error_log('Error en Produccion::generarCodigoOperacion(): ' . $e->getMessage());
            // En caso de error, generar un código con timestamp
            $fechaFormato = str_replace('-', '', $fecha ?? date('Y-m-d'));
            $timestamp = time();
            return 'OP-' . $fechaFormato . '-' . substr($timestamp, -3);
        }
    }
    
}
