-- Una prenda, varias tallas. Cada talla tiene su stock.
-- Lo aplica docs/migrations/aplicar_003_tallas.php una sola vez.

CREATE TABLE IF NOT EXISTS producto_tallas (
    id INT NOT NULL AUTO_INCREMENT,
    producto_id INT NOT NULL,
    talla VARCHAR(20) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_producto_talla (producto_id, talla),
    KEY idx_producto_tallas_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
