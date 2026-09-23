<?php
/**
 * Modelo de Producción
 */

class Produccion {
    private $conn;
    private $tableRegistros = 'registros_produccion';
    private $tableOperaciones = 'operaciones_produccion';
    private $tableRetrocesos = 'retrocesos_produccion';
    private $tableHistorial = 'historial_operaciones_produccion';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
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