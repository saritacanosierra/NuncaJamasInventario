<?php
/**
 * Controlador de Gastos
 */

class GastoController {
    private $gastoModel;
    private $categoriaGastoModel;
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->gastoModel = new Gasto($db);
        require_once BASE_DIR . '/back/models/CategoriaGasto.php';
        $this->categoriaGastoModel = new CategoriaGasto($db);
    }
    
    /**
     * Listar gastos
     */
    public function index() {
        requireAuth();
        
        $filters = [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'categoria' => $_GET['categoria'] ?? ''
        ];
        
        // Si no hay filtros, mostrar mes actual
        if (empty($filters['fecha_desde'])) {
            $filters['fecha_desde'] = date('Y-m-01');
            $filters['fecha_hasta'] = date('Y-m-t');
        }
        
        $gastos = $this->gastoModel->getAll($filters);
        $totalGastos = $this->gastoModel->getTotalPorPeriodo(
            $filters['fecha_desde'], 
            $filters['fecha_hasta'] ?: date('Y-m-d')
        );
        $gastosPorCategoria = $this->gastoModel->getGastosPorCategoria(
            $filters['fecha_desde'], 
            $filters['fecha_hasta'] ?: date('Y-m-d')
        );
        
        // Cargar categorías para los selects
        $categorias = $this->categoriaGastoModel->getAll();
        
        // Cargar inversiones
        require_once BASE_DIR . '/back/models/Inversion.php';
        $inversionModel = new Inversion($this->db);
        $inversiones = $inversionModel->getAll($filters);
        $totalInversiones = $inversionModel->getTotalPorPeriodo(
            $filters['fecha_desde'], 
            $filters['fecha_hasta'] ?: date('Y-m-d')
        );
        $inversionesPorCategoria = $inversionModel->getInversionesPorCategoria(
            $filters['fecha_desde'], 
            $filters['fecha_hasta'] ?: date('Y-m-d')
        );
        
        require_once BASE_DIR . '/front/views/gastos/index.php';
    }
    
    /**
     * Guardar nuevo gasto
     */
    public function store() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=gastos');
        }
        exigir_csrf_redirect('index.php?action=gastos');
        
        $data = [
            'concepto' => trim($_POST['concepto'] ?? ''),
            'monto' => floatval($_POST['monto'] ?? 0),
            'categoria' => $_POST['categoria'] ?? 'Otros',
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'usuario_id' => $_SESSION['usuario_id']
        ];
        
        if (empty($data['concepto']) || $data['monto'] <= 0) {
            $_SESSION['error'] = 'El concepto y monto son obligatorios';
            redirect('index.php?action=gastos');
        }
        
        if ($this->gastoModel->create($data)) {
            $_SESSION['success'] = 'Gasto registrado exitosamente';
            redirect('index.php?action=gastos');
        } else {
            $_SESSION['error'] = 'Error al registrar el gasto';
            redirect('index.php?action=gastos');
        }
    }
    
    /**
     * Obtener gasto por ID (API para modal)
     */
    public function getGasto() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $id = intval($_GET['id'] ?? 0);
        $gasto = $this->gastoModel->getById($id);
        
        if ($gasto) {
            echo json_encode(['success' => true, 'gasto' => $gasto]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gasto no encontrado']);
        }
        exit;
    }
    
    /**
     * Actualizar gasto
     */
    public function update() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=gastos');
        }
        exigir_csrf_redirect('index.php?action=gastos');
        
        $id = intval($_POST['id'] ?? 0);
        $gasto = $this->gastoModel->getById($id);
        
        if (!$gasto) {
            $_SESSION['error'] = 'Gasto no encontrado';
            redirect('index.php?action=gastos');
        }
        
        $data = [
            'concepto' => trim($_POST['concepto'] ?? ''),
            'monto' => floatval($_POST['monto'] ?? 0),
            'categoria' => $_POST['categoria'] ?? 'Otros',
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'descripcion' => trim($_POST['descripcion'] ?? '')
        ];
        
        if ($this->gastoModel->update($id, $data)) {
            $_SESSION['success'] = 'Gasto actualizado exitosamente';
            redirect('index.php?action=gastos');
        } else {
            $_SESSION['error'] = 'Error al actualizar el gasto';
            redirect('index.php?action=gastos&method=edit&id=' . $id);
        }
    }
    
    /**
     * Eliminar gasto
     */
    public function delete() {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=gastos');
        }
        
        $id = intval($_POST['id'] ?? 0);
        $gasto = $this->gastoModel->getById($id);
        if (!$gasto) {
            $_SESSION['error'] = 'Gasto no encontrado';
            redirect('index.php?action=gastos');
        }
        if (!codigo_eliminacion_valido($gasto['concepto'] ?? '')) {
            $_SESSION['error'] = 'La confirmación no coincide. No se eliminó.';
            redirect('index.php?action=gastos');
        }
        
        if ($this->gastoModel->delete($id)) {
            $_SESSION['success'] = 'Gasto eliminado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar el gasto';
        }
        
        redirect('index.php?action=gastos');
    }
    
    /**
     * Obtener todas las categorías (API)
     */
    public function obtenerCategorias() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $categorias = $this->categoriaGastoModel->getAll();
        
        echo json_encode(['success' => true, 'categorias' => $categorias]);
        exit;
    }
    
    /**
     * Crear categoría (API)
     */
    public function crearCategoria() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
            exit;
        }
        
        $data = [
            'nombre' => $nombre,
            'descripcion' => trim($_POST['descripcion'] ?? '')
        ];
        
        $id = $this->categoriaGastoModel->create($data);
        
        if ($id) {
            $categoria = $this->categoriaGastoModel->getById($id);
            echo json_encode([
                'success' => true,
                'categoria' => $categoria,
                'message' => 'Categoría creada exitosamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear la categoría']);
        }
        exit;
    }
    
    /**
     * Actualizar categoría (API)
     */
    public function actualizarCategoria() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
            exit;
        }
        
        $data = [
            'nombre' => $nombre,
            'descripcion' => trim($_POST['descripcion'] ?? '')
        ];
        
        if ($this->categoriaGastoModel->update($id, $data)) {
            $categoria = $this->categoriaGastoModel->getById($id);
            echo json_encode([
                'success' => true,
                'categoria' => $categoria,
                'message' => 'Categoría actualizada exitosamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar la categoría']);
        }
        exit;
    }
    
    /**
     * Eliminar categoría (API)
     */
    public function eliminarCategoria() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        exigir_csrf_json();
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de categoría inválido']);
            exit;
        }

        $categoria = $this->categoriaGastoModel->getById($id);
        if (!$categoria || !codigo_eliminacion_valido($categoria['nombre'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'La confirmación no coincide. No se eliminó.']);
            exit;
        }
        
        $result = $this->categoriaGastoModel->delete($id);
        
        echo json_encode($result);
        exit;
    }
    
    /**
     * Obtener categoría por ID (API)
     */
    public function getCategoria() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        $id = intval($_GET['id'] ?? 0);
        $categoria = $this->categoriaGastoModel->getById($id);
        
        if ($categoria) {
            echo json_encode(['success' => true, 'categoria' => $categoria]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Categoría no encontrada']);
        }
        exit;
    }
    
    // ========== MÉTODOS DE INVERSIONES ==========
    
    /**
     * Guardar nueva inversión
     */
    public function storeInversion() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=gastos');
        }
        exigir_csrf_redirect('index.php?action=gastos');
        
        require_once BASE_DIR . '/back/models/Inversion.php';
        $inversionModel = new Inversion($this->db);
        
        $data = [
            'concepto' => trim($_POST['concepto'] ?? ''),
            'monto' => floatval($_POST['monto'] ?? 0),
            'categoria' => $_POST['categoria'] ?? 'Otros',
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'usuario_id' => $_SESSION['usuario_id']
        ];
        
        if (empty($data['concepto']) || $data['monto'] <= 0) {
            $_SESSION['error'] = 'El concepto y monto son obligatorios';
            redirect('index.php?action=gastos');
        }

        if ($this->inversionDeProducto($data['categoria'])) {
            $_SESSION['error'] = 'La mercancía se registra en Compras de producto. Así entra al inventario y también suma como inversión.';
            redirect('index.php?action=compras');
        }
        
        if ($inversionModel->create($data)) {
            $_SESSION['success'] = 'Inversión registrada exitosamente';
            redirect('index.php?action=gastos');
        } else {
            $_SESSION['error'] = 'Error al registrar la inversión';
            redirect('index.php?action=gastos');
        }
    }
    
    /**
     * Obtener inversión por ID (API para modal)
     */
    public function getInversion() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        require_once BASE_DIR . '/back/models/Inversion.php';
        $inversionModel = new Inversion($this->db);
        
        $id = intval($_GET['id'] ?? 0);
        $inversion = $inversionModel->getById($id);
        
        if ($inversion) {
            echo json_encode(['success' => true, 'inversion' => $inversion]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Inversión no encontrada']);
        }
        exit;
    }
    
    /**
     * Actualizar inversión
     */
    public function updateInversion() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=gastos');
        }
        exigir_csrf_redirect('index.php?action=gastos');
        
        require_once BASE_DIR . '/back/models/Inversion.php';
        $inversionModel = new Inversion($this->db);
        
        $id = intval($_POST['id'] ?? 0);
        $inversion = $inversionModel->getById($id);
        
        if (!$inversion) {
            $_SESSION['error'] = 'Inversión no encontrada';
            redirect('index.php?action=gastos');
        }
        
        $data = [
            'concepto' => trim($_POST['concepto'] ?? ''),
            'monto' => floatval($_POST['monto'] ?? 0),
            'categoria' => $_POST['categoria'] ?? 'Otros',
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'descripcion' => trim($_POST['descripcion'] ?? '')
        ];

        if ($this->inversionDeProducto($data['categoria']) && !$this->inversionDeProducto($inversion['categoria'])) {
            $_SESSION['error'] = 'La mercancía se registra en Compras de producto. Así entra al inventario y también suma como inversión.';
            redirect('index.php?action=compras');
        }
        
        if ($inversionModel->update($id, $data)) {
            $_SESSION['success'] = 'Inversión actualizada exitosamente';
            redirect('index.php?action=gastos');
        } else {
            $_SESSION['error'] = 'Error al actualizar la inversión';
            redirect('index.php?action=gastos');
        }
    }
    
    /**
     * Eliminar inversión
     */
    public function deleteInversion() {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=gastos');
        }
        
        require_once BASE_DIR . '/back/models/Inversion.php';
        $inversionModel = new Inversion($this->db);
        
        $id = intval($_POST['id'] ?? 0);
        $inversion = $inversionModel->getById($id);
        if (!$inversion) {
            $_SESSION['error'] = 'Inversión no encontrada';
            redirect('index.php?action=gastos');
        }
        if (!codigo_eliminacion_valido($inversion['concepto'] ?? '')) {
            $_SESSION['error'] = 'La confirmación no coincide. No se eliminó.';
            redirect('index.php?action=gastos');
        }
        
        if ($inversionModel->delete($id)) {
            $_SESSION['success'] = 'Inversión eliminada exitosamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar la inversión';
        }
        
        redirect('index.php?action=gastos');
    }
    
    /**
     * Obtener historial (API)
     */
    public function obtenerHistorial() {
        requireAuth();
        
        header('Content-Type: application/json');
        
        require_once BASE_DIR . '/back/models/Inversion.php';
        $inversionModel = new Inversion($this->db);
        
        $gastosPorMes = $this->gastoModel->getGastosPorMes();
        $gastosPorAnio = $this->gastoModel->getGastosPorAnio();
        $inversionesPorMes = $inversionModel->getInversionesPorMes();
        $inversionesPorAnio = $inversionModel->getInversionesPorAnio();
        
        echo json_encode([
            'success' => true,
            'gastos_por_mes' => $gastosPorMes,
            'gastos_por_anio' => $gastosPorAnio,
            'inversiones_por_mes' => $inversionesPorMes,
            'inversiones_por_anio' => $inversionesPorAnio
        ]);
        exit;
    }

    private function inversionDeProducto($categoria) {
        return strtolower(trim((string) $categoria)) === 'producto';
    }
}