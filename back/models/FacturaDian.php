<?php
/**
 * Resolución de facturación y documento electrónico guardado en el local.
 */

class FacturaDian {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function resolucionVigente() {
        $stmt = $this->conn->query(
            'SELECT * FROM resolucion_facturacion WHERE vigente = 1 ORDER BY id DESC LIMIT 1'
        );
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function guardarResolucion($data) {
        $this->conn->exec('UPDATE resolucion_facturacion SET vigente = 0');
        $stmt = $this->conn->prepare(
            'INSERT INTO resolucion_facturacion
             (numero, prefijo, desde_numero, hasta_numero, fecha_desde, fecha_hasta, clave_tecnica, nit, razon_social, ambiente, software_id, software_pin, set_pruebas, vigente)
             VALUES
             (:numero, :prefijo, :desde_numero, :hasta_numero, :fecha_desde, :fecha_hasta, :clave_tecnica, :nit, :razon_social, :ambiente, :software_id, :software_pin, :set_pruebas, 1)'
        );
        $stmt->execute([
            ':numero' => $data['numero'],
            ':prefijo' => $data['prefijo'],
            ':desde_numero' => (int) $data['desde_numero'],
            ':hasta_numero' => (int) $data['hasta_numero'],
            ':fecha_desde' => $data['fecha_desde'],
            ':fecha_hasta' => $data['fecha_hasta'],
            ':clave_tecnica' => $data['clave_tecnica'],
            ':nit' => $data['nit'],
            ':razon_social' => $data['razon_social'],
            ':ambiente' => (int) $data['ambiente'],
            ':software_id' => $data['software_id'] ?? '',
            ':software_pin' => $data['software_pin'] ?? '',
            ':set_pruebas' => $data['set_pruebas'] ?? '',
        ]);
    }

    public function porVenta($ventaId) {
        $stmt = $this->conn->prepare('SELECT * FROM facturas_dian WHERE venta_id = :venta_id');
        $stmt->bindValue(':venta_id', (int) $ventaId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function emitir($venta) {
        if ($this->porVenta($venta['id'])) {
            return ['success' => false, 'error' => 'Esta venta ya tiene documento electrónico.'];
        }

        $resolucion = $this->resolucionVigente();
        if (!$resolucion) {
            return ['success' => false, 'error' => 'Falta la resolución de facturación.'];
        }
        if (trim($resolucion['clave_tecnica']) === '') {
            return ['success' => false, 'error' => 'La resolución no tiene clave técnica. Sin ella no se puede armar el CUFE.'];
        }

        $hoy = date('Y-m-d');
        if ($hoy < $resolucion['fecha_desde'] || $hoy > $resolucion['fecha_hasta']) {
            return ['success' => false, 'error' => 'La resolución no está vigente en la fecha de hoy.'];
        }

        $consecutivo = $this->siguienteConsecutivo($resolucion);
        if ($consecutivo > (int) $resolucion['hasta_numero']) {
            return ['success' => false, 'error' => 'Se acabó el rango de la resolución.'];
        }

        $numero = $resolucion['prefijo'] . $consecutivo;
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
        ];
        if ($datos['nit_ofe'] === '' || $datos['num_adq'] === '') {
            return ['success' => false, 'error' => 'Faltan el NIT del emisor o la cédula del cliente.'];
        }

        $datos['cufe'] = FacturaElectronica::cufe($datos);
        $xml = FacturaElectronica::xml($datos);

        $stmt = $this->conn->prepare(
            'INSERT INTO facturas_dian
             (venta_id, resolucion_id, prefijo, consecutivo, numero, cufe, xml, estado)
             VALUES
             (:venta_id, :resolucion_id, :prefijo, :consecutivo, :numero, :cufe, :xml, :estado)'
        );
        $stmt->execute([
            ':venta_id' => (int) $venta['id'],
            ':resolucion_id' => (int) $resolucion['id'],
            ':prefijo' => $resolucion['prefijo'],
            ':consecutivo' => $consecutivo,
            ':numero' => $numero,
            ':cufe' => $datos['cufe'],
            ':xml' => $xml,
            ':estado' => 'generada',
        ]);

        return ['success' => true, 'numero' => $numero];
    }

    public function guardarEnvio($id, $resultado) {
        if (!in_array($resultado['estado'], ['enviada', 'aceptada', 'rechazada'], true)) {
            return;
        }
        $stmt = $this->conn->prepare(
            'UPDATE facturas_dian
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

    private function siguienteConsecutivo($resolucion) {
        $stmt = $this->conn->prepare(
            'SELECT COALESCE(MAX(consecutivo), 0) FROM facturas_dian WHERE resolucion_id = :id'
        );
        $stmt->bindValue(':id', (int) $resolucion['id'], PDO::PARAM_INT);
        $stmt->execute();
        $ultimo = (int) $stmt->fetchColumn();
        $desde = (int) $resolucion['desde_numero'];
        return $ultimo < $desde ? $desde : $ultimo + 1;
    }
}
