<?php
/**
 * Permite guardar una venta a fiado.
 * Uso: php docs/migrations/aplicar_009_metodo_fiado.php
 */

require_once dirname(__DIR__, 2) . '/back/config/config.php';
require_once dirname(__DIR__, 2) . '/back/config/db.php';

$database = new Database();
$db = $database->getConnection();

$ya = $db->prepare('SELECT version FROM schema_migrations WHERE version = :version');
$ya->execute([':version' => '009_metodo_fiado']);
if ($ya->fetch()) {
    echo "009_metodo_fiado ya estaba aplicada\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/009_metodo_fiado.sql');
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
$marca->execute([':version' => '009_metodo_fiado']);
echo "009_metodo_fiado aplicada\n";
