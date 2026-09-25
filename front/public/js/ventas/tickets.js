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
    const domicilioContra = document.getElementById('domicilio_contra_entrega').checked;
    const cobrarAhora = domicilioContra && !contra ? Math.max(0, caja.total - caja.domicilio) : caja.total;
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
        const devuelta = recibidoEfectivo - cobrarAhora;
        return {
            total: caja.total,
            pagoEfectivo: cobrarAhora,
            pagoTransferencia: 0,
            pagoTarjeta: 0,
            recibido: recibidoEfectivo,
            devuelta: devuelta > 0 ? devuelta : 0,
            falta: cobrarAhora,
            error: devuelta < -0.001 ? 'El billete no alcanza para lo que se cobra ahora.' : ''
        };
    }
    if (metodo === 'Tarjeta') {
        vacio.pagoTarjeta = cobrarAhora;
        return vacio;
    }
    if (metodo === 'Transferencia') {
        vacio.pagoTransferencia = cobrarAhora;
        return vacio;
    }
    const efectivo = Math.max(0, cobrarAhora - transferencia);
    const devuelta = recibidoMixto - efectivo;
    let error = '';
    if (transferencia <= 0 || transferencia >= cobrarAhora) {
        error = 'En mixto escribe cuánto va por transferencia. El resto se paga en efectivo.';
    } else if (devuelta < -0.001) {
        error = 'El efectivo recibido no cubre lo que falta.';
    }
    return {
        total: caja.total,
        pagoEfectivo: efectivo,
        pagoTransferencia: Math.min(transferencia, cobrarAhora),
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

