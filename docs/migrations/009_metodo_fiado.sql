ALTER TABLE ventas
    MODIFY metodo_pago ENUM('Efectivo','Tarjeta','Transferencia','Mixto','Fiado') NOT NULL DEFAULT 'Efectivo';
