ALTER TABLE ventas
    ADD COLUMN pago_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER metodo_pago,
    ADD COLUMN pago_transferencia DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER pago_efectivo,
    ADD COLUMN pago_tarjeta DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER pago_transferencia,
    ADD COLUMN recibido DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER pago_tarjeta,
    ADD COLUMN devuelta DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER recibido;

UPDATE ventas
SET pago_efectivo = total, recibido = total
WHERE metodo_pago = 'Efectivo' AND pago_contra_entrega = 0;

UPDATE ventas
SET pago_tarjeta = total
WHERE metodo_pago = 'Tarjeta' AND pago_contra_entrega = 0;

UPDATE ventas
SET pago_transferencia = total
WHERE metodo_pago IN ('Transferencia', 'Mixto') AND pago_contra_entrega = 0;
