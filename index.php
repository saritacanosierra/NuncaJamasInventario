<?php
/**
 * Punto de entrada principal del sistema
 * Router simple basado en parámetros GET
 * 
 * @package Router
 * @version 1.0
 */

require_once __DIR__ . '/back/config/config.php';
require_once __DIR__ . '/back/config/db.php';

// Inicializar conexión a base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener acción y método
$action = $_GET['action'] ?? 'login';
$method = $_GET['method'] ?? 'index';

/**
 * Router principal
 * Maneja el enrutamiento de todas las peticiones del sistema
 */
switch ($action) {
    case 'login':
        $controller = new AuthController($db);
        if ($method === 'doLogin') {
            $controller->doLogin();
        } else {
            $controller->login();
        }
        break;
        
    case 'logout':
        $controller = new AuthController($db);
        $controller->logout();
        break;

    case 'recuperar':
        $controller = new AuthController($db);
        if ($method === 'enviar') {
            $controller->enviarRecuperacion();
        } elseif ($method === 'restablecer') {
            $controller->restablecer();
        } elseif ($method === 'guardar') {
            $controller->guardarClave();
        } else {
            $controller->recuperar();
        }
        break;
        
    case 'usuarios':
        if (!isAdmin()) {
            $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
            $rol = $_SESSION['usuario_rol'] ?? 'cajero';
            $redirectAction = ($rol === 'cajero') ? 'ventas' : (($rol === 'operario') ? 'produccion' : 'dashboard');
            redirect('index.php?action=' . $redirectAction);
        }
        $controller = new UsuarioController($db);
        $allowedMethods = ['store', 'update', 'delete', 'getUsuario', 'index'];
        if (in_array($method, $allowedMethods)) {
            $controller->{$method}();
        } else {
            $controller->index();
        }
        break;
        
    case 'dashboard':
        if (!canAccess('dashboard')) {
            $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
            redirect('index.php?action=' . (isCajero() ? 'ventas' : (isOperario() ? 'produccion' : 'login')));
        }
        $controller = new DashboardController($db);
        $allowedMethods = ['getMetaDiaria', 'index'];
        if (in_array($method, $allowedMethods)) {
            $controller->{$method}();
        } else {
            $controller->index();
        }
        break;
        
    case 'productos':
        if (!canAccess('productos')) {
            $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
            redirect('index.php?action=' . (isCajero() ? 'ventas' : (isOperario() ? 'produccion' : 'login')));
        }
        $controller = new ProductoController($db);
        // Verificar permisos para acciones que requieren privilegios
        $restrictedMethods = ['create', 'store', 'update', 'delete', 'crearCategoria', 'eliminarCategoria'];
        if (in_array($method, $restrictedMethods) && !canPerform($method, 'producto')) {
            $_SESSION['error'] = 'No tiene permisos para realizar esta acción';
            redirect('index.php?action=productos');
        }
        $allowedMethods = ['create', 'store', 'edit', 'update', 'delete', 'buscarPorCodigo', 
                          'buscarPorNombre', 'generarCodigo', 'crearCategoria', 'obtenerCategorias', 
                          'eliminarCategoria', 'index'];
        if (in_array($method, $allowedMethods)) {
            $controller->{$method}();
        } else {
            $controller->index();
        }
        break;
        
    case 'ventas':
        if (!canAccess('ventas')) {
            $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
            redirect('index.php?action=' . (isCajero() ? 'productos' : (isOperario() ? 'produccion' : 'login')));
        }
        $controller = new VentaController($db);
        $allowedMethods = ['procesar', 'factura', 'historial', 'edit', 'update', 'rotuloEnvio', 'obtenerHistorial', 'index'];
        if (in_array($method, $allowedMethods)) {
            $controller->{$method}();
        } else {
            $controller->index();
        }
        break;
        
    case 'clientes':
        $controller = new ClienteController($db);
        // Métodos de API permitidos para cajeros (necesarios para el punto de venta)
        $metodosApiPermitidos = ['buscar', 'crearRapido', 'getCliente'];
        
        // Si es un método de API y el usuario es cajero, permitir acceso
        if (!in_array($method, $metodosApiPermitidos) || !isCajero()) {
            if (!canAccess('clientes')) {
                $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
                redirect('index.php?action=' . (isCajero() ? 'ventas' : (isOperario() ? 'produccion' : 'login')));
            }
        }
        
        $allowedMethods = ['store', 'update', 'delete', 'historial', 'buscar', 'crearRapido', 'getCliente', 'index'];
        if (in_array($method, $allowedMethods)) {
            $controller->{$method}();
        } else {
            $controller->index();
        }
        break;
        
    case 'gastos':
        if (!canAccess('gastos')) {
            $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
            redirect('index.php?action=' . (isCajero() ? 'ventas' : (isOperario() ? 'produccion' : 'login')));
        }
        $controller = new GastoController($db);
        $allowedMethods = ['store', 'update', 'delete', 'getGasto', 'obtenerCategorias', 'crearCategoria',
                          'actualizarCategoria', 'eliminarCategoria', 'getCategoria', 'storeInversion',
                          'getInversion', 'updateInversion', 'deleteInversion', 'obtenerHistorial', 'index'];
        if (in_array($method, $allowedMethods)) {
            $controller->{$method}();
        } else {
            $controller->index();
        }
        break;
        
    case 'agenda':
        if (!canAccess('agenda')) {
            $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
            redirect('index.php?action=' . (isCajero() ? 'ventas' : (isOperario() ? 'produccion' : 'login')));
        }
        $controller = new AgendaController($db);
        $allowedMethods = ['store', 'update', 'delete', 'completar', 'getTarea', 'getTareasPorFecha', 'index'];
        if (in_array($method, $allowedMethods)) {
            $controller->{$method}();
        } else {
            $controller->index();
        }
        break;
        
    case 'produccion':
        if (!canAccess('produccion')) {
            $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
            // Redirigir según el rol del usuario
            if (isCajero()) {
                redirect('index.php?action=ventas');
            } elseif (isOperario()) {
                redirect('index.php?action=login');
            } else {
                redirect('index.php?action=login');
            }
        }
        $controller = new ProduccionController($db);
        // Verificar permisos específicos para operario
        if (isOperario() && in_array($method, ['dashboardOperaciones', 'finalizarDia'])) {
            $_SESSION['error'] = 'No tiene permisos para realizar esta acción';
            redirect('index.php?action=produccion');
        }
        $allowedMethods = ['guardarRegistro', 'guardarOperacion', 'eliminarOperacion', 'guardarRetroceso',
                          'getRetrocesos', 'getHistorialOperacion', 'getOperaciones', 'getOperariasDelDia',
                          'buscarOperarias', 'generarCodigoOperacion', 'getResumenDia', 'finalizarDia',
                          'dashboardOperaciones', 'verificarDiaFinalizado', 'index'];
        if (in_array($method, $allowedMethods)) {
            $controller->{$method}();
        } else {
            $controller->index();
        }
        break;
        
    default:
        requireAuth();
        // Redirigir según el rol del usuario
        $rol = $_SESSION['usuario_rol'] ?? 'cajero';
        $defaultRoutes = [
            'admin' => 'dashboard',
            'cajero' => 'ventas',
            'operario' => 'produccion'
        ];
        $defaultRoute = $defaultRoutes[$rol] ?? 'login';
        redirect('index.php?action=' . $defaultRoute);
}