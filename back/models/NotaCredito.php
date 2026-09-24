<?php
/**
 * Nota crédito de una factura electrónica. Devuelve la prenda al stock.
 */

class NotaCredito {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function porFactura($facturaId) {
        $stmt = $this->conn->prepare('SELECT * FROM notas_credito WHERE factura_id = :id');
        $stmt->bindValue(':id', (int) $facturaId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function porVenta($ventaId) {
        $stmt = $this->conn->prepare('SELECT * FROM notas_credito WHERE venta_id = :id');
        $stmt->bindValue(':id', (int) $ventaId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function crear($venta, $factura, $resolucion, $motivo, $usuarioId) {
        if ($this->porFactura($factura['id'])) {
            return ['success' => false, 'error' => 'Esta factura ya tiene nota crédito.'];
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            return ['success' => false, 'error' => 'Escribe el motivo de la nota crédito.'];
        }

        $fechaVenta = substr((string) ($venta['fecha_venta'] ?? ''), 0, 10);
        if ((new CierreCaja($this->conn))->estaCerrado($fechaVenta)) {
            return ['success' => false, 'error' => 'La caja de ese día ya está cerrada.'];
        }

        $numero = $this->siguienteNumero();
        $valFac = round((float) $venta['subtotal'] - (float) $venta['descuento'], 2);
        if ($valFac < 0) {
            $valFac = 0;
        }
        $datos = [
            'numero' => $numero,
            'fecha' => date('Y-m-d'),
            'hora' => date('H:i:s') . '-05:00',
            'val_fac' => $valFac,
            'val_imp1' => 0,
            'val_tot' => (float) $venta['total'],
            'nit_ofe' => FacturaElectronica::documento($resolucion['nit']),
            'num_adq' => FacturaElectronica::documento($venta['cliente_cedula'] ?? ''),
            'clave_tecnica' => $resolucion['clave_tecnica'],
            'ambiente' => (string) (int) $resolucion['ambiente'],
            'razon_social' => $resolucion['razon_social'],
            'cliente_nombre' => $venta['cliente_nombre'] ?? '',
            'resolucion' => $resolucion['numero'],
            'cufe_factura' => $factura['cufe'],
            'motivo' => $motivo,
        ];
        $datos['cufe'] = FacturaElectronica::cufe($datos);
        $xml = FacturaElectronica::xmlNota($datos);

        $this->conn->beginTransaction();
        try {
            $producto = new Producto($this->conn);
            $kardex = new Kardex($this->conn);
            foreach ($venta['detalles'] as $detalle) {
                $tallaId = $producto->subirStock(
                    (int) $detalle['producto_id'],
                    (int) ($detalle['talla_id'] ?? 0),
                    (int) $detalle['cantidad']
                );
                $kardex->anotar(
                    (int) $detalle['producto_id'],
                    'nota_credito',
                    (int) $detalle['cantidad'],
                    'nota',
                    (int) $venta['id'],
                    (int) $usuarioId,
                    $numero,
                    $tallaId
                );
            }

            $stmt = $this->conn->prepare(
                'INSERT INTO notas_credito
                 (factura_id, venta_id, numero, cude, xml, motivo, total, estado)
                 VALUES
                 (:factura_id, :venta_id, :numero, :cude, :xml, :motivo, :total, :estado)'
            );
            $stmt->execute([
                ':factura_id' => (int) $factura['id'],
                ':venta_id' => (int) $venta['id'],
                ':numero' => $numero,
                ':cude' => $datos['cufe'],
                ':xml' => $xml,
                ':motivo' => mb_substr($motivo, 0, 255),
                ':total' => (float) $venta['total'],
                ':estado' => 'generada',
            ]);
            $this->conn->commit();
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'error' => 'No se pudo crear la nota crédito.'];
        }

        return ['success' => true, 'numero' => $numero];
    }

    public function guardarEnvio($id, $resultado) {
        if (!in_array($resultado['estado'], ['enviada', 'aceptada', 'rechazada'], true)) {
            return;
        }
        $stmt = $this->conn->prepare(
            'UPDATE notas_credito
             SET estado = :estado, respuesta_dian = :respuesta, zip_key = :zip_key, enviado_en = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':estado' => $resultado['estado'],
            ':respuesta' => $resultado['respuesta'],
            ':zip_key' => $resultado['zip_key'],
            ':id' => (int) $id,
        ]);
    }

    private function siguienteNumero() {
        $ultimo = (int) $this->conn->query('SELECT COALESCE(MAX(id), 0) FROM notas_credito')->fetchColumn();
        return 'NC-' . str_pad((string) ($ultimo + 1), 6, '0', STR_PAD_LEFT);
    }
}
