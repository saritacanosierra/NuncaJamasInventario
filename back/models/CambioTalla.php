<?php
/**
 * Cambio de talla de una línea ya vendida.
 * Devuelve la prenda saliente y descuenta la que entra.
 */

class CambioTalla {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function aplicar($ventaId, $detalleId, $productoEntraId, $cantidad, $usuarioId, $tallaEntraId = 0) {
        $this->conn->beginTransaction();
        try {
            $ventaStmt = $this->conn->prepare('SELECT * FROM ventas WHERE id = :id');
            $ventaStmt->bindValue(':id', (int) $ventaId, PDO::PARAM_INT);
            $ventaStmt->execute();
            $venta = $ventaStmt->fetch();
            if (!$venta) {
                throw new Exception('La venta no existe');
            }

            $fecha = date('Y-m-d', strtotime($venta['fecha_venta']));
            if ((new CierreCaja($this->conn))->estaCerrado($fecha)) {
                throw new Exception('El día de esa venta ya tiene la caja cerrada');
            }

            $dian = $this->conn->prepare('SELECT id FROM facturas_dian WHERE venta_id = :id');
            $dian->bindValue(':id', (int) $ventaId, PDO::PARAM_INT);
            $dian->execute();
            if ($dian->fetch()) {
                throw new Exception('Esta venta ya tiene documento electrónico. El cambio de talla cambiaría una factura emitida');
            }

            $detalleStmt = $this->conn->prepare(
                'SELECT d.*, p.nombre, p.talla
                 FROM detalle_venta d
                 INNER JOIN productos p ON p.id = d.producto_id
                 WHERE d.id = :id AND d.venta_id = :venta_id'
            );
            $detalleStmt->bindValue(':id', (int) $detalleId, PDO::PARAM_INT);
            $detalleStmt->bindValue(':venta_id', (int) $ventaId, PDO::PARAM_INT);
            $detalleStmt->execute();
            $detalle = $detalleStmt->fetch();
            if (!$detalle) {
                throw new Exception('La prenda no está en esa venta');
            }

            $cantidad = (int) $cantidad;
            if ($cantidad < 1 || $cantidad > (int) $detalle['cantidad']) {
                throw new Exception('La cantidad del cambio no coincide con lo vendido');
            }
            $producto = new Producto($this->conn);
            $entra = $producto->getById((int) $productoEntraId);
            if (!$entra) {
                throw new Exception('La talla nueva no existe');
            }
            $tallaEntraId = (int) $tallaEntraId;
            if ($tallaEntraId < 1 && count($entra['tallas']) === 1) {
                $tallaEntraId = (int) $entra['tallas'][0]['id'];
            }
            $tallaSaleId = (int) ($detalle['talla_id'] ?? 0);
            if ($tallaEntraId < 1) {
                throw new Exception('Elige la talla');
            }
            if ($tallaSaleId > 0 && $tallaEntraId === $tallaSaleId) {
                throw new Exception('Elige otra talla');
            }

            $producto->subirStock((int) $detalle['producto_id'], $tallaSaleId, $cantidad);
            $kardex = new Kardex($this->conn);
            $kardex->anotar((int) $detalle['producto_id'], 'cambio_sale', $cantidad, 'cambio', (int) $ventaId, (int) $usuarioId, 'Sale del cliente', $tallaSaleId);
            $tallaEntraId = $producto->bajarStock((int) $productoEntraId, $tallaEntraId, $cantidad);
            $kardex->anotar((int) $productoEntraId, 'cambio_entra', -$cantidad, 'cambio', (int) $ventaId, (int) $usuarioId, 'Entra al cliente', $tallaEntraId);

            $precioNuevo = (float) $entra['precio_venta'];
            $precioViejo = (float) $detalle['precio_unitario'];
            $diferencia = round(($precioNuevo - $precioViejo) * $cantidad, 2);

            if ($cantidad === (int) $detalle['cantidad']) {
                $upd = $this->conn->prepare(
                    'UPDATE detalle_venta
                     SET producto_id = :producto_id, talla_id = :talla_id, precio_unitario = :precio, subtotal = :subtotal
                     WHERE id = :id'
                );
                $upd->execute([
                    ':producto_id' => (int) $productoEntraId,
                    ':talla_id' => $tallaEntraId,
                    ':precio' => $precioNuevo,
                    ':subtotal' => round($precioNuevo * $cantidad, 2),
                    ':id' => (int) $detalleId,
                ]);
            } else {
                $queda = (int) $detalle['cantidad'] - $cantidad;
                $upd = $this->conn->prepare(
                    'UPDATE detalle_venta SET cantidad = :cantidad, subtotal = :subtotal WHERE id = :id'
                );
                $upd->execute([
                    ':cantidad' => $queda,
                    ':subtotal' => round($precioViejo * $queda, 2),
                    ':id' => (int) $detalleId,
                ]);
                $ins = $this->conn->prepare(
                    'INSERT INTO detalle_venta (venta_id, producto_id, talla_id, cantidad, precio_unitario, subtotal)
                     VALUES (:venta_id, :producto_id, :talla_id, :cantidad, :precio, :subtotal)'
                );
                $ins->execute([
                    ':venta_id' => (int) $ventaId,
                    ':producto_id' => (int) $productoEntraId,
                    ':talla_id' => $tallaEntraId,
                    ':cantidad' => $cantidad,
                    ':precio' => $precioNuevo,
                    ':subtotal' => round($precioNuevo * $cantidad, 2),
                ]);
            }

            $suma = $this->conn->prepare('SELECT COALESCE(SUM(subtotal), 0) FROM detalle_venta WHERE venta_id = :id');
            $suma->bindValue(':id', (int) $ventaId, PDO::PARAM_INT);
            $suma->execute();
            $subtotal = round((float) $suma->fetchColumn(), 2);
            $total = round($subtotal - (float) $venta['descuento'], 2);
            if ($total < 0) {
                $total = 0;
            }
            $ventaUpd = $this->conn->prepare('UPDATE ventas SET subtotal = :subtotal, total = :total WHERE id = :id');
            $ventaUpd->execute([
                ':subtotal' => $subtotal,
                ':total' => $total,
                ':id' => (int) $ventaId,
            ]);

            $cambio = $this->conn->prepare(
                'INSERT INTO cambios_talla
                 (venta_id, producto_sale_id, producto_entra_id, cantidad, diferencia, usuario_id, observacion)
                 VALUES
                 (:venta_id, :sale, :entra, :cantidad, :diferencia, :usuario_id, :observacion)'
            );
            $cambio->execute([
                ':venta_id' => (int) $ventaId,
                ':sale' => (int) $detalle['producto_id'],
                ':entra' => (int) $productoEntraId,
                ':cantidad' => $cantidad,
                ':diferencia' => $diferencia,
                ':usuario_id' => (int) $usuarioId,
                ':observacion' => $detalle['nombre'] . ' talla ' . $detalle['talla'] . ' por ' . $entra['nombre'] . ' talla ' . $entra['talla'],
            ]);

            $this->conn->commit();
            return ['success' => true, 'diferencia' => $diferencia];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Error al cambiar talla: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
