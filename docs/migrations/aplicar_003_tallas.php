<?php
/**
 * Crea las tallas de cada producto y copia el stock que ya tenían.
 * Uso: php docs/migrations/aplicar_003_tallas.php
 */

require_once dirname(__DIR__, 2) . '/back/config/config.php';
require_once dirname(__DIR__, 2) . '/back/config/db.php';

$database = new Database();
$db = $database->getConnection();

$ya = $db->prepare('SELECT version FROM schema_migrations WHERE version = :version');
$ya->execute([':version' => '003_tallas']);
if ($ya->fetch()) {
    echo "003_tallas ya estaba aplicada\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/003_tallas.sql');
foreach (array_filter(array_map('trim', explode(';', $sql))) as $sentencia) {
    $lineas = [];
    foreach (explode("\n", $sentencia) as $linea) {
        if (!str_starts_with(trim($linea), '--')) {
            $lineas[] = $linea;
        }
    }
    $sentencia = trim(implode("\n", $lineas));
    if ($sentencia === '') {
        continue;
    }
    $db->exec($sentencia);
}

$db->exec(
    'INSERT INTO producto_tallas (producto_id, talla, stock)
     SELECT p.id, IFNULL(NULLIF(p.talla, ""), "0"), p.stock
     FROM productos p
     WHERE NOT EXISTS (
         SELECT 1 FROM producto_tallas t WHERE t.producto_id = p.id
     )'
);

$columna = $db->query("SHOW COLUMNS FROM detalle_venta LIKE 'talla_id'")->fetch();
if (!$columna) {
    $db->exec('ALTER TABLE detalle_venta ADD COLUMN talla_id INT NULL');
}
$columnaKardex = $db->query("SHOW COLUMNS FROM movimientos_kardex LIKE 'talla_id'")->fetch();
if (!$columnaKardex) {
    $db->exec('ALTER TABLE movimientos_kardex ADD COLUMN talla_id INT NULL');
}

$db->exec(
    'UPDATE detalle_venta d
     INNER JOIN producto_tallas t ON t.producto_id = d.producto_id
     SET d.talla_id = t.id
     WHERE d.talla_id IS NULL'
);

$marca = $db->prepare('INSERT INTO schema_migrations (version) VALUES (:version)');
$marca->execute([':version' => '003_tallas']);
echo "003_tallas aplicada\n";
