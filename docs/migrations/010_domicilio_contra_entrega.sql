ALTER TABLE ventas
    ADD COLUMN domicilio_contra_entrega TINYINT(1) NOT NULL DEFAULT 0 AFTER pago_contra_entrega;
