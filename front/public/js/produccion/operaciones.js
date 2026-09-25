function habilitarSeccionOperaciones(id) {
    const btnNuevaOperacion = document.getElementById(`btnNuevaOperacion_${id}`);
    const cardOperaciones = document.getElementById(`cardOperaciones_${id}`);
    
    if (btnNuevaOperacion) {
        btnNuevaOperacion.disabled = window.DIA_FINALIZADO === true;
        btnNuevaOperacion.setAttribute('data-en-dia', '1');
    }
    
    if (cardOperaciones) {
        // Remover el mensaje de advertencia si existe
        const alertWarning = cardOperaciones.querySelector('.alert-warning');
        if (alertWarning) {
            alertWarning.remove();
        }
        
        // Habilitar la tabla
        const tableResponsive = cardOperaciones.querySelector('.table-responsive');
        if (tableResponsive) {
            tableResponsive.style.opacity = '1';
            tableResponsive.style.pointerEvents = 'auto';
        }
    }
}

function renderizarOperaciones(operaciones) {
    if (!operaciones || operaciones.length === 0) {
        return '<tr><td colspan="10" class="text-center text-muted">Todavía no hay trabajos. Cuando la persona esté en el día, toca Nueva operación.</td></tr>';
    }
    
    return operaciones.map(op => {
        // Obtener información de pausas directamente de la operación
        const cantidadPausas = parseInt(op.cantidad_pausas) || 0;
        const tiempoPausasMinutos = parseFloat(op.tiempo_pausas_minutos) || 0;
        
        // Formatear tiempo de pausas
        let tiempoPausasFormateado = '-';
        if (tiempoPausasMinutos > 0) {
            const totalSegundos = Math.round(tiempoPausasMinutos * 60);
            const horas = Math.floor(totalSegundos / 3600);
            const minutos = Math.floor((totalSegundos % 3600) / 60);
            const segundos = totalSegundos % 60;
            tiempoPausasFormateado = horas > 0 
                ? `${horas}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`
                : `${minutos}:${String(segundos).padStart(2, '0')}`;
        }
        
        return `
        <tr data-id="${op.id}">
            <td>
                <strong>${op.codigo_operacion || ''}</strong><br>
                <small class="text-muted">${op.nombre_operacion || ''}</small>
            </td>
            <td>${op.maquina_usada || ''}</td>
            <td>${op.hora_inicio ? new Date(op.hora_inicio).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}) : '-'}</td>
            <td>${op.hora_fin ? new Date(op.hora_fin).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}) : '-'}</td>
            <td><strong>${op.tiempo_total_minutos || 0}</strong> min</td>
            <td><strong>${op.piezas_producidas || 0}</strong></td>
            <td>
                <span class="badge bg-${(function() {
                    const eff = parseFloat(op.eficiencia) || 0;
                    return eff >= 100 ? 'success' : (eff >= 80 ? 'warning' : 'danger');
                })()}">
                    ${(function() {
                        const eff = parseFloat(op.eficiencia) || 0;
                        return eff.toFixed(2);
                    })()}%
                </span>
            </td>
            <td>
                <strong>${cantidadPausas}</strong>
            </td>
            <td>
                ${tiempoPausasMinutos > 0 ? `<strong class="text-warning">${tiempoPausasFormateado}</strong>` : '<span class="text-muted">-</span>'}
            </td>
            <td>
                <button class="btn btn-sm btn-outline-info btn-icono" onclick="verHistorialOperacion(${op.id})" title="Historial">
                    <i class="bi bi-clock-history"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled' : ''} btn-icono" 
                        onclick="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'return false;' : `editarOperacion(${op.id})`}" 
                        title="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'Día finalizado - No se puede editar' : 'Editar'}"
                        ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-warning ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled' : ''} btn-icono" 
                        onclick="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'return false;' : `abrirModalRetroceso(${op.id}, '${operariaActivaId}')`}" 
                        title="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'Día finalizado - No se pueden agregar retrocesos' : 'Retrocesos'}"
                        ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                    <i class="bi bi-exclamation-triangle"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled' : ''} btn-icono" 
                        onclick="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'return false;' : `eliminarOperacion(${op.id}, '${operariaActivaId}', '${encodeURIComponent(op.codigo_operacion || op.nombre_operacion || '')}')`}" 
                        title="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'Día finalizado - No se puede eliminar' : 'Eliminar'}"
                        ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
        `;
    }).join('');
}

function cargarInfoPausas(operacionId, operacion) {
    // Cargar historial para contar pausas
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=getHistorialOperacion&operacion_id=${operacionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.historial) {
                const infoPausas = calcularInfoPausas(data.historial);
                
                // Actualizar las celdas de pausas
                const countCell = document.getElementById(`pausas-count-${operacionId}`);
                const tiempoCell = document.getElementById(`pausas-tiempo-${operacionId}`);
                
                if (countCell) {
                    countCell.innerHTML = `<strong>${infoPausas.cantidad}</strong>`;
                }
                
                if (tiempoCell) {
                    if (infoPausas.tiempoTotal > 0) {
                        // Convertir minutos a formato horas:minutos:segundos
                        const totalSegundos = Math.round(infoPausas.tiempoTotal * 60);
                        const horas = Math.floor(totalSegundos / 3600);
                        const minutos = Math.floor((totalSegundos % 3600) / 60);
                        const segundos = totalSegundos % 60;
                        const tiempoFormateado = horas > 0 
                            ? `${horas}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`
                            : `${minutos}:${String(segundos).padStart(2, '0')}`;
                        tiempoCell.innerHTML = `<strong class="text-warning">${tiempoFormateado}</strong>`;
                    } else {
                        tiempoCell.innerHTML = '<span class="text-muted">-</span>';
                    }
                }
            } else {
                // Si no hay historial, mostrar 0
                const countCell = document.getElementById(`pausas-count-${operacionId}`);
                const tiempoCell = document.getElementById(`pausas-tiempo-${operacionId}`);
                if (countCell) countCell.innerHTML = '<strong>0</strong>';
                if (tiempoCell) tiempoCell.innerHTML = '<span class="text-muted">-</span>';
            }
        })
        .catch(error => {
            const countCell = document.getElementById(`pausas-count-${operacionId}`);
            const tiempoCell = document.getElementById(`pausas-tiempo-${operacionId}`);
            if (countCell) countCell.innerHTML = '<span class="text-danger">Error</span>';
            if (tiempoCell) tiempoCell.innerHTML = '<span class="text-danger">Error</span>';
        });
}

function calcularInfoPausas(historial) {
    let cantidadPausas = 0;
    let tiempoTotalPausas = 0; // en minutos
    
    // Ordenar historial por fecha
    const historialOrdenado = [...historial].sort((a, b) => {
        const fechaA = new Date(a.fecha_creacion || a.fecha_creacion || 0);
        const fechaB = new Date(b.fecha_creacion || b.fecha_creacion || 0);
        return fechaA - fechaB;
    });
    
    // Buscar eventos de pausa y reanudar para calcular el tiempo de pausa
    const eventosPausa = [];
    const eventosReanudar = [];
    
    historialOrdenado.forEach(item => {
        if (item.tipo_cambio === 'pausa') {
            const fechaPausa = new Date(item.fecha_creacion || item.fecha_creacion);
            eventosPausa.push({
                fecha: fechaPausa,
                horaInicio: item.hora_inicio ? new Date(item.hora_inicio) : null
            });
            cantidadPausas++;
        } else if (item.tipo_cambio === 'reanudar') {
            const fechaReanudar = new Date(item.fecha_creacion || item.fecha_creacion);
            eventosReanudar.push({
                fecha: fechaReanudar,
                horaInicio: item.hora_inicio ? new Date(item.hora_inicio) : null
            });
        }
    });
    
    // Calcular el tiempo total de pausas
    // El tiempo de pausa es la diferencia entre cuando se pausó y cuando se reanudó
    eventosPausa.forEach((pausa) => {
        // Buscar el siguiente evento de reanudar después de esta pausa
        const siguienteReanudar = eventosReanudar.find(r => r.fecha > pausa.fecha);
        
        if (siguienteReanudar) {
            // Calcular diferencia en minutos usando las fechas de creación
            const diferenciaMs = siguienteReanudar.fecha - pausa.fecha;
            const diferenciaMinutos = diferenciaMs / 60000;
            tiempoTotalPausas += diferenciaMinutos;
        } else {
            // Si no hay reanudar después de la pausa, la pausa podría estar activa
            // O la operación terminó en pausa, en ese caso no contamos el tiempo
        }
    });
    
    return {
        cantidad: cantidadPausas,
        tiempoTotal: tiempoTotalPausas
    };
}

// ==== Funciones de carga de datos ====
function cargarOperariasDelDia() {
    const fecha = window.FECHA_ACTUAL || new Date().toISOString().split('T')[0];
    
        
    // Limpiar estado anterior
    document.getElementById('operariasTabs').innerHTML = '';
    document.getElementById('contenedorOperarias').innerHTML = '';
    operariasAbiertas = {};
    operariaActivaId = null;
    contadorOperarias = 0;
    
    // Primero verificar si el día está finalizado, pero continuar aunque falle
    verificarEstadoDiaFinalizado()
        .then(() => {
            // Continuar con la carga de operarias
            cargarOperariasDelDiaContinuacion(fecha);
        })
        .catch(() => {
            // Si falla la verificación, continuar de todas formas
            window.DIA_FINALIZADO = false;
            cargarOperariasDelDiaContinuacion(fecha);
        });
}

function cargarOperariasDelDiaContinuacion(fecha) {
    return fetch(`${BASE_URL_PROD}index.php?action=produccion&method=getOperariasDelDia&fecha=${fecha}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
                        if (data.success && data.operarias && data.operarias.length > 0) {
                                data.operarias.forEach(op => {
                    contadorOperarias++;
                    const id = 'operaria_' + contadorOperarias;
                    // El objeto 'op' ahora contiene todos los datos del registro
                    operariasAbiertas[id] = {
                        nombre: op.operaria_nombre,
                        registro: op,
                        operaciones: [],
                        retrocesos: [],
                        datos: {
                            fecha: op.fecha || fecha,
                            turno: op.turno || 'mañana',
                            meta_dia: op.meta_dia || 0,
                            maquina_asignada: op.maquina_asignada || '',
                            tiempo_total_trabajado: op.tiempo_total_trabajado || 0,
                            tiempo_perdido_retrocesos: op.tiempo_perdido_retrocesos || 0,
                            piezas_producidas: op.piezas_producidas || 0,
                            eficiencia_promedio: op.eficiencia_promedio || 0
                        }
                    };
                    agregarTabOperaria(id, op.operaria_nombre);
                                    });
                
                // Activar la primera operaria si hay alguna
                if (Object.keys(operariasAbiertas).length > 0) {
                    const primeraOperariaId = Object.keys(operariasAbiertas)[0];
                    cambiarOperariaActiva(primeraOperariaId);
                    
                    // Cargar operaciones para cada operaria
                    Object.keys(operariasAbiertas).forEach(id => {
                        cargarDatosOperaria(id);
                    });
                } else {
                                    }
            } else {
                mostrarGuiaDia();
            }
            return data;
        })
        .catch(error => {
            throw error;
        });
}

// Función específica para cargar operarias del día cuando es operario
function cargarOperariasDelDiaParaOperario(fecha, nombreOperario) {
        
    // Limpiar estado anterior
    document.getElementById('operariasTabs').innerHTML = '';
    document.getElementById('contenedorOperarias').innerHTML = '';
    operariasAbiertas = {};
    operariaActivaId = null;
    contadorOperarias = 0;
    
    // Cargar operarias del día
    cargarOperariasDelDiaContinuacion(fecha)
        .then(data => {
            // Después de cargar, verificar si se encontró la operaria del operario
            const nombreOperarioLower = nombreOperario.toLowerCase();
            const operariaEncontrada = Object.values(operariasAbiertas).find(op => 
                op.nombre.toLowerCase() === nombreOperarioLower
            );
            
            if (operariaEncontrada) {
                                // Activar la operaria encontrada
                const idEncontrado = Object.keys(operariasAbiertas).find(id => 
                    operariasAbiertas[id].nombre.toLowerCase() === nombreOperarioLower
                );
                if (idEncontrado) {
                    cambiarOperariaActiva(idEncontrado);
                    cargarDatosOperaria(idEncontrado);
                }
            } else {
                                // Si no se encontró, crear una nueva operaria vacía
                crearOperariaConNombre(nombreOperario);
            }
        })
        .catch(error => {
            // Si hay error, crear operaria vacía como fallback
            crearOperariaConNombre(nombreOperario);
        });
}

