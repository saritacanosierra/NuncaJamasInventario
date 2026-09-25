<?php
$pageTitle = 'Punto de Venta';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container pos">
    <div class="pos-cabecera">
        <div>
            <h2 class="mb-0"><i class="bi bi-cart-check"></i> Caja<?php $ayuda = 'Caja del día. Escanea el código o busca la prenda, elige cliente y forma de pago, y cobra. Nueva venta abre otro ticket. El reloj abre las ventas ya hechas.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (tienePermiso('ventas_historial:view')): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=historial" class="btn btn-outline-info btn-icono" title="Historial">
                <i class="bi bi-clock-history"></i>
            </a>
            <?php endif; ?>
            <button class="btn btn-primary" id="btnNuevaVenta">
                <i class="bi bi-plus-circle"></i> Nueva venta
            </button>
        </div>
    </div>
    <div id="ventasTabs" class="pos-tabs"></div>

    <div class="pos-lienzo">
        <div class="pos-busqueda">
            <div class="card pos-panel">
                <div class="card-body">
                    <label for="codigo_barras_input" class="form-label">Escanear</label>
                    <div class="input-group pos-escaner">
                        <input type="text" class="form-control" id="codigo_barras_input" autofocus
                               placeholder="Código de barras">
                        <button class="btn btn-primary btn-icono" id="btn_buscar_codigo" title="Buscar">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="mt-3">
                        <label for="buscar_nombre" class="form-label">O busca por nombre</label>
                        <input type="text" class="form-control" id="buscar_nombre"
                               placeholder="Nombre, color o talla">
                        <div id="resultados_busqueda" class="mt-2"></div>
                    </div>
                    
                    <!-- Información del producto seleccionado -->
                    <div id="producto_info" class="pos-prenda card-producto-oculto">
                        <input type="hidden" id="producto_id">
                        <div class="pos-prenda-fila">
                            <img id="producto_foto" src="" alt="" class="img-producto-venta d-none">
                            <div class="pos-prenda-datos">
                                <h6 id="producto_nombre" class="mb-0"></h6>
                                <p class="pos-color mb-0"><span id="producto_color"></span></p>
                                <p class="pos-precio-grande mb-0">$<span id="producto_precio"></span></p>
                                <p class="pos-color mb-0" id="producto_iva"></p>
                                <p class="mb-0">En stock: <span id="producto_stock" class="badge"></span></p>
                            </div>
                        </div>
                        <span id="producto_talla" class="d-none"></span>
                        <div class="pos-tallas" id="pos_tallas"></div>
                        <select id="producto_talla_sel" class="pos-talla-real" aria-label="Talla"></select>
                        <div class="pos-prenda-accion">
                            <div class="pos-cantidad">
                                <button type="button" class="pos-paso" id="btn_cant_menos" aria-label="Menos">−</button>
                                <input type="number" id="cantidad_producto" min="1" value="1" inputmode="numeric">
                                <button type="button" class="pos-paso" id="btn_cant_mas" aria-label="Más">+</button>
                            </div>
                            <button class="btn btn-primary pos-agregar" id="btn_agregar_carrito">
                                <i class="bi bi-plus-circle"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="pos-cobro">
            <div class="card pos-ticket">
                <div class="pos-ticket-cabeza">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Ticket<?php $ayuda = 'El precio de la prenda ya incluye el IVA. Si la pones a $20.000, la caja cobra $20.000. El domicilio y el empaque se suman aparte y no llevan IVA.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h5>
                    <span class="pos-cuenta" id="pos_cuenta">0 prendas</span>
                </div>
                <div class="table-responsive pos-ticket-lista">
                    <table class="table pos-tabla mb-0">
                        <thead class="pos-tabla-cabeza">
                            <tr>
                                <th>Prenda</th>
                                <th>Cant.</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="carrito_tbody">
                            <tr>
                                <td colspan="4" class="pos-vacio">Escanea o toca una prenda.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="pos-lateral">
            <div class="card pos-panel-cobro">
                <input type="hidden" id="cliente_id" value="1">
                <div id="cliente_seleccionado" class="pos-cliente-actual">
                    <p class="mb-0"><strong>Cliente:</strong> Cliente General</p>
                </div>
                <div class="pos-cliente-linea">
                    <?php if (tieneAlgunPermiso(permisos_lista('buscar_cliente'))): ?>
                    <div class="pos-cliente-busca">
                        <label for="buscar_cliente" class="form-label">Buscar cliente</label>
                        <input type="text" class="form-control" id="buscar_cliente" placeholder="Nombre o cédula">
                        <div id="resultados_cliente"></div>
                    </div>
                    <?php endif; ?>
                    <?php if (tieneAlgunPermiso(permisos_lista('crear_cliente'))): ?>
                    <button type="button" class="btn btn-outline-primary pos-cliente-nuevo" data-bs-toggle="modal" data-bs-target="#modalNuevoClienteRapido" title="Cliente nuevo">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="pos-metodos" id="pos_metodos">
                    <button type="button" class="pos-metodo is-activo" data-metodo="Efectivo">Efectivo</button>
                    <button type="button" class="pos-metodo" data-metodo="Tarjeta">Tarjeta</button>
                    <button type="button" class="pos-metodo" data-metodo="Transferencia">Transferencia</button>
                    <button type="button" class="pos-metodo" data-metodo="Mixto">Mixto</button>
                    <button type="button" class="pos-metodo" data-metodo="Fiado">Fiado</button>
                </div>
                <select class="pos-metodo-real" id="metodo_pago" aria-label="Método de pago">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Mixto">Mixto</option>
                    <option value="Fiado">Fiado</option>
                </select>
                <div class="pos-pago" id="pos_pago_efectivo">
                    <label for="recibido_efectivo">Billete recibido
                        <input type="number" id="recibido_efectivo" value="0" min="0" step="1" inputmode="numeric">
                    </label>
                    <p class="pos-devuelta mb-0">Devuelta <span id="devuelta_venta">$0</span></p>
                </div>
                <div class="pos-pago d-none" id="pos_pago_mixto">
                    <label for="monto_transferencia">Transferencia
                        <input type="number" id="monto_transferencia" value="0" min="0" step="1" inputmode="numeric">
                    </label>
                    <label for="recibido_mixto">Efectivo recibido
                        <input type="number" id="recibido_mixto" value="0" min="0" step="1" inputmode="numeric">
                    </label>
                    <p class="mb-0">En efectivo va <span id="falta_efectivo">$0</span></p>
                    <p class="pos-devuelta mb-0">Devuelta <span id="devuelta_mixta">$0</span></p>
                </div>
                <div class="pos-pago d-none" id="pos_pago_fiado">
                    <p class="mb-1" id="fiado_resumen">Elige un cliente con cédula. Esta venta se suma a lo que debe.</p>
                    <?php if (tienePermiso('clientes_fiado:view')): ?>
                    <a href="<?php echo BASE_URL; ?>index.php?action=clientes&method=deudas">Quienes deben y sus abonos</a>
                    <?php endif; ?>
                </div>

                <table class="pos-tabla-cierre">
                    <tbody>
                        <tr>
                            <th scope="row">Subtotal</th>
                            <td id="subtotal_carrito">$0</td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="descuento_venta">Descuento</label></th>
                            <td>
                                <input type="number" id="descuento_venta" value="0" min="0" step="1" inputmode="numeric" title="Se resta del precio de la prenda. El IVA incluido se calcula sobre lo que queda.">
                                <span id="descuento_aplicado" class="pos-dato-oculto">$0</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">IVA incluido</th>
                            <td>
                                <span id="iva_aplicado">$0</span>
                                <input type="hidden" id="iva_venta" value="0">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="valor_domicilio">Domicilio</label></th>
                            <td>
                                <input type="number" id="valor_domicilio" value="0" min="0" step="1" inputmode="numeric">
                                <span id="domicilio_aplicado" class="pos-dato-oculto">$0</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="valor_empaque">Empaque</label></th>
                            <td>
                                <input type="number" id="valor_empaque" value="0" min="0" step="1" inputmode="numeric">
                                <span id="empaque_aplicado" class="pos-dato-oculto">$0</span>
                            </td>
                        </tr>
                        <tr class="pos-fila-total">
                            <th scope="row">Total</th>
                            <td id="total_carrito">$0</td>
                        </tr>
                    </tbody>
                </table>
                <p class="small text-muted mb-2">Domicilio en 0: pagaron solo el producto o es recogida en tienda. Si escribes un valor, ese domicilio entra en la factura.</p>

                <details class="pos-mas">
                    <summary>Nota de entrega</summary>
                        <div class="pos-estado">
                            <label class="pos-estado-opcion" for="pago_pagado">
                                <input class="form-check-input" type="radio" name="estado_pago" id="pago_pagado" value="pagado" checked>
                                Ya pagó
                            </label>
                            <label class="pos-estado-opcion" for="pago_contra_entrega">
                                <input class="form-check-input" type="radio" name="estado_pago" id="pago_contra_entrega" value="contra_entrega">
                                Contra entrega
                            </label>
                        </div>
                        <div class="form-check pos-domicilio">
                            <input class="form-check-input" type="checkbox" id="con_domicilio">
                            <label class="form-check-label" for="con_domicilio">Sale a domicilio</label>
                        </div>
                        <input type="checkbox" id="domicilio_contra_entrega" class="d-none">
                        <div id="div_observaciones_domicilio">
                            <label for="observaciones_domicilio" class="form-label">Nota para quien entrega</label>
                            <textarea class="form-control" id="observaciones_domicilio" rows="2"></textarea>
                        </div>
                </details>

                <?php if (tienePermiso('ventas_punto:create')): ?>
                <button class="btn btn-primary btn-lg w-100 pos-cobrar" id="btn_procesar_venta" disabled>
                    <i class="bi bi-check-circle"></i> Cobrar
                </button>
                <?php endif; ?>
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

<?php
$modalId = 'modalObservacionesCobro';
$title = '<i class="bi bi-chat-left-text"></i> Cómo sale el pedido';
$body = '<p class="mb-3">Elige una sola opción. Abajo se ve qué se cobra ahora y qué queda pendiente.</p>'
    . '<div class="form-check mb-2"><input class="form-check-input" type="radio" name="obs_modo_entrega" id="obs_recogida" value="recogida" checked>'
    . '<label class="form-check-label" for="obs_recogida">Recogida en tienda. Pagaron solo el producto y lo recogen aquí.</label></div>'
    . '<div class="form-check mb-2"><input class="form-check-input" type="radio" name="obs_modo_entrega" id="obs_domicilio_factura" value="factura">'
    . '<label class="form-check-label" for="obs_domicilio_factura">El domicilio se paga con esta factura, junto con el pedido.</label></div>'
    . '<div class="form-check mb-2"><input class="form-check-input" type="radio" name="obs_modo_entrega" id="obs_domicilio_contra" value="domicilio_contra">'
    . '<label class="form-check-label" for="obs_domicilio_contra">El pedido ya está pago. El domicilio se cobra contra entrega.</label></div>'
    . '<div class="form-check mb-3"><input class="form-check-input" type="radio" name="obs_modo_entrega" id="obs_pedido_contra" value="pedido_contra">'
    . '<label class="form-check-label" for="obs_pedido_contra">El pedido y el domicilio están por pagar. Se cobran contra entrega.</label></div>'
    . '<p class="small mb-3" id="obs_explicacion"></p>'
    . '<div id="obs_bloque_domicilio"><label for="obs_valor_domicilio" class="form-label">Valor del domicilio</label>'
    . '<input type="number" class="form-control mb-2" id="obs_valor_domicilio" min="0" step="1" value="0">'
    . '<p class="small text-muted mb-3">En 0 solo va el producto. Si escribes un valor, ese domicilio entra en la factura.</p></div>'
    . '<label for="obs_texto" class="form-label">Nota para quien entrega</label>'
    . '<textarea class="form-control" id="obs_texto" rows="3" placeholder="Ej.: Llamar antes de llegar. Torre 2, apto 301."></textarea>';
$footer = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cancelar</button>'
    . '<button type="button" class="btn btn-primary" id="btnAceptarCobro"><i class="bi bi-check-circle"></i> Aceptar y facturar</button>';
$size = '';
$scrollable = false;
require BASE_DIR . '/front/views/components/modal.php';
?>

<!-- Pasar BASE_URL al JavaScript -->
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<!-- JavaScript del módulo de ventas -->
<?php
$scriptPartes = [
    'front/public/js/ventas/estado.js',
    'front/public/js/ventas/tickets.js',
    'front/public/js/ventas/productos.js',
    'front/public/js/ventas/cobro.js',
];
$scriptVersion = '21';
require BASE_DIR . '/front/views/components/script_partes.php';
?>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

