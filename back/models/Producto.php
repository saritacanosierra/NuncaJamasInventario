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
                       precio_venta, categoria_id, stock, stock_minimo, estado, foto) 
                      VALUES 
                      (:codigo_barras, :nombre, :descripcion, :color, :talla, :precio_costo, 
                       :precio_venta, :categoria_id, :stock, :stock_minimo, :estado, :foto)";
            
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
        $stmt->bindParam(':foto', $data['foto']);
        
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
        
        return $stmt->fetch();
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
        
        return $stmt->fetch();
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
                         OR p.color LIKE :term2
                         OR p.talla LIKE :term3
                         OR c.nombre LIKE :term4)
                  ORDER BY p.fecha_creacion DESC
                  LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $like = '%' . $termino . '%';
        $stmt->bindParam(':term1', $like);
        $stmt->bindParam(':term2', $like);
        $stmt->bindParam(':term3', $like);
        $stmt->bindParam(':term4', $like);
        $stmt->execute();

        return $stmt->fetchAll();
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
        
        return $stmt->execute();
    }
    
    /**
     * Aumentar stock de un producto
     */
    public function aumentarStock($id, $cantidad) {
        $query = "UPDATE " . $this->table . " 
                  SET stock = stock + :cantidad,
                      estado = CASE 
                          WHEN stock > 0 AND estado = 'Agotado' THEN 'Disponible'
                          ELSE estado
                      END
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':cantidad', $cantidad);
        
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
}