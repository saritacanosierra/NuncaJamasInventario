<?php
$pageTitle = 'Productos';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($categorias) || !is_array($categorias)) {
    $categorias = [];
}
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-seam"></i> Gestión de Productos</h2>
        <?php if (tienePermiso('productos_categorias:view') || tienePermiso('productos_catalogo:create')): ?>
        <div>
            <?php if (tienePermiso('productos_categorias:view')): ?>
            <button type="button" class="btn btn-outline-secondary btn-icono me-2" data-bs-toggle="modal" data-bs-target="#modalCategorias" title="Categorías">
                <i class="bi bi-tags"></i>
            </button>
            <?php endif; ?>
            <?php if (tienePermiso('productos_catalogo:create')): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                <i class="bi bi-plus-circle"></i> Nuevo Producto
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>index.php" id="formFiltrosProductos" class="row g-3">
                <input type="hidden" name="action" value="productos">
                <div class="col-md-3">
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
                        <?php if (tienePermiso('productos_categorias:view')): ?>
                        <button type="button" class="btn btn-outline-secondary btn-icono" data-bs-toggle="modal" data-bs-target="#modalCategorias" title="Categorías">
                            <i class="bi bi-tags"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="estado" id="selectEstado">
                        <option value="">Todos los estados</option>
                        <option value="Disponible" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                        <option value="Agotado" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Agotado') ? 'selected' : ''; ?>>Agotado</option>
                        <option value="Vendido" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Vendido') ? 'selected' : ''; ?>>Vendido</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="origen" id="selectOrigen">
                        <option value="">Confeccionado y comprado</option>
                        <option value="confeccionado" <?php echo (isset($_GET['origen']) && $_GET['origen'] == 'confeccionado') ? 'selected' : ''; ?>>Confeccionado</option>
                        <option value="comprado" <?php echo (isset($_GET['origen']) && $_GET['origen'] == 'comprado') ? 'selected' : ''; ?>>Comprado</option>
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
                            <th>Origen</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyProductos">
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="12" class="text-center text-muted">No se encontraron productos</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $producto): ?>
                                <tr data-nombre="<?php echo htmlspecialchars(strtolower($producto['nombre'])); ?>"
                                    data-origen="<?php echo ($producto['origen'] ?? '') === 'comprado' ? 'comprado' : 'confeccionado'; ?>"
                                    data-estado="<?php echo htmlspecialchars(strtolower($producto['estado'] ?? '')); ?>"
                                    data-color="<?php echo htmlspecialchars(strtolower($producto['color'])); ?>"
                                    data-talla="<?php echo htmlspecialchars(strtolower(!empty($producto['tallas']) ? implode(' ', array_column($producto['tallas'], 'talla')) : $producto['talla'])); ?>"
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
                                                $barcodeUrl = 'https://barcode.tec-it.com/barcode.ashx?data=' . rawurlencode($producto['codigo_barras']) . '&code=EAN13';
                                            ?>
                                            <div class="mt-1 text-center">
                                                <img src="<?php echo htmlspecialchars($barcodeUrl); ?>"
                                                     alt="Código de barras <?php echo $codigo; ?>"
                                                     class="img-fluid img-barcode-tabla"
                                                     data-codigo="<?php echo $codigo; ?>"
                                                     data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                     title="Ver código de barras">
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['color']); ?></td>
                                    <td>
                                        <?php if (!empty($producto['tallas'])): ?>
                                            <?php foreach ($producto['tallas'] as $tallaFila): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($tallaFila['talla']); ?> · <?php echo (int) $tallaFila['stock']; ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($producto['talla']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'N/A'); ?></td>
                                    <td><?php echo pesos($producto['precio_costo']); ?></td>
                                    <td>
                                        <strong><?php echo pesos(precio_con_iva($producto['precio_venta'])); ?></strong>
                                        <div class="small text-muted">Venta <?php echo pesos($producto['precio_venta']); ?> · IVA <?php echo pesos(iva_de($producto['precio_venta'])); ?></div>
                                    </td>
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
                                        <?php if (($producto['origen'] ?? '') === 'comprado'): ?>
                                            <span class="badge bg-info">Comprado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Confeccionado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (tienePermiso('productos_catalogo:edit')): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary btn-icono btnEditarProducto" 
                                                data-id="<?php echo $producto['id']; ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (tienePermiso('productos_catalogo:delete')): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=productos&method=delete" class="d-inline form-doble-eliminar" data-titulo="Eliminar producto" data-detalle="Se borra el producto y no se puede recuperar." data-codigo="<?php echo htmlspecialchars(trim($producto['codigo_barras'] ?? '') !== '' ? $producto['codigo_barras'] : $producto['nombre']); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $producto['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icono" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if (!tienePermiso('productos_catalogo:edit') && !tienePermiso('productos_catalogo:delete')): ?>
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarProductoLabel">
                    <i class="bi bi-pencil"></i> Editar Producto
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

<div class="modal fade" id="modalVerCodigoProducto" tabindex="-1" aria-labelledby="modalVerCodigoProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerCodigoProductoLabel">
                    <i class="bi bi-upc-scan"></i> Código de barras
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <p id="nombreCodigoProducto" class="fw-semibold mb-2"></p>
                <img id="imgVistaCodigo" src="" alt="Código de barras" class="img-codigo-modal">
                <p id="textoCodigoProducto" class="mt-2 mb-0 font-monospace"></p>
            </div>
            <div class="modal-footer">
                <a id="btnDescargarCodigo" class="btn btn-outline-primary" href="#" download>
                    <i class="bi bi-download"></i> Descargar
                </a>
                <button type="button" class="btn btn-outline-secondary" id="btnImprimirCodigoModal">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
                            <?php if (tienePermiso('productos_categorias:delete')): ?>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger btn-icono btnEliminarCategoria" 
                                    data-id="<?php echo $cat['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($cat['nombre']); ?>"
                                    data-productos="<?php echo $cat['total_productos'] ?? 0; ?>"
                                    title="Eliminar categoría">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
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
                    <i class="bi bi-tags"></i> Nueva Categoría
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
<script src="<?php echo BASE_URL; ?>front/public/js/productos.js?v=7"></script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
