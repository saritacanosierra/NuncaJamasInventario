<?php
/**
 * Configuración general del sistema
 * Sistema de Inventario - Ropa Infantil
 * 
 * @package Config
 * @version 1.0
 */

// En local se ven los errores. En cualquier otro host quedan en el log.
$hostActual = $_SERVER['HTTP_HOST'] ?? 'localhost';
$esLocal = $hostActual === 'localhost'
    || $hostActual === '127.0.0.1'
    || str_starts_with($hostActual, 'localhost:')
    || str_starts_with($hostActual, '127.0.0.1:');
define('ENVIRONMENT', $esLocal ? 'development' : 'production');

// Ruta base del proyecto (directorio raíz) - DEFINIR PRIMERO
// Usar __FILE__ para mayor confiabilidad en diferentes entornos
if (!defined('BASE_DIR')) {
    $configFile = __FILE__;
    $baseDir = dirname($configFile, 3);
    // Normalizar separadores de directorio para compatibilidad Windows/Linux
    $baseDir = str_replace('\\', '/', $baseDir);
    $baseDir = rtrim($baseDir, '/');
    define('BASE_DIR', $baseDir);
}

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $https,
    ]);
    session_start();
}

// Zona horaria
date_default_timezone_set('America/Bogota');

// Constantes del sistema
// Detectar automáticamente la URL base con múltiples métodos de fallback
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
             (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
             (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) 
             ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// La carpeta real del proyecto sale de SCRIPT_NAME (index.php),
// no de REQUEST_URI: al entrar por /inventario/ dirname() sube a la raíz.
$baseUrl = null;
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if ($scriptName !== '' && $scriptName !== '/index.php') {
    $scriptDir = dirname($scriptName);
} elseif (!empty($_SERVER['REQUEST_URI'])) {
    $requestUri = strtok($_SERVER['REQUEST_URI'], '?');
    $scriptDir = dirname($requestUri);
    
    // Normalizar el directorio
    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\' || empty($scriptDir) || $scriptDir === '/index.php') {
        $scriptDir = '/';
    } else {
        // Asegurar que termine con '/'
        $scriptDir = rtrim($scriptDir, '/') . '/';
    }
    
    $baseUrl = $protocol . $host . $scriptDir;
}

// Método 2: Fallback usando SCRIPT_NAME
if (empty($baseUrl)) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $scriptDir = dirname($scriptName);
    
    // Normalizar separadores
    $scriptDir = str_replace('\\', '/', $scriptDir);
    
    // Si el script está en la raíz del dominio/subdominio, usar solo '/'
    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\' || empty($scriptDir)) {
        $scriptDir = '/';
    } else {
        // Asegurar que termine con '/'
        $scriptDir = rtrim($scriptDir, '/') . '/';
    }
    
    $baseUrl = $protocol . $host . $scriptDir;
}

// Método 3: Fallback usando PHP_SELF
if (empty($baseUrl)) {
    $phpSelf = $_SERVER['PHP_SELF'] ?? '/index.php';
    $scriptDir = dirname($phpSelf);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    
    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\' || empty($scriptDir)) {
        $scriptDir = '/';
    } else {
        $scriptDir = rtrim($scriptDir, '/') . '/';
    }
    
    $baseUrl = $protocol . $host . $scriptDir;
}

// Si la carpeta tiene espacios, codificarlos
$baseUrl = str_replace(' ', '%20', $baseUrl);

// Validar que BASE_URL no esté vacío
if (empty($baseUrl)) {
    // Último recurso: usar raíz
    $baseUrl = $protocol . $host . '/';
}

// Asegurar que termine con '/'
$baseUrl = rtrim($baseUrl, '/') . '/';

define('BASE_URL', $baseUrl);

// UPLOAD_DIR ya usa BASE_DIR que se definió al inicio
define('UPLOAD_DIR', BASE_DIR . '/front/public/uploads/productos/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Configuración de errores según el entorno
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    // Crear directorio de logs si no existe
    $logDir = dirname(__DIR__, 2) . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/error.log';
    ini_set('error_log', $logFile);
    
    // También capturar errores fatales
    register_shutdown_function(function() use ($logFile) {
        $error = error_get_last();
        if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = date('Y-m-d H:i:s') . " - FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}\n";
            @file_put_contents($logFile, $message, FILE_APPEND);
        }
    });
}

// Autoload de clases
spl_autoload_register(function ($class) {
    // Obtener la ruta base del proyecto usando __FILE__ que es más confiable
    $configFile = __FILE__;
    $baseDir = dirname($configFile, 3);
    
    // Normalizar separadores de directorio para compatibilidad Windows/Linux
    $baseDir = str_replace('\\', '/', $baseDir);
    $baseDir = rtrim($baseDir, '/');
    
    $paths = [
        $baseDir . '/back/models/',
        $baseDir . '/back/controllers/',
        $baseDir . '/back/helpers/'
    ];
    
    foreach ($paths as $path) {
        // Normalizar la ruta del archivo
        $file = rtrim($path, '/') . '/' . $class . '.php';
        $file = str_replace('\\', '/', $file);
        $file = str_replace('//', '/', $file); // Eliminar dobles barras
        
        // Verificar si el archivo existe (case-sensitive en Linux)
        if (file_exists($file) && is_file($file)) {
            require_once $file;
            return;
        }
    }
    
    // Si no se encontró, intentar búsqueda case-insensitive (último recurso)
    foreach ($paths as $path) {
        $path = rtrim($path, '/');
        if (is_dir($path)) {
            $files = glob($path . '/*.php');
            if ($files) {
                foreach ($files as $f) {
                    $className = basename($f, '.php');
                    if (strcasecmp($className, $class) === 0) {
                        require_once $f;
                        return;
                    }
                }
            }
        }
    }
});

require_once BASE_DIR . '/back/config/permisos_catalogo.php';
require_once BASE_DIR . '/back/helpers/Permisos.php';

/**
 * Redirige a una URL
 * 
 * @param string $url URL relativa o absoluta
 * @return void
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function pesos($valor) {
    $numero = round((float) $valor);
    $signo = $numero < 0 ? '-' : '';
    return $signo . '$' . number_format(abs($numero), 0, ',', '.');
}

/**
 * Verifica que el usuario esté autenticado
 * Redirige al login si no hay sesión activa
 * 
 * @return void
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function csrf_valid() {
    $guardado = $_SESSION['csrf_token'] ?? '';
    $enviado = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($enviado) && $guardado !== '' && hash_equals($guardado, $enviado);
}

function exigir_csrf_json() {
    if (csrf_valid()) {
        return;
    }
    echo json_encode(['success' => false, 'error' => 'La solicitud no es válida. Recargue la página e intente de nuevo.']);
    exit;
}

function exigir_csrf_redirect($destino) {
    if (csrf_valid()) {
        return;
    }
    $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
    redirect($destino);
}

function codigo_eliminacion_valido($esperado) {
    $escrito = trim((string) ($_POST['codigo_confirmacion'] ?? ''));
    $esperado = trim((string) $esperado);
    return $escrito !== '' && $esperado !== '' && strcasecmp($escrito, $esperado) === 0;
}

function requireAuth() {
    if (!isset($_SESSION['usuario_id'])) {
        redirect('index.php?action=login');
    }
}

/**
 * Verifica que el usuario tenga un rol específico
 * 
 * @param string $rol Rol requerido
 * @return void
 */
function requireRole($rol) {
    requireAuth();
    $usuarioRol = $_SESSION['usuario_rol'] ?? '';
    if ($usuarioRol !== $rol && $usuarioRol !== 'admin') {
        // Redirigir según el rol del usuario
        if ($usuarioRol === 'cajero') {
            redirect('index.php?action=ventas');
        } elseif ($usuarioRol === 'operario') {
            redirect('index.php?action=produccion');
        } else {
            redirect('index.php?action=dashboard');
        }
    }
}

/**
 * Verifica si el usuario tiene un rol específico
 * 
 * @param string $rol Rol a verificar
 * @return bool
 */
function hasRole($rol) {
    if (!isset($_SESSION['usuario_rol'])) {
        return false;
    }
    return $_SESSION['usuario_rol'] === $rol;
}

/**
 * Verifica si el usuario es administrador
 * 
 * @return bool
 */
function isAdmin() {
    if (!isset($_SESSION['usuario_rol'])) {
        return false;
    }
    return $_SESSION['usuario_rol'] === 'admin';
}

/**
 * Verifica si el usuario es cajero
 * 
 * @return bool
 */
function isCajero() {
    if (!isset($_SESSION['usuario_rol'])) {
        return false;
    }
    return $_SESSION['usuario_rol'] === 'cajero';
}

/**
 * Verifica si el usuario es operario
 * 
 * @return bool
 */
function isOperario() {
    if (!isset($_SESSION['usuario_rol'])) {
        return false;
    }
    return $_SESSION['usuario_rol'] === 'operario';
}

/**
 * Verifica si el usuario tiene acceso a una acción según su rol
 * 
 * @param string $action Acción a verificar
 * @return bool
 */
function canAccess($action) {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    return puedeVerModulo($action);
}

/**
 * Verifica si el usuario puede realizar una acción específica sobre un recurso
 * 
 * @param string $action Acción a realizar
 * @param string|null $resource Recurso sobre el que se realiza la acción
 * @return bool
 */
function canPerform($action, $resource = null) {
    requireAuth();
    if ($resource === 'producto') {
        $mapa = [
            'create' => 'productos_catalogo:create',
            'store' => 'productos_catalogo:create',
            'update' => 'productos_catalogo:edit',
            'delete' => 'productos_catalogo:delete',
            'crearCategoria' => 'productos_categorias:create',
            'eliminarCategoria' => 'productos_categorias:delete',
        ];
        return isset($mapa[$action]) && tienePermiso($mapa[$action]);
    }
    return false;
}