<?php
/**
 * Tarifa y liquidación de lo que se le paga a cada operaria por pieza.
 */

class PagoPieza {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function tarifas() {
        $stmt = $this->conn->query('SELECT * FROM tarifas_operacion ORDER BY nombre, codigo');
        return $stmt->fetchAll();
    }

    public function codigosUsados() {
        $stmt = $this->conn->query(
            'SELECT codigo_operacion AS codigo, MAX(nombre_operacion) AS nombre
             FROM operaciones_produccion
             GROUP BY codigo_operacion
             ORDER BY nombre'
        );
        return $stmt->fetchAll();
    }

    public function guardar($codigo, $nombre, $valor) {
        $stmt = $this->conn->prepare(
            'INSERT INTO tarifas_operacion (codigo, nombre, valor_pieza)
             VALUES (:codigo, :nombre, :valor)
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), valor_pieza = VALUES(valor_pieza)'
        );
        $stmt->execute([
            ':codigo' => $codigo,
            ':nombre' => $nombre,
            ':valor' => (int) $valor,
        ]);
    }

    public function liquidacion($desde, $hasta) {
        $stmt = $this->conn->prepare(
            'SELECT r.operaria_nombre,
                    o.codigo_operacion,
                    MAX(o.nombre_operacion) AS nombre_operacion,
                    SUM(o.piezas_producidas) AS piezas
             FROM operaciones_produccion o
             INNER JOIN registros_produccion r ON r.id = o.registro_id
             WHERE r.fecha BETWEEN :desde AND :hasta
               AND o.piezas_producidas > 0
             GROUP BY r.operaria_nombre, o.codigo_operacion
             ORDER BY r.operaria_nombre, o.codigo_operacion'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $filas = $stmt->fetchAll();

        $tarifas = [];
        foreach ($this->tarifas() as $tarifa) {
            $tarifas[$tarifa['codigo']] = (int) $tarifa['valor_pieza'];
        }

        $salida = [];
        foreach ($filas as $fila) {
            $codigo = (string) $fila['codigo_operacion'];
            $tiene = array_key_exists($codigo, $tarifas);
            $valor = $tiene ? $tarifas[$codigo] : null;
            $piezas = (int) $fila['piezas'];
            $salida[] = [
                'operaria' => $fila['operaria_nombre'],
                'codigo' => $codigo,
                'nombre' => $fila['nombre_operacion'],
                'piezas' => $piezas,
                'valor' => $valor,
                'pago' => $tiene ? $piezas * $valor : null,
            ];
        }
        return $salida;
    }
}
