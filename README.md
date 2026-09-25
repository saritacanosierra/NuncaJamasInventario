# Nunca Jamás

Inventario, caja y producción. Entra por `index.php`.

En local se abre en http://localhost:8080/inventario/ con la base `inventario_ropa_infantil`.

## Subir a Imagina Colombia

1. En cPanel elige PHP 8.2 o 8.3.
2. Crea la base MySQL y un usuario con todos los privilegios sobre esa base.
3. En phpMyAdmin importa la base de XAMPP con sus datos. `docs/schema.sql` solo crea las tablas vacías, sin usuarios.
4. Sube esta carpeta a `public_html`. No subas `.git` ni `.cursor`.
5. Copia `back/config/db.local.example.php` como `back/config/db.local.php` y escribe el usuario de cPanel.
6. Activa el SSL del dominio.

La recuperación de contraseña usa `back/config/mail.local.php`. La factura no se envía a la DIAN.
