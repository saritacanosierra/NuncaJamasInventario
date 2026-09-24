<?php
/**
 * Movimientos de inventario. Se anota después de cambiar el stock.
 */

class Kardex {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function anotar($productoId, $tipo, $delta, $referenciaTipo, $referenciaId, $usuarioId, $nota = '', $tallaId = null) {
        if ((int) $tallaId > 0) {
            $stmt = $this->conn->prepare('SELECT stock FROM producto_tallas WHERE id = :id');
            $stmt->bindValue(':id', (int) $tallaId, PDO::PARAM_INT);
        } else {
            $stmt = $this->conn->prepare('SELECT stock FROM productos WHERE id = :id');
            $stmt->bindValue(':id', (int) $productoId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $stockNuevo = $stmt->fetchColumn();
        if ($stockNuevo === false) {
            throw new Exception('El producto del movimiento no existe');
        }

        $stockNuevo = (int) $stockNuevo;
        $delta = (int) $delta;
        $stockAnterior = $stockNuevo - $delta;
        $nota = mb_substr((string) $nota, 0, 255);

        $insert = $this->conn->prepare(
            'INSERT INTO movimientos_kardex
             (producto_id, talla_id, tipo, cantidad, stock_anterior, stock_nuevo, referencia_tipo, referencia_id, usuario_id, nota)
             VALUES
             (:producto_id, :talla_id, :tipo, :cantidad, :stock_anterior, :stock_nuevo, :referencia_tipo, :referencia_id, :usuario_id, :nota)'
        );
        if ((int) $tallaId > 0) {
            $insert->bindValue(':talla_id', (int) $tallaId, PDO::PARAM_INT);
        } else {
            $insert->bindValue(':talla_id', null, PDO::PARAM_NULL);
        }
        $insert->bindValue(':producto_id', (int) $productoId, PDO::PARAM_INT);
        $insert->bindValue(':tipo', $tipo);
        $insert->bindValue(':cantidad', $delta, PDO::PARAM_INT);
        $insert->bindValue(':stock_anterior', $stockAnterior, PDO::PARAM_INT);
        $insert->bindValue(':stock_nuevo', $stockNuevo, PDO::PARAM_INT);
        $insert->bindValue(':referencia_tipo', $referenciaTipo);
        $insert->bindValue(':referencia_id', (int) $referenciaId, PDO::PARAM_INT);
        $insert->bindValue(':usuario_id', (int) $usuarioId, PDO::PARAM_INT);
        $insert->bindValue(':nota', $nota);
        $insert->execute();
    }

    public function listar($productoId = 0, $limite = 200) {
        $query = 'SELECT k.*, p.nombre AS producto_nombre, COALESCE(pt.talla, p.talla) AS talla, p.color, p.codigo_barras, u.nombre AS usuario_nombre
                  FROM movimientos_kardex k
                  INNER JOIN productos p ON p.id = k.producto_id
                  LEFT JOIN producto_tallas pt ON pt.id = k.talla_id
                  LEFT JOIN usuarios u ON u.id = k.usuario_id';
        if ($productoId > 0) {
            $query .= ' WHERE k.producto_id = :producto_id';
        }
        $query .= ' ORDER BY k.id DESC LIMIT ' . (int) $limite;

        $stmt = $this->conn->prepare($query);
        if ($productoId > 0) {
            $stmt->bindValue(':producto_id', (int) $productoId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
