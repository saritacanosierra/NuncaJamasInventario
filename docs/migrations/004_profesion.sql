-- Envío a la DIAN, nota crédito, tarifas de pago por pieza.
-- Lo aplica docs/migrations/aplicar_004_profesion.php una sola vez.

ALTER TABLE resolucion_facturacion
    ADD COLUMN software_id VARCHAR(80) NOT NULL DEFAULT '',
    ADD COLUMN software_pin VARCHAR(80) NOT NULL DEFAULT '',
    ADD COLUMN set_pruebas VARCHAR(80) NOT NULL DEFAULT '';

ALTER TABLE facturas_dian
    ADD COLUMN respuesta_dian MEDIUMTEXT NULL,
    ADD COLUMN zip_key VARCHAR(80) NULL,
    ADD COLUMN enviado_en DATETIME NULL;

CREATE TABLE IF NOT EXISTS notas_credito (
    id INT NOT NULL AUTO_INCREMENT,
    factura_id INT NOT NULL,
    venta_id INT NOT NULL,
    numero VARCHAR(30) NOT NULL,
    cude CHAR(96) NOT NULL,
    xml MEDIUMTEXT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'generada',
    respuesta_dian MEDIUMTEXT NULL,
    zip_key VARCHAR(80) NULL,
    enviado_en DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nota_factura (factura_id),
    UNIQUE KEY uq_nota_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tarifas_operacion (
    id INT NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(40) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    valor_pieza INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tarifa_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
