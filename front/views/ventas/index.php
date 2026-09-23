<?php
$pageTitle = 'Punto de Venta';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="bi bi-cart-check"></i> Punto de Venta</h2>
                <button class="btn btn-primary" id="btnNuevaVenta">
                    <i class="bi bi-plus-circle"></i> Nueva Venta
                </button>
            </div>
            <div id="ventasTabs" class="mb-3"></div>
        </div>
    </div>
    
    <div class="row">
        <!-- Columna izquierda: Solo búsqueda de productos -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5><i class="bi bi-search"></i> Buscar Producto</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="codigo_barras_input" class="form-label">Código de Barras</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="codigo_barras_input" 
                                   placeholder="Escanear o ingresar código">
                            <button class="btn btn-primary" id="btn_buscar_codigo">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="buscar_nombre" class="form-label">Buscar por Nombre</label>
                        <input type="text" class="form-control" id="buscar_nombre" 
                               placeholder="Escriba el nombre del producto">
                        <div id="resultados_busqueda" class="mt-2"></div>
                    </div>
                    
                    <!-- Información del producto seleccionado -->
                    <div id="producto_info" class="card card-producto-oculto mt-3">
                        <div class="card-body">
                            <input type="hidden" id="producto_id">
                            <h6 id="producto_nombre"></h6>
                            <p class="mb-1"><strong>Color:</strong> <span id="producto_color"></span></p>
                            <p class="mb-1"><strong>Talla:</strong> <span id="producto_talla"></span></p>
                            <p class="mb-1"><strong>Precio:</strong> $<span id="producto_precio"></span></p>
                            <p class="mb-2">
                                <strong>Stock:</strong> 
                                <span id="producto_stock" class="badge"></span>
                            </p>
                            <img id="producto_foto" src="" alt="Foto producto" 
                                 class="img-thumbnail d-none img-producto-venta">
                            <div class="mt-2">
                                <label for="cantidad_producto" class="form-label">Cantidad</label>
                                <input type="number" class="form-control" id="cantidad_producto" 
                                       min="1" value="1">
                            </div>
                            <button class="btn btn-success w-100 mt-2" id="btn_agregar_carrito">
                                <i class="bi bi-cart-plus"></i> Agregar al Carrito
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Columna derecha: Carrito, Cliente, Método de Pago y Resumen -->
        <div class="col-md-8">
            <!-- Carrito de Compra -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5><i class="bi bi-cart"></i> Carrito de Compra</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Subtotal</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="carrito_tbody">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">El carrito está vacío</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Selección de Cliente -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5><i class="bi bi-person"></i> Cliente</h5>
                </div>
                <div class="card-body">
                    <input type="hidden" id="cliente_id" value="1">
                    <div class="mb-3">
                        <label for="buscar_cliente" class="form-label">Buscar Cliente</label>
                        <input type="text" class="form-control" id="buscar_cliente" 
                               placeholder="Nombre o cédula/NIT">
                        <div id="resultados_cliente" class="mt-2"></div>
                    </div>
                    <div id="cliente_seleccionado" class="mb-3">
                        <p class="mb-0"><strong>Cliente:</strong> Cliente General</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoClienteRapido">
                        <i class="bi bi-person-plus"></i> Nuevo cliente rápido
                    </button>
                </div>
            </div>
            
            <!-- Método de Pago y Opciones -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="metodo_pago" class="form-label">Método de Pago</label>
                                <select class="form-select" id="metodo_pago">
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Tarjeta">Tarjeta</option>
                                    <option value="Transferencia">Transferencia</option>
                                    <option value="Mixto">Mixto</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descuento_venta" class="form-label">Descuento ($)</label>
                                <input type="number" class="form-control" id="descuento_venta" 
                                       value="0" min="0" step="0.01">
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="con_domicilio">
                                    <label class="form-check-label" for="con_domicilio">
                                        Con Domicilio
                                    </label>
                                </div>
                                <div id="div_observaciones_domicilio" class="mt-2">
                                    <label for="observaciones_domicilio" class="form-label">
                                        Observaciones para el Domiciliario
                                    </label>
                                    <textarea class="form-control" id="observaciones_domicilio" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estado de Pago</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="estado_pago" 
                                               id="pago_pagado" value="pagado" checked>
                                        <label class="form-check-label" for="pago_pagado">
                                            Pagado
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="estado_pago" 
                                               id="pago_contra_entrega" value="contra_entrega">
                                        <label class="form-check-label" for="pago_contra_entrega">
                                            Pago Contra Entrega
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Resumen de la venta -->
                            <div class="border rounded p-3">
                                <p class="mb-2"><strong>Subtotal:</strong> <span id="subtotal_carrito">$0</span></p>
                                <p class="mb-2"><strong>Descuento:</strong> <span id="descuento_aplicado">$0</span></p>
                                <hr>
                                <h4 class="mb-3"><strong>Total:</strong> <span id="total_carrito">$0</span></h4>
                                <button class="btn btn-success btn-lg w-100 mb-2" id="btn_procesar_venta" disabled>
                                    <i class="bi bi-check-circle"></i> Procesar Venta
                                </button>
                                <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=historial" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-clock-history"></i> Ver Historial
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para nuevo cliente rápido -->
<div class="modal fade" id="modalNuevoClienteRapido" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Cliente Rápido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoClienteRapido">
                    <div class="mb-3">
                        <label for="cliente_nombre_rapido" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="cliente_nombre_rapido" required>
                    </div>
                    <div class="mb-3">
                        <label for="cliente_cedula_rapido" class="form-label">Cédula/NIT *</label>
                        <input type="text" class="form-control" id="cliente_cedula_rapido" required>
                    </div>
                    <div class="mb-3">
                        <label for="cliente_telefono_rapido" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="cliente_telefono_rapido">
                    </div>
                    <div class="mb-3">
                        <label for="cliente_email_rapido" class="form-label">Email</label>
                        <input type="email" class="form-control" id="cliente_email_rapido">
                    </div>
                    <div class="mb-3">
                        <label for="cliente_direccion_rapido" class="form-label">Dirección</label>
                        <textarea class="form-control" id="cliente_direccion_rapido" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="cliente_fecha_rapido" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="cliente_fecha_rapido">
                    </div>
                    <div class="mb-3">
                        <label for="cliente_observaciones_rapido" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="cliente_observaciones_rapido" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarClienteRapido">
                    <i class="bi bi-save"></i> Guardar cliente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Pasar BASE_URL al JavaScript -->
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<!-- JavaScript del módulo de ventas -->
<script src="<?php echo BASE_URL; ?>front/public/js/ventas.js"></script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

