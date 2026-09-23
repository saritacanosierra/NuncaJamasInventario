<?php
/**
 * Aplica 001_permisos una sola vez y siembra roles desde el catálogo.
 * Uso: php docs/migrations/aplicar_001_permisos.php
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
$ya->execute([':version' => '001_permisos']);
if ($ya->fetch()) {
    echo "001_permisos ya estaba aplicada\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/001_permisos.sql');
foreach (array_filter(array_map('trim', explode(';', $sql))) as $sentencia) {
    if ($sentencia === '' || str_starts_with($sentencia, '--')) {
        continue;
    }
    $db->exec($sentencia);
}

$columnas = $db->query('SHOW COLUMNS FROM usuarios')->fetchAll();
$nombres = array_column($columnas, 'Field');
if (!in_array('role_id', $nombres, true)) {
    $db->exec('ALTER TABLE usuarios ADD COLUMN role_id INT NULL');
}
$rolCol = null;
foreach ($columnas as $columna) {
    if ($columna['Field'] === 'rol') {
        $rolCol = $columna;
    }
}
if ($rolCol && stripos($rolCol['Type'], 'enum') !== false) {
    $db->exec('ALTER TABLE usuarios MODIFY rol VARCHAR(50) NULL');
}

$insertPermiso = $db->prepare('INSERT IGNORE INTO permissions (slug, label) VALUES (:slug, :label)');
foreach (permisos_catalogo() as $grupo) {
    foreach ($grupo['items'] as $item) {
        if (!permisos_slug_valido($item['slug'])) {
            throw new RuntimeException('Slug inválido en el catálogo: ' . $item['slug']);
        }
        $insertPermiso->execute([
            ':slug' => $item['slug'],
            ':label' => $item['label'],
        ]);
    }
}

$insertRol = $db->prepare('INSERT IGNORE INTO roles (`key`, name) VALUES (:key, :name)');
$buscarRol = $db->prepare('SELECT id FROM roles WHERE `key` = :key');
$contarEnlaces = $db->prepare('SELECT COUNT(*) FROM role_permissions WHERE role_id = :role_id');
$insertEnlace = $db->prepare(
    'INSERT INTO role_permissions (role_id, permission_id)
     SELECT :role_id, id FROM permissions WHERE slug = :slug'
);
foreach (permisos_semilla_roles() as $key => $rol) {
    foreach ($rol['slugs'] as $slug) {
        if (!permisos_slug_valido($slug)) {
            throw new RuntimeException('Slug de semilla inválido: ' . $slug);
        }
    }
    $insertRol->execute([':key' => $key, ':name' => $rol['name']]);
    $buscarRol->execute([':key' => $key]);
    $roleId = (int) $buscarRol->fetchColumn();
    $contarEnlaces->execute([':role_id' => $roleId]);
    if ((int) $contarEnlaces->fetchColumn() > 0) {
        continue;
    }
    foreach ($rol['slugs'] as $slug) {
        $insertEnlace->execute([':role_id' => $roleId, ':slug' => $slug]);
    }
}

$db->exec(
    'UPDATE usuarios u
     INNER JOIN roles r ON r.`key` COLLATE utf8mb4_general_ci = u.rol
     SET u.role_id = r.id
     WHERE u.role_id IS NULL AND u.rol IS NOT NULL AND u.rol <> ""'
);

$fkUsuario = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_permission_overrides' AND CONSTRAINT_NAME = 'fk_user_overrides_user'")->fetch();
if (!$fkUsuario) {
    $db->exec('ALTER TABLE user_permission_overrides ADD CONSTRAINT fk_user_overrides_user FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE CASCADE');
}
$fkRol = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND CONSTRAINT_NAME = 'fk_usuarios_role'")->fetch();
if (!$fkRol) {
    $db->exec('ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_role FOREIGN KEY (role_id) REFERENCES roles (id)');
}

$marca = $db->prepare('INSERT INTO schema_migrations (version) VALUES (:version)');
$marca->execute([':version' => '001_permisos']);

$cuenta = (int) $db->query('SELECT COUNT(*) FROM permissions')->fetchColumn();
$sinRol = (int) $db->query('SELECT COUNT(*) FROM usuarios WHERE role_id IS NULL')->fetchColumn();
echo "Permisos sembrados: {$cuenta}. Usuarios sin rol: {$sinRol}\n";
