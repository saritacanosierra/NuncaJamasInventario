<?php
/**
 * Movimientos de un mes para el historial descargable.
 */

class MovimientoMes {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function gastos($desde, $hasta) {
        $stmt = $this->conn->prepare(
            'SELECT fecha, concepto, categoria, monto, descripcion
             FROM gastos
             WHERE fecha BETWEEN :desde AND :hasta
             ORDER BY fecha, id'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    public function inversiones($desde, $hasta) {
        $stmt = $this->conn->prepare(
            'SELECT fecha, concepto, categoria, monto, descripcion
             FROM inversiones
             WHERE fecha BETWEEN :desde AND :hasta
             ORDER BY fecha, id'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    public function ingresos($desde, $hasta) {
        $stmt = $this->conn->prepare(
            'SELECT v.fecha_venta, v.numero_factura, c.nombre_completo, v.metodo_pago,
                    v.subtotal, v.descuento, v.iva, v.domicilio, v.empaque, v.total,
                    v.pago_efectivo, v.pago_transferencia, v.pago_tarjeta, v.pago_contra_entrega
             FROM ventas v
             INNER JOIN clientes c ON c.id = v.cliente_id
             WHERE DATE(v.fecha_venta) BETWEEN :desde AND :hasta
             ORDER BY v.fecha_venta, v.id'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    public function abonos($desde, $hasta) {
        $stmt = $this->conn->prepare(
            'SELECT a.creado_en, a.monto, a.nota, c.nombre_completo, v.numero_factura
             FROM abonos a
             INNER JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN ventas v ON v.id = a.venta_id
             WHERE DATE(a.creado_en) BETWEEN :desde AND :hasta
             ORDER BY a.creado_en, a.id'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    public function productosVendidos($desde, $hasta) {
        $stmt = $this->conn->prepare(
            'SELECT v.fecha_venta, v.numero_factura, c.nombre_completo,
                    p.nombre, p.color, COALESCE(pt.talla, \'\') AS talla,
                    dv.cantidad, dv.precio_unitario, dv.subtotal
             FROM detalle_venta dv
             INNER JOIN ventas v ON v.id = dv.venta_id
             INNER JOIN clientes c ON c.id = v.cliente_id
             INNER JOIN productos p ON p.id = dv.producto_id
             LEFT JOIN producto_tallas pt ON pt.id = dv.talla_id
             WHERE DATE(v.fecha_venta) BETWEEN :desde AND :hasta
             ORDER BY v.fecha_venta, p.nombre, pt.talla'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }
}
