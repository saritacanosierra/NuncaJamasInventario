<?php
/**
 * Pasa las compras de mercancía ya guardadas a inversiones de tipo producto.
 * Uso: php docs/migrations/aplicar_006_compra_inversion.php
 */

require_once dirname(__DIR__, 2) . '/back/config/config.php';
require_once dirname(__DIR__, 2) . '/back/config/db.php';

$database = new Database();
$db = $database->getConnection();

$ya = $db->prepare('SELECT version FROM schema_migrations WHERE version = :version');
$ya->execute([':version' => '006_compra_inversion']);
if ($ya->fetch()) {
    echo "006_compra_inversion ya estaba aplicada\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/006_compra_inversion.sql');
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
$marca->execute([':version' => '006_compra_inversion']);
echo "006_compra_inversion aplicada\n";
