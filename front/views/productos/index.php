<?php
$pageTitle = 'Productos';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-seam"></i> Gestión de Productos</h2>
        <?php if (!isCajero()): ?>
        <div>
            <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#modalCategorias">
                <i class="bi bi-tags"></i> Categorías
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                <i class="bi bi-plus-circle"></i> Nuevo Producto
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>index.php" id="formFiltrosProductos" class="row g-3">
                <input type="hidden" name="action" value="productos">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="busqueda" id="inputBusqueda"
                           placeholder="Buscar por nombre, color, talla, categoría, código de barras..." 
                           value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <select class="form-select" name="categoria_id" id="selectCategoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo (isset($_GET['categoria_id']) && $_GET['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?> (<?php echo $cat['total_productos'] ?? 0; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!isCajero()): ?>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCategorias" title="Gestionar Categorías">
                            <i class="bi bi-tags"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="estado" id="selectEstado">
                        <option value="">Todos los estados</option>
                        <option value="Disponible" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                        <option value="Agotado" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Agotado') ? 'selected' : ''; ?>>Agotado</option>
                        <option value="Vendido" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Vendido') ? 'selected' : ''; ?>>Vendido</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de productos -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Color</th>
                            <th>Talla</th>
                            <th>Categoría</th>
                            <th>Precio Costo</th>
                            <th>Precio Venta</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyProductos">
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted">No se encontraron productos</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $producto): ?>
                                <tr data-nombre="<?php echo htmlspecialchars(strtolower($producto['nombre'])); ?>"
                                    data-color="<?php echo htmlspecialchars(strtolower($producto['color'])); ?>"
                                    data-talla="<?php echo htmlspecialchars(strtolower($producto['talla'])); ?>"
                                    data-categoria="<?php echo htmlspecialchars(strtolower($producto['categoria_nombre'] ?? '')); ?>"
                                    data-codigo="<?php echo htmlspecialchars(strtolower($producto['codigo_barras'])); ?>">
                                    <td>
                                        <?php 
                                        $tieneFoto = !empty($producto['foto']) && trim($producto['foto']) !== '';
                                        if ($tieneFoto): 
                                            $nombreArchivo = trim($producto['foto']);
                                            // La imagen se guarda en UPLOAD_DIR (config.php) = .../public/uploads/productos/
                                            // Para el navegador usamos la ruta absoluta con BASE_URL
                                            $rutaRelativa = BASE_URL . 'front/public/uploads/productos/' . $nombreArchivo;
                                            // Ruta física correcta usando la constante UPLOAD_DIR
                                            $rutaFisica = UPLOAD_DIR . $nombreArchivo;

                                            // Verificar si el archivo realmente existe en disco
                                            if (file_exists($rutaFisica)):
                                        ?>
                                                <img src="<?php echo htmlspecialchars($rutaRelativa); ?>" 
                                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                                     class="img-thumbnail img-producto-thumb" 
                                                     data-imagen="<?php echo htmlspecialchars($rutaRelativa); ?>"
                                                     title="Ver imagen completa"
                                                     onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling && this.nextElementSibling.style.display='inline';"
                                                     loading="lazy">
                                                <i class="bi bi-image text-muted icon-foto-tabla" style="display:none;" title="Error al cargar imagen"></i>
                                        <?php 
                                            else:
                                                // Si no existe el archivo, mostramos solo el ícono genérico
                                        ?>
                                                <i class="bi bi-image text-muted icon-foto-tabla" title="Imagen no encontrada"></i>
                                        <?php 
                                            endif;
                                        else: 
                                            // Si el producto no tiene nombre de archivo en BD, ícono genérico
                                        ?>
                                            <i class="bi bi-image text-muted icon-foto-tabla" title="Sin foto"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($producto['codigo_barras']); ?></code>
                                        <?php if (!empty($producto['codigo_barras'])): ?>
                                            <?php 
                                                $codigo = htmlspecialchars($producto['codigo_barras']);
                                                // Servicio externo para generar imagen EAN-13
                                                $barcodeUrl = 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($codigo) . '&code=EAN13';
                                            ?>
                                            <div class="mt-1 text-center">
                                                <img src="<?php echo $barcodeUrl; ?>"
                                                     alt="Código de barras <?php echo $codigo; ?>"
                                                     class="img-fluid img-barcode-tabla">
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['color']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($producto['talla']); ?></span></td>
                                    <td><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'N/A'); ?></td>
                                    <td>$<?php echo number_format($producto['precio_costo'], 2); ?></td>
                                    <td><strong>$<?php echo number_format($producto['precio_venta'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $producto['stock'] <= $producto['stock_minimo'] ? 'bg-warning' : 'bg-success'; 
                                        ?>">
                                            <?php echo $producto['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $producto['estado'] == 'Disponible' ? 'bg-success' : 
                                                ($producto['estado'] == 'Agotado' ? 'bg-danger' : 'bg-secondary'); 
                                        ?>">
                                            <?php echo htmlspecialchars($producto['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!isCajero()): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary btnEditarProducto" 
                                                data-id="<?php echo $producto['id']; ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=productos&method=delete" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este producto?')">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $producto['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-muted">Solo lectura</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Usar componente modal para nuevo producto
$producto = null;
$modalId = 'modalNuevoProducto';
$formId = 'formNuevoProducto';
$title = 'Nuevo Producto';
$action = BASE_URL . 'index.php?action=productos&method=store';
require_once BASE_DIR . '/front/views/components/modal_producto.php';
?>

<!-- Modal para Editar Producto (se llenará dinámicamente) -->
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="modalEditarProductoLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarProductoLabel">
                    <i class="bi bi-pencil-square"></i> Editar Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalEditarProductoBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-dinamico" id="modalEditarProductoFooter">
                <!-- Se llenará dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal genérico para ver imagen de producto -->
<div class="modal fade" id="modalVerImagenProducto" tabindex="-1" aria-labelledby="modalVerImagenProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerImagenProductoLabel">
                    <i class="bi bi-image"></i> Imagen del producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imgVistaProducto" src="" alt="Imagen del producto" class="img-fluid rounded img-modal-ver-producto">
            </div>
        </div>
    </div>
</div>

<?php
// Modal de Gestión de Categorías usando componente
ob_start();
?>
<!-- Botón para crear nueva categoría -->
<div class="mb-3">
    <button type="button" class="btn btn-primary" id="btnAbrirNuevaCategoria">
        <i class="bi bi-plus-circle"></i> Nueva Categoría
    </button>
</div>

<!-- Tabla de categorías -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th class="text-center">Productos</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody id="tablaCategorias">
            <?php if (empty($categorias)): ?>
                <tr>
                    <td colspan="4" class="text-center categorias-text-muted">No hay categorías registradas</td>
                </tr>
            <?php else: ?>
                <?php foreach ($categorias as $cat): ?>
                    <tr data-categoria-id="<?php echo $cat['id']; ?>">
                        <td><strong class="categorias-text-strong"><?php echo htmlspecialchars($cat['nombre']); ?></strong></td>
                        <td class="categorias-text-muted"><?php echo htmlspecialchars($cat['descripcion'] ?? 'Sin descripción'); ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary categorias-text-strong">
                                <?php echo $cat['total_productos'] ?? 0; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger btnEliminarCategoria" 
                                    data-id="<?php echo $cat['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($cat['nombre']); ?>"
                                    data-productos="<?php echo $cat['total_productos'] ?? 0; ?>"
                                    title="Eliminar categoría">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$bodyCategorias = ob_get_clean();
$footerCategorias = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cerrar</button>';

$modalId = 'modalCategorias';
$title = '<i class="bi bi-tags"></i> Gestión de Categorías';
$body = $bodyCategorias;
$footer = $footerCategorias;
$size = 'lg';
$scrollable = false;
require_once BASE_DIR . '/front/views/components/modal.php';
?>

<!-- Modal para Nueva Categoría -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-labelledby="modalNuevaCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaCategoriaLabel">
                    <i class="bi bi-tag"></i> Nueva Categoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaCategoria">
                    <div class="mb-3">
                        <label for="nombre_categoria" class="form-label">Nombre de la Categoría *</label>
                        <input type="text" class="form-control" id="nombre_categoria" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion_categoria" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion_categoria" name="descripcion" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarCategoria">
                    <i class="bi bi-save"></i> Guardar Categoría
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Pasar BASE_URL al JavaScript -->
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<!-- JavaScript del módulo de productos -->
<script src="<?php echo BASE_URL; ?>front/public/js/productos.js"></script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
