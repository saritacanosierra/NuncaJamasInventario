function guardarOperacion() {
    const form = document.getElementById('formOperacion');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const operariaId = window.operariaIdCronometro || operariaActivaId;
    if (!operariaId || !operariasAbiertas[operariaId]) {
        aviso('Debe seleccionar una operaria primero');
        return;
    }
    
    const operaria = operariasAbiertas[operariaId];
    const tiempoEstandar = parseFloat(document.getElementById('tiempo_estandar_por_pieza').value) || 0;
    const piezas = parseInt(document.getElementById('piezas_producidas').value) || 0;
    
    // Convertir el formato horas:minutos:segundos a minutos decimales
    const tiempoInput = document.getElementById('tiempo_total_minutos');
    let tiempoReal = 0;
    
    // Si tiene el atributo data-valor-decimal, usarlo (es más preciso)
    const valorDecimal = tiempoInput.getAttribute('data-valor-decimal');
    if (valorDecimal) {
        tiempoReal = parseFloat(valorDecimal);
    } else {
        // Si no, intentar parsear el formato horas:minutos:segundos
        const tiempoTexto = tiempoInput.value || '';
        if (tiempoTexto.includes(':')) {
            const partes = tiempoTexto.split(':');
            if (partes.length === 3) {
                // Formato horas:minutos:segundos
                const horas = parseInt(partes[0]) || 0;
                const minutos = parseInt(partes[1]) || 0;
                const segundos = parseInt(partes[2]) || 0;
                const totalSegundos = (horas * 3600) + (minutos * 60) + segundos;
                tiempoReal = totalSegundos / 60;
            } else if (partes.length === 2) {
                // Formato minutos:segundos (compatibilidad)
                const minutos = parseInt(partes[0]) || 0;
                const segundos = parseInt(partes[1]) || 0;
                tiempoReal = minutos + (segundos / 60);
            }
        } else {
            tiempoReal = parseFloat(tiempoTexto) || 0;
        }
    }
    
    // Calcular tiempo real de trabajo (sin pausas)
    const tiempoPausasMinutos = parseFloat(document.getElementById('tiempo_pausas_minutos').value) || 0;
    const tiempoRealTrabajo = tiempoReal - tiempoPausasMinutos;
    
    // Calcular eficiencia: (Tiempo estándar total / Tiempo real de trabajo) × 100
    // Tiempo estándar total = tiempo estándar por pieza × cantidad de piezas
    let eficiencia = 0;
    if (tiempoEstandar > 0 && piezas > 0 && tiempoRealTrabajo > 0) {
        const tiempoEstandarTotal = tiempoEstandar * piezas;
        eficiencia = (tiempoEstandarTotal / tiempoRealTrabajo) * 100;
    } else if (tiempoEstandar > 0 && piezas > 0 && tiempoReal > 0) {
        // Si no hay tiempo de pausas, usar el tiempo real total
        const tiempoEstandarTotal = tiempoEstandar * piezas;
        eficiencia = (tiempoEstandarTotal / tiempoReal) * 100;
    }
    
    const formData = new FormData(form);
    
    // Reemplazar el valor del campo tiempo_total_minutos con el valor decimal
    formData.set('tiempo_total_minutos', tiempoReal.toFixed(2));
    
    // Agregar información de pausas
    formData.append('cantidad_pausas', cantidadPausas);
    formData.append('tiempo_pausas_minutos', document.getElementById('tiempo_pausas_minutos').value || '0');
    
    formData.append('fecha', operaria.datos.fecha || window.FECHA_ACTUAL);
    formData.append('operaria_nombre', operaria.nombre);
    formData.append('eficiencia', eficiencia.toFixed(2));
    
    const operacionId = document.getElementById('operacion_id').value;
    if (operacionId) {
        formData.append('operacion_id', operacionId);
    }
    
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=guardarOperacion`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.PISO_OPERARIO && typeof window.pisoOperarioAlGuardar === 'function') {
                window.pisoOperarioAlGuardar(true, data);
            } else {
                mostrarAviso({
                    titulo: 'Trabajo guardado',
                    mensaje: 'La operación quedó guardada.',
                    alCerrar: function () {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCronometro'));
                        if (modal) modal.hide();
                    }
                });
            }
            cargarDatosOperaria(operariaId);
        } else if (window.PISO_OPERARIO && typeof window.pisoOperarioAlGuardar === 'function') {
            window.pisoOperarioAlGuardar(false, data);
        } else {
            aviso('Error: ' + (data.error || 'No se pudo guardar la operación'));
        }
    })
    .catch(error => {
        aviso('Error al guardar la operación');
    });
}

function editarOperacion(operacionId) {
    // Verificar si el día está finalizado
    if (window.DIA_FINALIZADO) {
        aviso('No se puede editar operaciones de un día que ya está finalizado');
        return;
    }
    if (!operacionId) {
        aviso('ID de operación no válido');
        return;
    }
    
    // Cargar datos de la operación existente
    const operariaId = operariaActivaId;
    if (!operariaId || !operariasAbiertas[operariaId]) {
        aviso('No se encontró la operaria activa');
        return;
    }
    
    const operaria = operariasAbiertas[operariaId];
    const operacion = operaria.operaciones.find(op => op.id == operacionId);
    
    if (!operacion) {
        aviso('No se encontró la operación');
        return;
    }
    
    // Establecer el ID de la operación antes de abrir el modal
    document.getElementById('operacion_id').value = operacionId;
    
    // Abrir el modal en modo edición (no genera nuevo código)
    abrirModalCronometro(operariaId, true);
    
    // Esperar a que el modal esté completamente visible antes de llenar los datos
    const modal = document.getElementById('modalCronometro');
    if (modal) {
        const bootstrapModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        // Llenar datos después de que el modal esté visible
        modal.addEventListener('shown.bs.modal', function llenarDatos() {
            modal.removeEventListener('shown.bs.modal', llenarDatos);
            
            // Hacer el campo de tiempo editable cuando se está editando
            const tiempoInput = document.getElementById('tiempo_total_minutos');
            if (tiempoInput) {
                tiempoInput.removeAttribute('readonly');
                tiempoInput.style.backgroundColor = '#fff';
            }
            
            // Cargar información de pausas si existe
            cantidadPausas = parseInt(operacion.cantidad_pausas) || 0;
            const tiempoPausasMinutos = parseFloat(operacion.tiempo_pausas_minutos) || 0;
            tiempoTotalPausas = tiempoPausasMinutos * 60000; // Convertir a milisegundos
            actualizarTiempoPausas();
            document.getElementById('cantidad_pausas').value = cantidadPausas;
            
            // Llenar el formulario con los datos existentes
            document.getElementById('codigo_operacion').value = operacion.codigo_operacion || '';
            document.getElementById('nombre_operacion').value = operacion.nombre_operacion || '';
            document.getElementById('maquina_usada').value = operacion.maquina_usada || '';
            document.getElementById('tiempo_estandar_por_pieza').value = operacion.tiempo_estandar_por_pieza || '';
            document.getElementById('piezas_producidas').value = operacion.piezas_producidas || '';
            
            // Convertir minutos decimales a formato horas:minutos:segundos
            const tiempoMinutos = parseFloat(operacion.tiempo_total_minutos) || 0;
            const totalSegundos = Math.round(tiempoMinutos * 60);
            const horas = Math.floor(totalSegundos / 3600);
            const minutos = Math.floor((totalSegundos % 3600) / 60);
            const segundos = totalSegundos % 60;
            const tiempoFormateado = horas + ':' + String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
            document.getElementById('tiempo_total_minutos').value = tiempoFormateado;
            // Guardar el valor decimal para cuando se envíe
            document.getElementById('tiempo_total_minutos').setAttribute('data-valor-decimal', tiempoMinutos.toFixed(2));
            
            // Agregar listener para actualizar el valor decimal cuando se cambie manualmente
            tiempoInput.addEventListener('input', function() {
                const tiempoTexto = this.value || '';
                if (tiempoTexto.includes(':')) {
                    const partes = tiempoTexto.split(':');
                    if (partes.length === 3) {
                        const horas = parseInt(partes[0]) || 0;
                        const minutos = parseInt(partes[1]) || 0;
                        const segundos = parseInt(partes[2]) || 0;
                        const totalSegundos = (horas * 3600) + (minutos * 60) + segundos;
                        const minutosDecimales = (totalSegundos / 60).toFixed(2);
                        this.setAttribute('data-valor-decimal', minutosDecimales);
                    }
                }
            });
            
            document.getElementById('hora_inicio').value = operacion.hora_inicio || '';
            document.getElementById('hora_fin').value = operacion.hora_fin || '';
            
            // Actualizar displays
            if (operacion.hora_inicio) {
                const horaInicio = new Date(operacion.hora_inicio);
                document.getElementById('horaInicioDisplay').textContent = horaInicio.toLocaleTimeString('es-ES');
            }
            if (operacion.hora_fin) {
                const horaFin = new Date(operacion.hora_fin);
                document.getElementById('horaFinDisplay').textContent = horaFin.toLocaleTimeString('es-ES');
            }
            
            // Verificar campos para habilitar botón guardar
            verificarCamposParaGuardar();
        }, { once: true });
    }
}

function eliminarOperacion(operacionId, operariaId, codigo) {
    // Verificar si el día está finalizado
    if (window.DIA_FINALIZADO) {
        aviso('No se puede eliminar operaciones de un día que ya está finalizado');
        return;
    }
    let clave = String(codigo || '');
    if (clave.indexOf('%') !== -1) {
        try { clave = decodeURIComponent(clave); } catch (e) {}
    }
    clave = clave.trim();
    if (!clave || typeof pedirDobleConfirmacion !== 'function') {
        return;
    }
    pedirDobleConfirmacion({
        titulo: 'Eliminar trabajo',
        detalle: 'Se borra este trabajo y no se puede recuperar.',
        codigo: clave,
        alConfirmar: function (escrito) {
            enviarEliminarOperacion(operacionId, operariaId, escrito);
        }
    });
}

function enviarEliminarOperacion(operacionId, operariaId, codigo) {
    const formData = new FormData();
    formData.append('id', operacionId);
    formData.append('codigo_confirmacion', codigo);
    
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=eliminarOperacion`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            aviso('Operación eliminada exitosamente');
            cargarDatosOperaria(operariaId);
        } else {
            aviso('Error: ' + (data.error || 'No se pudo eliminar la operación'));
        }
    })
    .catch(error => {
        aviso('Error al eliminar la operación');
    });
}

function verHistorialOperacion(operacionId) {
    if (!operacionId) {
        aviso('ID de operación no válido');
        return;
    }
    
    // Mostrar modal y spinner de carga
    const modal = new bootstrap.Modal(document.getElementById('modalHistorialOperacion'));
    const contenido = document.getElementById('historialOperacionContenido');
    contenido.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando historial...</p>
        </div>
    `;
    modal.show();
    
    // Cargar historial desde el servidor
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=getHistorialOperacion&operacion_id=${operacionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.historial) {
                renderizarHistorialOperacion(data.historial);
            } else {
                contenido.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> ${data.error || 'No se pudo cargar el historial'}
                    </div>
                `;
            }
        })
        .catch(error => {
            contenido.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> Error al cargar el historial de la operación
                </div>
            `;
        });
}

function renderizarHistorialOperacion(historial) {
    const contenido = document.getElementById('historialOperacionContenido');
    
    if (!historial || historial.length === 0) {
        contenido.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No hay historial registrado para esta operación
            </div>
        `;
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-hover">';
    html += '<thead><tr>';
    html += '<th>Fecha/Hora</th>';
    html += '<th>Tipo de Cambio</th>';
    html += '<th>Hora Inicio</th>';
    html += '<th>Hora Fin</th>';
    html += '<th>Tiempo Total</th>';
    html += '<th>Piezas</th>';
    html += '<th>Eficiencia</th>';
    html += '<th>Observaciones</th>';
    html += '</tr></thead><tbody>';
    
    historial.forEach(item => {
        const fechaCreacion = item.fecha_creacion ? new Date(item.fecha_creacion).toLocaleString('es-ES') : '-';
        const tipoCambio = item.tipo_cambio || 'retroceso';
        const esRetroceso = tipoCambio === 'retroceso' || item.tipo_defecto;
        
        let horaInicio, horaFin, tiempoTotal, piezas, eficiencia, observaciones;
        
        if (esRetroceso) {
            // Para retrocesos, mostrar información específica
            horaInicio = '-';
            horaFin = '-';
            tiempoTotal = item.minutos_perdidos ? item.minutos_perdidos + ' min' : '-';
            piezas = '-';
            eficiencia = '-';
            // Observaciones para retrocesos: tipo defecto, máquina y acción correctiva
            let obsParts = [];
            if (item.tipo_defecto) obsParts.push(`<strong>Tipo:</strong> ${item.tipo_defecto}`);
            if (item.maquina) obsParts.push(`<strong>Máquina:</strong> ${item.maquina}`);
            if (item.accion_correctiva) obsParts.push(`<strong>Acción:</strong> ${item.accion_correctiva}`);
            observaciones = obsParts.length > 0 ? obsParts.join('<br>') : '-';
        } else {
            // Para cambios normales del historial
            horaInicio = item.hora_inicio ? new Date(item.hora_inicio).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit', second: '2-digit'}) : '-';
            horaFin = item.hora_fin ? new Date(item.hora_fin).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit', second: '2-digit'}) : '-';
            tiempoTotal = item.tiempo_total_minutos ? parseFloat(item.tiempo_total_minutos).toFixed(2) + ' min' : '-';
            piezas = item.piezas_producidas !== null && item.piezas_producidas !== undefined ? item.piezas_producidas : '-';
            eficiencia = item.eficiencia !== null && item.eficiencia !== undefined ? parseFloat(item.eficiencia).toFixed(2) + '%' : '-';
            observaciones = item.observaciones || '-';
        }
        
        // Determinar badge color según tipo de cambio
        let badgeClass = 'secondary';
        if (tipoCambio === 'inicio') badgeClass = 'success';
        else if (tipoCambio === 'pausa') badgeClass = 'warning';
        else if (tipoCambio === 'reanudar') badgeClass = 'info';
        else if (tipoCambio === 'retroceso' || esRetroceso) badgeClass = 'danger';
        else if (tipoCambio === 'finalizado') badgeClass = 'primary';
        
        // Capitalizar el tipo de cambio para mostrar
        const tipoCambioDisplay = esRetroceso ? 'Retroceso' : 
                                 tipoCambio.charAt(0).toUpperCase() + tipoCambio.slice(1);
        
        html += '<tr>';
        html += `<td>${fechaCreacion}</td>`;
        html += `<td><span class="badge bg-${badgeClass}">${tipoCambioDisplay}</span></td>`;
        html += `<td>${horaInicio}</td>`;
        html += `<td>${horaFin}</td>`;
        html += `<td>${tiempoTotal}</td>`;
        html += `<td>${piezas}</td>`;
        html += `<td>${eficiencia}</td>`;
        html += `<td><small>${observaciones}</small></td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    contenido.innerHTML = html;
}

