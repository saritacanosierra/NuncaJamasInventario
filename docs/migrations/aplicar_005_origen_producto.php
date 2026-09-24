<?php
/**
 * Marca en el inventario si la prenda es confeccionada o comprada.
 * Uso: php docs/migrations/aplicar_005_origen_producto.php
 */

require_once dirname(__DIR__, 2) . '/back/config/config.php';
require_once dirname(__DIR__, 2) . '/back/config/db.php';

$database = new Database();
$db = $database->getConnection();

$ya = $db->prepare('SELECT version FROM schema_migrations WHERE version = :version');
$ya->execute([':version' => '005_origen_producto']);
if ($ya->fetch()) {
    echo "005_origen_producto ya estaba aplicada\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/005_origen_producto.sql');
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

$marca = $db->prepare('INSERT INTO schema_migrations (version) VALUES (:version)');
$marca->execute([':version' => '005_origen_producto']);
echo "005_origen_producto aplicada\n";
