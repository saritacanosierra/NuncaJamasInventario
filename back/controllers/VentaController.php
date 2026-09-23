<?php
/**
 * Controlador de Ventas
 */

class VentaController {
    private $ventaModel;
    private $productoModel;
    private $clienteModel;
    
    public function __construct($db) {
        $this->ventaModel = new Venta($db);
        $this->productoModel = new Producto($db);
        $this->clienteModel = new Cliente($db);
    }
    
    /**
     * Mostrar punto de venta
     */
    public function index() {
        requireAuth();
        
        $clientes = $this->clienteModel->getAll();
        
        require_once BASE_DIR . '/front/views/ventas/index.php';
    }
    
    /**
     * Procesar venta
     */
    public function procesar() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                exit;
            }
            redirect('index.php?action=ventas');
        }
        
        $carrito = json_decode($_POST['carrito'] ?? '[]', true);
        $clienteId = intval($_POST['cliente_id'] ?? 1);
        $descuento = floatval($_POST['descuento'] ?? 0);
        $metodoPago = $_POST['metodo_pago'] ?? 'Efectivo';
        $conDomicilio = isset($_POST['con_domicilio']) && $_POST['con_domicilio'] === '1';
        $observacionesDomicilio = trim($_POST['observaciones_domicilio'] ?? '');
        $pagoContraEntrega = isset($_POST['pago_contra_entrega']) && $_POST['pago_contra_entrega'] === '1';
        
        if (empty($carrito)) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'El carrito está vacío']);
                exit;
            }
            $_SESSION['error'] = 'El carrito está vacío';
            redirect('index.php?action=ventas');
        }
        
        // Calcular totales con validación
        $subtotal = 0;
        foreach ($carrito as $item) {
            $precio = floatval($item['precio'] ?? 0);
            $cantidad = intval($item['cantidad'] ?? 0);
            $subtotal += $precio * $cantidad;
        }
        
        // Asegurar que subtotal y descuento sean números válidos
        $subtotal = round(floatval($subtotal), 2);
        $descuento = round(floatval($descuento), 2);
        
        // Calcular total con validación
        $total = $subtotal - $descuento;
        
        // Validar que el total no sea negativo
        if ($total < 0) {
            $total = 0;
        }
        
        // Validar que el total sea correcto matemáticamente
        $totalCalculado = round($subtotal - $descuento, 2);
        if (abs($total - $totalCalculado) > 0.01) {
            error_log("Error de cálculo en venta - Subtotal: $subtotal, Descuento: $descuento, Total calculado: $totalCalculado, Total recibido: $total");
            $total = $totalCalculado; // Corregir el total
        }
        
        // Asegurar que el total tenga máximo 2 decimales
        $total = round($total, 2);
        
        // Preparar datos de venta
        $data = [
            'cliente_id' => $clienteId,
            'usuario_id' => $_SESSION['usuario_id'],
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
            'metodo_pago' => $metodoPago,
            'con_domicilio' => $conDomicilio,
            'observaciones_domicilio' => $observacionesDomicilio,
            'pago_contra_entrega' => $pagoContraEntrega,
            'detalles' => []
        ];
        
        // Preparar detalles
        foreach ($carrito as $item) {
            $data['detalles'][] = [
                'producto_id' => $item['id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio'],
                'subtotal' => $item['precio'] * $item['cantidad']
            ];
        }
        
        // Crear venta
        $resultado = $this->ventaModel->create($data);
        
        if ($resultado['success']) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode($resultado);
                exit;
            }
            $_SESSION['success'] = 'Venta procesada exitosamente. Factura: ' . $resultado['numero_factura'];
            $_SESSION['venta_id'] = $resultado['venta_id'];
            redirect('index.php?action=ventas&method=factura&id=' . $resultado['venta_id']);
        } else {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode($resultado);
                exit;
            }
            $_SESSION['error'] = 'Error al procesar la venta: ' . ($resultado['error'] ?? 'Error desconocido');
            redirect('index.php?action=ventas');
        }
    }
    
    /**
     * Mostrar factura
     */
    public function factura() {
        requireAuth();
        
        $id = intval($_GET['id'] ?? 0);
        $venta = $this->ventaModel->getById($id);
        
        if (!$venta) {
            $_SESSION['error'] = 'Venta no encontrada';
            redirect('index.php?action=ventas');
        }
        
        // Datos fijos de la empresa (remitente)
        $remitente = [
            'nombre' => 'Liliana Maria Sierra',
            'cedula' => '21468472',
            'telefono' => '3103580161',
            'direccion' => 'Cl. 48 #49-41, La Candelaria, Medellín,' . "\n" . 'La Candelaria, Medellín, Antioquia local 218'
        ];
        
        require_once BASE_DIR . '/front/views/ventas/factura.php';
    }
    
    /**
     * Mostrar rótulo de envío
     */
    public function rotuloEnvio() {
        requireAuth();
        
        $id = intval($_GET['id'] ?? 0);
        $venta = $this->ventaModel->getById($id);
        
        if (!$venta) {
            $_SESSION['error'] = 'Venta no encontrada';
            redirect('index.php?action=ventas&method=historial');
        }
        
        // Datos fijos de la empresa (remitente)
        $remitente = [
            'nombre' => 'Liliana Maria Sierra',
            'cedula' => '21468472',
            'telefono' => '3103580161',
            'direccion' => 'Cl. 48 #49-41, La Candelaria, Medellín,' . "\n" . 'La Candelaria, Medellín, Antioquia local 218'
        ];
        
        require_once BASE_DIR . '/front/views/ventas/rotulo_envio.php';
    }
    
    /**
     * Listar historial de ventas
     */
    public function historial() {
        requireAuth();
        
        // Obtener filtros del GET
        $fechaDesde = trim($_GET['fecha_desde'] ?? '');
        $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
        $clienteId = trim($_GET['cliente_id'] ?? '');
        
        $filters = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'cliente_id' => $clienteId
        ];
        
        $ventas = $this->ventaModel->getAll($filters);
        $clientes = $this->clienteModel->getAll();
        
        require_once BASE_DIR . '/front/views/ventas/historial.php';
    }
    
    /**
     * Obtener historial de ventas (API)
     */
    public function obtenerHistorial() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $ventasPorMes = $this->ventaModel->getVentasPorMes();
        $ventasPorAnio = $this->ventaModel->getVentasPorAnio();
        
        echo json_encode([
            'success' => true,
            'ventas_por_mes' => $ventasPorMes,
            'ventas_por_anio' => $ventasPorAnio
        ]);
        exit;
    }
    
    /**
     * Mostrar formulario de edición de venta
     */
    public function edit() {
        requireAuth();
        
        $id = intval($_GET['id'] ?? 0);
        $venta = $this->ventaModel->getById($id);
        
        if (!$venta) {
            $_SESSION['error'] = 'Venta no encontrada';
            redirect('index.php?action=ventas&method=historial');
        }
        
        $clientes = $this->clienteModel->getAll();
        $productos = $this->productoModel->getAll(['estado' => 'Disponible']);
        
        require_once BASE_DIR . '/front/views/ventas/edit.php';
    }
    
    /**
     * Actualizar venta
     */
    public function update() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=ventas&method=historial');
        }
        
        $id = intval($_POST['venta_id'] ?? 0);
        $venta = $this->ventaModel->getById($id);
        
        if (!$venta) {
            $_SESSION['error'] = 'Venta no encontrada';
            redirect('index.php?action=ventas&method=historial');
        }
        
        $clienteId = intval($_POST['cliente_id'] ?? $venta['cliente_id']);
        $descuento = floatval($_POST['descuento'] ?? $venta['descuento']);
        $metodoPago = $_POST['metodo_pago'] ?? $venta['metodo_pago'];
        $conDomicilio = isset($_POST['con_domicilio']) && $_POST['con_domicilio'] === '1';
        $observacionesDomicilio = trim($_POST['observaciones_domicilio'] ?? '');
        $pagoContraEntrega = isset($_POST['estado_pago']) && $_POST['estado_pago'] === 'contra_entrega';
        
        // Obtener detalles actuales y nuevos
        $detallesActuales = $venta['detalles'];
        $detallesNuevos = json_decode($_POST['detalles'] ?? '[]', true);
        
        if (empty($detallesNuevos)) {
            $_SESSION['error'] = 'La venta debe tener al menos un producto';
            redirect('index.php?action=ventas&method=edit&id=' . $id);
        }
        
        // Calcular nuevo subtotal con validación
        $subtotal = 0;
        foreach ($detallesNuevos as $detalle) {
            $precio = floatval($detalle['precio_unitario'] ?? 0);
            $cantidad = intval($detalle['cantidad'] ?? 0);
            $subtotalItem = round($precio * $cantidad, 2);
            $detalle['subtotal'] = $subtotalItem; // Asegurar que el subtotal esté calculado
            $subtotal += $subtotalItem;
        }
        
        // Asegurar que subtotal y descuento sean números válidos
        $subtotal = round(floatval($subtotal), 2);
        $descuento = round(floatval($descuento), 2);
        
        // Calcular total con validación
        $total = $subtotal - $descuento;
        
        // Validar que el total no sea negativo
        if ($total < 0) {
            $total = 0;
        }
        
        // Validar que el total sea correcto matemáticamente
        $totalCalculado = round($subtotal - $descuento, 2);
        if (abs($total - $totalCalculado) > 0.01) {
            error_log("Error de cálculo en actualización de venta - Subtotal: $subtotal, Descuento: $descuento, Total calculado: $totalCalculado, Total recibido: $total");
            $total = $totalCalculado; // Corregir el total
        }
        
        // Asegurar que el total tenga máximo 2 decimales
        $total = round($total, 2);
        
        $data = [
            'cliente_id' => $clienteId,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
            'metodo_pago' => $metodoPago,
            'con_domicilio' => $conDomicilio,
            'observaciones_domicilio' => $observacionesDomicilio,
            'pago_contra_entrega' => $pagoContraEntrega,
            'detalles' => $detallesNuevos,
            'detalles_actuales' => $detallesActuales
        ];
        
        $resultado = $this->ventaModel->update($id, $data);
        
        if ($resultado['success']) {
            $_SESSION['success'] = 'Venta actualizada exitosamente';
            redirect('index.php?action=ventas&method=historial');
        } else {
            $_SESSION['error'] = 'Error al actualizar la venta: ' . ($resultado['error'] ?? 'Error desconocido');
            redirect('index.php?action=ventas&method=edit&id=' . $id);
        }
    }
}