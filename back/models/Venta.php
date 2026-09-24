<?php
/**
 * Modelo de Venta
 */

class Venta {
    private $conn;
    private $table = 'ventas';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear venta
     */
    public function create($data) {
        $this->conn->beginTransaction();
        
        try {
            $productoModel = new Producto($this->conn);
            $productoModel->asegurarStock($data['detalles']);

            // Generar número de factura
            $numeroFactura = $this->generarNumeroFactura();
            
            // Insertar venta
            $query = "INSERT INTO " . $this->table . " 
                      (numero_factura, cliente_id, usuario_id, subtotal, descuento, iva, domicilio, empaque, total, metodo_pago, pago_efectivo, pago_transferencia, pago_tarjeta, recibido, devuelta, con_domicilio, observaciones_domicilio, pago_contra_entrega, domicilio_contra_entrega) 
                      VALUES 
                      (:numero_factura, :cliente_id, :usuario_id, :subtotal, :descuento, :iva, :domicilio, :empaque, :total, :metodo_pago, :pago_efectivo, :pago_transferencia, :pago_tarjeta, :recibido, :devuelta, :con_domicilio, :observaciones_domicilio, :pago_contra_entrega, :domicilio_contra_entrega)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':numero_factura', $numeroFactura);
            $stmt->bindParam(':cliente_id', $data['cliente_id']);
            $stmt->bindParam(':usuario_id', $data['usuario_id']);
            $stmt->bindParam(':subtotal', $data['subtotal']);
            $stmt->bindParam(':descuento', $data['descuento']);
            $iva = $data['iva'] ?? 0;
            $domicilio = $data['domicilio'] ?? 0;
            $empaque = $data['empaque'] ?? 0;
            $stmt->bindValue(':iva', $iva);
            $stmt->bindValue(':domicilio', $domicilio);
            $stmt->bindValue(':empaque', $empaque);
            $stmt->bindParam(':total', $data['total']);
            $stmt->bindParam(':metodo_pago', $data['metodo_pago']);
            $pagoEfectivo = $data['pago_efectivo'] ?? 0;
            $pagoTransferencia = $data['pago_transferencia'] ?? 0;
            $pagoTarjeta = $data['pago_tarjeta'] ?? 0;
            $recibido = $data['recibido'] ?? 0;
            $devuelta = $data['devuelta'] ?? 0;
            $stmt->bindValue(':pago_efectivo', $pagoEfectivo);
            $stmt->bindValue(':pago_transferencia', $pagoTransferencia);
            $stmt->bindValue(':pago_tarjeta', $pagoTarjeta);
            $stmt->bindValue(':recibido', $recibido);
            $stmt->bindValue(':devuelta', $devuelta);
            $conDomicilio = $data['con_domicilio'] ? 1 : 0;
            $stmt->bindParam(':con_domicilio', $conDomicilio, PDO::PARAM_INT);
            $stmt->bindParam(':observaciones_domicilio', $data['observaciones_domicilio']);
            $pagoContraEntrega = isset($data['pago_contra_entrega']) && $data['pago_contra_entrega'] ? 1 : 0;
            $stmt->bindParam(':pago_contra_entrega', $pagoContraEntrega, PDO::PARAM_INT);
            $domicilioContra = !empty($data['domicilio_contra_entrega']) ? 1 : 0;
            $stmt->bindValue(':domicilio_contra_entrega', $domicilioContra, PDO::PARAM_INT);
            
            $stmt->execute();
            $ventaId = $this->conn->lastInsertId();
            
            // Insertar detalles
            $kardex = new Kardex($this->conn);
            foreach ($data['detalles'] as $detalle) {
                $tallaId = $productoModel->bajarStock(
                    (int) $detalle['producto_id'],
                    (int) ($detalle['talla_id'] ?? 0),
                    (int) $detalle['cantidad']
                );
                $this->insertarDetalle($ventaId, $detalle, $tallaId);
                $kardex->anotar(
                    (int) $detalle['producto_id'],
                    'venta',
                    -1 * (int) $detalle['cantidad'],
                    'venta',
                    (int) $ventaId,
                    (int) $data['usuario_id'],
                    $numeroFactura,
                    $tallaId
                );
            }
            
            $this->conn->commit();
            return ['success' => true, 'venta_id' => $ventaId, 'numero_factura' => $numeroFactura];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al crear venta: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generar número de factura único
     */
    private function generarNumeroFactura() {
        // Obtener último número
        $query = "SELECT numero_factura FROM " . $this->table . " 
                  ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $ultimo = $stmt->fetch();
        
        if ($ultimo) {
            // Extraer número y aumentar
            $numero = (int)substr($ultimo['numero_factura'], 4);
            $numero++;
        } else {
            $numero = 1;
        }
        
        return 'FAC-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Obtener venta por ID con detalles
     */
    public function getById($id) {
        $query = "SELECT v.*, c.nombre_completo as cliente_nombre, 
                         c.cedula_nit as cliente_cedula,
                         c.telefono as cliente_telefono,
                         c.direccion as cliente_direccion,
                         c.email as cliente_email,
                         u.nombre as vendedor
                  FROM " . $this->table . " v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN usuarios u ON v.usuario_id = u.id
                  WHERE v.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $venta = $stmt->fetch();
        
        if ($venta) {
            // Obtener detalles
            $queryDetalle = "SELECT dv.*, p.nombre as producto_nombre, 
                                    p.codigo_barras, p.color,
                                    COALESCE(pt.talla, p.talla) AS talla
                             FROM detalle_venta dv
                             LEFT JOIN productos p ON dv.producto_id = p.id
                             LEFT JOIN producto_tallas pt ON pt.id = dv.talla_id
                             WHERE dv.venta_id = :venta_id";
            
            $stmtDetalle = $this->conn->prepare($queryDetalle);
            $stmtDetalle->bindParam(':venta_id', $id);
            $stmtDetalle->execute();
            
            $venta['detalles'] = $stmtDetalle->fetchAll();
        }
        
        return $venta;
    }
    
    /**
     * Listar ventas con filtros
     */
    public function getAll($filters = []) {
        $query = "SELECT v.*, c.nombre_completo as cliente_nombre, 
                         u.nombre as vendedor,
                         v.con_domicilio
                  FROM " . $this->table . " v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN usuarios u ON v.usuario_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['fecha_desde'])) {
            $query .= " AND DATE(v.fecha_venta) >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }
        
        if (!empty($filters['fecha_hasta'])) {
            $query .= " AND DATE(v.fecha_venta) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }
        
        if (!empty($filters['cliente_id']) && $filters['cliente_id'] !== '') {
            $clienteId = intval($filters['cliente_id']);
            if ($clienteId > 0) {
                $query .= " AND v.cliente_id = :cliente_id";
                $params[':cliente_id'] = $clienteId;
            }
        }
        
        $query .= " ORDER BY v.fecha_venta DESC LIMIT 100";
        
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en Venta::getAll(): " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Params: " . print_r($params, true));
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de ventas
     */
    public function getEstadisticas($periodo = 'dia') {
        $fechaInicio = '';
        switch ($periodo) {
            case 'dia':
                $fechaInicio = date('Y-m-d');
                break;
            case 'semana':
                $fechaInicio = date('Y-m-d', strtotime('-7 days'));
                break;
            case 'mes':
                $fechaInicio = date('Y-m-01');
                break;
        }
        
        $query = "SELECT 
                    COALESCE(COUNT(*), 0) as total_ventas,
                    COALESCE(SUM(total), 0) as total_ingresos,
                    COALESCE(SUM(subtotal), 0) as total_subtotal,
                    COALESCE(SUM(descuento), 0) as total_descuentos,
                    COALESCE(AVG(total), 0) as promedio_venta
                  FROM " . $this->table . "
                  WHERE fecha_venta >= :fecha_inicio";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha_inicio', $fechaInicio);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado ?: [
            'total_ventas' => 0,
            'total_ingresos' => 0,
            'total_subtotal' => 0,
            'total_descuentos' => 0,
            'promedio_venta' => 0
        ];
    }
    
    /**
     * Obtener ventas por día (últimos 30 días)
     */
    public function getVentasPorDia() {
        try {
            $query = "SELECT 
                        DATE(fecha_venta) as fecha,
                        COUNT(*) as cantidad,
                        COALESCE(SUM(total), 0) as total
                      FROM " . $this->table . "
                      WHERE fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      GROUP BY DATE(fecha_venta)
                      ORDER BY fecha ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $resultado = $stmt->fetchAll();
            return $resultado ?: [];
        } catch (Exception $e) {
            error_log("Error en getVentasPorDia: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar venta
     */
    public function update($id, $data) {
        $this->conn->beginTransaction();
        
        try {
            // Actualizar datos principales de la venta
            $query = "UPDATE " . $this->table . " 
                      SET cliente_id = :cliente_id,
                          subtotal = :subtotal,
                          descuento = :descuento,
                          iva = :iva,
                          domicilio = :domicilio,
                          empaque = :empaque,
                          total = :total,
                          metodo_pago = :metodo_pago,
                          pago_efectivo = :pago_efectivo,
                          pago_transferencia = :pago_transferencia,
                          pago_tarjeta = :pago_tarjeta,
                          recibido = :recibido,
                          devuelta = :devuelta,
                          con_domicilio = :con_domicilio,
                          observaciones_domicilio = :observaciones_domicilio,
                          pago_contra_entrega = :pago_contra_entrega
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':cliente_id', $data['cliente_id']);
            $stmt->bindParam(':subtotal', $data['subtotal']);
            $stmt->bindParam(':descuento', $data['descuento']);
            $iva = $data['iva'] ?? 0;
            $domicilio = $data['domicilio'] ?? 0;
            $empaque = $data['empaque'] ?? 0;
            $stmt->bindValue(':iva', $iva);
            $stmt->bindValue(':domicilio', $domicilio);
            $stmt->bindValue(':empaque', $empaque);
            $stmt->bindParam(':total', $data['total']);
            $stmt->bindParam(':metodo_pago', $data['metodo_pago']);
            $pagoEfectivo = $data['pago_efectivo'] ?? 0;
            $pagoTransferencia = $data['pago_transferencia'] ?? 0;
            $pagoTarjeta = $data['pago_tarjeta'] ?? 0;
            $recibido = $data['recibido'] ?? 0;
            $devuelta = $data['devuelta'] ?? 0;
            $stmt->bindValue(':pago_efectivo', $pagoEfectivo);
            $stmt->bindValue(':pago_transferencia', $pagoTransferencia);
            $stmt->bindValue(':pago_tarjeta', $pagoTarjeta);
            $stmt->bindValue(':recibido', $recibido);
            $stmt->bindValue(':devuelta', $devuelta);
            $conDomicilio = $data['con_domicilio'] ? 1 : 0;
            $stmt->bindParam(':con_domicilio', $conDomicilio, PDO::PARAM_INT);
            $stmt->bindParam(':observaciones_domicilio', $data['observaciones_domicilio']);
            $pagoContraEntrega = isset($data['pago_contra_entrega']) && $data['pago_contra_entrega'] ? 1 : 0;
            $stmt->bindParam(':pago_contra_entrega', $pagoContraEntrega, PDO::PARAM_INT);
            $stmt->execute();
            
            $productoModel = new Producto($this->conn);
            $kardex = new Kardex($this->conn);
            $usuarioId = (int) ($data['usuario_id'] ?? 0);
            $viejas = $this->agruparLineas($data['detalles_actuales']);
            $nuevas = $this->agruparLineas($data['detalles']);
            $claves = array_unique(array_merge(array_keys($viejas), array_keys($nuevas)));
            foreach ($claves as $clave) {
                $vieja = (int) ($viejas[$clave]['cantidad'] ?? 0);
                $nueva = (int) ($nuevas[$clave]['cantidad'] ?? 0);
                $delta = $nueva - $vieja;
                $fila = $nuevas[$clave] ?? $viejas[$clave];
                if ($delta > 0) {
                    $tallaId = $productoModel->bajarStock((int) $fila['producto_id'], (int) ($fila['talla_id'] ?? 0), $delta);
                    $kardex->anotar((int) $fila['producto_id'], 'venta', -$delta, 'venta', (int) $id, $usuarioId, 'Edición de venta', $tallaId);
                } elseif ($delta < 0) {
                    $tallaId = $productoModel->subirStock((int) $fila['producto_id'], (int) ($fila['talla_id'] ?? 0), -$delta);
                    $kardex->anotar((int) $fila['producto_id'], 'anulacion', -$delta, 'venta', (int) $id, $usuarioId, 'Edición de venta', $tallaId);
                }
            }

            $queryDelete = "DELETE FROM detalle_venta WHERE venta_id = :venta_id";
            $stmtDelete = $this->conn->prepare($queryDelete);
            $stmtDelete->bindParam(':venta_id', $id);
            $stmtDelete->execute();

            foreach ($data['detalles'] as $detalle) {
                $this->insertarDetalle($id, $detalle, (int) ($detalle['talla_id'] ?? 0));
            }
            
            $this->conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al actualizar venta: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener ventas agrupadas por mes
     */
    public function getVentasPorMes() {
        $query = "SELECT 
                    DATE_FORMAT(fecha_venta, '%Y-%m') as mes,
                    DATE_FORMAT(fecha_venta, '%M %Y') as mes_nombre,
                    SUM(total) as total,
                    SUM(subtotal) as subtotal,
                    SUM(descuento) as descuento,
                    COUNT(*) as cantidad
                  FROM " . $this->table . "
                  GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m'), DATE_FORMAT(fecha_venta, '%M %Y')
                  ORDER BY mes DESC
                  LIMIT 12";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener ventas agrupadas por año
     */
    public function getVentasPorAnio() {
        $query = "SELECT 
                    YEAR(fecha_venta) as anio,
                    SUM(total) as total,
                    SUM(subtotal) as subtotal,
                    SUM(descuento) as descuento,
                    COUNT(*) as cantidad
                  FROM " . $this->table . "
                  GROUP BY YEAR(fecha_venta)
                  ORDER BY anio DESC
                  LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Elimina la venta, sus líneas y devuelve el stock.
     */
    public function eliminar($id, $usuarioId) {
        $this->conn->beginTransaction();

        try {
            $venta = $this->getById($id);
            if (!$venta) {
                $this->conn->rollBack();
                return false;
            }

            $productoModel = new Producto($this->conn);
            $kardex = new Kardex($this->conn);
            foreach ($venta['detalles'] as $detalle) {
                $tallaId = $productoModel->subirStock(
                    (int) $detalle['producto_id'],
                    (int) ($detalle['talla_id'] ?? 0),
                    (int) $detalle['cantidad']
                );
                $kardex->anotar(
                    (int) $detalle['producto_id'],
                    'anulacion',
                    (int) $detalle['cantidad'],
                    'venta',
                    (int) $id,
                    (int) $usuarioId,
                    'Anulación de venta',
                    $tallaId
                );
            }

            $stmtDetalle = $this->conn->prepare('DELETE FROM detalle_venta WHERE venta_id = :id');
            $stmtDetalle->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDetalle->execute();

            $stmt = $this->conn->prepare('DELETE FROM ' . $this->table . ' WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Error al eliminar venta: ' . $e->getMessage());
            return false;
        }
    }

    private function agruparLineas($detalles) {
        $grupos = [];
        foreach ($detalles as $detalle) {
            $clave = (int) $detalle['producto_id'] . ':' . (int) ($detalle['talla_id'] ?? 0);
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = $detalle;
                $grupos[$clave]['cantidad'] = (int) $detalle['cantidad'];
            } else {
                $grupos[$clave]['cantidad'] += (int) $detalle['cantidad'];
            }
        }
        return $grupos;
    }

    private function insertarDetalle($ventaId, $detalle, $tallaId) {
        $stmt = $this->conn->prepare(
            'INSERT INTO detalle_venta (venta_id, producto_id, talla_id, cantidad, precio_unitario, subtotal)
             VALUES (:venta_id, :producto_id, :talla_id, :cantidad, :precio_unitario, :subtotal)'
        );
        $stmt->bindValue(':venta_id', (int) $ventaId, PDO::PARAM_INT);
        $stmt->bindValue(':producto_id', (int) $detalle['producto_id'], PDO::PARAM_INT);
        if ((int) $tallaId > 0) {
            $stmt->bindValue(':talla_id', (int) $tallaId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':talla_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':cantidad', (int) $detalle['cantidad'], PDO::PARAM_INT);
        $stmt->bindValue(':precio_unitario', $detalle['precio_unitario']);
        $stmt->bindValue(':subtotal', $detalle['subtotal']);
        $stmt->execute();
    }
}