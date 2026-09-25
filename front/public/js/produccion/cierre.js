function registrarHistorialCambio(operacionId, tipoCambio, datos = {}) {
    // Esta función se llama para registrar cambios en el historial
    // El historial se registra automáticamente en el servidor cuando se guarda/actualiza la operación
    // Por ahora, solo registramos en consola para debugging
    }

// ==== Funciones de retrocesos ====
function abrirModalRetroceso(operacionId = null, operariaId = null) {
    window.operariaIdRetroceso = operariaId || operariaActivaId;
    document.getElementById('retroceso_operacion_id').value = operacionId || '';
    document.getElementById('formRetroceso').reset();
    if (operacionId) {
        document.getElementById('retroceso_operacion_id').value = operacionId;
    }
    const modal = new bootstrap.Modal(document.getElementById('modalRetroceso'));
    modal.show();
}

function guardarRetroceso() {
    const form = document.getElementById('formRetroceso');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const operariaId = window.operariaIdRetroceso || operariaActivaId;
    if (!operariaId || !operariasAbiertas[operariaId]) {
        aviso('Debe seleccionar una operaria primero');
        return;
    }
    
    const operaria = operariasAbiertas[operariaId];
    const formData = new FormData(form);
    formData.append('fecha', operaria.datos.fecha || window.FECHA_ACTUAL);
    formData.append('operaria_nombre', operaria.nombre);
    
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=guardarRetroceso`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAviso({
                titulo: 'Problema guardado',
                mensaje: 'El retroceso quedó registrado.',
                alCerrar: function () {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalRetroceso'));
                    if (modal) modal.hide();
                }
            });
            cargarDatosOperaria(operariaId);
        } else {
            aviso('Error: ' + (data.error || 'No se pudo registrar el retroceso'));
        }
    })
    .catch(error => {
        aviso('Error al registrar el retroceso');
    });
}

// ==== Utilidades ====
function limpiarFormularioOperacion() {
    document.getElementById('formOperacion').reset();
    document.getElementById('operacion_id').value = '';
    document.getElementById('hora_inicio').value = '';
    document.getElementById('hora_fin').value = '';
    document.getElementById('tiempo_total_minutos').value = '';
    // No limpiar el código de operación aquí, se generará automáticamente al abrir el modal
}

function cambiarFecha(fecha) {
    window.location.href = `${BASE_URL_PROD}index.php?action=produccion&fecha=${fecha}`;
}

// ==== Funciones para verificar estado del día finalizado ====
function verificarEstadoDiaFinalizado() {
    const fecha = document.getElementById('fechaSeleccionada')?.value || window.FECHA_ACTUAL;
    
    return fetch(`${BASE_URL_PROD}index.php?action=produccion&method=verificarDiaFinalizado&fecha=${fecha}`)
        .then(response => {
            // Verificar si la respuesta es JSON
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                return response.text().then(text => {
                    throw new Error('El servidor devolvió HTML en lugar de JSON');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.DIA_FINALIZADO = data.finalizado;
                                // Actualizar botones si ya están renderizados
                actualizarBotonesEstadoFinalizado();
                return data.finalizado;
            }
            window.DIA_FINALIZADO = false;
            return false;
        })
        .catch(error => {
            // Por defecto, asumir que no está finalizado
            window.DIA_FINALIZADO = false;
            return false;
        });
}

function actualizarBotonesEstadoFinalizado() {
    const esFinalizado = window.DIA_FINALIZADO === true;
    
    // Deshabilitar botón "Nueva Operación" si el día está finalizado
    const botonesNuevaOperacion = document.querySelectorAll('[id^="btnNuevaOperacion_"]');
    botonesNuevaOperacion.forEach(btn => {
        const enDia = btn.getAttribute('data-en-dia') !== '0';
        if (esFinalizado || !enDia) {
            btn.disabled = true;
            btn.classList.add('disabled');
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.title = esFinalizado
                ? 'El día está cerrado. No se pueden anotar trabajos.'
                : 'Primero guarda a la persona en el día.';
        } else {
            btn.disabled = false;
            btn.classList.remove('disabled');
            btn.style.opacity = '';
            btn.style.cursor = '';
            btn.title = 'Anota un trabajo de esta persona.';
        }
    });
    
    // Deshabilitar botón "Guardar Operación" en el modal si el día está finalizado
    const btnGuardarOperacion = document.getElementById('btnGuardarOperacion');
    if (btnGuardarOperacion) {
        if (esFinalizado) {
            btnGuardarOperacion.disabled = true;
            btnGuardarOperacion.classList.add('disabled');
            btnGuardarOperacion.style.opacity = '0.5';
            btnGuardarOperacion.style.cursor = 'not-allowed';
            btnGuardarOperacion.title = 'Día finalizado - No se pueden guardar operaciones';
        } else {
            // Si el día no está finalizado, verificar campos para habilitar/deshabilitar según corresponda
            verificarCamposParaGuardar();
        }
    }
    
    // Buscar botones en la tabla de operaciones
    const tbodyOperaciones = document.querySelector('.tbody-operaciones');
    if (tbodyOperaciones) {
        const botones = tbodyOperaciones.querySelectorAll('button');
        
        botones.forEach(btn => {
            const onclick = btn.getAttribute('onclick') || '';
            const esHistorial = onclick.includes('verHistorialOperacion');
            
            // Solo modificar botones de editar, eliminar y retrocesos (no historial)
            if (!esHistorial) {
                if (esFinalizado) {
                    btn.disabled = true;
                    btn.classList.add('disabled');
                    btn.style.opacity = '0.5';
                    btn.style.cursor = 'not-allowed';
                    // Cambiar onclick para prevenir acción
                    const originalOnclick = btn.getAttribute('onclick');
                    btn.setAttribute('data-original-onclick', originalOnclick);
                    btn.setAttribute('onclick', 'aviso("No se puede realizar esta acción en un día finalizado"); return false;');
                } else {
                    btn.disabled = false;
                    btn.classList.remove('disabled');
                    btn.style.opacity = '';
                    btn.style.cursor = '';
                    // Restaurar onclick original
                    const originalOnclick = btn.getAttribute('data-original-onclick');
                    if (originalOnclick) {
                        btn.setAttribute('onclick', originalOnclick);
                        btn.removeAttribute('data-original-onclick');
                    }
                }
            }
        });
    }
    
    const btnCerrarEmpresa = document.getElementById('btnFinalizarDia');
    if (btnCerrarEmpresa) {
        btnCerrarEmpresa.hidden = esFinalizado;
        btnCerrarEmpresa.disabled = esFinalizado;
        btnCerrarEmpresa.title = 'Cierra el día de toda la empresa. Solo se puede hacer una vez.';
    }

    const btnAbrirEmpresa = document.getElementById('btnReabrirDia');
    if (btnAbrirEmpresa) {
        btnAbrirEmpresa.hidden = !esFinalizado;
        btnAbrirEmpresa.disabled = !esFinalizado;
    }

    const btnNuevaOperaria = document.getElementById('btnNuevaOperaria');
    if (btnNuevaOperaria) {
        btnNuevaOperaria.disabled = esFinalizado;
        btnNuevaOperaria.title = esFinalizado
            ? 'El día está cerrado. Ábrelo para sumar a alguien.'
            : 'Suma una persona a este día.';
    }

    document.querySelectorAll('.formRegistroDia').forEach(form => {
        form.querySelectorAll('.turno, .meta_dia, .maquina_asignada, .fecha, button[type="submit"]').forEach(campo => {
            campo.disabled = esFinalizado;
        });
    });

    const estadoDia = document.getElementById('estadoDia');
    if (estadoDia) {
        estadoDia.className = 'estado-dia ' + (esFinalizado ? 'estado-cerrado' : 'estado-abierto');
        estadoDia.innerHTML = esFinalizado
            ? '<i class="bi bi-lock"></i><div><strong>Día cerrado</strong><span>Nadie puede registrar trabajo. Solo un administrador puede abrirlo de nuevo.</span></div>'
            : '<i class="bi bi-unlock"></i><div><strong>Día abierto</strong><span>Las operarias pueden registrar su trabajo. Cerrar el día bloquea a toda la empresa y solo se hace una vez.</span></div>';
    }

    }

// ==== Funciones para Finalizar Día ====
function abrirModalFinalizarDia() {
    if (window.DIA_FINALIZADO === true) {
        aviso('Este día ya está cerrado. Solo se cierra una vez. Si hace falta, usa Abrir el día.');
        return;
    }
    const fecha = document.getElementById('fechaSeleccionada').value || window.FECHA_ACTUAL;
    
    // Establecer la fecha en el formulario
    document.getElementById('fecha_cierre').value = fecha;
    
    // Obtener solo los totales de operarias y operaciones (para referencia, no editables)
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=getResumenDia&fecha=${fecha}`)
        .then(response => {
            // Verificar si la respuesta es JSON
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                return response.text().then(text => {
                    // Continuar de todas formas, solo no cargaremos los totales
                    return { success: false };
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Solo actualizar los campos de referencia (readonly)
                document.getElementById('total_operarias').value = data.total_operarias || 0;
                document.getElementById('total_operaciones').value = data.total_operaciones || 0;
            }
            
            // Limpiar campos editables
            document.getElementById('prendas_terminadas').value = '';
            document.getElementById('prendas_empezadas').value = '';
            document.getElementById('tiempo_total_horas').value = '0';
            document.getElementById('tiempo_total_minutos').value = '0';
            document.getElementById('tiempo_total_segundos').value = '0';
            document.getElementById('observaciones_cierre').value = '';
            
            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('modalFinalizarDia'));
            modal.show();
        })
        .catch(error => {
            // Abrir modal de todas formas, el usuario puede llenar los datos manualmente
            const modal = new bootstrap.Modal(document.getElementById('modalFinalizarDia'));
            modal.show();
        });
}

function formatearTiempo(minutosDecimales) {
    const totalSegundos = Math.round(minutosDecimales * 60);
    const horas = Math.floor(totalSegundos / 3600);
    const minutos = Math.floor((totalSegundos % 3600) / 60);
    const segundos = totalSegundos % 60;
    
    if (horas > 0) {
        return `${horas}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
    }
    return `${minutos}:${String(segundos).padStart(2, '0')}`;
}

function guardarCierreDia() {
    const fecha = document.getElementById('fecha_cierre').value;
    const prendasTerminadas = parseInt(document.getElementById('prendas_terminadas').value) || 0;
    const prendasEmpezadas = parseInt(document.getElementById('prendas_empezadas').value) || 0;
    const horas = parseInt(document.getElementById('tiempo_total_horas').value) || 0;
    const minutos = parseInt(document.getElementById('tiempo_total_minutos').value) || 0;
    const segundos = parseInt(document.getElementById('tiempo_total_segundos').value) || 0;
    const observaciones = document.getElementById('observaciones_cierre').value.trim();
    
    // Validar campos requeridos
    if (!fecha) {
        aviso('Error: No se pudo obtener la fecha');
        return;
    }
    
    if (prendasTerminadas < 0 || prendasEmpezadas < 0) {
        aviso('Por favor, ingrese valores válidos para las prendas');
        return;
    }
    
    // Convertir tiempo a minutos decimales
    const tiempoTotalMinutos = (horas * 60) + minutos + (segundos / 60);
    
    const formData = new FormData();
    formData.append('fecha', fecha);
    formData.append('prendas_terminadas', prendasTerminadas);
    formData.append('prendas_empezadas', prendasEmpezadas);
    formData.append('tiempo_total_minutos', tiempoTotalMinutos.toFixed(2));
    formData.append('observaciones', observaciones);
    
    // Deshabilitar botón mientras se guarda
    const btnGuardar = document.getElementById('btnGuardarCierreDia');
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
    
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=finalizarDia`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            return response.text().then(text => {
                throw new Error('El servidor devolvió HTML en lugar de JSON');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.DIA_FINALIZADO = true;
            const formulario = document.getElementById('modalFinalizarDia');
            const abierto = bootstrap.Modal.getInstance(formulario);
            const decir = function () {
                mostrarAviso({
                    titulo: 'Día cerrado',
                    mensaje: 'El día de la empresa quedó cerrado. Las operaciones de esta fecha quedan bloqueadas.',
                    alCerrar: function () {
                        window.location.reload();
                    }
                });
            };
            if (abierto) {
                formulario.addEventListener('hidden.bs.modal', function unaVez() {
                    formulario.removeEventListener('hidden.bs.modal', unaVez);
                    decir();
                });
                abierto.hide();
            } else {
                decir();
            }
        } else {
            aviso('Error al finalizar el día: ' + (data.error || 'Error desconocido'));
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="bi bi-lock"></i> Cerrar el día';
        }
    })
    .catch(error => {
        aviso('Error al finalizar el día: ' + error.message);
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="bi bi-lock"></i> Cerrar el día';
    });
}

function mostrarGuiaDia() {
    if (window.PRODUCCION_SOLO_PROPIOS) {
        return;
    }
    const contenedor = document.getElementById('contenedorOperarias');
    if (!contenedor) {
        return;
    }
    const cerrado = window.DIA_FINALIZADO === true;
    contenedor.innerHTML = cerrado
        ? '<div class="prod-vacia"><strong>Este día está cerrado.</strong><p>No hay personas para anotar. Si falta alguien, abre el día y luego usa Nueva operaria.</p></div>'
        : '<div class="prod-vacia"><strong>Nadie está en este día.</strong><p>1. Toca <strong>Nueva operaria</strong> y elige a la persona.<br>2. Elige el turno y la máquina.<br>3. Toca <strong>Guardar en el día</strong>.<br>Después anota cada trabajo con <strong>Nueva operación</strong>.</p></div>';
}

function reabrirDiaEmpresa() {
    if (window.DIA_FINALIZADO !== true) {
        return;
    }
    pedirConfirmacion({
        titulo: 'Abrir el día',
        mensaje: 'Vas a abrir de nuevo el día de la empresa. Las operarias podrán registrar trabajo otra vez.',
        boton: 'Abrir el día',
        alConfirmar: enviarReaperturaDia
    });
}

function enviarReaperturaDia() {
    const fecha = document.getElementById('fechaSeleccionada').value || window.FECHA_ACTUAL;
    const datos = new FormData();
    datos.append('fecha', fecha);
    const boton = document.getElementById('btnReabrirDia');
    if (boton) {
        boton.disabled = true;
    }
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=reabrirDia`, {
        method: 'POST',
        body: datos
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAviso({
                titulo: 'Día abierto',
                mensaje: 'El día quedó abierto. Las operarias pueden registrar trabajo otra vez.',
                alCerrar: function () {
                    window.location.reload();
                }
            });
            return;
        }
        aviso(data.error || 'No se pudo abrir el día');
        if (boton) {
            boton.disabled = false;
        }
    })
    .catch(() => {
        aviso('No se pudo abrir el día. Revisa tu conexión e intenta otra vez.');
        if (boton) {
            boton.disabled = false;
        }
    });
}
