-- Clave primaria y autoincremento en tablas que el volcado dejó sin ellos.
-- Sin esto, un alta nueva no puede obtener un id.
-- La base local ya tiene estos cambios. No volver a ejecutarlo ahí.
UPDATE registros_produccion SET id = 1 WHERE id = 0;
UPDATE operaciones_produccion SET id = 1, registro_id = 1 WHERE id = 0;

ALTER TABLE productos ADD PRIMARY KEY (id);
ALTER TABLE productos MODIFY id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE ventas ADD PRIMARY KEY (id);
ALTER TABLE ventas MODIFY id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE detalle_venta ADD PRIMARY KEY (id);
ALTER TABLE detalle_venta MODIFY id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE gastos ADD PRIMARY KEY (id);
ALTER TABLE gastos MODIFY id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE inversiones ADD PRIMARY KEY (id);
ALTER TABLE inversiones MODIFY id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE registros_produccion ADD PRIMARY KEY (id);
ALTER TABLE registros_produccion MODIFY id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE operaciones_produccion ADD PRIMARY KEY (id);
ALTER TABLE operaciones_produccion MODIFY id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE retrocesos_produccion ADD PRIMARY KEY (id);
ALTER TABLE retrocesos_produccion MODIFY id INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE cierres_dia_produccion ADD PRIMARY KEY (id);
ALTER TABLE cierres_dia_produccion MODIFY id INT(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS historial_operaciones_produccion (
    id INT(11) NOT NULL AUTO_INCREMENT,
    operacion_id INT(11) NOT NULL,
    tipo_cambio VARCHAR(50) NOT NULL,
    hora_inicio DATETIME NULL,
    hora_fin DATETIME NULL,
    tiempo_total_minutos DECIMAL(10,2) DEFAULT 0,
    piezas_producidas INT(11) DEFAULT 0,
    eficiencia DECIMAL(10,2) DEFAULT 0,
    observaciones TEXT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_historial_operacion (operacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE productos
    ADD CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id);
ALTER TABLE ventas
    ADD CONSTRAINT fk_ventas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id),
    ADD CONSTRAINT fk_ventas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id);
ALTER TABLE detalle_venta
    ADD CONSTRAINT fk_detalle_venta FOREIGN KEY (venta_id) REFERENCES ventas (id),
    ADD CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) REFERENCES productos (id);
ALTER TABLE gastos
    ADD CONSTRAINT fk_gastos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id);
ALTER TABLE inversiones
    ADD CONSTRAINT fk_inversiones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id);
ALTER TABLE tareas
    ADD CONSTRAINT fk_tareas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id);
ALTER TABLE registros_produccion
    ADD CONSTRAINT fk_registros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id);
ALTER TABLE operaciones_produccion
    ADD CONSTRAINT fk_operaciones_registro FOREIGN KEY (registro_id) REFERENCES registros_produccion (id);
ALTER TABLE retrocesos_produccion
    ADD CONSTRAINT fk_retrocesos_registro FOREIGN KEY (registro_id) REFERENCES registros_produccion (id),
    ADD CONSTRAINT fk_retrocesos_operacion FOREIGN KEY (operacion_id) REFERENCES operaciones_produccion (id);
ALTER TABLE cierres_dia_produccion
    ADD CONSTRAINT fk_cierres_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id);
ALTER TABLE recuperaciones_clave
    ADD CONSTRAINT fk_recuperaciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id);
ALTER TABLE historial_operaciones_produccion
    ADD CONSTRAINT fk_historial_operacion FOREIGN KEY (operacion_id) REFERENCES operaciones_produccion (id);
