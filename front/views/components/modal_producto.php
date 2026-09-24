<?php
/**
 * Componente Modal Reutilizable para Productos
 * 
 * @param array $producto - Datos del producto (null para crear nuevo)
 * @param array $categorias - Lista de categorías disponibles
 * @param string $modalId - ID único del modal
 * @param string $formId - ID único del formulario
 * @param string $title - Título del modal
 * @param string $action - URL de acción del formulario
 */
if (!isset($categorias) || !is_array($categorias)) {
    $categorias = [];
}
$producto = (isset($producto) && is_array($producto)) ? $producto : null;
$isEdit = $producto !== null;
$productoId = 0;
if (is_array($producto)) {
    $productoId = (int) $producto['id'];
}
$modalId = $modalId ?? 'modalProducto';
$formId = $formId ?? 'formProducto';
$title = $title ?? ($isEdit ? 'Editar Producto' : 'Nuevo Producto');
$action = $action ?? ($productoId > 0 ? BASE_URL . 'index.php?action=productos&method=update&id=' . $productoId : BASE_URL . 'index.php?action=productos&method=store');
?>

<!-- Modal de Producto -->
<div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?php echo $modalId; ?>Label">
                    <i class="bi <?php echo $isEdit ? 'bi-pencil' : 'bi-plus-circle'; ?>"></i> <?php echo htmlspecialchars($title); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="<?php echo $formId; ?>" method="POST" action="<?php echo $action; ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <?php if ($productoId > 0): ?>
                        <input type="hidden" name="id" value="<?php echo $productoId; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="codigo_barras_<?php echo $modalId; ?>" class="form-label">Código de Barras (EAN-13)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="codigo_barras_<?php echo $modalId; ?>" name="codigo_barras" 
                                       value="<?php echo htmlspecialchars($producto['codigo_barras'] ?? ''); ?>" 
                                       required maxlength="13" <?php echo $isEdit ? 'readonly' : ''; ?>>
                                <?php if (!$isEdit): ?>
                                <button type="button" class="btn btn-outline-secondary btnGenerarCodigo" data-target="codigo_barras_<?php echo $modalId; ?>">
                                    <i class="bi bi-arrow-clockwise"></i> Generar
                                </button>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?php echo $isEdit ? 'No se puede modificar' : 'Se genera automáticamente'; ?></small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="nombre_<?php echo $modalId; ?>" class="form-label">Nombre del Producto *</label>
                            <input type="text" class="form-control" id="nombre_<?php echo $modalId; ?>" name="nombre" 
                                   value="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-12">
                            <label for="descripcion_<?php echo $modalId; ?>" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion_<?php echo $modalId; ?>" name="descripcion" rows="2"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="color_<?php echo $modalId; ?>" class="form-label">Color *</label>
                            <input type="text" class="form-control" id="color_<?php echo $modalId; ?>" name="color" 
                                   value="<?php echo htmlspecialchars($producto['color'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Tallas y stock *</label>
                            <div class="lista-tallas">
                                <div class="row g-2 mb-2 fila-talla">
                                    <div class="col-5">
                                        <select class="form-select" name="tallas[]" required>
                                            <option value="">Talla</option>
                                            <option value="0">0</option>
                                            <option value="2">2</option>
                                            <option value="4">4</option>
                                            <option value="6">6</option>
                                            <option value="8">8</option>
                                            <option value="10">10</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <input type="number" class="form-control" name="stocks[]" min="0" value="0" placeholder="Stock" required>
                                    </div>
                                    <div class="col-2 d-flex align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-icono btn-quitar-talla" title="Quitar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="talla_ids[]" value="0">
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-info btn-sm mt-2 btn-otra-talla">
                                <i class="bi bi-plus-circle"></i> Otra talla
                            </button>
                        </div>

                        <div class="col-md-4">
                            <label for="categoria_id_<?php echo $modalId; ?>" class="form-label">Categoría *</label>
                            <div class="input-group">
                                <select class="form-select" id="categoria_id_<?php echo $modalId; ?>" name="categoria_id" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo (isset($producto['categoria_id']) && $producto['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?> (<?php echo $cat['total_productos'] ?? 0; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-icono" data-bs-toggle="modal" data-bs-target="#modalCategorias" title="Categorías">
                                    <i class="bi bi-tags"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="precio_costo_<?php echo $modalId; ?>" class="form-label">Precio de Costo *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="precio_costo_<?php echo $modalId; ?>" name="precio_costo" 
                                       value="<?php echo $producto['precio_costo'] ?? ''; ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="precio_venta_<?php echo $modalId; ?>" class="form-label">Precio de Venta *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="precio_venta_<?php echo $modalId; ?>" name="precio_venta" 
                                       value="<?php echo $producto['precio_venta'] ?? ''; ?>" step="0.01" min="0" required>
                            </div>
                            <p class="mb-0 small text-muted precio-iva-ayuda"></p>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="stock_minimo_<?php echo $modalId; ?>" class="form-label">Stock mínimo</label>
                            <input type="number" class="form-control" id="stock_minimo_<?php echo $modalId; ?>" name="stock_minimo" 
                                   value="<?php echo $producto['stock_minimo'] ?? 0; ?>" min="0">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="origen_<?php echo $modalId; ?>" class="form-label">Origen *</label>
                            <select class="form-select" id="origen_<?php echo $modalId; ?>" name="origen" required>
                                <option value="confeccionado" <?php echo (($producto['origen'] ?? 'confeccionado') !== 'comprado') ? 'selected' : ''; ?>>Confeccionado</option>
                                <option value="comprado" <?php echo (($producto['origen'] ?? '') === 'comprado') ? 'selected' : ''; ?>>Comprado</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="estado_<?php echo $modalId; ?>" class="form-label">Estado</label>
                            <select class="form-select" id="estado_<?php echo $modalId; ?>" name="estado">
                                <option value="Disponible" <?php echo (isset($producto['estado']) && $producto['estado'] == 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                                <option value="Agotado" <?php echo (isset($producto['estado']) && $producto['estado'] == 'Agotado') ? 'selected' : ''; ?>>Agotado</option>
                                <option value="Vendido" <?php echo (isset($producto['estado']) && $producto['estado'] == 'Vendido') ? 'selected' : ''; ?>>Vendido</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <label for="foto_<?php echo $modalId; ?>" class="form-label">Foto del Producto</label>
                            <input type="file" class="form-control" id="foto_<?php echo $modalId; ?>" name="foto" accept="image/*">
                            <small class="text-muted">
                                <?php if ($isEdit): ?>
                                    Dejar vacío para mantener la foto actual
                                    <?php if (!empty($producto['foto'])): ?>
                                        <br>Foto actual: <a href="<?php echo BASE_URL; ?>front/public/uploads/productos/<?php echo htmlspecialchars($producto['foto']); ?>" target="_blank">Ver</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Formatos: JPG, PNG, GIF. Máx. 5MB
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="submit" form="<?php echo $formId; ?>" class="btn btn-primary" id="btnGuardar_<?php echo $modalId; ?>">
                    <i class="bi bi-save"></i> <?php echo $isEdit ? 'Actualizar' : 'Guardar'; ?> Producto
                </button>
            </div>
        </div>
    </div>
</div>

