function leerCaja() {
    const subtotal = carrito.reduce((sum, item) => sum + ((parseInt(item.precio, 10) || baseDeItem(item)) * item.cantidad), 0);
    const descuentoPedido = Math.max(0, parseFloat(document.getElementById('descuento_venta').value) || 0);
    const descuento = Math.min(descuentoPedido, subtotal);
    const gravado = Math.max(0, subtotal - descuento);
    const iva = ivaIncluidoEn(gravado);
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
        total: gravado + domicilio + empaque
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
    const lista = document.getElementById('resultados_cliente');
    if (!termino) {
        lista.innerHTML = '';
        return;
    }
    lista.innerHTML = '<p class="text-muted">Buscando...</p>';

    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=clientes&method=buscar&termino=${encodeURIComponent(termino)}`)
        .then(r => r.text())
        .then(txt => {
                        let data;
            try {
                data = JSON.parse(txt);
            } catch (e) {
                document.getElementById('resultados_cliente').innerHTML =
                    '<p class="text-danger">No se pudo leer la respuesta al buscar el cliente.</p>';
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
            document.getElementById('resultados_cliente').innerHTML =
                '<p class="text-danger">No se pudo buscar el cliente. Intenta de nuevo.</p>';
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
    const pedidoContra = document.getElementById('pago_contra_entrega').checked;
    const domicilioContra = document.getElementById('domicilio_contra_entrega').checked;
    const domicilio = parseFloat(document.getElementById('valor_domicilio').value) || 0;
    const envia = document.getElementById('con_domicilio').checked || domicilio > 0;
    let modo = 'recogida';
    if (pedidoContra) modo = 'pedido_contra';
    else if (domicilioContra) modo = 'domicilio_contra';
    else if (envia) modo = 'factura';
    document.getElementById('obs_texto').value = document.getElementById('observaciones_domicilio').value;
    document.getElementById('obs_valor_domicilio').value = document.getElementById('valor_domicilio').value || '0';
    marcarModoEntrega(modo);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalObservacionesCobro')).show();
}

function modoEntregaElegido() {
    const marcado = document.querySelector('input[name="obs_modo_entrega"]:checked');
    return marcado ? marcado.value : 'recogida';
}

function marcarModoEntrega(modo) {
    const radio = document.querySelector('input[name="obs_modo_entrega"][value="' + modo + '"]');
    if (radio) radio.checked = true;
    explicarModoEntrega();
}

function explicarModoEntrega() {
    const ayuda = document.getElementById('obs_explicacion');
    if (!ayuda) return;
    const modo = modoEntregaElegido();
    const valor = Math.max(0, parseFloat(document.getElementById('obs_valor_domicilio').value) || 0);
    const bloque = document.getElementById('obs_bloque_domicilio');
    if (bloque) bloque.style.display = modo === 'recogida' ? 'none' : '';
    const caja = leerCaja();
    const producto = Math.max(0, caja.total - caja.domicilio);
    const dinero = function (n) { return '$' + formatearNumero(Math.round(n)); };
    if (modo === 'recogida') {
        ayuda.textContent = 'Recogida en tienda. El domicilio queda en 0. Se cobra ahora ' + dinero(producto) + ', solo el producto' + (caja.empaque > 0 ? ' y el empaque' : '') + '.';
        return;
    }
    if (modo === 'factura') {
        ayuda.textContent = valor > 0
            ? 'El domicilio de ' + dinero(valor) + ' se paga ahora, junto con el pedido. Se cobra ' + dinero(producto + valor) + '.'
            : 'Escribe el valor del domicilio. Si lo dejas en 0, la factura solo cobra el producto.';
        return;
    }
    if (modo === 'domicilio_contra') {
        ayuda.textContent = valor > 0
            ? 'Se cobra ahora ' + dinero(producto) + '. El domicilio de ' + dinero(valor) + ' queda para contra entrega.'
            : 'Escribe el valor del domicilio que se cobra contra entrega. El pedido ya está pago.';
        return;
    }
    ayuda.textContent = valor > 0
        ? 'No se cobra nada ahora. Contra entrega quedan el pedido (' + dinero(producto) + ') y el domicilio (' + dinero(valor) + ').'
        : 'El domicilio está en 0. No se cobra nada ahora: contra entrega queda solo el pedido, ' + dinero(producto) + '.';
}

function aceptarObservacionesCobro() {
    const modo = modoEntregaElegido();
    const texto = document.getElementById('obs_texto').value.trim();
    let domicilioModal = Math.max(0, parseFloat(document.getElementById('obs_valor_domicilio').value) || 0);
    if (modo === 'recogida') domicilioModal = 0;
    if ((modo === 'factura' || modo === 'domicilio_contra') && domicilioModal <= 0) {
        aviso('Escribe el valor del domicilio. En 0 solo se cobra el producto, o es recogida en tienda.');
        return;
    }
    if (modo !== 'recogida' && texto === '') {
        aviso('Escribe la nota para quien entrega: dirección, horario o cómo se cobra.');
        return;
    }
    const pedidoContra = modo === 'pedido_contra';
    const domicilioContra = modo === 'domicilio_contra' || (pedidoContra && domicilioModal > 0);
    document.getElementById('valor_domicilio').value = String(domicilioModal);
    document.getElementById('domicilio_contra_entrega').checked = domicilioContra;
    document.getElementById('pago_contra_entrega').checked = pedidoContra;
    document.getElementById('pago_pagado').checked = !pedidoContra;
    const envia = modo !== 'recogida';
    document.getElementById('con_domicilio').checked = envia;
    document.getElementById('div_observaciones_domicilio').style.display = envia ? 'block' : 'none';
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
                        try {
                return JSON.parse(text);
            } catch (e) {
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
            })
    .catch(err => {
        alert('Error al crear el cliente: ' + err.message);
    });
}

