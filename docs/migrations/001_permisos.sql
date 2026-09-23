-- Permisos híbridos: rol + excepciones por usuario.
-- La app local ya puede tener estas tablas. No vuelvas a ejecutar el seed a mano
-- si schema_migrations tiene la versión 001_permisos.
-- El script docs/migrations/aplicar_001_permisos.php aplica este archivo una sola vez
-- y carga los slugs desde back/config/permisos_catalogo.php.

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(64) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
    id INT NOT NULL AUTO_INCREMENT,
    slug VARCHAR(120) NOT NULL,
    label VARCHAR(160) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
    id INT NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(50) NOT NULL,
    name VARCHAR(80) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_permission_overrides (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    value TINYINT NOT NULL,
    PRIMARY KEY (user_id, permission_id),
    CONSTRAINT fk_user_overrides_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
