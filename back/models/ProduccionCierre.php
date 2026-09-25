<?php

trait ProduccionCierre {
    /**
     * Obtener resumen del día
     */
    public function getResumenDia($fecha, $usuarioId, $soloPropios = true) {
        try {
            // Si soloPropios es false (administrador), mostrar todos los registros sin filtrar por usuario_id
            if ($soloPropios) {
                $query = "SELECT 
                            COUNT(DISTINCT r.operaria_nombre) as total_operarias,
                            COUNT(DISTINCT o.id) as total_operaciones,
                            COALESCE(SUM(CASE WHEN o.piezas_producidas > 0 THEN o.piezas_producidas ELSE 0 END), 0) as prendas_terminadas,
                            COALESCE(SUM(CASE WHEN o.piezas_producidas = 0 OR o.piezas_producidas IS NULL THEN 1 ELSE 0 END), 0) as prendas_empezadas,
                            COALESCE(SUM(o.tiempo_total_minutos), 0) as tiempo_total_minutos
                          FROM " . $this->tableRegistros . " r
                          LEFT JOIN " . $this->tableOperaciones . " o ON o.registro_id = r.id
                          WHERE r.fecha = :fecha AND r.usuario_id = :usuario_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':fecha', $fecha);
                $stmt->bindParam(':usuario_id', $usuarioId);
            } else {
                $query = "SELECT 
                            COUNT(DISTINCT r.operaria_nombre) as total_operarias,
                            COUNT(DISTINCT o.id) as total_operaciones,
                            COALESCE(SUM(CASE WHEN o.piezas_producidas > 0 THEN o.piezas_producidas ELSE 0 END), 0) as prendas_terminadas,
                            COALESCE(SUM(CASE WHEN o.piezas_producidas = 0 OR o.piezas_producidas IS NULL THEN 1 ELSE 0 END), 0) as prendas_empezadas,
                            COALESCE(SUM(o.tiempo_total_minutos), 0) as tiempo_total_minutos
                          FROM " . $this->tableRegistros . " r
                          LEFT JOIN " . $this->tableOperaciones . " o ON o.registro_id = r.id
                          WHERE r.fecha = :fecha";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':fecha', $fecha);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'total_operarias' => intval($result['total_operarias'] ?? 0),
                    'total_operaciones' => intval($result['total_operaciones'] ?? 0),
                    'prendas_terminadas' => intval($result['prendas_terminadas'] ?? 0),
                    'prendas_empezadas' => intval($result['prendas_empezadas'] ?? 0),
                    'tiempo_total_minutos' => floatval($result['tiempo_total_minutos'] ?? 0)
                ];
            }
            
            // Si no hay resultados, devolver ceros pero contar operarias
            $operarias = $this->getOperariasDelDia($fecha, $usuarioId, $soloPropios);
            return [
                'total_operarias' => count($operarias),
                'total_operaciones' => 0,
                'prendas_terminadas' => 0,
                'prendas_empezadas' => 0,
                'tiempo_total_minutos' => 0
            ];
        } catch (Exception $e) {
            error_log('Error en Produccion::getResumenDia(): ' . $e->getMessage());
            // En caso de error, al menos contar las operarias
            try {
                $operarias = $this->getOperariasDelDia($fecha, $usuarioId);
                $totalOperarias = count($operarias);
            } catch (Exception $e2) {
                $totalOperarias = 0;
            }
            
            return [
                'total_operarias' => $totalOperarias,
                'total_operaciones' => 0,
                'prendas_terminadas' => 0,
                'prendas_empezadas' => 0,
                'tiempo_total_minutos' => 0
            ];
        }
    }
    
    /**
     * Guardar cierre del día en bitácora
     */
    public function guardarCierreDia($fecha, $usuarioId, $observaciones, $prendasTerminadas = 0, $prendasEmpezadas = 0, $tiempoTotalMinutos = 0) {
        try {
            // Asegurarse de que la tabla existe antes de intentar usarla
            $this->crearTablaCierresDia();
            
            // Verificar si ya existe un cierre para esta fecha
            $query = "SELECT id FROM cierres_dia_produccion WHERE fecha = :fecha AND usuario_id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':usuario_id', $usuarioId);
            $stmt->execute();
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener solo los totales de operarias y operaciones (para referencia)
            $resumen = $this->getResumenDia($fecha, $usuarioId, false);
            
            if ($existente) {
                // Actualizar cierre existente
                $query = "UPDATE cierres_dia_produccion SET 
                         prendas_terminadas = :prendas_terminadas,
                         prendas_empezadas = :prendas_empezadas,
                         tiempo_total_minutos = :tiempo_total_minutos,
                         total_operarias = :total_operarias,
                         total_operaciones = :total_operaciones,
                         observaciones = :observaciones,
                         finalizado = 1,
                         fecha_actualizacion = NOW()
                         WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $idExistente = intval($existente['id']);
                $stmt->bindParam(':id', $idExistente, PDO::PARAM_INT);
            } else {
                // Crear nuevo cierre
                $query = "INSERT INTO cierres_dia_produccion 
                         (fecha, usuario_id, prendas_terminadas, prendas_empezadas, tiempo_total_minutos, 
                          total_operarias, total_operaciones, observaciones, finalizado, fecha_creacion) 
                         VALUES 
                         (:fecha, :usuario_id, :prendas_terminadas, :prendas_empezadas, :tiempo_total_minutos,
                          :total_operarias, :total_operaciones, :observaciones, 1, NOW())";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':fecha', $fecha);
                $stmt->bindParam(':usuario_id', $usuarioId);
            }
            
            // Usar los valores proporcionados por el usuario (no calculados)
            $prendasTerminadasInt = intval($prendasTerminadas);
            $prendasEmpezadasInt = intval($prendasEmpezadas);
            $tiempoTotalMinutosFloat = floatval($tiempoTotalMinutos);
            $totalOperariasInt = intval($resumen['total_operarias'] ?? 0);
            $totalOperacionesInt = intval($resumen['total_operaciones'] ?? 0);
            
            $stmt->bindParam(':prendas_terminadas', $prendasTerminadasInt, PDO::PARAM_INT);
            $stmt->bindParam(':prendas_empezadas', $prendasEmpezadasInt, PDO::PARAM_INT);
            $stmt->bindParam(':tiempo_total_minutos', $tiempoTotalMinutosFloat);
            // Solo los totales de operarias y operaciones se calculan automáticamente
            $stmt->bindParam(':total_operarias', $totalOperariasInt, PDO::PARAM_INT);
            $stmt->bindParam(':total_operaciones', $totalOperacionesInt, PDO::PARAM_INT);
            $stmt->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
            
            $resultado = $stmt->execute();
            
            if (!$resultado) {
                $errorInfo = $stmt->errorInfo();
                $errorMsg = 'Error al ejecutar query: ' . print_r($errorInfo, true);
                error_log('Error en guardarCierreDia - ' . $errorMsg);
                error_log('Datos: fecha=' . $fecha . ', usuario_id=' . $usuarioId . ', prendas_terminadas=' . $prendasTerminadas . ', prendas_empezadas=' . $prendasEmpezadas);
                throw new Exception($errorMsg);
            }
            
            error_log('Cierre de día guardado exitosamente para fecha: ' . $fecha);
            return true;
        } catch (PDOException $e) {
            // Si la tabla no existe, crearla
            if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Table") !== false) {
                error_log('Tabla no existe, creándola...');
                $this->crearTablaCierresDia();
                // Intentar de nuevo
                try {
                    return $this->guardarCierreDia($fecha, $usuarioId, $observaciones, $prendasTerminadas, $prendasEmpezadas, $tiempoTotalMinutos);
                } catch (PDOException $e2) {
                    error_log('Error al intentar guardar después de crear tabla: ' . $e2->getMessage());
                    return false;
                }
            }
            error_log('Error en Produccion::guardarCierreDia(): ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log('Excepción en Produccion::guardarCierreDia(): ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Crear tabla de cierres de día si no existe
     */
    private function crearTablaCierresDia() {
        try {
            // Verificar si la tabla existe
            $checkQuery = "SHOW TABLES LIKE 'cierres_dia_produccion'";
            $stmt = $this->conn->query($checkQuery);
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                error_log('Creando tabla cierres_dia_produccion...');
                $query = "CREATE TABLE cierres_dia_produccion (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    fecha DATE NOT NULL,
                    usuario_id INT(11) NOT NULL,
                    prendas_terminadas INT(11) DEFAULT 0,
                    prendas_empezadas INT(11) DEFAULT 0,
                    tiempo_total_minutos DECIMAL(10,2) DEFAULT 0.00,
                    total_operarias INT(11) DEFAULT 0,
                    total_operaciones INT(11) DEFAULT 0,
                    observaciones TEXT,
                    finalizado TINYINT(1) DEFAULT 1,
                    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_fecha_usuario (fecha, usuario_id),
                    INDEX idx_fecha (fecha),
                    INDEX idx_usuario (usuario_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $this->conn->exec($query);
                error_log('Tabla cierres_dia_produccion creada exitosamente');
            } else {
                // La tabla existe, verificar si tiene la columna finalizado
                try {
                    $checkColumn = "SHOW COLUMNS FROM cierres_dia_produccion LIKE 'finalizado'";
                    $stmt = $this->conn->query($checkColumn);
                    if ($stmt->rowCount() == 0) {
                        error_log('Agregando columna finalizado a cierres_dia_produccion...');
                        $alterQuery = "ALTER TABLE cierres_dia_produccion ADD COLUMN finalizado TINYINT(1) DEFAULT 1";
                        $this->conn->exec($alterQuery);
                        error_log('Columna finalizado agregada exitosamente');
                    }
                } catch (PDOException $e) {
                    // Si hay error al verificar la columna, intentar agregarla de todas formas
                    if (strpos($e->getMessage(), "Duplicate column") === false) {
                        error_log('Error al verificar columna finalizado: ' . $e->getMessage());
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('Error al crear/verificar tabla cierres_dia_produccion: ' . $e->getMessage());
            // No lanzar excepción aquí, dejar que el método que llama maneje el error
        }
    }
    
    /**
     * Verificar si un día está finalizado
     */
    public function diaFinalizado($fecha, $usuarioId = null) {
        try {
            // Verificar si el día está finalizado por CUALQUIER usuario (especialmente administradores)
            // Esto permite que cuando un administrador finaliza el día, todos los usuarios vean el día como finalizado
            $query = "SELECT finalizado FROM cierres_dia_produccion 
                      WHERE fecha = :fecha AND finalizado = 1
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && intval($result['finalizado']) === 1;
        } catch (PDOException $e) {
            // Si la tabla no existe o hay error, el día no está finalizado
            return false;
        }
    }

    /**
     * Vuelve a abrir un día ya cerrado para toda la empresa.
     */
    public function reabrirDia($fecha) {
        try {
            $this->crearTablaCierresDia();
            $query = "UPDATE cierres_dia_produccion
                      SET finalizado = 0, fecha_actualizacion = NOW()
                      WHERE fecha = :fecha AND finalizado = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('Error en Produccion::reabrirDia(): ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener resumen general del período
     */
    public function getResumenGeneral($fechaInicio, $fechaFin, $usuarioId, $verTodas = false) {
        try {
            $query = "SELECT 
                        COUNT(DISTINCT r.id) as total_registros,
                        COUNT(DISTINCT r.operaria_nombre) as total_operarias,
                        COUNT(DISTINCT o.id) as total_operaciones,
                        SUM(o.piezas_producidas) as total_piezas,
                        SUM(o.tiempo_total_minutos) as tiempo_total_minutos,
                        AVG(o.eficiencia) as eficiencia_promedio
                      FROM registros_produccion r
                      LEFT JOIN operaciones_produccion o ON o.registro_id = r.id
                      WHERE r.fecha >= :fecha_inicio 
                        AND r.fecha <= :fecha_fin";
            if (!$verTodas) {
                $query .= " AND r.usuario_id = :usuario_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha_inicio', $fechaInicio);
            $stmt->bindParam(':fecha_fin', $fechaFin);
            if (!$verTodas) {
                $stmt->bindParam(':usuario_id', $usuarioId);
            }
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: [
                'total_registros' => 0,
                'total_operarias' => 0,
                'total_operaciones' => 0,
                'total_piezas' => 0,
                'tiempo_total_minutos' => 0,
                'eficiencia_promedio' => 0
            ];
        } catch (Exception $e) {
            error_log('Error en Produccion::getResumenGeneral(): ' . $e->getMessage());
            return [
                'total_registros' => 0,
                'total_operarias' => 0,
                'total_operaciones' => 0,
                'total_piezas' => 0,
                'tiempo_total_minutos' => 0,
                'eficiencia_promedio' => 0
            ];
        }
    }
    
    /**
     * Obtener rendimiento de operarias
     */
    public function getRendimientoOperarias($fechaInicio, $fechaFin, $usuarioId, $verTodas = false) {
        try {
            $query = "SELECT 
                        r.operaria_nombre,
                        COUNT(DISTINCT r.fecha) as dias_trabajados,
                        COUNT(DISTINCT o.id) as total_operaciones,
                        SUM(o.piezas_producidas) as total_piezas,
                        SUM(o.tiempo_total_minutos) as tiempo_total_minutos,
                        AVG(o.eficiencia) as eficiencia_promedio,
                        SUM(CASE WHEN o.eficiencia >= 100 THEN 1 ELSE 0 END) as operaciones_meta_cumplida,
                        MAX(r.meta_dia) as meta_dia,
                        (SUM(o.piezas_producidas) / NULLIF(COUNT(DISTINCT r.fecha), 0)) as promedio_piezas_dia
                      FROM registros_produccion r
                      LEFT JOIN operaciones_produccion o ON o.registro_id = r.id
                      WHERE r.fecha >= :fecha_inicio 
                        AND r.fecha <= :fecha_fin";
            if (!$verTodas) {
                $query .= " AND r.usuario_id = :usuario_id";
            }
            $query .= " GROUP BY r.operaria_nombre
                      ORDER BY total_piezas DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha_inicio', $fechaInicio);
            $stmt->bindParam(':fecha_fin', $fechaFin);
            if (!$verTodas) {
                $stmt->bindParam(':usuario_id', $usuarioId);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error en Produccion::getRendimientoOperarias(): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener bitácora de días
     */
    public function getBitacoraDias($fechaInicio, $fechaFin, $usuarioId, $verTodas = false) {
        try {
            $query = "SELECT 
                        fecha,
                        prendas_terminadas,
                        prendas_empezadas,
                        tiempo_total_minutos,
                        total_operarias,
                        total_operaciones,
                        observaciones,
                        finalizado,
                        fecha_creacion
                      FROM cierres_dia_produccion
                      WHERE fecha >= :fecha_inicio 
                        AND fecha <= :fecha_fin";
            if (!$verTodas) {
                $query .= " AND usuario_id = :usuario_id";
            }
            $query .= " ORDER BY fecha DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':fecha_inicio', $fechaInicio);
            $stmt->bindParam(':fecha_fin', $fechaFin);
            if (!$verTodas) {
                $stmt->bindParam(':usuario_id', $usuarioId);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error en Produccion::getBitacoraDias(): ' . $e->getMessage());
            return [];
        }
    }
}
