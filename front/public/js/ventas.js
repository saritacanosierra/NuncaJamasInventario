/**
 * JavaScript para el módulo de Ventas
 * Maneja múltiples ventas simultáneas, búsqueda de productos, clientes y procesamiento de ventas
 */

// Estructura para manejar múltiples ventas en paralelo
let ventasAbiertas = {};
let ventaActivaId = null;
let contadorVentas = 0;
let carrito = []; // seguirá representando el carrito de la venta activa
let saldoFiadoCliente = 0;
let BASE_URL_VENTAS = '';

function formatearNumero(valor) {
    const numero = Math.round(parseFloat(valor) || 0);
    return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function ivaDeBase(base) {
    return Math.round(Math.round(parseFloat(base) || 0) * 0.19);
}

function precioConIva(base) {
    return Math.round(parseFloat(base) || 0) + ivaDeBase(base);
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Obtener BASE_URL de la variable global definida en la vista
    BASE_URL_VENTAS = window.BASE_URL || '';
    
    // Crear la primera venta por defecto
    crearNuevaVenta();

    // Eventos de búsqueda de producto
    let timeoutBusqueda;
    document.getElementById('btn_buscar_codigo').addEventListener('click', buscarPorCodigo);
    document.getElementById('codigo_barras_input').addEventListener('input', function() {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(filtrarPorCodigo, 300);
    });
    document.getElementById('codigo_barras_input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') buscarPorCodigo();
    });

    // Buscar por nombre
    document.getElementById('buscar_nombre').addEventListener('input', function() {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(buscarPorNombre, 500);
    });

    // Buscar cliente existente
    let timeoutCliente;
    const buscarClienteInput = document.getElementById('buscar_cliente');
    if (buscarClienteInput) {
        buscarClienteInput.addEventListener('input', function() {
            clearTimeout(timeoutCliente);
            timeoutCliente = setTimeout(buscarCliente, 500);
        });
    }

    document.querySelectorAll('.pos-metodo').forEach(function (boton) {
        boton.addEventListener('click', function () {
            document.getElementById('metodo_pago').value = boton.dataset.metodo;
            marcarMetodoActivo();
        });
    });
    document.querySelectorAll('input[name="estado_pago"]').forEach(function (radio) {
        radio.addEventListener('change', pintarPago);
    });
    ['recibido_efectivo', 'recibido_mixto', 'monto_transferencia'].forEach(function (id) {
        const campo = document.getElementById(id);
        if (campo) campo.addEventListener('input', pintarPago);
    });

    // Agregar al carrito
    document.getElementById('btn_agregar_carrito').addEventListener('click', agregarAlCarrito);

    const btnProcesar = document.getElementById('btn_procesar_venta');
    if (btnProcesar) {
        btnProcesar.addEventListener('click', abrirObservacionesCobro);
    }

    const cantInput = document.getElementById('cantidad_producto');
    const btnMenos = document.getElementById('btn_cant_menos');
    const btnMas = document.getElementById('btn_cant_mas');
    if (cantInput && btnMenos && btnMas) {
        btnMenos.addEventListener('click', function () {
            const n = parseInt(cantInput.value, 10) || 1;
            if (n > 1) cantInput.value = String(n - 1);
        });
        btnMas.addEventListener('click', function () {
            const n = parseInt(cantInput.value, 10) || 1;
            const tope = parseInt(cantInput.max, 10);
            if (!isNaN(tope) && tope > 0 && n >= tope) return;
            cantInput.value = String(n + 1);
        });
    }

    // Actualizar total cuando cambia el descuento
    ['descuento_venta', 'valor_domicilio', 'valor_empaque'].forEach(function (id) {
        const campo = document.getElementById(id);
        if (!campo) return;
        campo.addEventListener('input', function () {
            if (id === 'valor_domicilio') {
                const lleva = (parseFloat(campo.value) || 0) > 0;
                const marca = document.getElementById('con_domicilio');
                if (marca && lleva) marca.checked = true;
                const nota = document.getElementById('div_observaciones_domicilio');
                if (nota && lleva) nota.style.display = 'block';
            }
            actualizarTotal();
        });
    });

    // Nueva venta
    document.getElementById('btnNuevaVenta').addEventListener('click', crearNuevaVenta);

    // El botón "Nuevo cliente rápido" ya existe en el HTML y usa data-bs-toggle="modal"
    // Solo necesitamos resetear el formulario cuando se abre el modal
    const modalNuevoCliente = document.getElementById('modalNuevoClienteRapido');
    if (modalNuevoCliente) {
        modalNuevoCliente.addEventListener('show.bs.modal', function() {
            document.getElementById('formNuevoClienteRapido').reset();
        });
    }

    // Guardar cliente rápido
    document.getElementById('btnGuardarClienteRapido').addEventListener('click', guardarClienteRapido);
    const btnAceptarCobro = document.getElementById('btnAceptarCobro');
    if (btnAceptarCobro) btnAceptarCobro.addEventListener('click', aceptarObservacionesCobro);
    const obsDomicilio = document.getElementById('obs_domicilio_contra');
    const obsPedido = document.getElementById('obs_pedido_contra');
    if (obsDomicilio && obsPedido) {
        obsDomicilio.addEventListener('change', function () {
            if (this.checked) obsPedido.checked = false;
        });
        obsPedido.addEventListener('change', function () {
            if (this.checked) obsDomicilio.checked = false;
        });
    }
    
    // Mostrar/ocultar campo de observaciones según checkbox de domicilio
    // El estado de pago siempre está visible
    document.getElementById('con_domicilio').addEventListener('change', function() {
        const divObservaciones = document.getElementById('div_observaciones_domicilio');
        if (this.checked) {
            divObservaciones.style.display = 'block';
        } else {
            divObservaciones.style.display = 'none';
            document.getElementById('observaciones_domicilio').value = '';
        }
    });
});

// ==== Gestión de ventas múltiples ====
function crearNuevaVenta() {
    contadorVentas++;
    const id = 'venta_' + contadorVentas;

    ventasAbiertas[id] = {
        clienteId: 1,
        clienteNombre: 'Cliente General',
        carrito: [],
        descuento: 0,
        iva: 0,
        domicilio: 0,
        empaque: 0,
        metodoPago: 'Efectivo',
        recibidoEfectivo: 0,
        recibidoMixto: 0,
        transferencia: 0,
        saldoFiado: 0,
        conDomicilio: false,
        observacionesDomicilio: '',
        pagoContraEntrega: false
    };

    agregarTabVenta(id);
    cambiarVentaActiva(id);
}

function agregarTabVenta(id) {
    const tabs = document.getElementById('ventasTabs');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-outline-secondary venta-tab';
    btn.dataset.ventaId = id;
    btn.textContent = 'Venta ' + id.split('_')[1];
    btn.addEventListener('click', function() {
        cambiarVentaActiva(id);
    });
    tabs.appendChild(btn);
}

function cambiarVentaActiva(nuevaId) {
    if (ventaActivaId && ventasAbiertas[ventaActivaId]) {
        guardarEstadoVenta(ventaActivaId);
    }

    ventaActivaId = nuevaId;
    
    // Asegurar que la venta existe
    if (!ventasAbiertas[ventaActivaId]) {
        console.error('Venta activa no existe:', ventaActivaId);
        return;
    }
    
    // Sincronizar carrito con la venta activa - CREAR COPIA, NO REFERENCIA
    const carritoVenta = ventasAbiertas[ventaActivaId].carrito || [];
    carrito = JSON.parse(JSON.stringify(carritoVenta)); // Crear copia profunda
    ventasAbiertas[ventaActivaId].carrito = JSON.parse(JSON.stringify(carrito)); // Asegurar copia también en ventasAbiertas
    
    refrescarUiDesdeVenta(ventaActivaId);

    // Marcar pestañas
    document.querySelectorAll('.venta-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.ventaId === ventaActivaId);
    });
}

function guardarEstadoVenta(id) {
    if (!ventasAbiertas[id]) return;
    const venta = ventasAbiertas[id];
    // Guardar COPIA del carrito, no referencia
    venta.carrito = JSON.parse(JSON.stringify(carrito || []));
    venta.descuento = parseFloat(document.getElementById('descuento_venta').value) || 0;
    venta.iva = parseFloat(document.getElementById('iva_venta').value) || 0;
    venta.domicilio = parseFloat(document.getElementById('valor_domicilio').value) || 0;
    venta.empaque = parseFloat(document.getElementById('valor_empaque').value) || 0;
    venta.recibidoEfectivo = parseFloat(document.getElementById('recibido_efectivo').value) || 0;
    venta.recibidoMixto = parseFloat(document.getElementById('recibido_mixto').value) || 0;
    venta.transferencia = parseFloat(document.getElementById('monto_transferencia').value) || 0;
    venta.saldoFiado = saldoFiadoCliente;
    venta.metodoPago = document.getElementById('metodo_pago').value;
    venta.clienteId = parseInt(document.getElementById('cliente_id').value) || 1;
    venta.clienteNombre = document.querySelector('#cliente_seleccionado strong')?.nextSibling?.textContent?.trim() || 'Cliente General';
    venta.conDomicilio = document.getElementById('con_domicilio').checked;
    venta.observacionesDomicilio = document.getElementById('observaciones_domicilio').value.trim();
    const estadoPago = document.querySelector('input[name="estado_pago"]:checked')?.value || 'pagado';
    venta.pagoContraEntrega = estadoPago === 'contra_entrega';
}

function refrescarUiDesdeVenta(id) {
    const venta = ventasAbiertas[id];
    if (!venta) return;

    // Cliente
    document.getElementById('cliente_id').value = venta.clienteId;
    document.getElementById('cliente_seleccionado').innerHTML =
        `<p class="mb-0"><strong>Cliente:</strong> ${venta.clienteNombre}</p>`;

    // Método de pago y descuento
    document.getElementById('metodo_pago').value = venta.metodoPago || 'Efectivo';
    marcarMetodoActivo();
    document.getElementById('descuento_venta').value = venta.descuento || 0;
    document.getElementById('iva_venta').value = venta.iva || 0;
    document.getElementById('valor_domicilio').value = venta.domicilio || 0;
    document.getElementById('valor_empaque').value = venta.empaque || 0;
    document.getElementById('recibido_efectivo').value = venta.recibidoEfectivo || 0;
    document.getElementById('recibido_mixto').value = venta.recibidoMixto || 0;
    document.getElementById('monto_transferencia').value = venta.transferencia || 0;
    saldoFiadoCliente = venta.saldoFiado || 0;
    
    // Domicilio
    document.getElementById('con_domicilio').checked = venta.conDomicilio || false;
    document.getElementById('observaciones_domicilio').value = venta.observacionesDomicilio || '';
    document.getElementById('div_observaciones_domicilio').style.display = 
        (venta.conDomicilio) ? 'block' : 'none';
    // Estado de pago siempre visible
    if (venta.pagoContraEntrega) {
        document.getElementById('pago_contra_entrega').checked = true;
    } else {
        document.getElementById('pago_pagado').checked = true;
    }

    // Carrito
    actualizarCarrito();
}

function marcarMetodoActivo() {
    const select = document.getElementById('metodo_pago');
    if (!select) return;
    const valor = select.value;
    document.querySelectorAll('.pos-metodo').forEach(function (boton) {
        boton.classList.toggle('is-activo', boton.dataset.metodo === valor);
    });
    pintarPago();
}

function partesPago() {
    const caja = leerCaja();
    const metodo = document.getElementById('metodo_pago').value;
    const contra = document.getElementById('pago_contra_entrega').checked;
    const recibidoEfectivo = Math.max(0, parseFloat(document.getElementById('recibido_efectivo').value) || 0);
    const recibidoMixto = Math.max(0, parseFloat(document.getElementById('recibido_mixto').value) || 0);
    const transferencia = Math.max(0, parseFloat(document.getElementById('monto_transferencia').value) || 0);
    const vacio = {
        total: caja.total,
        pagoEfectivo: 0,
        pagoTransferencia: 0,
        pagoTarjeta: 0,
        recibido: 0,
        devuelta: 0,
        falta: 0,
        error: ''
    };
    if (contra || metodo === 'Fiado') {
        return vacio;
    }
    if (metodo === 'Efectivo') {
        const devuelta = recibidoEfectivo - caja.total;
        return {
            total: caja.total,
            pagoEfectivo: caja.total,
            pagoTransferencia: 0,
            pagoTarjeta: 0,
            recibido: recibidoEfectivo,
            devuelta: devuelta > 0 ? devuelta : 0,
            falta: caja.total,
            error: devuelta < -0.001 ? 'El billete no alcanza para el total.' : ''
        };
    }
    if (metodo === 'Tarjeta') {
        vacio.pagoTarjeta = caja.total;
        return vacio;
    }
    if (metodo === 'Transferencia') {
        vacio.pagoTransferencia = caja.total;
        return vacio;
    }
    const efectivo = Math.max(0, caja.total - transferencia);
    const devuelta = recibidoMixto - efectivo;
    let error = '';
    if (transferencia <= 0 || transferencia >= caja.total) {
        error = 'En mixto escribe cuánto va por transferencia. El resto se paga en efectivo.';
    } else if (devuelta < -0.001) {
        error = 'El efectivo recibido no cubre lo que falta.';
    }
    return {
        total: caja.total,
        pagoEfectivo: efectivo,
        pagoTransferencia: Math.min(transferencia, caja.total),
        pagoTarjeta: 0,
        recibido: recibidoMixto,
        devuelta: devuelta > 0 ? devuelta : 0,
        falta: efectivo,
        error: error
    };
}

function pintarPago() {
    const metodo = document.getElementById('metodo_pago').value;
    const contra = document.getElementById('pago_contra_entrega').checked;
    const efectivo = document.getElementById('pos_pago_efectivo');
    const mixto = document.getElementById('pos_pago_mixto');
    const fiado = document.getElementById('pos_pago_fiado');
    if (efectivo) efectivo.classList.toggle('d-none', metodo !== 'Efectivo' || contra);
    if (mixto) mixto.classList.toggle('d-none', metodo !== 'Mixto' || contra);
    if (fiado) fiado.classList.toggle('d-none', metodo !== 'Fiado');
    const partes = partesPago();
    const devuelta = document.getElementById('devuelta_venta');
    const devueltaMixta = document.getElementById('devuelta_mixta');
    const falta = document.getElementById('falta_efectivo');
    if (devuelta) devuelta.textContent = '$' + formatearNumero(partes.devuelta);
    if (devueltaMixta) devueltaMixta.textContent = '$' + formatearNumero(partes.devuelta);
    if (falta) falta.textContent = '$' + formatearNumero(partes.falta);
    const resumen = document.getElementById('fiado_resumen');
    if (resumen) {
        const queda = saldoFiadoCliente + partes.total;
        resumen.textContent = 'Debe ' + '$' + formatearNumero(saldoFiadoCliente) + '. Con esta venta queda en ' + '$' + formatearNumero(queda) + '.';
    }
}

function buscarPorCodigo() {
    const codigo = document.getElementById('codigo_barras_input').value.trim();
    if (!codigo) return;
    
    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=productos&method=buscarPorCodigo&codigo=${encodeURIComponent(codigo)}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarProducto(data.producto);
                document.getElementById('resultados_busqueda').innerHTML = '';
            } else {
                filtrarProductos(codigo);
            }
        })
        .catch(() => filtrarProductos(codigo));
}

function filtrarPorCodigo() {
    const codigo = document.getElementById('codigo_barras_input').value.trim();
    if (!codigo) {
        document.getElementById('resultados_busqueda').innerHTML = '';
        return;
    }
    filtrarProductos(codigo);
}

function buscarPorNombre() {
    const termino = document.getElementById('buscar_nombre').value.trim();
    filtrarProductos(termino);
}

function filtrarProductos(termino) {
    if (!termino) {
        document.getElementById('resultados_busqueda').innerHTML = '';
        return;
    }
    
    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=productos&method=buscarPorNombre&termino=${encodeURIComponent(termino)}`)
        .then(r => r.text())
        .then(txt => {
            console.log('Respuesta buscarPorNombre (texto crudo):', txt);
            let data;
            try {
                data = JSON.parse(txt);
            } catch (e) {
                console.error('No se pudo parsear JSON en buscarPorNombre:', e);
                document.getElementById('resultados_busqueda').innerHTML =
                    '<p class="text-danger">Respuesta no válida del servidor. Revisa la consola.</p>';
                return;
            }

            let html = '';
            if (data.success && data.productos && data.productos.length > 0) {
                html = '<div class="list-group">';
                data.productos.forEach(p => {
                    const codigoLista = String(p.codigo_barras || '').replace(/'/g, '');
                    const tallasTxt = (p.tallas || []).map(function (t) {
                        return t.talla + ' (' + t.stock + ')';
                    }).join(', ') || (p.talla || '');
                    html += `<a href="#" class="list-group-item list-group-item-action pos-resultado" onclick="seleccionarProductoDesdeLista('${codigoLista}'); return false;">
                        <span class="pos-resultado-nombre">${p.nombre}</span>
                        <span class="pos-resultado-meta">${p.codigo_barras} · ${p.color} · ${tallasTxt}</span>
                    </a>`;
                });
                html += '</div>';
            } else {
                html = '<p class="text-muted">No se encontraron productos</p>';
            }
            document.getElementById('resultados_busqueda').innerHTML = html;
        })
        .catch(err => {
            console.error('Error al buscar por nombre (fetch):', err);
            document.getElementById('resultados_busqueda').innerHTML =
                '<p class="text-danger">Error al buscar productos. Revisa la consola.</p>';
        });
}

function seleccionarProductoDesdeLista(codigo) {
    document.getElementById('codigo_barras_input').value = codigo;
    buscarPorCodigo();
    document.getElementById('resultados_busqueda').innerHTML = '';
    document.getElementById('buscar_nombre').value = '';
}

function mostrarProducto(producto) {
    // Asegurar que el campo producto_id esté limpio antes de asignar nuevo valor
    const productoIdInput = document.getElementById('producto_id');
    if (productoIdInput) {
        productoIdInput.value = ''; // Limpiar primero para evitar problemas
        productoIdInput.value = producto.id.toString(); // Asignar nuevo ID como string
    }
    
    document.getElementById('producto_nombre').textContent = producto.nombre || '';
    document.getElementById('producto_color').textContent = producto.color || '';
    const basePesos = Math.round(parseFloat(producto.precio_venta) || 0);
    const ivaPesos = ivaDeBase(basePesos);
    const precioPesos = basePesos + ivaPesos;
    const precioEl = document.getElementById('producto_precio');
    precioEl.textContent = formatearNumero(precioPesos);
    precioEl.dataset.valor = String(precioPesos);
    precioEl.dataset.base = String(basePesos);
    precioEl.dataset.iva = String(ivaPesos);
    const ivaEl = document.getElementById('producto_iva');
    if (ivaEl) ivaEl.textContent = 'IVA 19% $' + formatearNumero(ivaPesos);

    const stockBadge = document.getElementById('producto_stock');
    const selectorTalla = document.getElementById('producto_talla_sel');
    const tallas = Array.isArray(producto.tallas) && producto.tallas.length
        ? producto.tallas
        : [{ id: 0, talla: producto.talla || '', stock: producto.stock || 0 }];
    selectorTalla.innerHTML = '';
    const cajaTallas = document.getElementById('pos_tallas');
    if (cajaTallas) cajaTallas.innerHTML = '';
    tallas.forEach(function (talla, indice) {
        const opcion = document.createElement('option');
        opcion.value = talla.id;
        opcion.dataset.stock = talla.stock;
        opcion.dataset.talla = talla.talla;
        opcion.textContent = talla.talla + ' · ' + talla.stock + ' en stock';
        selectorTalla.appendChild(opcion);
        if (!cajaTallas) return;
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'pos-talla';
        chip.textContent = talla.talla || 'Única';
        chip.addEventListener('click', function () {
            selectorTalla.selectedIndex = indice;
            pintarTallaElegida();
        });
        cajaTallas.appendChild(chip);
    });
    function pintarTallaElegida() {
        const elegida = selectorTalla.selectedOptions[0];
        const stock = elegida ? parseInt(elegida.dataset.stock, 10) || 0 : 0;
        stockBadge.textContent = stock;
        stockBadge.className = 'badge ' + (stock <= (producto.stock_minimo || 0) ? 'bg-warning' : 'bg-success');
        document.getElementById('producto_talla').textContent = elegida ? elegida.dataset.talla : '';
        document.getElementById('cantidad_producto').max = stock;
        if (!cajaTallas) return;
        Array.from(cajaTallas.children).forEach(function (chip, indice) {
            chip.classList.toggle('is-activo', indice === selectorTalla.selectedIndex);
        });
    }
    selectorTalla.onchange = pintarTallaElegida;
    pintarTallaElegida();
    
    const img = document.getElementById('producto_foto');
    if (producto.foto) {
        img.src = (BASE_URL_VENTAS || window.BASE_URL || '') + 'front/public/uploads/productos/' + producto.foto;
        img.classList.remove('d-none');
    } else {
        img.classList.add('d-none');
    }
    
    const cardInfo = document.getElementById('producto_info');
    cardInfo.classList.remove('card-producto-oculto');
    document.getElementById('cantidad_producto').value = 1;
    
    // Debug en producción
    console.log('Producto mostrado - ID:', producto.id, 'Nombre:', producto.nombre);
}

function agregarAlCarrito() {
    // Obtener el ID del producto ANTES de cualquier otra operación
    const productoIdInput = document.getElementById('producto_id');
    const productoIdRaw = productoIdInput ? productoIdInput.value.trim() : '';
    
    // Validar que hay un producto seleccionado
    if (!productoIdRaw) {
        alert('Por favor, seleccione un producto primero');
        return;
    }
    
    // Convertir productoId a número para comparación consistente
    const productoIdNum = parseInt(productoIdRaw);
    if (isNaN(productoIdNum) || productoIdNum <= 0) {
        console.error('Producto ID inválido:', productoIdRaw);
        alert('Error: ID de producto inválido. Por favor, seleccione un producto nuevamente.');
        return;
    }
    
    const cantidad = parseInt(document.getElementById('cantidad_producto').value) || 0;
    if (cantidad <= 0) {
        alert('Por favor, ingrese una cantidad válida');
        return;
    }
    
    // Asegurar que hay una venta activa
    if (!ventaActivaId || !ventasAbiertas[ventaActivaId]) {
        crearNuevaVenta();
    }
    
    // Obtener datos del producto desde la interfaz
    const productoNombre = document.getElementById('producto_nombre').textContent.trim();
    const precioEl = document.getElementById('producto_precio');
    const productoPrecio = parseInt(precioEl.dataset.valor, 10);
    const productoBase = parseInt(precioEl.dataset.base, 10) || productoPrecio;
    const productoIva = parseInt(precioEl.dataset.iva, 10) || 0;
    const tallaElegida = document.getElementById('producto_talla_sel').selectedOptions[0];
    const tallaId = tallaElegida ? parseInt(tallaElegida.value, 10) || 0 : 0;
    const tallaNombre = tallaElegida ? tallaElegida.dataset.talla : '';
    const productoStock = tallaElegida ? parseInt(tallaElegida.dataset.stock, 10) || 0 : parseInt(document.getElementById('producto_stock').textContent);
    
    if (!productoNombre || !productoPrecio || isNaN(productoStock)) {
        alert('Error: Datos del producto incompletos. Por favor, seleccione el producto nuevamente.');
        return;
    }
    
    if (cantidad > productoStock) {
        alert('No hay suficiente stock. Stock disponible: ' + productoStock);
        document.getElementById('cantidad_producto').value = productoStock;
        return;
    }
    
    // Obtener una COPIA del carrito actual (no referencia) - CRÍTICO para producción
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        // Crear una copia profunda del carrito usando JSON para evitar referencias
        try {
            carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
        } catch (e) {
            console.error('Error al copiar carrito:', e);
            carritoActual = [];
        }
    }
    
    // Debug en producción (comentar después de verificar)
    console.log('Producto a agregar - ID:', productoIdNum, 'Nombre:', productoNombre);
    console.log('Carrito actual antes de agregar:', JSON.stringify(carritoActual));
    
    // Verificar si el producto YA EXISTE en el carrito usando comparación estricta por ID
    const productoExistenteIndex = carritoActual.findIndex(item => {
        if (!item || !item.id) return false;
        return parseInt(item.id) === productoIdNum && (parseInt(item.talla_id, 10) || 0) === tallaId;
    });
    
    if (productoExistenteIndex >= 0) {
        // El producto YA EXISTE en el carrito - solo incrementar cantidad
        const productoExistente = carritoActual[productoExistenteIndex];
        const nuevaCantidad = productoExistente.cantidad + cantidad;
        
        if (nuevaCantidad > productoStock) {
            alert('No hay suficiente stock. Stock disponible: ' + productoStock);
            return;
        }
        
        // Actualizar solo la cantidad del producto existente
        carritoActual[productoExistenteIndex].cantidad = nuevaCantidad;
        carritoActual[productoExistenteIndex].stock = productoStock;
    } else {
        const nuevoItem = {
            id: productoIdNum.toString(),
            talla_id: tallaId,
            talla: tallaNombre,
            nombre: productoNombre + (tallaNombre ? ' · talla ' + tallaNombre : ''),
            precio: productoBase,
            base: productoBase,
            iva: productoIva,
            cantidad: cantidad,
            stock: productoStock
        };
        carritoActual.push(nuevoItem);
    }
    
    // Actualizar el carrito en ventasAbiertas - CREAR COPIA, NO REFERENCIA
    if (ventaActivaId && ventasAbiertas[ventaActivaId]) {
        // Guardar copia profunda en ventasAbiertas
        ventasAbiertas[ventaActivaId].carrito = JSON.parse(JSON.stringify(carritoActual));
        // Sincronizar también la variable global con copia
        carrito = JSON.parse(JSON.stringify(carritoActual));
        
        // Debug en producción
        console.log('Carrito después de agregar:', JSON.stringify(carritoActual));
        console.log('Total items en carrito:', carritoActual.length);
    }
    
    // Actualizar la visualización del carrito
    actualizarCarrito();
    
    // LIMPIAR TODOS los campos después de agregar exitosamente
    // Usar setTimeout para asegurar que la limpieza ocurra después de que se complete la actualización
    setTimeout(() => {
        document.getElementById('codigo_barras_input').value = '';
        document.getElementById('buscar_nombre').value = '';
        document.getElementById('resultados_busqueda').innerHTML = '';
        
        // Limpiar el campo producto_id CRÍTICO para evitar que se reutilice
        if (productoIdInput) {
            productoIdInput.value = '';
            // Verificar que se limpió correctamente
            if (productoIdInput.value !== '') {
                console.warn('El campo producto_id no se limpió correctamente');
                productoIdInput.value = ''; // Forzar limpieza
            }
        }
        
        // Limpiar también los campos de visualización del producto
        const productoNombreEl = document.getElementById('producto_nombre');
        const productoColorEl = document.getElementById('producto_color');
        const productoTallaEl = document.getElementById('producto_talla');
        const productoPrecioEl = document.getElementById('producto_precio');
        const productoStockEl = document.getElementById('producto_stock');
        const cantidadProductoEl = document.getElementById('cantidad_producto');
        
        if (productoNombreEl) productoNombreEl.textContent = '';
        if (productoColorEl) productoColorEl.textContent = '';
        if (productoTallaEl) productoTallaEl.textContent = '';
        if (productoPrecioEl) productoPrecioEl.textContent = '';
        if (productoStockEl) productoStockEl.textContent = '';
        if (cantidadProductoEl) cantidadProductoEl.value = '1';
        
        // Ocultar la tarjeta de información del producto
        const productoInfoEl = document.getElementById('producto_info');
        if (productoInfoEl) {
            productoInfoEl.classList.add('card-producto-oculto');
        }
        
        // Enfocar en el campo de búsqueda para siguiente producto
        const codigoBarrasInput = document.getElementById('codigo_barras_input');
        if (codigoBarrasInput) {
            codigoBarrasInput.focus();
        }
    }, 100); // Pequeño delay para asegurar que la actualización del carrito se complete primero
}

function actualizarCarrito() {
    const tbody = document.getElementById('carrito_tbody');
    
    // Obtener el carrito actualizado desde ventasAbiertas - CREAR COPIA, NO REFERENCIA
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        // Crear copia profunda para evitar problemas de referencia
        try {
            carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
        } catch (e) {
            console.error('Error al copiar carrito en actualizarCarrito:', e);
            carritoActual = [];
        }
    }
    
    // Sincronizar la variable global carrito con copia
    carrito = JSON.parse(JSON.stringify(carritoActual));
    
    // Limpiar el tbody completamente antes de renderizar
    tbody.innerHTML = '';
    
    if (!carritoActual || carritoActual.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="4" class="pos-vacio">Escanea o toca una prenda.</td>';
        tbody.appendChild(row);
        const botonCobro = document.getElementById('btn_procesar_venta');
        if (botonCobro) botonCobro.disabled = true;
    } else {
        // Crear una fila para cada ítem del carrito
        carritoActual.forEach((item, index) => {
            // Validar que el item tenga los datos necesarios
            if (!item || !item.id || !item.nombre) {
                console.warn('Item inválido en carrito:', item);
                return;
            }
            
            const row = document.createElement('tr');
            const subtotal = baseDeItem(item) * parseInt(item.cantidad || 0);
            
            // Escapar el nombre del producto para evitar problemas con caracteres especiales
            const nombreEscapado = (item.nombre || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            
            row.innerHTML = `
                <td class="pos-linea-nombre">${nombreEscapado}</td>
                <td class="pos-linea-cant">
                    <button type="button" class="pos-paso" onclick="cambiarCantidad(${index}, -1)" aria-label="Menos">−</button>
                    <span>${parseInt(item.cantidad || 0)}</span>
                    <button type="button" class="pos-paso" onclick="cambiarCantidad(${index}, 1)" aria-label="Más">+</button>
                </td>
                <td class="pos-linea-sub">$${formatearNumero(subtotal)}</td>
                <td><button type="button" class="pos-paso pos-paso-quitar" onclick="eliminarDelCarrito(${index})" title="Quitar"><i class="bi bi-trash"></i></button></td>
            `;
            tbody.appendChild(row);
        });
        const botonCobro = document.getElementById('btn_procesar_venta');
        if (botonCobro) botonCobro.disabled = false;
    }

    const cuenta = document.getElementById('pos_cuenta');
    if (cuenta) {
        const piezas = carritoActual.reduce(function (suma, item) {
            return suma + (parseInt(item.cantidad, 10) || 0);
        }, 0);
        cuenta.textContent = piezas === 1 ? '1 prenda' : piezas + ' prendas';
    }
    
    actualizarTotal();
}

function cambiarCantidad(index, delta) {
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
    }
    const item = carritoActual[index];
    if (!item) return;
    const nueva = (parseInt(item.cantidad, 10) || 0) + delta;
    if (nueva <= 0) {
        eliminarDelCarrito(index);
        return;
    }
    const tope = parseInt(item.stock, 10);
    if (!isNaN(tope) && tope > 0 && nueva > tope) {
        alert('No hay suficiente stock. Stock disponible: ' + tope);
        return;
    }
    item.cantidad = nueva;
    ventasAbiertas[ventaActivaId].carrito = JSON.parse(JSON.stringify(carritoActual));
    carrito = JSON.parse(JSON.stringify(carritoActual));
    actualizarCarrito();
}

function eliminarDelCarrito(index) {
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
    }
    const item = carritoActual[index];
    if (!item || typeof pedirDobleConfirmacion !== 'function') {
        return;
    }
    pedirDobleConfirmacion({
        titulo: 'Quitar producto',
        detalle: 'Se quita este producto de la venta.',
        codigo: item.nombre || '',
        alConfirmar: function () {
            quitarDelCarrito(index);
        }
    });
}

function quitarDelCarrito(index) {
    // Obtener copia del carrito actual
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
    }
    
    if (index >= 0 && index < carritoActual.length) {
        carritoActual.splice(index, 1);
        
        // Guardar copia en ventasAbiertas
        if (ventaActivaId && ventasAbiertas[ventaActivaId]) {
            ventasAbiertas[ventaActivaId].carrito = JSON.parse(JSON.stringify(carritoActual));
            carrito = JSON.parse(JSON.stringify(carritoActual));
        }
        
        actualizarCarrito();
    }
}

function baseDeItem(item) {
    if (item && item.base != null && item.base !== '') {
        return Math.round(parseFloat(item.base) || 0);
    }
    return Math.round(parseFloat(item && item.precio) || 0);
}

function leerCaja() {
    const subtotal = carrito.reduce((sum, item) => sum + (baseDeItem(item) * item.cantidad), 0);
    const descuentoPedido = Math.max(0, parseFloat(document.getElementById('descuento_venta').value) || 0);
    const descuento = Math.min(descuentoPedido, subtotal);
    const gravado = Math.max(0, subtotal - descuento);
    const iva = Math.round(gravado * 0.19);
    const domicilio = Math.max(0, parseFloat(document.getElementById('valor_domicilio').value) || 0);
    const empaque = Math.max(0, parseFloat(document.getElementById('valor_empaque').value) || 0);
    const campoIva = document.getElementById('iva_venta');
    if (campoIva) campoIva.value = String(iva);
    return {
        subtotal: subtotal,
        descuento: descuento,
        iva: iva,
        domicilio: domicilio,
        empaque: empaque,
        total: gravado + iva + domicilio + empaque
    };
}

function actualizarTotal() {
    const caja = leerCaja();
    document.getElementById('subtotal_carrito').textContent = '$' + formatearNumero(caja.subtotal);
    document.getElementById('descuento_aplicado').textContent = '$' + formatearNumero(caja.descuento);
    document.getElementById('iva_aplicado').textContent = '$' + formatearNumero(caja.iva);
    document.getElementById('domicilio_aplicado').textContent = '$' + formatearNumero(caja.domicilio);
    document.getElementById('empaque_aplicado').textContent = '$' + formatearNumero(caja.empaque);
    document.getElementById('total_carrito').textContent = '$' + formatearNumero(caja.total);
    if (document.getElementById('pos_pago_efectivo')) pintarPago();
}

function buscarCliente() {
    const termino = document.getElementById('buscar_cliente').value.trim();
    if (!termino) {
        document.getElementById('resultados_cliente').innerHTML = '';
        return;
    }
    
    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=clientes&method=buscar&termino=${encodeURIComponent(termino)}`)
        .then(r => r.text())
        .then(txt => {
            console.log('Respuesta buscarCliente (texto crudo):', txt);
            let data;
            try {
                data = JSON.parse(txt);
            } catch (e) {
                console.error('No se pudo parsear JSON en buscarCliente:', e);
                document.getElementById('resultados_cliente').innerHTML =
                    '<p class="text-danger">Respuesta no válida del servidor al buscar cliente. Revisa la consola.</p>';
                return;
            }

            let html = '';
            if (data.success && data.clientes && data.clientes.length > 0) {
                html = '<div class="list-group">';
                data.clientes.forEach(c => {
                    const nombre = (c.nombre_completo || '').replace(/'/g, "\\'");
                    const saldo = Math.max(0, parseFloat(c.saldo_fiado) || 0);
                    html += `<a href="#" class="list-group-item list-group-item-action" onclick="seleccionarCliente(${c.id}, '${nombre}', ${saldo}); return false;">
                        <strong>${c.nombre_completo}</strong> - ${c.cedula_nit}
                    </a>`;
                });
                html += '</div>';
            } else {
                html = '<p class="text-muted">No se encontraron clientes con ese dato. Puedes usar "Cliente General" o crear uno nuevo rápido.</p>';
            }
            document.getElementById('resultados_cliente').innerHTML = html;
        })
        .catch(err => {
            console.error('Error al buscar clientes (fetch):', err);
            document.getElementById('resultados_cliente').innerHTML =
                '<p class="text-danger">Error al buscar clientes. Revisa la consola.</p>';
        });
}

function seleccionarCliente(id, nombre, saldo) {
    document.getElementById('cliente_id').value = id;
    saldoFiadoCliente = Math.max(0, parseFloat(saldo) || 0);
    document.getElementById('cliente_seleccionado').innerHTML = `<p class="mb-0"><strong>Cliente:</strong> ${nombre}</p>`;
    document.getElementById('resultados_cliente').innerHTML = '';
    if (ventaActivaId && ventasAbiertas[ventaActivaId]) {
        ventasAbiertas[ventaActivaId].clienteId = parseInt(id, 10) || 1;
        ventasAbiertas[ventaActivaId].clienteNombre = nombre;
        ventasAbiertas[ventaActivaId].saldoFiado = saldoFiadoCliente;
    }
    pintarPago();
}

function envioTieneClienteReal() {
    const clienteId = parseInt(document.getElementById('cliente_id').value, 10) || 1;
    const envia = document.getElementById('con_domicilio').checked
        || document.getElementById('pago_contra_entrega').checked
        || document.getElementById('domicilio_contra_entrega').checked
        || (parseFloat(document.getElementById('valor_domicilio').value) || 0) > 0;
    if (envia && clienteId <= 1) {
        aviso('Este envío necesita el cliente real. Cliente General no tiene los datos de entrega.');
        return false;
    }
    return true;
}

function abrirObservacionesCobro() {
    if (!ventaActivaId || carrito.length === 0) {
        aviso('El carrito está vacío');
        return;
    }
    const metodoPago = document.getElementById('metodo_pago').value;
    const clienteId = parseInt(document.getElementById('cliente_id').value, 10) || 1;
    if (metodoPago === 'Fiado') {
        if (clienteId <= 1) {
            aviso('El fiado necesita un cliente con cédula, no el cliente general.');
            return;
        }
        procesarVenta();
        return;
    }
    document.getElementById('obs_domicilio_contra').checked = document.getElementById('domicilio_contra_entrega').checked;
    document.getElementById('obs_pedido_contra').checked = document.getElementById('pago_contra_entrega').checked;
    document.getElementById('obs_texto').value = document.getElementById('observaciones_domicilio').value;
    document.getElementById('obs_valor_domicilio').value = document.getElementById('valor_domicilio').value || '0';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalObservacionesCobro')).show();
}

function aceptarObservacionesCobro() {
    const domicilioContra = document.getElementById('obs_domicilio_contra').checked;
    const pedidoContra = document.getElementById('obs_pedido_contra').checked;
    const texto = document.getElementById('obs_texto').value.trim();
    const domicilioModal = Math.max(0, parseFloat(document.getElementById('obs_valor_domicilio').value) || 0);
    if (domicilioContra && domicilioModal <= 0) {
        aviso('Escribe el valor del domicilio. Entra en el total y queda por cobrar contra entrega.');
        return;
    }
    if ((domicilioContra || pedidoContra) && texto === '') {
        aviso('Escribe en las observaciones cómo se paga el envío o el pedido.');
        return;
    }
    document.getElementById('valor_domicilio').value = String(domicilioModal);
    document.getElementById('domicilio_contra_entrega').checked = domicilioContra;
    document.getElementById('pago_contra_entrega').checked = pedidoContra;
    document.getElementById('pago_pagado').checked = !pedidoContra;
    if (domicilioContra || pedidoContra || (parseFloat(document.getElementById('valor_domicilio').value) || 0) > 0) {
        document.getElementById('con_domicilio').checked = true;
        document.getElementById('div_observaciones_domicilio').style.display = 'block';
    }
    document.getElementById('observaciones_domicilio').value = texto;
    actualizarTotal();
    if (!envioTieneClienteReal()) return;
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalObservacionesCobro'));
    if (modal) modal.hide();
    procesarVenta();
}

function procesarVenta() {
    if (!ventaActivaId || carrito.length === 0) {
        alert('El carrito está vacío');
        return;
    }
    
    const formData = new FormData();
    formData.append('carrito', JSON.stringify(carrito));
    formData.append('cliente_id', document.getElementById('cliente_id').value);
    const caja = leerCaja();
    formData.append('descuento', String(caja.descuento));
    formData.append('iva', String(caja.iva));
    formData.append('domicilio', String(caja.domicilio));
    formData.append('empaque', String(caja.empaque));
    const metodoPago = document.getElementById('metodo_pago').value;
    const clienteId = parseInt(document.getElementById('cliente_id').value, 10) || 1;
    if (metodoPago === 'Fiado' && clienteId <= 1) {
        aviso('El fiado necesita un cliente con cédula, no el cliente general.');
        return;
    }
    if (!envioTieneClienteReal()) return;
    const partes = partesPago();
    if (partes.error) {
        aviso(partes.error);
        return;
    }
    formData.append('metodo_pago', metodoPago);
    formData.append('pago_efectivo', String(partes.pagoEfectivo));
    formData.append('pago_transferencia', String(partes.pagoTransferencia));
    formData.append('pago_tarjeta', String(partes.pagoTarjeta));
    formData.append('recibido', String(partes.recibido));
    formData.append('devuelta', String(partes.devuelta));
    const llevaDomicilio = caja.domicilio > 0 || document.getElementById('con_domicilio').checked;
    formData.append('con_domicilio', llevaDomicilio ? '1' : '0');
    const contraEntrega = document.getElementById('pago_contra_entrega');
    formData.append('pago_contra_entrega', contraEntrega && contraEntrega.checked ? '1' : '0');
    const domicilioContra = document.getElementById('domicilio_contra_entrega');
    formData.append('domicilio_contra_entrega', domicilioContra && domicilioContra.checked ? '1' : '0');
    formData.append('observaciones_domicilio', document.getElementById('observaciones_domicilio').value.trim());
    
    const btn = document.getElementById('btn_procesar_venta');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
    
    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=ventas&method=procesar`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => {
        if (r.ok) {
            return r.json();
        }
        throw new Error('Error en la respuesta del servidor');
    })
    .then(data => {
        if (data.success) {
            const factura = `${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=ventas&method=factura&id=${data.venta_id}`;
            if (metodoPago === 'Fiado') {
                aviso('Esta venta quedó agregada a la lista en mora.', function () {
                    window.location.href = factura;
                });
                return;
            }
            window.location.href = factura;
        } else {
            const decir = typeof aviso === 'function' ? aviso : alert;
            decir(data.error || 'No se pudo facturar la venta.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Cobrar';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la venta. Por favor, intente nuevamente.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Cobrar';
    });
}

// Crear cliente rápido vía AJAX
function guardarClienteRapido() {
    const nombre = document.getElementById('cliente_nombre_rapido').value.trim();
    const cedula = document.getElementById('cliente_cedula_rapido').value.trim();
    const telefono = document.getElementById('cliente_telefono_rapido').value.trim();
    const email = document.getElementById('cliente_email_rapido').value.trim();
    const direccion = document.getElementById('cliente_direccion_rapido').value.trim();
    const fechaNac = document.getElementById('cliente_fecha_rapido').value || null;
    const observaciones = document.getElementById('cliente_observaciones_rapido').value.trim();

    if (!nombre || !cedula) {
        alert('El nombre y la cédula/NIT son obligatorios');
        return;
    }

    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('cedula_nit', cedula);
    formData.append('telefono', telefono);
    formData.append('email', email);
    formData.append('direccion', direccion);
    if (fechaNac) {
        formData.append('fecha_nacimiento', fechaNac);
    }
    formData.append('observaciones', observaciones);

    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=clientes&method=crearRapido`, {
        method: 'POST',
        body: formData
    })
    .then(r => {
        // Leer como texto primero para depuración
        return r.text().then(text => {
            console.log('Respuesta crearRapido (texto crudo):', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                console.error('Texto recibido:', text);
                throw new Error('La respuesta del servidor no es JSON válido: ' + text.substring(0, 200));
            }
        });
    })
    .then(data => {
        if (!data.success) {
            alert(data.error || 'Error al crear el cliente');
            return;
        }

        const cliente = data.cliente;
        
        // Actualizar el estado de la venta activa
        if (ventaActivaId && ventasAbiertas[ventaActivaId]) {
            ventasAbiertas[ventaActivaId].clienteId = cliente.id;
            ventasAbiertas[ventaActivaId].clienteNombre = cliente.nombre_completo;
        }
        
        // Seleccionar el cliente en la interfaz (igual que cuando se selecciona desde la búsqueda)
        seleccionarCliente(cliente.id, cliente.nombre_completo);
        
        // Limpiar campo de búsqueda de cliente
        const buscaCliente = document.getElementById('buscar_cliente');
        if (buscaCliente) buscaCliente.value = '';

        // Cerrar modal y limpiar formulario
        const modalEl = document.getElementById('modalNuevoClienteRapido');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        document.getElementById('formNuevoClienteRapido').reset();
        
        // Mostrar mensaje de éxito
        console.log('Cliente creado y seleccionado:', cliente.nombre_completo);
    })
    .catch(err => {
        console.error('Error al crear cliente (fetch):', err);
        alert('Error al crear el cliente: ' + err.message);
    });
}

