<?php
/**
 * Cierre de caja de un día. Se cierra una vez.
 */

class CierreCaja {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function porFecha($fecha) {
        $stmt = $this->conn->prepare(
            'SELECT c.*, u.nombre AS usuario_nombre
             FROM cierres_caja c
             LEFT JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.fecha = :fecha'
        );
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function estaCerrado($fecha) {
        return $this->porFecha($fecha) !== null;
    }

    public function resumen($fecha) {
        $stmt = $this->conn->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN pago_contra_entrega = 1 THEN total ELSE 0 END), 0) AS contra,
                COALESCE(SUM(CASE WHEN pago_contra_entrega = 0 AND metodo_pago = \'Fiado\' THEN total ELSE 0 END), 0) AS fiado,
                COALESCE(SUM(CASE WHEN pago_contra_entrega = 0 AND metodo_pago <> \'Fiado\' THEN pago_efectivo ELSE 0 END), 0) AS efectivo,
                COALESCE(SUM(CASE WHEN pago_contra_entrega = 0 AND metodo_pago <> \'Fiado\' THEN pago_transferencia + pago_tarjeta ELSE 0 END), 0) AS otros
             FROM ventas
             WHERE DATE(fecha_venta) = :fecha'
        );
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        $fila = $stmt->fetch() ?: [];

        $efectivo = (float) ($fila['efectivo'] ?? 0);
        $otros = (float) ($fila['otros'] ?? 0);
        $fiado = (float) ($fila['fiado'] ?? 0);
        $contraEntrega = (float) ($fila['contra'] ?? 0);

        $gastos = (new Gasto($this->conn))->getTotalPorPeriodo($fecha, $fecha);
        $comprasCaja = (new Compra($this->conn))->totalCajaEnFecha($fecha);

        return [
            'fecha' => $fecha,
            'ventas_efectivo' => $efectivo,
            'ventas_otros' => $otros,
            'ventas_fiado' => $fiado,
            'ventas_contra_entrega' => $contraEntrega,
            'gastos' => (float) $gastos['total'],
            'compras_caja' => $comprasCaja,
        ];
    }

    public function cerrar($data) {
        if ($this->estaCerrado($data['fecha'])) {
            return ['success' => false, 'error' => 'Este día de caja ya está cerrado.'];
        }

        $resumen = $this->resumen($data['fecha']);
        $base = round((float) $data['base'], 2);
        $contado = round((float) $data['contado'], 2);
        $esperado = round($base + $resumen['ventas_efectivo'] - $resumen['gastos'] - $resumen['compras_caja'], 2);
        $diferencia = round($contado - $esperado, 2);

        $stmt = $this->conn->prepare(
            'INSERT INTO cierres_caja
             (fecha, usuario_id, base, ventas_efectivo, ventas_otros, ventas_fiado, ventas_contra_entrega,
              gastos, compras_caja, esperado, contado, diferencia, observacion)
             VALUES
             (:fecha, :usuario_id, :base, :ventas_efectivo, :ventas_otros, :ventas_fiado, :ventas_contra_entrega,
              :gastos, :compras_caja, :esperado, :contado, :diferencia, :observacion)'
        );
        $stmt->bindValue(':fecha', $data['fecha']);
        $stmt->bindValue(':usuario_id', (int) $data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':base', $base);
        $stmt->bindValue(':ventas_efectivo', $resumen['ventas_efectivo']);
        $stmt->bindValue(':ventas_otros', $resumen['ventas_otros']);
        $stmt->bindValue(':ventas_fiado', $resumen['ventas_fiado']);
        $stmt->bindValue(':ventas_contra_entrega', $resumen['ventas_contra_entrega']);
        $stmt->bindValue(':gastos', $resumen['gastos']);
        $stmt->bindValue(':compras_caja', $resumen['compras_caja']);
        $stmt->bindValue(':esperado', $esperado);
        $stmt->bindValue(':contado', $contado);
        $stmt->bindValue(':diferencia', $diferencia);
        $stmt->bindValue(':observacion', $data['observacion']);
        $stmt->execute();

        return ['success' => true, 'cierre' => $this->porFecha($data['fecha'])];
    }

    public function historial($limite = 30) {
        $stmt = $this->conn->prepare(
            'SELECT c.*, u.nombre AS usuario_nombre
             FROM cierres_caja c
             LEFT JOIN usuarios u ON u.id = c.usuario_id
             ORDER BY c.fecha DESC
             LIMIT ' . (int) $limite
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
