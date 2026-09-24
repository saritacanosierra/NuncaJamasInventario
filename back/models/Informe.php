<?php
/**
 * Cifras de un mes para entregar al contador.
 */

class Informe {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function delMes($anio, $mes) {
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));

        $ventas = $this->fila(
            'SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS total
             FROM ventas
             WHERE DATE(fecha_venta) BETWEEN :desde AND :hasta',
            $desde,
            $hasta
        );
        $gastos = $this->fila(
            'SELECT COUNT(*) AS n, COALESCE(SUM(monto), 0) AS total
             FROM gastos
             WHERE fecha BETWEEN :desde AND :hasta',
            $desde,
            $hasta
        );
        $cierres = $this->conn->prepare(
            'SELECT fecha, esperado, contado, diferencia
             FROM cierres_caja
             WHERE fecha BETWEEN :desde AND :hasta
             ORDER BY fecha'
        );
        $cierres->execute([':desde' => $desde, ':hasta' => $hasta]);

        $facturas = $this->conn->prepare(
            'SELECT estado, COUNT(*) AS n
             FROM facturas_dian
             WHERE DATE(creado_en) BETWEEN :desde AND :hasta
             GROUP BY estado'
        );
        $facturas->execute([':desde' => $desde, ':hasta' => $hasta]);

        $notas = $this->fila(
            'SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS total
             FROM notas_credito
             WHERE DATE(creado_en) BETWEEN :desde AND :hasta',
            $desde,
            $hasta
        );

        $fiadoVentas = (float) $this->conn->query(
            "SELECT COALESCE(SUM(total), 0) FROM ventas WHERE metodo_pago = 'Fiado'"
        )->fetchColumn();
        $fiadoAbonos = (float) $this->conn->query(
            'SELECT COALESCE(SUM(monto), 0) FROM abonos'
        )->fetchColumn();

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'ventas_n' => (int) $ventas['n'],
            'ventas_total' => (float) $ventas['total'],
            'gastos_n' => (int) $gastos['n'],
            'gastos_total' => (float) $gastos['total'],
            'cierres' => $cierres->fetchAll(),
            'facturas' => $facturas->fetchAll(),
            'notas_n' => (int) $notas['n'],
            'notas_total' => (float) $notas['total'],
            'fiado' => $fiadoVentas - $fiadoAbonos,
        ];
    }

    private function fila($sql, $desde, $hasta) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $fila = $stmt->fetch();
        return $fila ?: ['n' => 0, 'total' => 0];
    }
}
