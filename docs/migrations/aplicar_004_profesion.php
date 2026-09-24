<?php
/**
 * Agrega el envío a la DIAN, la nota crédito y la tarifa por pieza.
 * Uso: php docs/migrations/aplicar_004_profesion.php
 */

require_once dirname(__DIR__, 2) . '/back/config/config.php';
require_once dirname(__DIR__, 2) . '/back/config/db.php';

$database = new Database();
$db = $database->getConnection();

$ya = $db->prepare('SELECT version FROM schema_migrations WHERE version = :version');
$ya->execute([':version' => '004_profesion']);
if ($ya->fetch()) {
    echo "004_profesion ya estaba aplicada\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/004_profesion.sql');
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

$nuevos = [
    'ventas_dian:decide',
    'ventas_nota:create',
    'informes:view',
    'informes_mes:view',
    'produccion_pago:view',
    'produccion_pago:edit',
];
$cajero = ['ventas_nota:create'];

$insertPermiso = $db->prepare('INSERT IGNORE INTO permissions (slug, label) VALUES (:slug, :label)');
foreach (permisos_catalogo() as $grupo) {
    foreach ($grupo['items'] as $item) {
        if (!in_array($item['slug'], $nuevos, true)) {
            continue;
        }
        $insertPermiso->execute([':slug' => $item['slug'], ':label' => $item['label']]);
    }
}

$buscarRol = $db->prepare('SELECT id FROM roles WHERE `key` = :key');
$insertEnlace = $db->prepare(
    'INSERT IGNORE INTO role_permissions (role_id, permission_id)
     SELECT :role_id, id FROM permissions WHERE slug = :slug'
);

$entregar = ['admin' => $nuevos, 'cajero' => $cajero];
foreach ($entregar as $key => $slugs) {
    $buscarRol->execute([':key' => $key]);
    $roleId = (int) $buscarRol->fetchColumn();
    if ($roleId < 1) {
        continue;
    }
    foreach ($slugs as $slug) {
        $insertEnlace->execute([':role_id' => $roleId, ':slug' => $slug]);
    }
}

$marca = $db->prepare('INSERT INTO schema_migrations (version) VALUES (:version)');
$marca->execute([':version' => '004_profesion']);
echo "004_profesion aplicada\n";
