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

function ivaIncluidoEn(precio) {
    const valor = Math.round(parseFloat(precio) || 0);
    if (valor <= 0) {
        return 0;
    }
    return Math.round(valor * 0.19 / 1.19);
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
    document.querySelectorAll('input[name="obs_modo_entrega"]').forEach(function (radio) {
        radio.addEventListener('change', explicarModoEntrega);
    });
    const obsValor = document.getElementById('obs_valor_domicilio');
    if (obsValor) obsValor.addEventListener('input', explicarModoEntrega);
    
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

