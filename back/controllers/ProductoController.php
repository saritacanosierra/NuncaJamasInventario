<?php
/**
 * Controlador de Productos
 */

class ProductoController {
    private $productoModel;
    private $categoriaModel;
    private $imageUploader;
    
    public function __construct($db) {
        $this->productoModel = new Producto($db);
        $this->categoriaModel = new Categoria($db);
        $this->imageUploader = new ImageUploader();
    }
    
    /**
     * Listar productos
     */
    public function index() {
        requireAuth();
        
        $filters = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'categoria_id' => $_GET['categoria_id'] ?? '',
            'estado' => $_GET['estado'] ?? ''
        ];
        
        $productos = $this->productoModel->getAll($filters);
        $categorias = $this->categoriaModel->getAllWithCount();
        
        require_once BASE_DIR . '/front/views/productos/index.php';
    }
    
    /**
     * Generar código de barras (API para modal)
     */
    public function generarCodigo() {
        // Iniciar output buffering al inicio
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        try {
            $codigo = BarcodeGenerator::generateEAN13();
            
            // Verificar que no exista
            while ($this->productoModel->codigoExiste($codigo)) {
                $codigo = BarcodeGenerator::generateEAN13();
            }
            
            echo json_encode(['success' => true, 'codigo' => $codigo]);
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en generarCodigo: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al generar código']);
        }
        
        exit;
    }
    
    /**
     * Guardar nuevo producto
     */
    public function store() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=productos');
        }
        
        // Validar y sanitizar datos
        $data = [
            'codigo_barras' => $_POST['codigo_barras'] ?? '',
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'color' => trim($_POST['color'] ?? ''),
            'talla' => trim($_POST['talla'] ?? ''),
            'precio_costo' => floatval($_POST['precio_costo'] ?? 0),
            'precio_venta' => floatval($_POST['precio_venta'] ?? 0),
            'categoria_id' => intval($_POST['categoria_id'] ?? 0),
            'stock' => intval($_POST['stock'] ?? 0),
            'stock_minimo' => intval($_POST['stock_minimo'] ?? 5),
            'estado' => $_POST['estado'] ?? 'Disponible',
            'foto' => null
        ];
        
        // Validaciones
        if (empty($data['nombre']) || empty($data['codigo_barras'])) {
            $_SESSION['error'] = 'El nombre y código de barras son obligatorios';
            redirect('index.php?action=productos');
        }
        
        if (empty($data['categoria_id']) || $data['categoria_id'] == 0) {
            $_SESSION['error'] = 'Debe seleccionar una categoría';
            redirect('index.php?action=productos');
        }
        
        // Verificar si código ya existe
        if ($this->productoModel->codigoExiste($data['codigo_barras'])) {
            $_SESSION['error'] = 'El código de barras ya existe';
            redirect('index.php?action=productos');
        }
        
        // Subir imagen si existe
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload = $this->imageUploader->upload($_FILES['foto'], 'producto');
            if ($upload['success']) {
                $data['foto'] = $upload['filename'];
            }
        }
        
        // Crear producto
        try {
            // Verificar duplicado una vez más antes de crear (doble verificación)
            if ($this->productoModel->codigoExiste($data['codigo_barras'])) {
                $_SESSION['error'] = 'El código de barras ya existe. Por favor, use un código diferente.';
                redirect('index.php?action=productos');
            }
            
            $result = $this->productoModel->create($data);
            
            // El método create() ahora retorna el ID del producto o false
            if ($result && $result > 0) {
                $_SESSION['success'] = 'Producto creado exitosamente';
                redirect('index.php?action=productos');
            } else {
                // Verificar si el error fue por duplicado
                if ($this->productoModel->codigoExiste($data['codigo_barras'])) {
                    $_SESSION['error'] = 'El código de barras ya existe. El producto no se pudo crear.';
                } else {
                    $_SESSION['error'] = 'Error al crear el producto. Verifique los datos e intente nuevamente.';
                }
                redirect('index.php?action=productos');
            }
        } catch (PDOException $e) {
            error_log("ProductoController::store() - PDOException: " . $e->getMessage());
            // Verificar si es un error de duplicado
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
                $_SESSION['error'] = 'El código de barras ya existe. Por favor, use un código diferente.';
            } else {
                $_SESSION['error'] = 'Error al crear el producto: ' . $e->getMessage();
            }
            redirect('index.php?action=productos');
        } catch (Exception $e) {
            error_log("ProductoController::store() - Exception: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear el producto';
            redirect('index.php?action=productos');
        }
    }
    
    /**
     * Mostrar formulario de editar (API para modal)
     */
    public function edit() {
        // Iniciar output buffering al inicio
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        $id = intval($_GET['id'] ?? 0);
        
        try {
            $producto = $this->productoModel->getById($id);
            
            if (!$producto) {
                echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
                exit;
            }
            
            $categorias = $this->categoriaModel->getAllWithCount();
            
            echo json_encode([
                'success' => true, 
                'producto' => $producto,
                'categorias' => $categorias
            ]);
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en edit: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al cargar el producto']);
        }
        
        exit;
    }
    
    /**
     * Actualizar producto
     */
    public function update() {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=productos');
        }
        
        $id = intval($_POST['id'] ?? 0);
        $producto = $this->productoModel->getById($id);
        
        if (!$producto) {
            $_SESSION['error'] = 'Producto no encontrado';
            redirect('index.php?action=productos');
        }
        
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'color' => trim($_POST['color'] ?? ''),
            'talla' => trim($_POST['talla'] ?? ''),
            'precio_costo' => floatval($_POST['precio_costo'] ?? 0),
            'precio_venta' => floatval($_POST['precio_venta'] ?? 0),
            'categoria_id' => intval($_POST['categoria_id'] ?? 0),
            'stock' => intval($_POST['stock'] ?? 0),
            'stock_minimo' => intval($_POST['stock_minimo'] ?? 5),
            'estado' => $_POST['estado'] ?? 'Disponible',
            'foto' => $producto['foto'] // Mantener foto actual
        ];
        
        // Subir nueva imagen si existe
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            // Eliminar foto anterior
            if ($producto['foto']) {
                $this->imageUploader->delete($producto['foto']);
            }
            
            $upload = $this->imageUploader->upload($_FILES['foto'], 'producto');
            if ($upload['success']) {
                $data['foto'] = $upload['filename'];
            }
        }
        
        if ($this->productoModel->update($id, $data)) {
            $_SESSION['success'] = 'Producto actualizado exitosamente';
            redirect('index.php?action=productos');
        } else {
            $_SESSION['error'] = 'Error al actualizar el producto';
            redirect('index.php?action=productos');
        }
    }
    
    /**
     * Eliminar producto
     */
    public function delete() {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=productos');
        }
        
        $id = intval($_POST['id'] ?? 0);
        $producto = $this->productoModel->getById($id);
        
        if (!$producto) {
            $_SESSION['error'] = 'Producto no encontrado';
            redirect('index.php?action=productos');
        }
        
        // Eliminar imagen
        if ($producto['foto']) {
            $this->imageUploader->delete($producto['foto']);
        }
        
        if ($this->productoModel->delete($id)) {
            $_SESSION['success'] = 'Producto eliminado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar el producto';
        }
        
        redirect('index.php?action=productos');
    }
    
    /**
     * Buscar producto por código de barras (API para ventas)
     */
    public function buscarPorCodigo() {
        // Iniciar output buffering al inicio
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        $codigo = $_GET['codigo'] ?? '';
        
        if (empty($codigo)) {
            echo json_encode(['success' => false, 'message' => 'Código requerido']);
            exit;
        }
        
        try {
            $producto = $this->productoModel->getByCodigoBarras($codigo);
            
            if ($producto) {
                echo json_encode(['success' => true, 'producto' => $producto]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            }
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en buscarPorCodigo: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al buscar producto']);
        }
        
        exit;
    }
    
    /**
     * Buscar productos por nombre (API para ventas)
     */
    public function buscarPorNombre() {
        // Iniciar output buffering al inicio
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        $termino = $_GET['termino'] ?? '';
        
        if (empty($termino)) {
            echo json_encode(['success' => false, 'productos' => []]);
            exit;
        }
        
        try {
            // Usar búsqueda específica para punto de venta para evitar problemas de parámetros
            $productos = $this->productoModel->buscarParaVentas($termino);
            
            echo json_encode(['success' => true, 'productos' => $productos]);
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en buscarPorNombre: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al buscar productos']);
        }
        
        exit;
    }
    
    /**
     * Crear categoría (API para modal)
     */
    public function crearCategoria() {
        // Iniciar output buffering al inicio
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'error' => 'El nombre de la categoría es obligatorio']);
            exit;
        }
        
        try {
            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion
            ];
            
            $id = $this->categoriaModel->create($data);
            
            if ($id && $id > 0) {
                // Obtener la categoría creada con su conteo
                $categoria = $this->categoriaModel->getById($id);
                if ($categoria) {
                    $categoria['total_productos'] = 0; // Nueva categoría, sin productos aún
                    
                    echo json_encode([
                        'success' => true, 
                        'categoria' => $categoria,
                        'message' => 'Categoría creada exitosamente'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Categoría creada pero no se pudo recuperar']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al crear la categoría. Verifique que el nombre no esté duplicado.']);
            }
        } catch (PDOException $e) {
            ob_clean();
            @error_log('Error de base de datos en crearCategoria: ' . $e->getMessage());
            header('Content-Type: application/json');
            
            // Verificar si es un error de duplicado
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            if ($errorCode == 23000 || strpos($errorMessage, 'Duplicate') !== false || strpos($errorMessage, 'duplicate') !== false || strpos($errorMessage, 'UNIQUE') !== false) {
                echo json_encode(['success' => false, 'error' => 'Ya existe una categoría con ese nombre']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos. Verifique la configuración.']);
            }
        } catch (Exception $e) {
            ob_clean();
            @error_log('Error en crearCategoria: ' . $e->getMessage());
            header('Content-Type: application/json');
            
            // Verificar si el mensaje indica un duplicado
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Ya existe') !== false || strpos($errorMessage, 'duplicado') !== false || strpos($errorMessage, 'duplicate') !== false) {
                echo json_encode(['success' => false, 'error' => 'Ya existe una categoría con ese nombre']);
            } else {
                echo json_encode(['success' => false, 'error' => $errorMessage ?: 'Error al crear la categoría']);
            }
        }
        
        exit;
    }
    
    /**
     * Obtener categorías con conteo (API)
     */
    public function obtenerCategorias() {
        // Iniciar output buffering al inicio
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        try {
            $categorias = $this->categoriaModel->getAllWithCount();
            
            echo json_encode(['success' => true, 'categorias' => $categorias]);
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en obtenerCategorias: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al obtener categorías']);
        }
        
        exit;
    }
    
    /**
     * Eliminar categoría (API)
     */
    public function eliminarCategoria() {
        // Iniciar output buffering al inicio
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Verificar autenticación sin redirecciones para API JSON
        if (!isset($_SESSION['usuario_id'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }
        
        // Limpiar cualquier output previo antes de enviar headers
        ob_clean();
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        
        // Obtener el ID y validar
        $idRaw = $_POST['id'] ?? null;
        
        // Validar que el ID esté presente y sea válido
        if ($idRaw === null || $idRaw === '') {
            echo json_encode([
                'success' => false, 
                'error' => 'ID de categoría no proporcionado'
            ]);
            exit;
        }
        
        // Convertir a entero y validar
        $id = filter_var($idRaw, FILTER_VALIDATE_INT);
        
        if ($id === false || $id <= 0) {
            echo json_encode([
                'success' => false, 
                'error' => 'ID de categoría inválido: ' . htmlspecialchars($idRaw)
            ]);
            exit;
        }
        
        // Verificar que la categoría existe antes de intentar eliminarla
        $categoria = $this->categoriaModel->getById($id);
        if (!$categoria) {
            echo json_encode([
                'success' => false, 
                'error' => 'La categoría no existe'
            ]);
            exit;
        }
        
        try {
            $result = $this->categoriaModel->delete($id);
            
            echo json_encode($result);
        } catch (Exception $e) {
            ob_clean();
            error_log('Error en eliminarCategoria: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al eliminar categoría: ' . $e->getMessage()]);
        }
        
        exit;
    }
}