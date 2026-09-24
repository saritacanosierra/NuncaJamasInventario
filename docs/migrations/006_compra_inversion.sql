-- La compra de mercancía también es una inversión de tipo producto.

INSERT INTO categorias_gastos (nombre, descripcion)
SELECT 'Producto', 'Mercancía que entra al inventario'
WHERE NOT EXISTS (
    SELECT 1 FROM categorias_gastos WHERE nombre = 'Producto'
);

INSERT INTO inversiones (concepto, monto, categoria, fecha, descripcion, usuario_id)
SELECT CONCAT('Compra ', c.numero), c.total, 'Producto', c.fecha, c.proveedor, c.usuario_id
FROM compras c
WHERE c.total > 0
  AND NOT EXISTS (
      SELECT 1 FROM inversiones i
      WHERE i.concepto COLLATE utf8mb4_general_ci = CONCAT('Compra ', c.numero) COLLATE utf8mb4_general_ci
  );
