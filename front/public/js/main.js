/**
 * JavaScript principal del Sistema de Inventario
 */

(function () {
    if (!window.fetch) {
        return;
    }
    const fetchOriginal = window.fetch.bind(window);
    window.fetch = function (entrada, opciones) {
        const init = opciones ? Object.assign({}, opciones) : {};
        const metodo = String(init.method || 'GET').toUpperCase();
        if ((metodo === 'POST' || metodo === 'PUT' || metodo === 'PATCH' || metodo === 'DELETE') && window.CSRF_TOKEN) {
            const cabeceras = new Headers(init.headers || {});
            if (!cabeceras.has('X-CSRF-TOKEN')) {
                cabeceras.set('X-CSRF-TOKEN', window.CSRF_TOKEN);
            }
            init.headers = cabeceras;
        }
        return fetchOriginal(entrada, init);
    };
})();

// Auto-ocultar alertas después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Confirmación antes de eliminar
function confirmarEliminacion(mensaje = '¿Está seguro de realizar esta acción?') {
    return confirm(mensaje);
}

function mostrarAviso(opciones) {
    const modalEl = document.getElementById('modalAviso');
    if (!modalEl || !window.bootstrap) {
        return;
    }
    const titulo = document.getElementById('modalAvisoTitulo');
    const texto = document.getElementById('modalAvisoTexto');
    const cancelar = document.getElementById('modalAvisoCancelar');
    const ok = document.getElementById('modalAvisoOk');
    const confirmar = !!(opciones && opciones.confirmar);
    titulo.textContent = (opciones && opciones.titulo) || 'Aviso';
    texto.textContent = (opciones && opciones.mensaje) || '';
    cancelar.classList.toggle('d-none', !confirmar);
    ok.textContent = (opciones && opciones.boton) || 'Aceptar';
    ok.className = 'btn btn-primary';
    let acepto = false;
    ok.onclick = function () {
        acepto = true;
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    };
    modalEl.addEventListener('hidden.bs.modal', function alCerrar() {
        modalEl.removeEventListener('hidden.bs.modal', alCerrar);
        if (acepto && confirmar && opciones && typeof opciones.alConfirmar === 'function') {
            opciones.alConfirmar();
            return;
        }
        if (!confirmar && opciones && typeof opciones.alCerrar === 'function') {
            opciones.alCerrar();
        }
    });
    modalEl.addEventListener('show.bs.modal', function alFrente() {
        const abiertos = document.querySelectorAll('.modal.show').length;
        const z = 1055 + (abiertos * 20);
        modalEl.style.zIndex = String(z);
        setTimeout(function () {
            const fondos = document.querySelectorAll('.modal-backdrop');
            if (fondos.length) {
                fondos[fondos.length - 1].style.zIndex = String(z - 5);
            }
            document.querySelectorAll('.modal.show').forEach(function (otro) {
                if (otro === modalEl) {
                    return;
                }
                const instancia = bootstrap.Modal.getInstance(otro);
                if (instancia && instancia._focustrap) {
                    instancia._focustrap.deactivate();
                }
            });
        }, 0);
        modalEl.removeEventListener('show.bs.modal', alFrente);
    });
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function pedirConfirmacion(opciones) {
    mostrarAviso({
        titulo: (opciones && opciones.titulo) || 'Confirmar',
        mensaje: (opciones && opciones.mensaje) || '',
        boton: (opciones && opciones.boton) || 'Aceptar',
        confirmar: true,
        alConfirmar: opciones && opciones.alConfirmar
    });
}

function aviso(mensaje, alCerrar) {
    mostrarAviso({
        titulo: 'Aviso',
        mensaje: mensaje,
        alCerrar: alCerrar
    });
}

function pedirDobleConfirmacion(opciones) {
    const modalEl = document.getElementById('modalDobleEliminar');
    if (!modalEl || !window.bootstrap) {
        return;
    }
    const titulo = document.getElementById('modalDobleEliminarTitulo');
    const detalle = document.getElementById('modalDobleEliminarDetalle');
    const codigoTexto = document.getElementById('modalDobleEliminarCodigoTexto');
    const campo = document.getElementById('modalDobleEliminarCampo');
    const aviso = document.getElementById('modalDobleEliminarAviso');
    const ok = document.getElementById('modalDobleEliminarOk');
    const esperado = String((opciones && opciones.codigo) || '').trim();
    titulo.textContent = (opciones && opciones.titulo) || 'Eliminar';
    detalle.textContent = (opciones && opciones.detalle) || 'Esta acción no se puede deshacer.';
    codigoTexto.textContent = esperado;
    campo.value = '';
    aviso.classList.add('d-none');
    ok.disabled = true;
    ok.textContent = (opciones && opciones.boton) || 'Eliminar';
    const coincide = function () {
        const escrito = campo.value.trim().toLowerCase();
        const bien = escrito !== '' && escrito === esperado.toLowerCase();
        ok.disabled = !bien;
        aviso.classList.toggle('d-none', campo.value.trim() === '' || bien);
        return bien;
    };
    campo.oninput = coincide;
    ok.onclick = function () {
        if (!coincide()) {
            return;
        }
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        if (opciones && typeof opciones.alConfirmar === 'function') {
            opciones.alConfirmar(campo.value.trim());
        }
    };
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
    modalEl.addEventListener('shown.bs.modal', function enfocar() {
        campo.focus();
        modalEl.removeEventListener('shown.bs.modal', enfocar);
    });
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || !form.classList || !form.classList.contains('form-doble-eliminar')) {
        return;
    }
    if (form.dataset.confirmado === '1') {
        return;
    }
    e.preventDefault();
    pedirDobleConfirmacion({
        titulo: form.getAttribute('data-titulo') || 'Eliminar',
        detalle: form.getAttribute('data-detalle') || 'Esta acción no se puede deshacer.',
        codigo: form.getAttribute('data-codigo') || '',
        alConfirmar: function (codigo) {
            let oculto = form.querySelector('input[name="codigo_confirmacion"]');
            if (!oculto) {
                oculto = document.createElement('input');
                oculto.type = 'hidden';
                oculto.name = 'codigo_confirmacion';
                form.appendChild(oculto);
            }
            oculto.value = codigo;
            form.dataset.confirmado = '1';
            form.submit();
        }
    });
});

// Formatear números como moneda (sin decimales, punto como separador de miles)
function formatearMoneda(valor) {
    const numero = Math.round(parseFloat(valor) || 0);
    return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Formatear números como moneda con símbolo de peso
function formatearMonedaConSimbolo(valor) {
    return '$' + formatearMoneda(valor);
}

// Validar formularios
function validarFormulario(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    if (form.checkValidity() === false) {
        form.classList.add('was-validated');
        return false;
    }
    
    return true;
}

// Auto-focus en campos de búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const busquedaInputs = document.querySelectorAll('input[type="text"][placeholder*="Buscar"]');
    busquedaInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    });
});

// Prevenir doble envío de formularios
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
            }
        });
    });
});

// Tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Popovers de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

