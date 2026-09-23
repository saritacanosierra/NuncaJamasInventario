/**
 * JavaScript principal del Sistema de Inventario
 */

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

