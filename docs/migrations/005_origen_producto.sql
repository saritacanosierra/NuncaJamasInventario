-- Origen de la prenda: confeccionada en el taller o comprada a un proveedor.

ALTER TABLE productos
    ADD COLUMN origen VARCHAR(20) NOT NULL DEFAULT 'confeccionado' AFTER estado;

UPDATE productos p
INNER JOIN (
    SELECT DISTINCT producto_id FROM compras_detalle
) c ON c.producto_id = p.id
SET p.origen = 'comprado';
