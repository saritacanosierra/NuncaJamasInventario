<?php
/**
 * Modelo de Producto
 */

class Producto {
    private $conn;
    private $table = 'productos';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear producto
     * Retorna el ID del producto creado o false en caso de error
     */
    public function create($data) {
        try {
            if (empty($data['codigo_barras']) || empty($data['nombre']) || empty($data['categoria_id'])) {
                error_log("Producto::create() - Datos incompletos: codigo_barras=" . ($data['codigo_barras'] ?? 'vacío') . ", nombre=" . ($data['nombre'] ?? 'vacío') . ", categoria_id=" . ($data['categoria_id'] ?? 'vacío'));
                return false;
            }
            
            // Verificar duplicado por código de barras antes de insertar
            if ($this->codigoExiste($data['codigo_barras'])) {
                error_log("Producto::create() - Código de barras duplicado: " . $data['codigo_barras']);
                return false;
            }
            
            $query = "INSERT INTO " . $this->table . " 
                      (codigo_barras, nombre, descripcion, color, talla, precio_costo, 
                       precio_venta, categoria_id, stock, stock_minimo, estado, origen, foto) 
                      VALUES 
                      (:codigo_barras, :nombre, :descripcion, :color, :talla, :precio_costo, 
                       :precio_venta, :categoria_id, :stock, :stock_minimo, :estado, :origen, :foto)";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                $errorInfo = $this->conn->errorInfo();
                error_log("Producto::create() - Error al preparar query: " . print_r($errorInfo, true));
                return false;
            }
            
            $stmt->bindParam(':codigo_barras', $data['codigo_barras']);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':descripcion', $data['descripcion']);
            $stmt->bindParam(':color', $data['color']);
            $stmt->bindParam(':talla', $data['talla']);
            $stmt->bindParam(':precio_costo', $data['precio_costo']);
            $stmt->bindParam(':precio_venta', $data['precio_venta']);
            $stmt->bindParam(':categoria_id', $data['categoria_id']);
            $stmt->bindParam(':stock', $data['stock']);
            $stmt->bindParam(':stock_minimo', $data['stock_minimo']);
            $stmt->bindParam(':estado', $data['estado']);
            $stmt->bindParam(':origen', $data['origen']);
            $stmt->bindParam(':foto', $data['foto']);
            
            $resultado = $stmt->execute();
            
            if ($resultado) {
                $productoId = $this->conn->lastInsertId();
                if ($productoId && $productoId > 0) {
                    error_log("Producto::create() - Producto creado exitosamente con ID: " . $productoId);
                    return $productoId;
                } else {
                    error_log("Producto::create() - Producto insertado pero ID inválido: " . $productoId);
                    return false;
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Producto::create() - Error al ejecutar query: " . print_r($errorInfo, true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("Producto::create() - PDOException: " . $e->getMessage() . " | Código: " . $e->getCode());
            // Si es un error de duplicado (código 23000), retornar false específico
            if ($e->getCode() == 23000) {
                error_log("Producto::create() - Violación de restricción única (duplicado)");
            }
            return false;
        } catch (Exception $e) {
            error_log("Producto::create() - Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar producto
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                  nombre = :nombre,
                  descripcion = :descripcion,
                  color = :color,
                  talla = :talla,
                  precio_costo = :precio_costo,
                  precio_venta = :precio_venta,
                  categoria_id = :categoria_id,
                  stock = :stock,
                  stock_minimo = :stock_minimo,
                  estado = :estado,
                  origen = :origen,
                  foto = :foto
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':color', $data['color']);
        $stmt->bindParam(':talla', $data['talla']);
        $stmt->bindParam(':precio_costo', $data['precio_costo']);
        $stmt->bindParam(':precio_venta', $data['precio_venta']);
        $stmt->bindParam(':categoria_id', $data['categoria_id']);
        $stmt->bindParam(':stock', $data['stock']);
        $stmt->bindParam(':stock_minimo', $data['stock_minimo']);
        $stmt->bindParam(':estado', $data['estado']);
        $stmt->bindParam(':origen', $data['origen']);
        $stmt->bindParam(':foto', $data['foto']);
        
        return $stmt->execute();
    }

    public function marcarComprado($id) {
        $query = "UPDATE " . $this->table . " SET origen = 'comprado' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * Eliminar producto
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Obtener producto por ID
     */
    public function getById($id) {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE p.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $producto = $stmt->fetch();
        if ($producto) {
            $producto['tallas'] = $this->getTallas((int) $producto['id']);
        }
        return $producto;
    }
    
    /**
     * Obtener producto por código de barras
     */
    public function getByCodigoBarras($codigo) {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE p.codigo_barras = :codigo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->execute();

        $producto = $stmt->fetch();
        if ($producto) {
            $producto['tallas'] = $this->getTallas((int) $producto['id']);
        }
        return $producto;
    }
    
    /**
     * Listar todos los productos con filtros
     */
    public function getAll($filters = []) {
        $query = "SELECT p.*, c.nombre as categoria_nombre,
                         (p.precio_venta - p.precio_costo) as margen_ganancia
                  FROM " . $this->table . " p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['busqueda'])) {
            $query .= " AND (p.nombre LIKE :busqueda1 OR p.color LIKE :busqueda2 
                           OR p.talla LIKE :busqueda3 OR c.nombre LIKE :busqueda4)";
            $likeValue = '%' . $filters['busqueda'] . '%';
            $params[':busqueda1'] = $likeValue;
            $params[':busqueda2'] = $likeValue;
            $params[':busqueda3'] = $likeValue;
            $params[':busqueda4'] = $likeValue;
        }
        
        if (!empty($filters['categoria_id'])) {
            $query .= " AND p.categoria_id = :categoria_id";
            $params[':categoria_id'] = $filters['categoria_id'];
        }
        
        if (!empty($filters['estado'])) {
            $query .= " AND p.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }

        if (!empty($filters['origen']) && in_array($filters['origen'], ['confeccionado', 'comprado'], true)) {
            $query .= " AND p.origen = :origen";
            $params[':origen'] = $filters['origen'];
        }
        
        $query .= " ORDER BY p.fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Buscar productos por nombre (uso específico para el POS / ventas)
     * Solo productos disponibles y con stock > 0
     */
    public function buscarParaVentas($termino) {
        $query = "SELECT p.*, c.nombre as categoria_nombre,
                         (p.precio_venta - p.precio_costo) as margen_ganancia
                  FROM " . $this->table . " p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE p.estado = 'Disponible'
                    AND p.stock > 0
                    AND (p.nombre LIKE :term1
                         OR p.codigo_barras LIKE :term2
                         OR p.color LIKE :term3
                         OR p.talla LIKE :term4
                         OR c.nombre LIKE :term5)
                  ORDER BY p.fecha_creacion DESC
                  LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $like = '%' . $termino . '%';
        $stmt->bindValue(':term1', $like);
        $stmt->bindValue(':term2', $like);
        $stmt->bindValue(':term3', $like);
        $stmt->bindValue(':term4', $like);
        $stmt->bindValue(':term5', $like);
        $stmt->execute();

        return $this->conTallas($stmt->fetchAll());
    }

    /**
     * Busca prendas para una compra, también si están agotadas.
     */
    public function buscarParaCompra($termino) {
        $query = "SELECT p.id, p.nombre, p.talla, p.color, p.codigo_barras, p.stock, p.precio_costo
                  FROM " . $this->table . " p
                  WHERE p.nombre LIKE :term1
                     OR p.codigo_barras LIKE :term2
                     OR p.talla LIKE :term3
                     OR p.color LIKE :term4
                  ORDER BY p.nombre ASC
                  LIMIT 10";
        $stmt = $this->conn->prepare($query);
        $like = '%' . $termino . '%';
        $stmt->bindValue(':term1', $like);
        $stmt->bindValue(':term2', $like);
        $stmt->bindValue(':term3', $like);
        $stmt->bindValue(':term4', $like);
        $stmt->execute();
        return $this->conTallas($stmt->fetchAll());
    }
    
    /**
     * Reducir stock
     */
    public function reducirStock($id, $cantidad) {
        $query = "UPDATE " . $this->table . " 
                  SET stock = stock - :cantidad1,
                      estado = CASE 
                          WHEN (stock - :cantidad2) <= 0 THEN 'Agotado'
                          WHEN (stock - :cantidad3) <= stock_minimo THEN estado
                          ELSE estado
                      END
                  WHERE id = :id AND stock >= :cantidad4";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':cantidad1', $cantidad);
        $stmt->bindParam(':cantidad2', $cantidad);
        $stmt->bindParam(':cantidad3', $cantidad);
        $stmt->bindParam(':cantidad4', $cantidad);
        $stmt->execute();

        if ($stmt->rowCount() < 1) {
            throw new Exception('No hay stock suficiente para completar la venta');
        }

        return true;
    }
    
    /**
     * Aumentar stock de un producto
     */
    public function aumentarStock($id, $cantidad) {
        $query = "UPDATE " . $this->table . " 
                  SET stock = stock + :cantidad1,
                      estado = CASE 
                          WHEN (stock + :cantidad2) > 0 AND estado = 'Agotado' THEN 'Disponible'
                          ELSE estado
                      END
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':cantidad1', $cantidad);
        $stmt->bindParam(':cantidad2', $cantidad);
        
        return $stmt->execute();
    }
    
    /**
     * Obtener productos con stock bajo
     */
    public function getStockBajo() {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                      FROM " . $this->table . " p
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE p.stock <= p.stock_minimo AND p.estado != 'Vendido'
                      ORDER BY p.stock ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $resultado = $stmt->fetchAll();
            return $resultado ?: [];
        } catch (Exception $e) {
            error_log("Error en getStockBajo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar si código de barras existe
     */
    public function codigoExiste($codigo, $excludeId = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE codigo_barras = :codigo";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }
    
    /**
     * Obtener productos más vendidos
     */
    public function getMasVendidos($limite = 10) {
        try {
            $query = "SELECT 
                        p.id,
                        p.nombre,
                        p.precio_venta,
                        p.stock,
                        c.nombre as categoria_nombre,
                        COALESCE(SUM(dv.cantidad), 0) as total_vendido
                      FROM " . $this->table . " p
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      LEFT JOIN detalle_venta dv ON p.id = dv.producto_id
                      GROUP BY p.id, p.nombre, p.precio_venta, p.stock, c.nombre
                      HAVING total_vendido > 0
                      ORDER BY total_vendido DESC
                      LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            
            $resultado = $stmt->fetchAll();
            return $resultado ?: [];
        } catch (Exception $e) {
            error_log("Error en getMasVendidos: " . $e->getMessage());
            return [];
        }
    }

    public function getTallas($productoId) {
        $stmt = $this->conn->prepare(
            'SELECT id, producto_id, talla, stock
             FROM producto_tallas
             WHERE producto_id = :id
             ORDER BY talla'
        );
        $stmt->bindValue(':id', (int) $productoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function conTallas(array $productos) {
        if ($productos === []) {
            return $productos;
        }
        $ids = [];
        foreach ($productos as $producto) {
            $ids[] = (int) $producto['id'];
        }
        $ids = array_values(array_unique($ids));
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare(
            "SELECT id, producto_id, talla, stock
             FROM producto_tallas
             WHERE producto_id IN ($marcas)
             ORDER BY talla"
        );
        $stmt->execute($ids);
        $porProducto = [];
        foreach ($stmt->fetchAll() as $fila) {
            $porProducto[(int) $fila['producto_id']][] = $fila;
        }
        foreach ($productos as &$producto) {
            $producto['tallas'] = $porProducto[(int) $producto['id']] ?? [];
        }
        unset($producto);
        return $productos;
    }

    public function guardarTallas($productoId, array $filas) {
        $productoId = (int) $productoId;
        $conservar = [];
        $actualizar = $this->conn->prepare(
            'UPDATE producto_tallas SET talla = :talla, stock = :stock WHERE id = :id AND producto_id = :producto_id'
        );
        $buscar = $this->conn->prepare(
            'SELECT id FROM producto_tallas WHERE producto_id = :producto_id AND talla = :talla'
        );
        $insertar = $this->conn->prepare(
            'INSERT INTO producto_tallas (producto_id, talla, stock) VALUES (:producto_id, :talla, :stock)'
        );

        foreach ($filas as $fila) {
            $talla = trim((string) $fila['talla']);
            $stock = max(0, (int) $fila['stock']);
            $id = (int) ($fila['id'] ?? 0);
            if ($id > 0) {
                $actualizar->execute([
                    ':talla' => $talla,
                    ':stock' => $stock,
                    ':id' => $id,
                    ':producto_id' => $productoId,
                ]);
                $conservar[] = $id;
                continue;
            }
            $buscar->execute([':producto_id' => $productoId, ':talla' => $talla]);
            $existente = (int) $buscar->fetchColumn();
            if ($existente > 0) {
                $actualizar->execute([
                    ':talla' => $talla,
                    ':stock' => $stock,
                    ':id' => $existente,
                    ':producto_id' => $productoId,
                ]);
                $conservar[] = $existente;
                continue;
            }
            $insertar->execute([
                ':producto_id' => $productoId,
                ':talla' => $talla,
                ':stock' => $stock,
            ]);
            $conservar[] = (int) $this->conn->lastInsertId();
        }

        $usadas = $this->conn->prepare('SELECT COUNT(*) FROM detalle_venta WHERE talla_id = :id');
        $borrar = $this->conn->prepare('DELETE FROM producto_tallas WHERE id = :id AND producto_id = :producto_id');
        foreach ($this->getTallas($productoId) as $actual) {
            if (in_array((int) $actual['id'], $conservar, true)) {
                continue;
            }
            $usadas->execute([':id' => (int) $actual['id']]);
            if ((int) $usadas->fetchColumn() > 0) {
                continue;
            }
            $borrar->execute([':id' => (int) $actual['id'], ':producto_id' => $productoId]);
        }

        $this->sincronizarTallas($productoId);
    }

    public function asegurarStock(array $detalles) {
        $grupos = [];
        foreach ($detalles as $detalle) {
            $productoId = (int) ($detalle['producto_id'] ?? 0);
            $tallaId = $this->resolverTalla($productoId, (int) ($detalle['talla_id'] ?? 0));
            $cantidad = (int) ($detalle['cantidad'] ?? 0);
            if ($productoId < 1 || $cantidad < 1) {
                throw new Exception('Hay una prenda sin cantidad en la venta.');
            }
            $clave = $productoId . ':' . $tallaId;
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'producto_id' => $productoId,
                    'talla_id' => $tallaId,
                    'cantidad' => 0,
                ];
            }
            $grupos[$clave]['cantidad'] += $cantidad;
        }
        usort($grupos, function ($a, $b) {
            if ($a['producto_id'] === $b['producto_id']) {
                return $a['talla_id'] <=> $b['talla_id'];
            }
            return $a['producto_id'] <=> $b['producto_id'];
        });
        foreach ($grupos as $grupo) {
            $this->leerStockBloqueado($grupo['producto_id'], $grupo['talla_id'], $grupo['cantidad']);
        }
    }

    public function bajarStock($productoId, $tallaId, $cantidad) {
        $productoId = (int) $productoId;
        $cantidad = (int) $cantidad;
        $tallaId = $this->resolverTalla($productoId, (int) $tallaId);
        $this->leerStockBloqueado($productoId, $tallaId, $cantidad);
        if ($tallaId > 0) {
            $stmt = $this->conn->prepare(
                'UPDATE producto_tallas
                 SET stock = stock - :cantidad
                 WHERE id = :id AND producto_id = :producto_id AND stock >= :cantidad2'
            );
            $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindValue(':cantidad2', $cantidad, PDO::PARAM_INT);
            $stmt->bindValue(':id', $tallaId, PDO::PARAM_INT);
            $stmt->bindValue(':producto_id', $productoId, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() < 1) {
                throw new Exception($this->mensajeSinStock($this->nombreProducto($productoId), '', 0));
            }
            $this->sincronizarTallas($productoId);
            return $tallaId;
        }
        $this->reducirStock($productoId, $cantidad);
        return 0;
    }

    public function subirStock($productoId, $tallaId, $cantidad) {
        $tallaId = $this->resolverTalla((int) $productoId, (int) $tallaId);
        if ($tallaId > 0) {
            $stmt = $this->conn->prepare(
                'UPDATE producto_tallas SET stock = stock + :cantidad WHERE id = :id AND producto_id = :producto_id'
            );
            $stmt->bindValue(':cantidad', (int) $cantidad, PDO::PARAM_INT);
            $stmt->bindValue(':id', $tallaId, PDO::PARAM_INT);
            $stmt->bindValue(':producto_id', (int) $productoId, PDO::PARAM_INT);
            $stmt->execute();
            $this->sincronizarTallas((int) $productoId);
            return $tallaId;
        }
        $this->aumentarStock((int) $productoId, (int) $cantidad);
        return 0;
    }

    private function leerStockBloqueado($productoId, $tallaId, $cantidad) {
        if ($tallaId > 0) {
            $stmt = $this->conn->prepare(
                'SELECT pt.stock, pt.talla, p.nombre
                 FROM producto_tallas pt
                 INNER JOIN productos p ON p.id = pt.producto_id
                 WHERE pt.id = :id AND pt.producto_id = :producto_id
                 FOR UPDATE'
            );
            $stmt->execute([
                ':id' => $tallaId,
                ':producto_id' => $productoId,
            ]);
            $fila = $stmt->fetch();
            $queda = $fila ? (int) $fila['stock'] : 0;
            if (!$fila || $queda < $cantidad) {
                $nombre = $fila ? (string) $fila['nombre'] : $this->nombreProducto($productoId);
                $talla = $fila ? (string) $fila['talla'] : '';
                throw new Exception($this->mensajeSinStock($nombre, $talla, $queda));
            }
            return;
        }
        $stmt = $this->conn->prepare('SELECT nombre, stock FROM productos WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $productoId]);
        $fila = $stmt->fetch();
        $queda = $fila ? (int) $fila['stock'] : 0;
        if (!$fila || $queda < $cantidad) {
            $nombre = $fila ? (string) $fila['nombre'] : 'esa prenda';
            throw new Exception($this->mensajeSinStock($nombre, '', $queda));
        }
    }

    private function nombreProducto($productoId) {
        $stmt = $this->conn->prepare('SELECT nombre FROM productos WHERE id = :id');
        $stmt->execute([':id' => $productoId]);
        $nombre = $stmt->fetchColumn();
        return $nombre ? (string) $nombre : 'esa prenda';
    }

    private function mensajeSinStock($nombre, $talla, $queda) {
        $pieza = $nombre;
        if ($talla !== '') {
            $pieza .= ' talla ' . $talla;
        }
        if ((int) $queda <= 0) {
            return 'Ya no queda ' . $pieza . '. La otra caja lo facturó primero.';
        }
        return 'Solo queda ' . (int) $queda . ' de ' . $pieza . '. No alcanza para esta venta.';
    }

    private function resolverTalla($productoId, $tallaId) {
        if ($tallaId > 0) {
            return $tallaId;
        }
        $tallas = $this->getTallas($productoId);
        if (count($tallas) === 1) {
            return (int) $tallas[0]['id'];
        }
        return 0;
    }

    private function sincronizarTallas($productoId) {
        $tallas = $this->getTallas($productoId);
        $stock = 0;
        $nombres = [];
        foreach ($tallas as $talla) {
            $stock += (int) $talla['stock'];
            $nombres[] = $talla['talla'];
        }
        $texto = implode(', ', $nombres);
        $estado = $stock > 0 ? 'Disponible' : 'Agotado';
        $stmt = $this->conn->prepare(
            'UPDATE productos SET stock = :stock, talla = :talla, estado = :estado WHERE id = :id'
        );
        $stmt->execute([
            ':stock' => $stock,
            ':talla' => $texto,
            ':estado' => $estado,
            ':id' => (int) $productoId,
        ]);
    }
}