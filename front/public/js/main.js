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
            if (e.defaultPrevented) {
                return;
            }
            if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') {
                return;
            }
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

function iniciarPaginacion(nav) {
    const caja = nav.previousElementSibling;
    const tabla = caja ? caja.querySelector('table') : null;
    const cuerpo = tabla ? tabla.tBodies[0] : null;
    if (!cuerpo) {
        return;
    }
    const porPagina = parseInt(nav.dataset.por, 10) || 10;
    const anterior = nav.querySelector('.paginacion-anterior');
    const siguiente = nav.querySelector('.paginacion-siguiente');
    const numeros = nav.querySelector('.paginacion-paginas');
    const resumen = nav.querySelector('.paginacion-resumen');
    let pagina = 1;

    function filas() {
        return Array.from(cuerpo.rows).filter(function (fila) {
            return !fila.querySelector('td[colspan]');
        });
    }

    function aplicar() {
        const todas = filas();
        todas.forEach(function (fila) {
            fila.classList.remove('fuera-pagina');
        });
        const visibles = todas.filter(function (fila) {
            return fila.style.display !== 'none';
        });
        const paginas = Math.max(1, Math.ceil(visibles.length / porPagina));
        if (pagina > paginas) {
            pagina = paginas;
        }
        if (pagina < 1) {
            pagina = 1;
        }
        const desde = (pagina - 1) * porPagina;
        visibles.forEach(function (fila, indice) {
            fila.classList.toggle('fuera-pagina', indice < desde || indice >= desde + porPagina);
        });
        nav.hidden = paginas < 2;
        anterior.disabled = pagina <= 1;
        siguiente.disabled = pagina >= paginas;
        numeros.innerHTML = '';
        let inicio = Math.max(1, pagina - 2);
        let fin = Math.min(paginas, inicio + 4);
        inicio = Math.max(1, fin - 4);
        for (let numero = inicio; numero <= fin; numero += 1) {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'btn btn-sm ' + (numero === pagina ? 'btn-primary' : 'btn-outline-secondary');
            boton.textContent = String(numero);
            boton.addEventListener('click', function () {
                pagina = numero;
                aplicar();
            });
            numeros.appendChild(boton);
        }
        if (visibles.length === 0) {
            resumen.textContent = '';
            return;
        }
        const hasta = Math.min(visibles.length, desde + porPagina);
        resumen.textContent = (desde + 1) + '–' + hasta + ' de ' + visibles.length;
    }

    anterior.addEventListener('click', function () {
        if (pagina > 1) {
            pagina -= 1;
            aplicar();
        }
    });
    siguiente.addEventListener('click', function () {
        pagina += 1;
        aplicar();
    });
    new MutationObserver(function () {
        pagina = 1;
        aplicar();
    }).observe(cuerpo, { childList: true });
    nav._aplicar = function () {
        aplicar();
    };
    aplicar();
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.paginacion').forEach(iniciarPaginacion);
});

document.addEventListener('input', function () {
    document.querySelectorAll('.paginacion').forEach(function (nav) {
        if (typeof nav._aplicar === 'function') {
            nav._aplicar();
        }
    });
});

// Popovers de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

document.addEventListener('DOMContentLoaded', function () {
    let flotante = null;
    let activa = null;
    let cerrarLuego = null;

    function ocultar() {
        clearTimeout(cerrarLuego);
        if (flotante) {
            flotante.remove();
            flotante = null;
        }
        if (activa) {
            const boton = activa.querySelector('.ayuda-icono');
            if (boton) {
                boton.setAttribute('aria-expanded', 'false');
            }
            activa = null;
        }
    }

    function mostrar(ayuda) {
        const origen = ayuda.querySelector('.ayuda-tarjeta');
        const boton = ayuda.querySelector('.ayuda-icono');
        if (!origen || !boton) {
            return;
        }
        if (activa === ayuda && flotante) {
            return;
        }
        ocultar();
        activa = ayuda;
        boton.setAttribute('aria-expanded', 'true');
        flotante = origen.cloneNode(true);
        flotante.classList.add('ayuda-tarjeta-flotante');
        document.body.appendChild(flotante);
        const rect = boton.getBoundingClientRect();
        const margen = 12;
        let left = rect.left;
        let top = rect.bottom + 8;
        const ancho = flotante.offsetWidth;
        const alto = flotante.offsetHeight;
        if (left + ancho > window.innerWidth - margen) {
            left = Math.max(margen, window.innerWidth - ancho - margen);
        }
        if (top + alto > window.innerHeight - margen) {
            top = Math.max(margen, rect.top - alto - 8);
        }
        flotante.style.left = left + 'px';
        flotante.style.top = top + 'px';
        flotante.addEventListener('mouseenter', function () {
            clearTimeout(cerrarLuego);
        });
        flotante.addEventListener('mouseleave', programarCierre);
    }

    function programarCierre() {
        clearTimeout(cerrarLuego);
        cerrarLuego = setTimeout(ocultar, 160);
    }

    document.addEventListener('mouseover', function (evento) {
        const ayuda = evento.target.closest('.ayuda');
        if (!ayuda) {
            return;
        }
        clearTimeout(cerrarLuego);
        mostrar(ayuda);
    });

    document.addEventListener('mouseout', function (evento) {
        if (!activa) {
            return;
        }
        const hacia = evento.relatedTarget;
        if (hacia && (activa.contains(hacia) || (flotante && flotante.contains(hacia)))) {
            return;
        }
        if (evento.target.closest('.ayuda') === activa || (flotante && flotante.contains(evento.target))) {
            programarCierre();
        }
    });

    document.addEventListener('focusin', function (evento) {
        const ayuda = evento.target.closest('.ayuda');
        if (ayuda) {
            mostrar(ayuda);
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            ocultar();
        }
    });

    document.addEventListener('click', function (evento) {
        if (!window.matchMedia('(hover: none)').matches) {
            return;
        }
        const boton = evento.target.closest('.ayuda-icono');
        if (boton) {
            evento.preventDefault();
            const ayuda = boton.closest('.ayuda');
            if (activa === ayuda) {
                ocultar();
            } else {
                mostrar(ayuda);
            }
            return;
        }
        if (!evento.target.closest('.ayuda-tarjeta-flotante')) {
            ocultar();
        }
    });

    window.addEventListener('scroll', ocultar, true);
    window.addEventListener('resize', ocultar);
});

(function () {
    let avisoInstalar = null;

    window.addEventListener('beforeinstallprompt', function (evento) {
        evento.preventDefault();
        avisoInstalar = evento;
    });

    function esMovil() {
        const ua = navigator.userAgent || '';
        if (/Android|iPhone|iPad|iPod/i.test(ua)) {
            return true;
        }
        if (navigator.maxTouchPoints > 1 && /Macintosh/i.test(ua)) {
            return true;
        }
        return window.matchMedia('(max-width: 1024px) and (pointer: coarse)').matches;
    }

    function enPantallaInicio() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const pcs = document.querySelectorAll('[data-instalar="pc"]');
        const moviles = document.querySelectorAll('[data-instalar="movil"]');
        if (!pcs.length && !moviles.length) {
            return;
        }
        if (enPantallaInicio()) {
            pcs.forEach(function (el) { el.classList.add('d-none'); });
            moviles.forEach(function (el) { el.classList.add('d-none'); });
            return;
        }
        if (esMovil()) {
            pcs.forEach(function (el) { el.classList.add('d-none'); });
            moviles.forEach(function (el) { el.classList.remove('d-none'); });
        }
        moviles.forEach(function (boton) {
            boton.addEventListener('click', function () {
                if (avisoInstalar) {
                    const pendiente = avisoInstalar;
                    avisoInstalar = null;
                    pendiente.prompt();
                    return;
                }
                const ios = /iPhone|iPad|iPod/i.test(navigator.userAgent)
                    || (navigator.maxTouchPoints > 1 && /Macintosh/i.test(navigator.userAgent));
                aviso(ios
                    ? 'En Safari toca Compartir y luego Agregar a inicio. El icono de Nunca Jamás queda en la pantalla y abre la app. Hace falta internet.'
                    : 'En Chrome abre el menú de tres puntos y toca Instalar aplicación o Agregar a la pantalla principal. El icono queda en el celular y abre la app. Hace falta internet.');
            });
        });
    });
})();

