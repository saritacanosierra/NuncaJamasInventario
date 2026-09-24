<?php
/**
 * Controlador de Ventas
 */

class VentaController {
    private $db;
    private $ventaModel;
    private $productoModel;
    private $clienteModel;
    
    public function __construct($db) {
        $this->db = $db;
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
        if (!csrf_valid()) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'La solicitud no es válida. Recargue la página e intente de nuevo.']);
                exit;
            }
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=ventas');
        }
        
        $carrito = json_decode($_POST['carrito'] ?? '[]', true);
        $clienteId = intval($_POST['cliente_id'] ?? 1);
        $descuento = floatval($_POST['descuento'] ?? 0);
        $metodoPago = $_POST['metodo_pago'] ?? 'Efectivo';
        $conDomicilio = isset($_POST['con_domicilio']) && $_POST['con_domicilio'] === '1';
        $domicilioContra = isset($_POST['domicilio_contra_entrega']) && $_POST['domicilio_contra_entrega'] === '1';
        $observacionesDomicilio = trim($_POST['observaciones_domicilio'] ?? '');
        $pagoContraEntrega = isset($_POST['pago_contra_entrega']) && $_POST['pago_contra_entrega'] === '1';

        if ($metodoPago === 'Fiado' && $clienteId <= 1) {
            $this->responderVenta(false, 'El fiado necesita un cliente con cédula, no el cliente general.');
        }
        $llevaEnvio = $conDomicilio || $pagoContraEntrega || $domicilioContra || (float) ($_POST['domicilio'] ?? 0) > 0;
        if ($llevaEnvio && $clienteId <= 1) {
            $this->responderVenta(false, 'Este envío necesita el cliente real. Cliente General no tiene los datos de entrega.');
        }
        
        if (empty($carrito)) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'El carrito está vacío']);
                exit;
            }
            $_SESSION['error'] = 'El carrito está vacío';
            redirect('index.php?action=ventas');
        }
        
        // El precio de caja es el valor de venta más el IVA del 19%.
        $subtotal = 0;
        $detallesVenta = [];
        foreach ($carrito as $item) {
            $productoId = (int) ($item['id'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);
            if ($productoId < 1 || $cantidad < 1) {
                continue;
            }
            $producto = $this->productoModel->getById($productoId);
            $base = $producto ? (float) $producto['precio_venta'] : (float) ($item['base'] ?? 0);
            $unitario = (int) round($base);
            $subtotal += $unitario * $cantidad;
            $detallesVenta[] = [
                'producto_id' => $productoId,
                'talla_id' => (int) ($item['talla_id'] ?? 0),
                'cantidad' => $cantidad,
                'precio_unitario' => $unitario,
                'subtotal' => $unitario * $cantidad,
            ];
        }
        
        $descuentoTope = min(max(0, $descuento), $subtotal);
        $caja = $this->totalesDeCaja(
            $subtotal,
            $descuento,
            iva_de($subtotal - $descuentoTope),
            $_POST['domicilio'] ?? 0,
            $_POST['empaque'] ?? 0,
            false
        );
        $subtotal = $caja['subtotal'];
        $descuento = $caja['descuento'];
        $total = $caja['total'];
        if ((float) $caja['domicilio'] > 0 || $domicilioContra || $pagoContraEntrega) {
            $conDomicilio = $conDomicilio || (float) $caja['domicilio'] > 0 || $domicilioContra;
        }
        $partes = $this->partesDePago($metodoPago, $total, $pagoContraEntrega, $_POST, true);
        if ($partes['error'] !== '') {
            $this->responderVenta(false, $partes['error']);
        }
        
        // Preparar datos de venta
        $data = [
            'cliente_id' => $clienteId,
            'usuario_id' => $_SESSION['usuario_id'],
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'iva' => $caja['iva'],
            'domicilio' => $caja['domicilio'],
            'empaque' => $caja['empaque'],
            'total' => $total,
            'metodo_pago' => $metodoPago,
            'pago_efectivo' => $partes['pago_efectivo'],
            'pago_transferencia' => $partes['pago_transferencia'],
            'pago_tarjeta' => $partes['pago_tarjeta'],
            'recibido' => $partes['recibido'],
            'devuelta' => $partes['devuelta'],
            'con_domicilio' => $conDomicilio,
            'observaciones_domicilio' => $observacionesDomicilio,
            'pago_contra_entrega' => $pagoContraEntrega,
            'domicilio_contra_entrega' => $domicilioContra,
            'detalles' => []
        ];
        
        $data['detalles'] = $detallesVenta;
        
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

        $facturas = new FacturaDian($this->db);
        $documentoDian = $facturas->porVenta($id);
        $notaCredito = $documentoDian ? (new NotaCredito($this->db))->porFactura($documentoDian['id']) : null;
        $resolucion = $facturas->resolucionVigente();
        $faltasEnvio = $resolucion ? DianCliente::faltas($resolucion) : ['la resolución'];
        
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
        $productos = $this->productoModel->conTallas($this->productoModel->getAll(['estado' => 'Disponible']));
        
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
        exigir_csrf_redirect('index.php?action=ventas&method=historial');
        
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
        
        $ivaIncluido = ($_POST['iva_incluido'] ?? '') === '1';
        $descuentoTope = min(max(0, $descuento), $subtotal);
        $iva = $ivaIncluido ? 0 : iva_de($subtotal - $descuentoTope);
        $caja = $this->totalesDeCaja(
            $subtotal,
            $descuento,
            $iva,
            $_POST['domicilio'] ?? ($venta['domicilio'] ?? 0),
            $_POST['empaque'] ?? ($venta['empaque'] ?? 0),
            $ivaIncluido
        );
        $subtotal = $caja['subtotal'];
        $descuento = $caja['descuento'];
        $total = $caja['total'];
        if ((float) $caja['domicilio'] > 0) {
            $conDomicilio = true;
        }
        $partes = $this->partesDePago($metodoPago, $total, $pagoContraEntrega, $_POST, false);
        
        $data = [
            'cliente_id' => $clienteId,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'iva' => $caja['iva'],
            'domicilio' => $caja['domicilio'],
            'empaque' => $caja['empaque'],
            'total' => $total,
            'metodo_pago' => $metodoPago,
            'pago_efectivo' => $partes['pago_efectivo'],
            'pago_transferencia' => $partes['pago_transferencia'],
            'pago_tarjeta' => $partes['pago_tarjeta'],
            'recibido' => $partes['recibido'],
            'devuelta' => $partes['devuelta'],
            'con_domicilio' => $conDomicilio,
            'observaciones_domicilio' => $observacionesDomicilio,
            'pago_contra_entrega' => $pagoContraEntrega,
            'detalles' => $detallesNuevos,
            'detalles_actuales' => $detallesActuales,
            'usuario_id' => (int) $_SESSION['usuario_id'],
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

    /**
     * Eliminar venta solo si el código escrito coincide con la factura.
     */
    public function delete() {
        requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=ventas&method=historial');
        }

        $id = intval($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo_venta'] ?? '');
        $venta = $this->ventaModel->getById($id);

        if (!$venta) {
            $_SESSION['error'] = 'Venta no encontrada';
            redirect('index.php?action=ventas&method=historial');
        }

        if ($codigo === '' || strcasecmp($codigo, $venta['numero_factura']) !== 0) {
            $_SESSION['error'] = 'El código no coincide con la venta. No se eliminó.';
            redirect('index.php?action=ventas&method=historial');
        }

        if ((new FacturaDian($this->db))->porVenta($id)) {
            $_SESSION['error'] = 'Esta venta tiene documento electrónico y no se puede borrar.';
            redirect('index.php?action=ventas&method=historial');
        }

        if ($this->ventaModel->eliminar($id, (int) $_SESSION['usuario_id'])) {
            $_SESSION['success'] = 'Venta ' . $venta['numero_factura'] . ' eliminada. El stock volvió al inventario.';
        } else {
            $_SESSION['error'] = 'No se pudo eliminar la venta';
        }

        redirect('index.php?action=ventas&method=historial');
    }

    public function cambiarTalla() {
        $id = (int) ($_GET['id'] ?? $_POST['venta_id'] ?? 0);
        $venta = $this->ventaModel->getById($id);
        if (!$venta) {
            $_SESSION['error'] = 'Venta no encontrada';
            redirect('index.php?action=ventas&method=historial');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            exigir_csrf_redirect('index.php?action=ventas&method=cambiarTalla&id=' . $id);
            $resultado = (new CambioTalla($this->db))->aplicar(
                $id,
                (int) ($_POST['detalle_id'] ?? 0),
                (int) ($_POST['producto_entra_id'] ?? 0),
                (int) ($_POST['cantidad'] ?? 0),
                (int) $_SESSION['usuario_id'],
                (int) ($_POST['talla_entra_id'] ?? 0)
            );
            if ($resultado['success']) {
                $_SESSION['success'] = 'La talla quedó cambiada.';
                redirect('index.php?action=ventas&method=factura&id=' . $id);
            }
            $_SESSION['error'] = $resultado['error'];
            redirect('index.php?action=ventas&method=cambiarTalla&id=' . $id);
        }

        require_once BASE_DIR . '/front/views/ventas/cambiar_talla.php';
    }

    public function resolucion() {
        $resolucion = (new FacturaDian($this->db))->resolucionVigente();
        require_once BASE_DIR . '/front/views/ventas/resolucion.php';
    }

    public function guardarResolucion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=ventas&method=resolucion');
        }
        exigir_csrf_redirect('index.php?action=ventas&method=resolucion');

        $numero = trim($_POST['numero'] ?? '');
        $prefijo = strtoupper(trim($_POST['prefijo'] ?? ''));
        $desde = (int) ($_POST['desde_numero'] ?? 0);
        $hasta = (int) ($_POST['hasta_numero'] ?? 0);
        $fechaDesde = trim($_POST['fecha_desde'] ?? '');
        $fechaHasta = trim($_POST['fecha_hasta'] ?? '');
        $nit = trim($_POST['nit'] ?? '');
        $razon = trim($_POST['razon_social'] ?? '');
        $clave = trim($_POST['clave_tecnica'] ?? '');
        $ambiente = (int) ($_POST['ambiente'] ?? 2) === 1 ? 1 : 2;
        $softwareId = trim($_POST['software_id'] ?? '');
        $softwarePin = trim($_POST['software_pin'] ?? '');
        $setPruebas = trim($_POST['set_pruebas'] ?? '');

        if ($numero === '' || $prefijo === '' || $desde < 1 || $hasta < $desde || $nit === '' || $razon === '' || $clave === '') {
            $_SESSION['error'] = 'Completa la resolución, el rango, el NIT, la razón social y la clave técnica.';
            redirect('index.php?action=ventas&method=resolucion');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $_SESSION['error'] = 'Las fechas de la resolución no son válidas.';
            redirect('index.php?action=ventas&method=resolucion');
        }

        (new FacturaDian($this->db))->guardarResolucion([
            'numero' => $numero,
            'prefijo' => $prefijo,
            'desde_numero' => $desde,
            'hasta_numero' => $hasta,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'clave_tecnica' => $clave,
            'nit' => $nit,
            'razon_social' => $razon,
            'ambiente' => $ambiente,
            'software_id' => $softwareId,
            'software_pin' => $softwarePin,
            'set_pruebas' => $setPruebas,
        ]);
        $_SESSION['success'] = 'Resolución guardada. Con ella se puede generar el documento y el CUFE.';
        redirect('index.php?action=ventas&method=resolucion');
    }

    public function emitirDian() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=ventas&method=historial');
        }
        $id = (int) ($_POST['venta_id'] ?? 0);
        exigir_csrf_redirect('index.php?action=ventas&method=factura&id=' . $id);

        $venta = $this->ventaModel->getById($id);
        if (!$venta) {
            $_SESSION['error'] = 'Venta no encontrada';
            redirect('index.php?action=ventas&method=historial');
        }
        if ((int) $venta['cliente_id'] <= 1) {
            $_SESSION['error'] = 'La factura electrónica necesita un cliente con cédula, no el cliente general.';
            redirect('index.php?action=ventas&method=factura&id=' . $id);
        }

        $resultado = (new FacturaDian($this->db))->emitir($venta);
        if ($resultado['success']) {
            $_SESSION['success'] = 'Documento ' . $resultado['numero'] . ' generado con CUFE. Puedes enviarlo a la DIAN.';
        } else {
            $_SESSION['error'] = $resultado['error'];
        }
        redirect('index.php?action=ventas&method=factura&id=' . $id);
    }

    public function guardarCertificado() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=ventas&method=resolucion');
        }
        exigir_csrf_redirect('index.php?action=ventas&method=resolucion');

        $clave = (string) ($_POST['certificado_clave'] ?? '');
        $archivo = $_FILES['certificado'] ?? null;
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Elige el certificado .p12.';
            redirect('index.php?action=ventas&method=resolucion');
        }
        $nombre = strtolower((string) ($archivo['name'] ?? ''));
        if (!str_ends_with($nombre, '.p12') && !str_ends_with($nombre, '.pfx')) {
            $_SESSION['error'] = 'El certificado tiene que ser .p12 o .pfx.';
            redirect('index.php?action=ventas&method=resolucion');
        }
        if (($archivo['size'] ?? 0) > 1024 * 1024) {
            $_SESSION['error'] = 'El certificado pesa demasiado.';
            redirect('index.php?action=ventas&method=resolucion');
        }
        $binario = file_get_contents($archivo['tmp_name']);
        if ($binario === false || $clave === '') {
            $_SESSION['error'] = 'Faltan el archivo o la clave del certificado.';
            redirect('index.php?action=ventas&method=resolucion');
        }
        $error = DianCliente::guardarCertificado($binario, $clave);
        if ($error) {
            $_SESSION['error'] = $error;
        } else {
            $_SESSION['success'] = 'Certificado guardado. Ya se puede intentar el envío a la DIAN.';
        }
        redirect('index.php?action=ventas&method=resolucion');
    }

    public function enviarDian() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=ventas&method=historial');
        }
        $id = (int) ($_POST['venta_id'] ?? 0);
        exigir_csrf_redirect('index.php?action=ventas&method=factura&id=' . $id);

        $facturas = new FacturaDian($this->db);
        $documento = $facturas->porVenta($id);
        $resolucion = $facturas->resolucionVigente();
        if (!$documento || !$resolucion) {
            $_SESSION['error'] = 'Primero genera el documento y guarda la resolución.';
            redirect('index.php?action=ventas&method=factura&id=' . $id);
        }
        if ($documento['estado'] === 'aceptada') {
            $_SESSION['error'] = 'La DIAN ya aceptó este documento.';
            redirect('index.php?action=ventas&method=factura&id=' . $id);
        }

        $resultado = DianCliente::enviar($documento['xml'], $documento['numero'], $resolucion);
        $facturas->guardarEnvio($documento['id'], $resultado);
        if ($resultado['ok']) {
            $_SESSION['success'] = $resultado['mensaje'];
        } else {
            $_SESSION['error'] = $resultado['mensaje'];
        }
        redirect('index.php?action=ventas&method=factura&id=' . $id);
    }

    public function notaCredito() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=ventas&method=historial');
        }
        $id = (int) ($_POST['venta_id'] ?? 0);
        exigir_csrf_redirect('index.php?action=ventas&method=factura&id=' . $id);

        $venta = $this->ventaModel->getById($id);
        $facturas = new FacturaDian($this->db);
        $documento = $facturas->porVenta($id);
        $resolucion = $facturas->resolucionVigente();
        if (!$venta || !$documento || !$resolucion) {
            $_SESSION['error'] = 'La nota crédito necesita la venta y su documento electrónico.';
            redirect('index.php?action=ventas&method=factura&id=' . $id);
        }

        $resultado = (new NotaCredito($this->db))->crear(
            $venta,
            $documento,
            $resolucion,
            $_POST['motivo'] ?? '',
            (int) $_SESSION['usuario_id']
        );
        if ($resultado['success']) {
            $_SESSION['success'] = 'Nota crédito ' . $resultado['numero'] . ' creada. La prenda volvió al stock.';
        } else {
            $_SESSION['error'] = $resultado['error'];
        }
        redirect('index.php?action=ventas&method=factura&id=' . $id);
    }

    public function enviarNota() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=ventas&method=historial');
        }
        $id = (int) ($_POST['venta_id'] ?? 0);
        exigir_csrf_redirect('index.php?action=ventas&method=factura&id=' . $id);

        $facturas = new FacturaDian($this->db);
        $documento = $facturas->porVenta($id);
        $resolucion = $facturas->resolucionVigente();
        $notas = new NotaCredito($this->db);
        $nota = $documento ? $notas->porFactura($documento['id']) : null;
        if (!$nota || !$resolucion) {
            $_SESSION['error'] = 'Primero crea la nota crédito.';
            redirect('index.php?action=ventas&method=factura&id=' . $id);
        }
        if ($nota['estado'] === 'aceptada') {
            $_SESSION['error'] = 'La DIAN ya aceptó esta nota crédito.';
            redirect('index.php?action=ventas&method=factura&id=' . $id);
        }

        $resultado = DianCliente::enviar($nota['xml'], $nota['numero'], $resolucion);
        $notas->guardarEnvio($nota['id'], $resultado);
        if ($resultado['ok']) {
            $_SESSION['success'] = $resultado['mensaje'];
        } else {
            $_SESSION['error'] = $resultado['mensaje'];
        }
        redirect('index.php?action=ventas&method=factura&id=' . $id);
    }

    private function partesDePago($metodo, $total, $contraEntrega, $post, $exigir) {
        $total = round(max(0, (float) $total), 2);
        $recibido = round(max(0, (float) ($post['recibido'] ?? 0)), 2);
        $transferencia = round(max(0, (float) ($post['pago_transferencia'] ?? 0)), 2);
        $efectivo = 0.0;
        $tarjeta = 0.0;
        $trans = 0.0;
        $devuelta = 0.0;
        $error = '';
        if ($contraEntrega || $metodo === 'Fiado') {
            return $this->partesVacias($error);
        }
        if (!$exigir) {
            if ($metodo === 'Efectivo') {
                $efectivo = $total;
                $recibido = $total;
            } elseif ($metodo === 'Tarjeta') {
                $tarjeta = $total;
            } else {
                $trans = $total;
            }
            return [
                'pago_efectivo' => $efectivo,
                'pago_transferencia' => $trans,
                'pago_tarjeta' => $tarjeta,
                'recibido' => $recibido,
                'devuelta' => 0,
                'error' => '',
            ];
        }
        if ($metodo === 'Efectivo') {
            $efectivo = $total;
            $devuelta = round($recibido - $total, 2);
            if ($devuelta < -0.009) {
                $error = 'El billete no alcanza para el total.';
            }
        } elseif ($metodo === 'Tarjeta') {
            $tarjeta = $total;
            $recibido = 0;
        } elseif ($metodo === 'Transferencia') {
            $trans = $total;
            $recibido = 0;
        } else {
            if ($transferencia <= 0 || $transferencia >= $total) {
                $error = 'En mixto escribe cuánto va por transferencia. El resto se paga en efectivo.';
            }
            $trans = min($transferencia, $total);
            $efectivo = round($total - $trans, 2);
            $devuelta = round($recibido - $efectivo, 2);
            if ($error === '' && $devuelta < -0.009) {
                $error = 'El efectivo recibido no cubre lo que falta.';
            }
        }
        if ($devuelta < 0) {
            $devuelta = 0;
        }
        return [
            'pago_efectivo' => $efectivo,
            'pago_transferencia' => $trans,
            'pago_tarjeta' => $tarjeta,
            'recibido' => $recibido,
            'devuelta' => $devuelta,
            'error' => $error,
        ];
    }

    private function partesVacias($error) {
        return [
            'pago_efectivo' => 0,
            'pago_transferencia' => 0,
            'pago_tarjeta' => 0,
            'recibido' => 0,
            'devuelta' => 0,
            'error' => $error,
        ];
    }

    private function totalesDeCaja($subtotal, $descuento, $iva, $domicilio, $empaque, $ivaIncluido = false) {
        $subtotal = round(max(0, (float) $subtotal), 2);
        $descuento = round(max(0, (float) $descuento), 2);
        if ($descuento > $subtotal) {
            $descuento = $subtotal;
        }
        $domicilio = round(max(0, (float) $domicilio), 2);
        $empaque = round(max(0, (float) $empaque), 2);
        if ($ivaIncluido) {
            $iva = iva_incluido_en($subtotal - $descuento);
            $total = round(($subtotal - $descuento) + $domicilio + $empaque, 2);
        } else {
            $iva = round(max(0, (float) $iva), 2);
            $total = round(($subtotal - $descuento) + $iva + $domicilio + $empaque, 2);
        }
        return [
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'iva' => $iva,
            'domicilio' => $domicilio,
            'empaque' => $empaque,
            'total' => $total,
        ];
    }

    private function responderVenta($ok, $mensaje) {
        $json = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $ok, 'error' => $mensaje]);
            exit;
        }
        $_SESSION['error'] = $mensaje;
        redirect('index.php?action=ventas');
    }
}