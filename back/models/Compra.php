<?php
/**
 * Compras de mercancía. Suben el stock y dejan el costo de la última compra.
 */

class Compra {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($data) {
        $this->conn->beginTransaction();
        try {
            $numero = $this->siguienteNumero();
            $stmt = $this->conn->prepare(
                'INSERT INTO compras (numero, proveedor, documento, fecha, total, sale_de_caja, usuario_id, observaciones)
                 VALUES (:numero, :proveedor, :documento, :fecha, :total, :sale_de_caja, :usuario_id, :observaciones)'
            );
            $stmt->bindValue(':numero', $numero);
            $stmt->bindValue(':proveedor', $data['proveedor']);
            $stmt->bindValue(':documento', $data['documento']);
            $stmt->bindValue(':fecha', $data['fecha']);
            $stmt->bindValue(':total', $data['total']);
            $stmt->bindValue(':sale_de_caja', !empty($data['sale_de_caja']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', (int) $data['usuario_id'], PDO::PARAM_INT);
            $stmt->bindValue(':observaciones', $data['observaciones']);
            $stmt->execute();
            $compraId = (int) $this->conn->lastInsertId();

            $detalle = $this->conn->prepare(
                'INSERT INTO compras_detalle (compra_id, producto_id, cantidad, costo_unitario, subtotal)
                 VALUES (:compra_id, :producto_id, :cantidad, :costo_unitario, :subtotal)'
            );
            $costo = $this->conn->prepare('UPDATE productos SET precio_costo = :costo WHERE id = :id');
            $producto = new Producto($this->conn);
            $kardex = new Kardex($this->conn);

            foreach ($data['lineas'] as $linea) {
                $nueva = (int) $linea['producto_id'] < 1;
                if ($nueva) {
                    $linea['producto_id'] = $this->crearComprada($linea);
                    $linea['talla_id'] = 0;
                }
                $detalle->bindValue(':compra_id', $compraId, PDO::PARAM_INT);
                $detalle->bindValue(':producto_id', (int) $linea['producto_id'], PDO::PARAM_INT);
                $detalle->bindValue(':cantidad', (int) $linea['cantidad'], PDO::PARAM_INT);
                $detalle->bindValue(':costo_unitario', $linea['costo_unitario']);
                $detalle->bindValue(':subtotal', $linea['subtotal']);
                $detalle->execute();

                if ($nueva) {
                    $tallas = $producto->getTallas((int) $linea['producto_id']);
                    $tallaId = $tallas ? (int) $tallas[0]['id'] : 0;
                } else {
                    $tallaId = $producto->subirStock(
                        (int) $linea['producto_id'],
                        (int) ($linea['talla_id'] ?? 0),
                        (int) $linea['cantidad']
                    );
                }
                $kardex->anotar(
                    (int) $linea['producto_id'],
                    'compra',
                    (int) $linea['cantidad'],
                    'compra',
                    $compraId,
                    (int) $data['usuario_id'],
                    $numero,
                    $tallaId
                );
                $costo->bindValue(':costo', $linea['costo_unitario']);
                $costo->bindValue(':id', (int) $linea['producto_id'], PDO::PARAM_INT);
                $costo->execute();
                $producto->marcarComprado((int) $linea['producto_id']);
            }

            if ((float) $data['total'] > 0) {
                $inversion = $this->conn->prepare(
                    'INSERT INTO inversiones (concepto, monto, categoria, fecha, descripcion, usuario_id)
                     VALUES (:concepto, :monto, :categoria, :fecha, :descripcion, :usuario_id)'
                );
                $inversion->bindValue(':concepto', 'Compra ' . $numero);
                $inversion->bindValue(':monto', $data['total']);
                $inversion->bindValue(':categoria', 'Producto');
                $inversion->bindValue(':fecha', $data['fecha']);
                $inversion->bindValue(':descripcion', $data['proveedor']);
                $inversion->bindValue(':usuario_id', (int) $data['usuario_id'], PDO::PARAM_INT);
                $inversion->execute();
            }

            $this->conn->commit();
            return ['success' => true, 'numero' => $numero];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Error al crear compra: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function recientes($limite = 40) {
        $stmt = $this->conn->prepare(
            'SELECT c.*, u.nombre AS usuario_nombre
             FROM compras c
             LEFT JOIN usuarios u ON u.id = c.usuario_id
             ORDER BY c.id DESC
             LIMIT ' . (int) $limite
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function totalCajaEnFecha($fecha) {
        $stmt = $this->conn->prepare(
            'SELECT COALESCE(SUM(total), 0)
             FROM compras
             WHERE fecha = :fecha AND sale_de_caja = 1'
        );
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    private function crearComprada($linea) {
        $producto = new Producto($this->conn);
        $codigo = BarcodeGenerator::generateEAN13();
        while ($producto->codigoExiste($codigo)) {
            $codigo = BarcodeGenerator::generateEAN13();
        }
        $color = trim((string) ($linea['color'] ?? ''));
        if ($color === '') {
            $color = 'Sin color';
        }
        $id = $producto->create([
            'codigo_barras' => $codigo,
            'nombre' => $linea['nombre'],
            'descripcion' => '',
            'color' => $color,
            'talla' => $linea['talla'],
            'precio_costo' => $linea['costo_unitario'],
            'precio_venta' => $linea['costo_unitario'],
            'categoria_id' => (int) $linea['categoria_id'],
            'stock' => 0,
            'stock_minimo' => 0,
            'estado' => 'Disponible',
            'origen' => 'comprado',
            'foto' => null,
        ]);
        if (!$id) {
            throw new Exception('No se pudo crear la prenda comprada');
        }
        $producto->guardarTallas($id, [[
            'talla' => $linea['talla'],
            'stock' => (int) $linea['cantidad'],
        ]]);
        return (int) $id;
    }

    private function siguienteNumero() {
        $stmt = $this->conn->query('SELECT numero FROM compras ORDER BY id DESC LIMIT 1');
        $ultimo = $stmt->fetchColumn();
        $numero = $ultimo ? ((int) substr($ultimo, 4)) + 1 : 1;
        return 'COM-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
    }
}
