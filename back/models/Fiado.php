<?php
/**
 * Deuda de clientes y abonos.
 */

class Fiado {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function saldoDe($clienteId) {
        $cargos = $this->conn->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM ventas WHERE cliente_id = :id AND metodo_pago = 'Fiado'"
        );
        $cargos->bindValue(':id', (int) $clienteId, PDO::PARAM_INT);
        $cargos->execute();

        $abonos = $this->conn->prepare('SELECT COALESCE(SUM(monto), 0) FROM abonos WHERE cliente_id = :id');
        $abonos->bindValue(':id', (int) $clienteId, PDO::PARAM_INT);
        $abonos->execute();

        return round((float) $cargos->fetchColumn() - (float) $abonos->fetchColumn(), 2);
    }

    public function saldosPorCliente() {
        $cargos = $this->conn->query(
            "SELECT cliente_id, COALESCE(SUM(total), 0) AS cargo
             FROM ventas
             WHERE metodo_pago = 'Fiado'
             GROUP BY cliente_id"
        )->fetchAll();
        $abonos = $this->conn->query(
            'SELECT cliente_id, COALESCE(SUM(monto), 0) AS abono
             FROM abonos
             GROUP BY cliente_id'
        )->fetchAll();

        $saldos = [];
        foreach ($cargos as $fila) {
            $saldos[(int) $fila['cliente_id']] = (float) $fila['cargo'];
        }
        foreach ($abonos as $fila) {
            $id = (int) $fila['cliente_id'];
            $saldos[$id] = ($saldos[$id] ?? 0) - (float) $fila['abono'];
        }
        return $saldos;
    }

    public function deudores() {
        $stmt = $this->conn->query(
            "SELECT c.id, c.nombre_completo, c.cedula_nit, c.telefono,
                    ROUND(COALESCE(v.cargo, 0) - COALESCE(a.abono, 0), 2) AS saldo,
                    v.desde,
                    COALESCE(a.cantidad, 0) AS abonos
             FROM clientes c
             INNER JOIN (
                 SELECT cliente_id, SUM(total) AS cargo, MIN(fecha_venta) AS desde
                 FROM ventas
                 WHERE metodo_pago = 'Fiado'
                 GROUP BY cliente_id
             ) v ON v.cliente_id = c.id
             LEFT JOIN (
                 SELECT cliente_id, SUM(monto) AS abono, COUNT(*) AS cantidad
                 FROM abonos
                 GROUP BY cliente_id
             ) a ON a.cliente_id = c.id
             WHERE c.id <> 1
             HAVING saldo > 0.009
             ORDER BY v.desde ASC"
        );
        return $stmt->fetchAll();
    }

    public function abonosDe($clienteId) {
        $stmt = $this->conn->prepare(
            'SELECT a.*, u.nombre AS usuario_nombre
             FROM abonos a
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.cliente_id = :id
             ORDER BY a.id DESC'
        );
        $stmt->bindValue(':id', (int) $clienteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function repartirAbonos($clienteId = null) {
        $sqlVentas = "SELECT v.id, v.cliente_id, v.numero_factura, v.fecha_venta, v.total,
                             c.nombre_completo, c.cedula_nit, c.telefono
                      FROM ventas v
                      INNER JOIN clientes c ON c.id = v.cliente_id
                      WHERE v.metodo_pago = 'Fiado' AND c.id <> 1";
        $sqlAbonos = 'SELECT id, cliente_id, venta_id, monto, nota, creado_en FROM abonos';
        if ($clienteId !== null) {
            $sqlVentas .= ' AND v.cliente_id = :cliente_venta';
            $sqlAbonos .= ' WHERE cliente_id = :cliente_abono';
        }
        $sqlVentas .= ' ORDER BY v.fecha_venta ASC, v.id ASC';
        $sqlAbonos .= ' ORDER BY creado_en ASC, id ASC';

        $ventas = $this->conn->prepare($sqlVentas);
        $abonos = $this->conn->prepare($sqlAbonos);
        if ($clienteId !== null) {
            $ventas->bindValue(':cliente_venta', (int) $clienteId, PDO::PARAM_INT);
            $abonos->bindValue(':cliente_abono', (int) $clienteId, PDO::PARAM_INT);
        }
        $ventas->execute();
        $abonos->execute();

        $facturas = [];
        $orden = [];
        foreach ($ventas->fetchAll() as $venta) {
            $id = (int) $venta['id'];
            $cid = (int) $venta['cliente_id'];
            $total = round((float) $venta['total']);
            $facturas[$id] = [
                'id' => $id,
                'cliente_id' => $cid,
                'nombre_completo' => $venta['nombre_completo'],
                'cedula_nit' => $venta['cedula_nit'],
                'telefono' => $venta['telefono'],
                'numero_factura' => $venta['numero_factura'],
                'fecha_venta' => $venta['fecha_venta'],
                'total' => $total,
                'abonado' => 0,
                'saldo' => $total,
                'abonos' => [],
            ];
            $orden[$cid][] = $id;
        }

        foreach ($abonos->fetchAll() as $abono) {
            $cid = (int) $abono['cliente_id'];
            $resto = round((float) $abono['monto']);
            $ventaId = (int) ($abono['venta_id'] ?? 0);
            $cola = ($ventaId > 0 && isset($facturas[$ventaId]) && $facturas[$ventaId]['cliente_id'] === $cid)
                ? [$ventaId]
                : ($orden[$cid] ?? []);
            foreach ($cola as $facturaId) {
                if ($resto <= 0) {
                    break;
                }
                if ($facturas[$facturaId]['saldo'] <= 0) {
                    continue;
                }
                $aplica = min($resto, $facturas[$facturaId]['saldo']);
                $facturas[$facturaId]['abonado'] += $aplica;
                $facturas[$facturaId]['saldo'] -= $aplica;
                $facturas[$facturaId]['abonos'][] = [
                    'creado_en' => $abono['creado_en'],
                    'monto' => $aplica,
                    'nota' => $abono['nota'],
                ];
                $resto -= $aplica;
                if ($ventaId > 0) {
                    break;
                }
            }
        }

        return array_values($facturas);
    }

    public function abonar($clienteId, $monto, $usuarioId, $nota, $ventaId) {
        $monto = round((float) $monto);
        $ventaId = (int) $ventaId;
        if ($monto <= 0) {
            return ['success' => false, 'error' => 'El abono tiene que ser mayor que cero.'];
        }
        if ($ventaId <= 0) {
            return ['success' => false, 'error' => 'Elige la factura que se abona.'];
        }
        $factura = null;
        foreach ($this->repartirAbonos($clienteId) as $fila) {
            if ((int) $fila['id'] === $ventaId) {
                $factura = $fila;
                break;
            }
        }
        if (!$factura) {
            return ['success' => false, 'error' => 'Esa factura no está fiada a este cliente.'];
        }
        if ($monto - $factura['saldo'] > 0.009) {
            return ['success' => false, 'error' => 'El abono supera lo que falta de esa factura.'];
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO abonos (cliente_id, venta_id, monto, usuario_id, nota)
             VALUES (:cliente_id, :venta_id, :monto, :usuario_id, :nota)'
        );
        $stmt->bindValue(':cliente_id', (int) $clienteId, PDO::PARAM_INT);
        $stmt->bindValue(':venta_id', $ventaId, PDO::PARAM_INT);
        $stmt->bindValue(':monto', $monto);
        $stmt->bindValue(':usuario_id', (int) $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':nota', mb_substr((string) $nota, 0, 255));
        $stmt->execute();
        return ['success' => true];
    }

    public function enlaceCobro($telefono, $nombre, $saldo) {
        $digitos = preg_replace('/\D+/', '', (string) $telefono);
        if ($digitos === null) {
            return null;
        }
        if (strlen($digitos) === 10) {
            $digitos = '57' . $digitos;
        }
        if (strlen($digitos) < 12) {
            return null;
        }
        $texto = 'Hola, ' . $nombre . ' 👋 Te escribimos de Nunca Jamás. Tienes un saldo pendiente de 💰 *' . pesos($saldo) . '* 💰. Cuando tengas un momento, puedes pasar por el local a abonarlo o me avisas por aquí. ¡Gracias! 🙏';
        return 'https://wa.me/' . $digitos . '?text=' . rawurlencode($texto);
    }
}
