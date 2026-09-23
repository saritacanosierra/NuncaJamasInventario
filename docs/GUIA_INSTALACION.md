# 📖 Guía de Instalación Detallada
## Sistema de Inventario - Ropa Infantil

Esta guía te ayudará a instalar y configurar el sistema paso a paso.

---

## 📋 Tabla de Contenidos

1. [Requisitos del Sistema](#requisitos-del-sistema)
2. [Instalación en XAMPP (Windows)](#instalación-en-xampp-windows)
3. [Instalación en Linux/Mac](#instalación-en-linuxmac)
4. [Configuración de la Base de Datos](#configuración-de-la-base-de-datos)
5. [Configuración de la Aplicación](#configuración-de-la-aplicación)
6. [Verificación de la Instalación](#verificación-de-la-instalación)
7. [Solución de Problemas](#solución-de-problemas)
8. [Configuración para Producción](#configuración-para-producción)

---

## 📦 Requisitos del Sistema

### Requisitos Mínimos

- **PHP:** 7.4 o superior
- **MySQL:** 5.7 o superior (o MariaDB 10.2+)
- **Apache:** 2.4 o superior
- **Extensiones PHP requeridas:**
  - PDO
  - PDO_MySQL
  - GD (para redimensionar imágenes)
  - mbstring
  - session
  - json

### Software Recomendado

- **XAMPP** (Windows/Mac/Linux) - Incluye PHP, MySQL y Apache
- **WAMP** (Windows)
- **MAMP** (Mac)
- **LAMP** (Linux)

---

## 🪟 Instalación en XAMPP (Windows)

### Paso 1: Descargar e Instalar XAMPP

1. Descarga XAMPP desde: https://www.apachefriends.org/
2. Ejecuta el instalador
3. Selecciona los componentes: **Apache**, **MySQL** y **PHP**
4. Completa la instalación (recomendado: `C:\xampp`)

### Paso 2: Copiar el Proyecto

1. Copia toda la carpeta del proyecto a:
   ```
   C:\xampp\htdocs\inventario nunca jamas\
   ```

2. Asegúrate de que la estructura de carpetas sea correcta:
   ```
   C:\xampp\htdocs\inventario nunca jamas\
   ├── config/
   ├── controllers/
   ├── models/
   ├── views/
   ├── public/
   ├── helpers/
   └── index.php
   ```

### Paso 3: Iniciar Servicios

1. Abre el **Panel de Control de XAMPP**
2. Inicia **Apache**
3. Inicia **MySQL**
4. Verifica que ambos servicios estén en verde (corriendo)

### Paso 4: Crear la Base de Datos

1. Abre phpMyAdmin: `http://localhost/phpmyadmin`
2. Haz clic en **"Nueva"** en el menú lateral
3. Nombre de la base de datos: `inventario_ropa_infantil`
4. Intercalación: `utf8mb4_general_ci`
5. Haz clic en **"Crear"**

### Paso 5: Importar la Estructura SQL

1. En phpMyAdmin, selecciona la base de datos `inventario_ropa_infantil`
2. Ve a la pestaña **"Importar"**
3. Haz clic en **"Seleccionar archivo"**
4. Busca el archivo SQL en la carpeta del proyecto (si existe)
5. Haz clic en **"Continuar"**

**Nota:** Si no tienes un archivo SQL, el sistema creará las tablas automáticamente al usarlo por primera vez (si está configurado así).

### Paso 6: Configurar Permisos de Carpetas

1. Navega a: `C:\xampp\htdocs\inventario nunca jamas\public\uploads\productos\`
2. Haz clic derecho en la carpeta → **Propiedades**
3. Ve a la pestaña **"Seguridad"**
4. Haz clic en **"Editar"**
5. Selecciona **"Usuarios"** y marca **"Control total"**
6. Haz clic en **"Aplicar"** y **"Aceptar"**

### Paso 7: Acceder al Sistema

1. Abre tu navegador
2. Ve a: `http://localhost/inventario%20nunca%20jamas/`
3. Deberías ver la página de login

---

## 🐧 Instalación en Linux/Mac

### Paso 1: Instalar LAMP/MAMP

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-gd php-mbstring
```

**CentOS/RHEL:**
```bash
sudo yum install httpd mysql-server php php-mysql php-gd php-mbstring
```

**Mac (con Homebrew):**
```bash
brew install php mysql
brew services start mysql
```

### Paso 2: Copiar el Proyecto

```bash
# Copia el proyecto a la carpeta web
sudo cp -r "inventario nunca jamas" /var/www/html/
# O para Mac con MAMP:
cp -r "inventario nunca jamas" /Applications/MAMP/htdocs/
```

### Paso 3: Configurar Permisos

```bash
# Cambiar propietario
sudo chown -R www-data:www-data /var/www/html/"inventario nunca jamas"
# O para Mac:
sudo chown -R _www:_www /Applications/MAMP/htdocs/"inventario nunca jamas"

# Dar permisos de escritura
sudo chmod -R 755 /var/www/html/"inventario nunca jamas"/public/uploads
```

### Paso 4: Crear Base de Datos

```bash
# Acceder a MySQL
mysql -u root -p

# Crear base de datos
CREATE DATABASE inventario_ropa_infantil CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

# Salir
exit;
```

### Paso 5: Acceder al Sistema

- **Linux:** `http://localhost/inventario%20nunca%20jamas/`
- **Mac (MAMP):** `http://localhost:8888/inventario%20nunca%20jamas/`

---

## 🗄️ Configuración de la Base de Datos

### Opción 1: Configuración por Defecto (XAMPP)

Edita el archivo `config/db.php`:

```php
private $host = 'localhost';
private $db_name = 'inventario_ropa_infantil';
private $username = 'root';
private $password = '';  // Sin contraseña por defecto en XAMPP
```

### Opción 2: Configuración Personalizada

Si tienes credenciales diferentes:

```php
private $host = 'localhost';
private $db_name = 'inventario_ropa_infantil';
private $username = 'tu_usuario';
private $password = 'tu_contraseña';
```

### Verificar Conexión

1. Abre `http://localhost/inventario%20nunca%20jamas/`
2. Si hay un error de conexión, verifica:
   - MySQL está corriendo
   - Las credenciales son correctas
   - La base de datos existe

---

## ⚙️ Configuración de la Aplicación

### Configurar URL Base

El sistema detecta automáticamente la URL base, pero puedes ajustarla en `config/config.php`:

```php
// Si tu URL es diferente, puedes definirla manualmente:
define('BASE_URL', 'http://localhost/inventario%20nunca%20jamas/');
```

### Configurar Entorno

En `config/config.php`, cambia el entorno según corresponda:

```php
// Desarrollo
define('ENVIRONMENT', 'development');

// Producción
define('ENVIRONMENT', 'production');
```

**Diferencias:**
- **Development:** Muestra errores en pantalla
- **Production:** Oculta errores y los registra en logs

### Configurar Zona Horaria

La zona horaria está configurada en `config/config.php`:

```php
date_default_timezone_set('America/Bogota');
```

Cambia según tu ubicación:
- `America/Mexico_City`
- `America/New_York`
- `Europe/Madrid`
- etc.

---

## ✅ Verificación de la Instalación

### Checklist de Verificación

- [ ] Apache está corriendo
- [ ] MySQL está corriendo
- [ ] Base de datos creada
- [ ] Permisos de carpetas configurados
- [ ] Página de login se muestra correctamente
- [ ] Puedes iniciar sesión con credenciales por defecto

### Probar Funcionalidades Básicas

1. **Login:**
   - Email: `admin@inventario.com`
   - Contraseña: `admin123`

2. **Verificar Dashboard:**
   - Deberías ver métricas y gráficas

3. **Probar Subida de Imágenes:**
   - Ve a Productos → Crear Producto
   - Intenta subir una imagen
   - Verifica que se guarde en `public/uploads/productos/`

---

## 🔧 Solución de Problemas

### Error: "No se puede conectar a la base de datos"

**Solución:**
1. Verifica que MySQL esté corriendo
2. Revisa las credenciales en `config/db.php`
3. Verifica que la base de datos exista:
   ```sql
   SHOW DATABASES LIKE 'inventario_ropa_infantil';
   ```

### Error: "Permission denied" al subir imágenes

**Solución:**
1. Verifica permisos de la carpeta `public/uploads/productos/`
2. En Windows: Propiedades → Seguridad → Control total
3. En Linux/Mac:
   ```bash
   chmod -R 755 public/uploads/productos/
   ```

### Error: "Extension GD not found"

**Solución:**
1. En XAMPP, edita `php.ini`
2. Busca: `;extension=gd`
3. Cambia a: `extension=gd`
4. Reinicia Apache

### Página en blanco

**Solución:**
1. Activa mostrar errores en `config/config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Revisa los logs de Apache/MySQL
3. Verifica que todas las rutas de archivos sean correctas

### Error 404 en todas las páginas

**Solución:**
1. Verifica que el archivo `.htaccess` exista (si se usa)
2. Asegúrate de que mod_rewrite esté habilitado en Apache
3. Verifica la URL base en `config/config.php`

---

## 🚀 Configuración para Producción

### 1. Cambiar Entorno

En `config/config.php`:
```php
define('ENVIRONMENT', 'production');
```

### 2. Cambiar Credenciales por Defecto

**IMPORTANTE:** Cambia las contraseñas de los usuarios por defecto:
- Admin: `admin@inventario.com`
- Cajero: `cajero@inventario.com`

### 3. Configurar Base de Datos Segura

```php
// Usa un usuario específico con permisos limitados
private $username = 'app_user';
private $password = 'contraseña_segura_compleja';
```

### 4. Configurar HTTPS

1. Obtén un certificado SSL
2. Configura Apache para usar HTTPS
3. Actualiza `BASE_URL` en `config/config.php`:
   ```php
   define('BASE_URL', 'https://tudominio.com/');
   ```

### 5. Configurar Logs

Crea la carpeta `logs/` y asegúrate de que tenga permisos de escritura:
```bash
mkdir logs
chmod 755 logs
```

### 6. Optimizar PHP

En `php.ini`:
```ini
max_execution_time = 30
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
```

### 7. Configurar Backup Automático

Configura un cron job para respaldar la base de datos:
```bash
# Ejemplo de cron job (diario a las 2 AM)
0 2 * * * mysqldump -u root -p contraseña inventario_ropa_infantil > /backups/db_$(date +\%Y\%m\%d).sql
```

---

## 📝 Notas Adicionales

### Espacios en el Nombre de la Carpeta

Si la carpeta tiene espacios (como "inventario nunca jamas"), el sistema los codifica automáticamente como `%20` en las URLs.

### Puerto Personalizado

Si usas un puerto diferente (ej: 8080), actualiza la URL:
```
http://localhost:8080/inventario%20nunca%20jamas/
```

### Múltiples Instalaciones

Si necesitas múltiples instalaciones:
1. Crea carpetas separadas
2. Crea bases de datos separadas
3. Configura cada una con su propio `config/db.php`

---

## 🆘 Soporte

Si encuentras problemas:

1. Revisa los logs de errores:
   - Apache: `C:\xampp\apache\logs\error.log`
   - PHP: `C:\xampp\php\logs\php_error_log`
   - MySQL: `C:\xampp\mysql\data\*.err`

2. Verifica que todas las extensiones PHP estén habilitadas

3. Asegúrate de que la versión de PHP sea compatible (7.4+)

---

*Última actualización: 2025-01-28*

