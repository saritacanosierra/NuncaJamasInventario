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

$database = new Database();
$db = $database->getConnection();
$GLOBALS['db'] = $db;

$action = $_GET['action'] ?? 'login';
$method = $_GET['method'] ?? 'index';

$rutasPublicas = ['login', 'logout', 'recuperar', 'escritorio'];
if (!empty($_SESSION['usuario_id']) && !in_array($action, $rutasPublicas, true)) {
    if (!permisos_cargar_en_sesion($db, (int) $_SESSION['usuario_id'])) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['error'] = 'Su usuario no tiene un rol asignado. Contacte al administrador.';
        redirect('index.php?action=login');
    }
}

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

    case 'sesion':
        $controller = new SesionController($db);
        if ($method !== 'me') {
            $method = 'me';
        }
        exigir_ruta('sesion', $method);
        $controller->me();
        break;

    case 'usuarios':
        $controller = new UsuarioController($db);
        $allowedMethods = ['store', 'update', 'delete', 'getUsuario', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('usuarios', $method);
        $controller->{$method}();
        break;

    case 'roles':
        $controller = new RolController($db);
        $allowedMethods = ['guardar', 'crear', 'slugs', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('roles', $method);
        $controller->{$method}();
        break;

    case 'wordpress':
        $controller = new WordpressController($db);
        $allowedMethods = ['guardar', 'probar', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('wordpress', $method);
        $controller->{$method}();
        break;

    case 'dashboard':
        $controller = new DashboardController($db);
        $allowedMethods = ['getMetaDiaria', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('dashboard', $method);
        $controller->{$method}();
        break;

    case 'productos':
        $controller = new ProductoController($db);
        $allowedMethods = ['create', 'store', 'edit', 'update', 'delete', 'buscarPorCodigo',
                          'buscarPorNombre', 'generarCodigo', 'codigoBarras', 'crearCategoria', 'obtenerCategorias',
                          'eliminarCategoria', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('productos', $method);
        $controller->{$method}();
        break;

    case 'ventas':
        $controller = new VentaController($db);
        $allowedMethods = ['procesar', 'factura', 'historial', 'edit', 'update', 'delete', 'rotuloEnvio', 'obtenerHistorial', 'cambiarTalla', 'resolucion', 'guardarResolucion', 'guardarCertificado', 'emitirDian', 'enviarDian', 'notaCredito', 'enviarNota', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('ventas', $method);
        $controller->{$method}();
        break;

    case 'clientes':
        $controller = new ClienteController($db);
        $allowedMethods = ['store', 'update', 'delete', 'historial', 'buscar', 'crearRapido', 'getCliente', 'abonar', 'deudas', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('clientes', $method);
        $controller->{$method}();
        break;

    case 'compras':
        $controller = new CompraController($db);
        $allowedMethods = ['store', 'kardex', 'buscarProducto', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('compras', $method);
        $controller->{$method}();
        break;

    case 'caja':
        $controller = new CajaController($db);
        $allowedMethods = ['cerrar', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('caja', $method);
        $controller->{$method}();
        break;

    case 'informes':
        $controller = new InformeController($db);
        $allowedMethods = ['index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('informes', $method);
        $controller->{$method}();
        break;

    case 'gastos':
        $controller = new GastoController($db);
        $allowedMethods = ['store', 'update', 'delete', 'getGasto', 'obtenerCategorias', 'crearCategoria',
                          'actualizarCategoria', 'eliminarCategoria', 'getCategoria', 'storeInversion',
                          'getInversion', 'updateInversion', 'deleteInversion', 'obtenerHistorial', 'descargar', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('gastos', $method);
        $controller->{$method}();
        break;

    case 'agenda':
        $controller = new AgendaController($db);
        $allowedMethods = ['store', 'update', 'delete', 'completar', 'getTarea', 'getTareasPorFecha', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('agenda', $method);
        $controller->{$method}();
        break;

    case 'produccion':
        $controller = new ProduccionController($db);
        $allowedMethods = ['guardarRegistro', 'guardarOperacion', 'eliminarOperacion', 'guardarRetroceso',
                          'getRetrocesos', 'getHistorialOperacion', 'getOperaciones', 'getOperariasDelDia',
                          'buscarOperarias', 'generarCodigoOperacion', 'getResumenDia', 'finalizarDia', 'reabrirDia',
                          'dashboardOperaciones', 'verificarDiaFinalizado', 'pago', 'guardarTarifa', 'index'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'index';
        }
        exigir_ruta('produccion', $method);
        $controller->{$method}();
        break;

    case 'escritorio':
        $controller = new EscritorioController();
        $controller->descargar();
        break;

    default:
        requireAuth();
        redirect(permisos_url_inicio());
}
