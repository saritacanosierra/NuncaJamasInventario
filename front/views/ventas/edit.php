<?php
$pageTitle = 'Editar Venta';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil"></i> Editar Venta - <?php echo htmlspecialchars($venta['numero_factura']); ?></h2>
        <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=historial" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Historial
        </a>
    </div>
    
    <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=ventas&method=update" id="formEditarVenta">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="venta_id" value="<?php echo $venta['id']; ?>">
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-info-circle"></i> Información de la Venta
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cliente_id" class="form-label">Cliente *</label>
                                <select class="form-select" name="cliente_id" id="cliente_id" required>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?php echo $cliente['id']; ?>" 
                                                <?php echo ($venta['cliente_id'] == $cliente['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cliente['nombre_completo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="metodo_pago" class="form-label">Método de Pago *</label>
                                <select class="form-select" name="metodo_pago" id="metodo_pago" required>
                                    <option value="Efectivo" <?php echo ($venta['metodo_pago'] == 'Efectivo') ? 'selected' : ''; ?>>Efectivo</option>
                                    <option value="Tarjeta" <?php echo ($venta['metodo_pago'] == 'Tarjeta') ? 'selected' : ''; ?>>Tarjeta</option>
                                    <option value="Transferencia" <?php echo ($venta['metodo_pago'] == 'Transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                                    <option value="Mixto" <?php echo ($venta['metodo_pago'] == 'Mixto') ? 'selected' : ''; ?>>Mixto</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="descuento" class="form-label">Descuento</label>
                                <input type="number" class="form-control" name="descuento" id="descuento" 
                                       value="<?php echo number_format($venta['descuento'], 2, '.', ''); ?>" 
                                       min="0" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="con_domicilio" 
                                           id="con_domicilio" value="1" 
                                           <?php echo (!empty($venta['con_domicilio'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="con_domicilio">
                                        <i class="bi bi-truck"></i> Con Domicilio
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="div_estado_pago" class="ms-4">
                            <label class="form-label small mb-1">Estado de Pago</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="estado_pago" id="pago_pagado_edit" value="pagado" 
                                       <?php echo (empty($venta['pago_contra_entrega']) || $venta['pago_contra_entrega'] == 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pago_pagado_edit">
                                    <i class="bi bi-check-circle"></i> Pagado
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="estado_pago" id="pago_contra_entrega_edit" value="contra_entrega"
                                       <?php echo (!empty($venta['pago_contra_entrega']) && $venta['pago_contra_entrega'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pago_contra_entrega_edit">
                                    <i class="bi bi-clock"></i> Pago Contra Entrega
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3 <?php echo (!empty($venta['con_domicilio'])) ? 'show' : 'hide'; ?>" id="div_observaciones_domicilio">
                            <label for="observaciones_domicilio" class="form-label">
                                <i class="bi bi-info-circle"></i> Observaciones para el Domiciliario
                            </label>
                            <textarea class="form-control" name="observaciones_domicilio" 
                                      id="observaciones_domicilio" rows="3"
                                      placeholder="Ej: Llamar antes de llegar, entregar en portería, etc."><?php echo htmlspecialchars($venta['observaciones_domicilio'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-cart"></i> Productos de la Venta
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaDetalles">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Subtotal</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="detalles_tbody">
                                    <?php foreach ($venta['detalles'] as $index => $detalle): ?>
                                        <tr data-producto-id="<?php echo $detalle['producto_id']; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($detalle['producto_nombre']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($detalle['color']); ?> - 
                                                    <?php echo htmlspecialchars($detalle['talla']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm cantidad-detalle" 
                                                       name="detalles[<?php echo $index; ?>][cantidad]" 
                                                       value="<?php echo $detalle['cantidad']; ?>" 
                                                       min="1" data-precio="<?php echo $detalle['precio_unitario']; ?>" 
                                                       data-index="<?php echo $index; ?>" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm precio-detalle" 
                                                       name="detalles[<?php echo $index; ?>][precio_unitario]" 
                                                       value="<?php echo number_format($detalle['precio_unitario'], 2, '.', ''); ?>" 
                                                       min="0" step="0.01" data-index="<?php echo $index; ?>" required>
                                            </td>
                                            <td>
                                                <span class="subtotal-detalle" data-index="<?php echo $index; ?>">
                                                    <?php echo pesos($detalle['subtotal']); ?>
                                                </span>
                                                <input type="hidden" name="detalles[<?php echo $index; ?>][producto_id]" 
                                                       value="<?php echo $detalle['producto_id']; ?>">
                                                <input type="hidden" name="detalles[<?php echo $index; ?>][subtotal]" 
                                                       class="subtotal-input" data-index="<?php echo $index; ?>" 
                                                       value="<?php echo number_format($detalle['subtotal'], 2, '.', ''); ?>">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger btn-eliminar-detalle btn-icono">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td><strong id="subtotal_total"><?php echo pesos($venta['subtotal']); ?></strong></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Descuento:</strong></td>
                                        <td><strong id="descuento_total"><?php echo pesos(-($venta['descuento'])); ?></strong></td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-success">
                                        <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                        <td><strong class="h4" id="total_venta"><?php echo pesos($venta['total']); ?></strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary" id="btnAgregarProducto">
                                <i class="bi bi-plus-circle"></i> Agregar Producto
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-info-circle"></i> Información
                    </div>
                    <div class="card-body">
                        <p><strong>Número de Factura:</strong><br>
                        <code><?php echo htmlspecialchars($venta['numero_factura']); ?></code></p>
                        <p><strong>Fecha de Venta:</strong><br>
                        <?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></p>
                        <p><strong>Vendedor:</strong><br>
                        <?php echo htmlspecialchars($venta['vendedor']); ?></p>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                        <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=historial" class="btn btn-secondary w-100">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal para agregar producto -->
<div class="modal fade" id="modalAgregarProducto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="producto_select" class="form-label">Producto</label>
                    <select class="form-select" id="producto_select">
                        <option value="">Seleccionar producto...</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?php echo $producto['id']; ?>" 
                                    data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                    data-color="<?php echo htmlspecialchars($producto['color']); ?>"
                                    data-talla="<?php echo htmlspecialchars($producto['talla']); ?>"
                                    data-precio="<?php echo $producto['precio_venta']; ?>"
                                    data-stock="<?php echo $producto['stock']; ?>">
                                <?php echo htmlspecialchars($producto['nombre']); ?> - 
                                <?php echo htmlspecialchars($producto['color']); ?> - 
                                <?php echo htmlspecialchars($producto['talla']); ?> 
                                (Stock: <?php echo $producto['stock']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="cantidad_nueva" class="form-label">Cantidad</label>
                    <input type="number" class="form-control" id="cantidad_nueva" value="1" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarAgregar">Agregar</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let contadorDetalles = <?php echo count($venta['detalles']); ?>;

// Mostrar/ocultar campo de observaciones
document.getElementById('con_domicilio').addEventListener('change', function() {
    const div = document.getElementById('div_observaciones_domicilio');
    div.style.display = this.checked ? 'block' : 'none';
    if (!this.checked) {
        document.getElementById('observaciones_domicilio').value = '';
    }
});

// Función para formatear números sin decimales, con punto como separador de miles
function formatearNumero(valor) {
    const numero = Math.round(parseFloat(valor) || 0);
    return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Calcular subtotales cuando cambian cantidad o precio
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('cantidad-detalle') || e.target.classList.contains('precio-detalle')) {
        const index = e.target.dataset.index;
        const cantidad = parseFloat(document.querySelector(`.cantidad-detalle[data-index="${index}"]`).value) || 0;
        const precio = parseFloat(document.querySelector(`.precio-detalle[data-index="${index}"]`).value) || 0;
        const subtotal = cantidad * precio;
        
        document.querySelector(`.subtotal-detalle[data-index="${index}"]`).textContent = '$' + formatearNumero(subtotal);
        document.querySelector(`.subtotal-input[data-index="${index}"]`).value = subtotal.toFixed(2);
        
        actualizarTotales();
    }
    
    if (e.target.id === 'descuento') {
        actualizarTotales();
    }
});

// Actualizar totales
function actualizarTotales() {
    let subtotal = 0;
    document.querySelectorAll('.subtotal-input').forEach(input => {
        subtotal += parseFloat(input.value) || 0;
    });
    
    const descuento = parseFloat(document.getElementById('descuento').value) || 0;
    const total = subtotal - descuento;
    
    document.getElementById('subtotal_total').textContent = '$' + formatearNumero(subtotal);
    document.getElementById('descuento_total').textContent = '-$' + formatearNumero(descuento);
    document.getElementById('total_venta').textContent = '$' + formatearNumero(total > 0 ? total : 0);
}

// Eliminar detalle
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-eliminar-detalle');
    if (!btn) return;
    const fila = btn.closest('tr');
    const nombre = fila && fila.querySelector('strong') ? fila.querySelector('strong').textContent.trim() : '';
    if (!nombre || typeof pedirDobleConfirmacion !== 'function') return;
    pedirDobleConfirmacion({
        titulo: 'Quitar producto',
        detalle: 'Se quita este producto de la venta.',
        codigo: nombre,
        alConfirmar: function () {
            fila.remove();
            actualizarTotales();
        }
    });
});

// Agregar producto
document.getElementById('btnAgregarProducto').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalAgregarProducto'));
    modal.show();
});

document.getElementById('btnConfirmarAgregar').addEventListener('click', function() {
    const productoSelect = document.getElementById('producto_select');
    const cantidad = parseInt(document.getElementById('cantidad_nueva').value);
    
    if (!productoSelect.value || cantidad < 1) {
        alert('Seleccione un producto y una cantidad válida');
        return;
    }
    
    const option = productoSelect.options[productoSelect.selectedIndex];
    const productoId = productoSelect.value;
    const nombre = option.dataset.nombre;
    const color = option.dataset.color;
    const talla = option.dataset.talla;
    const precio = parseFloat(option.dataset.precio);
    const stock = parseInt(option.dataset.stock);
    
    if (cantidad > stock) {
        alert('No hay suficiente stock disponible');
        return;
    }
    
    // Verificar si el producto ya está en la lista
    const existe = document.querySelector(`tr[data-producto-id="${productoId}"]`);
    if (existe) {
        alert('Este producto ya está en la venta');
        return;
    }
    
    // Agregar fila
    const tbody = document.getElementById('detalles_tbody');
    const subtotal = cantidad * precio;
    const row = `
        <tr data-producto-id="${productoId}">
            <td>
                <strong>${nombre}</strong><br>
                <small class="text-muted">${color} - ${talla}</small>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm cantidad-detalle" 
                       name="detalles[${contadorDetalles}][cantidad]" 
                       value="${cantidad}" min="1" 
                       data-precio="${precio}" data-index="${contadorDetalles}" required>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm precio-detalle" 
                       name="detalles[${contadorDetalles}][precio_unitario]" 
                       value="${precio.toFixed(2)}" min="0" step="0.01" 
                       data-index="${contadorDetalles}" required>
            </td>
            <td>
                <span class="subtotal-detalle" data-index="${contadorDetalles}">
                    $${formatearNumero(subtotal)}
                </span>
                <input type="hidden" name="detalles[${contadorDetalles}][producto_id]" 
                       value="${productoId}">
                <input type="hidden" name="detalles[${contadorDetalles}][subtotal]" 
                       class="subtotal-input" data-index="${contadorDetalles}" 
                       value="${subtotal.toFixed(2)}">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger btn-eliminar-detalle btn-icono">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    tbody.insertAdjacentHTML('beforeend', row);
    contadorDetalles++;
    
    actualizarTotales();
    
    bootstrap.Modal.getInstance(document.getElementById('modalAgregarProducto')).hide();
    productoSelect.value = '';
    document.getElementById('cantidad_nueva').value = 1;
});

// Enviar formulario con detalles en JSON
document.getElementById('formEditarVenta').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const detalles = [];
    document.querySelectorAll('#detalles_tbody tr').forEach(tr => {
        const productoId = tr.querySelector('input[name*="[producto_id]"]').value;
        const cantidad = tr.querySelector('input[name*="[cantidad]"]').value;
        const precioUnitario = tr.querySelector('input[name*="[precio_unitario]"]').value;
        const subtotal = tr.querySelector('.subtotal-input').value;
        
        detalles.push({
            producto_id: productoId,
            cantidad: parseInt(cantidad),
            precio_unitario: parseFloat(precioUnitario),
            subtotal: parseFloat(subtotal)
        });
    });
    
    // Crear FormData
    const formData = new FormData(this);
    formData.delete('detalles[]'); // Eliminar los detalles del formulario
    formData.append('detalles', JSON.stringify(detalles));
    
    // Enviar
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(r => r.text())
    .then(text => {
        if (text.includes('success') || text.includes('actualizada')) {
            window.location.href = BASE_URL + 'index.php?action=ventas&method=historial';
        } else {
            alert('Error al actualizar la venta');
            console.error(text);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error al actualizar la venta');
    });
});
</script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

