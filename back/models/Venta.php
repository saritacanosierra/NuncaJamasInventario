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
            // Generar número de factura
            $numeroFactura = $this->generarNumeroFactura();
            
            // Insertar venta
            $query = "INSERT INTO " . $this->table . " 
                      (numero_factura, cliente_id, usuario_id, subtotal, descuento, total, metodo_pago, con_domicilio, observaciones_domicilio, pago_contra_entrega) 
                      VALUES 
                      (:numero_factura, :cliente_id, :usuario_id, :subtotal, :descuento, :total, :metodo_pago, :con_domicilio, :observaciones_domicilio, :pago_contra_entrega)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':numero_factura', $numeroFactura);
            $stmt->bindParam(':cliente_id', $data['cliente_id']);
            $stmt->bindParam(':usuario_id', $data['usuario_id']);
            $stmt->bindParam(':subtotal', $data['subtotal']);
            $stmt->bindParam(':descuento', $data['descuento']);
            $stmt->bindParam(':total', $data['total']);
            $stmt->bindParam(':metodo_pago', $data['metodo_pago']);
            $conDomicilio = $data['con_domicilio'] ? 1 : 0;
            $stmt->bindParam(':con_domicilio', $conDomicilio, PDO::PARAM_INT);
            $stmt->bindParam(':observaciones_domicilio', $data['observaciones_domicilio']);
            $pagoContraEntrega = isset($data['pago_contra_entrega']) && $data['pago_contra_entrega'] ? 1 : 0;
            $stmt->bindParam(':pago_contra_entrega', $pagoContraEntrega, PDO::PARAM_INT);
            
            $stmt->execute();
            $ventaId = $this->conn->lastInsertId();
            
            // Insertar detalles
            $productoModel = new Producto($this->conn);
            foreach ($data['detalles'] as $detalle) {
                // Insertar detalle
                $queryDetalle = "INSERT INTO detalle_venta 
                                (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                                VALUES 
                                (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
                
                $stmtDetalle = $this->conn->prepare($queryDetalle);
                $stmtDetalle->bindParam(':venta_id', $ventaId);
                $stmtDetalle->bindParam(':producto_id', $detalle['producto_id']);
                $stmtDetalle->bindParam(':cantidad', $detalle['cantidad']);
                $stmtDetalle->bindParam(':precio_unitario', $detalle['precio_unitario']);
                $stmtDetalle->bindParam(':subtotal', $detalle['subtotal']);
                $stmtDetalle->execute();
                
                // Reducir stock
                $productoModel->reducirStock($detalle['producto_id'], $detalle['cantidad']);
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
                                    p.codigo_barras, p.color, p.talla
                             FROM detalle_venta dv
                             LEFT JOIN productos p ON dv.producto_id = p.id
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
                          total = :total,
                          metodo_pago = :metodo_pago,
                          con_domicilio = :con_domicilio,
                          observaciones_domicilio = :observaciones_domicilio,
                          pago_contra_entrega = :pago_contra_entrega
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':cliente_id', $data['cliente_id']);
            $stmt->bindParam(':subtotal', $data['subtotal']);
            $stmt->bindParam(':descuento', $data['descuento']);
            $stmt->bindParam(':total', $data['total']);
            $stmt->bindParam(':metodo_pago', $data['metodo_pago']);
            $conDomicilio = $data['con_domicilio'] ? 1 : 0;
            $stmt->bindParam(':con_domicilio', $conDomicilio, PDO::PARAM_INT);
            $stmt->bindParam(':observaciones_domicilio', $data['observaciones_domicilio']);
            $pagoContraEntrega = isset($data['pago_contra_entrega']) && $data['pago_contra_entrega'] ? 1 : 0;
            $stmt->bindParam(':pago_contra_entrega', $pagoContraEntrega, PDO::PARAM_INT);
            $stmt->execute();
            
            // Restaurar stock de productos eliminados o con cantidad reducida
            $productoModel = new Producto($this->conn);
            foreach ($data['detalles_actuales'] as $detalleActual) {
                $encontrado = false;
                $nuevaCantidad = 0;
                
                foreach ($data['detalles'] as $detalleNuevo) {
                    if ($detalleActual['producto_id'] == $detalleNuevo['producto_id']) {
                        $encontrado = true;
                        $nuevaCantidad = intval($detalleNuevo['cantidad']);
                        break;
                    }
                }
                
                if (!$encontrado) {
                    // Producto eliminado, restaurar todo el stock
                    $productoModel->aumentarStock($detalleActual['producto_id'], $detalleActual['cantidad']);
                } else if ($nuevaCantidad < $detalleActual['cantidad']) {
                    // Cantidad reducida, restaurar la diferencia
                    $diferencia = $detalleActual['cantidad'] - $nuevaCantidad;
                    $productoModel->aumentarStock($detalleActual['producto_id'], $diferencia);
                } else if ($nuevaCantidad > $detalleActual['cantidad']) {
                    // Cantidad aumentada, reducir la diferencia
                    $diferencia = $nuevaCantidad - $detalleActual['cantidad'];
                    $productoModel->reducirStock($detalleActual['producto_id'], $diferencia);
                }
            }
            
            // Eliminar todos los detalles actuales
            $queryDelete = "DELETE FROM detalle_venta WHERE venta_id = :venta_id";
            $stmtDelete = $this->conn->prepare($queryDelete);
            $stmtDelete->bindParam(':venta_id', $id);
            $stmtDelete->execute();
            
            // Insertar nuevos detalles
            foreach ($data['detalles'] as $detalle) {
                $queryDetalle = "INSERT INTO detalle_venta 
                                (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                                VALUES 
                                (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
                
                $stmtDetalle = $this->conn->prepare($queryDetalle);
                $stmtDetalle->bindParam(':venta_id', $id);
                $stmtDetalle->bindParam(':producto_id', $detalle['producto_id']);
                $stmtDetalle->bindParam(':cantidad', $detalle['cantidad']);
                $stmtDetalle->bindParam(':precio_unitario', $detalle['precio_unitario']);
                $stmtDetalle->bindParam(':subtotal', $detalle['subtotal']);
                $stmtDetalle->execute();
            }
            
            // Reducir stock de productos nuevos (que no estaban en la venta original)
            foreach ($data['detalles'] as $detalleNuevo) {
                $esNuevo = true;
                foreach ($data['detalles_actuales'] as $detalleActual) {
                    if ($detalleActual['producto_id'] == $detalleNuevo['producto_id']) {
                        $esNuevo = false;
                        break;
                    }
                }
                if ($esNuevo) {
                    $productoModel->reducirStock($detalleNuevo['producto_id'], $detalleNuevo['cantidad']);
                }
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
    public function eliminar($id) {
        $this->conn->beginTransaction();

        try {
            $venta = $this->getById($id);
            if (!$venta) {
                $this->conn->rollBack();
                return false;
            }

            $productoModel = new Producto($this->conn);
            foreach ($venta['detalles'] as $detalle) {
                $productoModel->aumentarStock($detalle['producto_id'], $detalle['cantidad']);
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
}