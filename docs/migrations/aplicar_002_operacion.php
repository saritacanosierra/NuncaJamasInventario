<?php
/**
 * Crea compras, kardex, caja, cambios, fiado y factura electrónica.
 * Entrega los permisos nuevos al administrador y al cajero.
 * Uso: php docs/migrations/aplicar_002_operacion.php
 */

require_once dirname(__DIR__, 2) . '/back/config/config.php';
require_once dirname(__DIR__, 2) . '/back/config/db.php';

$database = new Database();
$db = $database->getConnection();

$db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(64) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$ya = $db->prepare('SELECT version FROM schema_migrations WHERE version = :version');
$ya->execute([':version' => '002_operacion']);
if ($ya->fetch()) {
    echo "002_operacion ya estaba aplicada\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/002_operacion.sql');
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
    'ventas_cambio:create',
    'ventas_dian:view',
    'ventas_dian:create',
    'ventas_resolucion:edit',
    'clientes_fiado:view',
    'clientes_fiado:create',
    'compras:view',
    'compras_registro:view',
    'compras_registro:create',
    'compras_kardex:view',
    'caja:view',
    'caja_cierre:view',
    'caja_cierre:decide',
];
$cajero = array_values(array_filter($nuevos, function ($slug) {
    return $slug !== 'ventas_resolucion:edit';
}));
$cajero = array_merge($cajero, ['clientes:view', 'clientes_lista:view', 'clientes_historial:view']);

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
$marca->execute([':version' => '002_operacion']);
echo "002_operacion aplicada\n";
