-- Compras, kardex, cierre de caja, cambios de talla, fiado y factura electrónica local.
-- Lo aplica docs/migrations/aplicar_002_operacion.php una sola vez.

CREATE TABLE IF NOT EXISTS movimientos_kardex (
    id INT NOT NULL AUTO_INCREMENT,
    producto_id INT NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    referencia_tipo VARCHAR(30) NOT NULL,
    referencia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nota VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_kardex_producto (producto_id),
    KEY idx_kardex_referencia (referencia_tipo, referencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS compras (
    id INT NOT NULL AUTO_INCREMENT,
    numero VARCHAR(30) NOT NULL,
    proveedor VARCHAR(200) NOT NULL,
    documento VARCHAR(50) NULL,
    fecha DATE NOT NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    sale_de_caja TINYINT NOT NULL DEFAULT 0,
    usuario_id INT NOT NULL,
    observaciones TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_compras_numero (numero),
    KEY idx_compras_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS compras_detalle (
    id INT NOT NULL AUTO_INCREMENT,
    compra_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_compras_detalle_compra (compra_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cierres_caja (
    id INT NOT NULL AUTO_INCREMENT,
    fecha DATE NOT NULL,
    usuario_id INT NOT NULL,
    base DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ventas_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ventas_otros DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ventas_fiado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ventas_contra_entrega DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    gastos DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    compras_caja DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    esperado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    contado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    diferencia DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    observacion TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cierre_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cambios_talla (
    id INT NOT NULL AUTO_INCREMENT,
    venta_id INT NOT NULL,
    producto_sale_id INT NOT NULL,
    producto_entra_id INT NOT NULL,
    cantidad INT NOT NULL,
    diferencia DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    usuario_id INT NOT NULL,
    observacion VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cambios_venta (venta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS abonos (
    id INT NOT NULL AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    venta_id INT NULL,
    monto DECIMAL(12,2) NOT NULL,
    usuario_id INT NOT NULL,
    nota VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_abonos_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resolucion_facturacion (
    id INT NOT NULL AUTO_INCREMENT,
    numero VARCHAR(40) NOT NULL,
    prefijo VARCHAR(10) NOT NULL,
    desde_numero INT NOT NULL,
    hasta_numero INT NOT NULL,
    fecha_desde DATE NOT NULL,
    fecha_hasta DATE NOT NULL,
    clave_tecnica VARCHAR(255) NOT NULL DEFAULT '',
    nit VARCHAR(20) NOT NULL,
    razon_social VARCHAR(200) NOT NULL,
    ambiente TINYINT NOT NULL DEFAULT 2,
    vigente TINYINT NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS facturas_dian (
    id INT NOT NULL AUTO_INCREMENT,
    venta_id INT NOT NULL,
    resolucion_id INT NOT NULL,
    prefijo VARCHAR(10) NOT NULL,
    consecutivo INT NOT NULL,
    numero VARCHAR(30) NOT NULL,
    cufe CHAR(96) NOT NULL,
    xml MEDIUMTEXT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'generada',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_factura_dian_venta (venta_id),
    UNIQUE KEY uq_factura_dian_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
