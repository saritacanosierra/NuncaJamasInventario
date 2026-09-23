/**
 * JavaScript para el módulo de Producción
 * Maneja múltiples operarias simultáneas con pestañas, cronómetros, operaciones, retrocesos y cálculos de eficiencia
 */

// Estructura para manejar múltiples operarias en paralelo
let operariasAbiertas = {};
let operariaActivaId = null;
let contadorOperarias = 0;
let BASE_URL_PROD = '';

// Variables del cronómetro
let cronometroInterval = null;
let tiempoInicio = null;
let tiempoAcumulado = 0; // Tiempo total acumulado (incluye pausas)
let tiempoTotal = 0; // Tiempo en segundos para mostrar en display
let estadoCronometro = 'detenido'; // detenido, corriendo, pausado, finalizado
let cantidadPausas = 0; // Contador de pausas
let tiempoTotalPausas = 0; // Tiempo total de pausas en milisegundos
let tiempoPausaInicio = null; // Momento en que se inició la pausa actual

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    BASE_URL_PROD = window.BASE_URL || '';
    
    // Verificar estado del día al cargar
    verificarEstadoDiaFinalizado();
    
    // Botón nueva operaria (solo si existe, no para operarios)
    const btnNuevaOperaria = document.getElementById('btnNuevaOperaria');
    if (btnNuevaOperaria) {
        btnNuevaOperaria.addEventListener('click', crearNuevaOperaria);
    }
    
    // Si es operario, primero intentar cargar operarias del día (que incluirá la suya si existe)
    // Si no es operario, cargar todas las operarias del día normalmente
    if (window.USUARIO_ROL === 'operario' && window.USUARIO_NOMBRE) {
        // Para operarios, primero intentar cargar las operarias del día
        // Esto cargará su operaria si ya tiene un registro guardado
        const fecha = window.FECHA_ACTUAL || new Date().toISOString().split('T')[0];
        const nombreOperario = window.USUARIO_NOMBRE.trim();
        
        // Cargar operarias del día y luego verificar si se encontró la del operario
        cargarOperariasDelDiaParaOperario(fecha, nombreOperario);
    } else {
        // Para no-operarios, cargar normalmente
        cargarOperariasDelDia();
    }
    
    // Limpiar cronómetro al cerrar modal
    const modalCronometro = document.getElementById('modalCronometro');
    if (modalCronometro) {
        modalCronometro.addEventListener('hidden.bs.modal', function() {
            // Limpiar intervalo si existe
            if (cronometroInterval) {
                clearInterval(cronometroInterval);
                cronometroInterval = null;
            }
            reiniciarCronometro();
            limpiarFormularioOperacion();
        });
        
        // Asegurar que el código se genere cuando el modal esté completamente visible
        modalCronometro.addEventListener('shown.bs.modal', function() {
            const operacionId = document.getElementById('operacion_id').value;
            const codigoOperacion = document.getElementById('codigo_operacion').value;
            // Si no hay operacion_id y no hay código, generar uno
            if (!operacionId && !codigoOperacion) {
                generarCodigoOperacion();
            }
        });
    }
    
    // Habilitar botón de guardar cuando se llenen los campos requeridos
    const formOperacion = document.getElementById('formOperacion');
    if (formOperacion) {
        const verificarCampos = function() {
            // Si el día está finalizado, no habilitar el botón
            if (window.DIA_FINALIZADO === true) {
                const btnGuardar = document.getElementById('btnGuardarOperacion');
                if (btnGuardar) {
                    btnGuardar.disabled = true;
                    btnGuardar.title = 'Día finalizado - No se pueden guardar operaciones';
                }
                return;
            }
            
            const nombreOperacion = document.getElementById('nombre_operacion')?.value || '';
            const maquinaUsada = document.getElementById('maquina_usada')?.value || '';
            const piezasProducidas = document.getElementById('piezas_producidas')?.value || '';
            const tiempoTotalMinutos = document.getElementById('tiempo_total_minutos')?.value || '';
            
            // Verificar si hay tiempo (puede estar en formato H:MM:SS o vacío)
            const tieneTiempo = tiempoTotalMinutos && tiempoTotalMinutos.trim() !== '' && tiempoTotalMinutos !== '0:00:00';
            
            const btnGuardar = document.getElementById('btnGuardarOperacion');
            if (btnGuardar) {
                // Habilitar si todos los campos requeridos están llenos y hay tiempo registrado
                if (nombreOperacion && maquinaUsada && piezasProducidas && tieneTiempo) {
                    btnGuardar.disabled = false;
                    btnGuardar.title = '';
                    console.log('verificarCampos: Botón HABILITADO - Todos los campos completos');
                } else if (!tieneTiempo && nombreOperacion && maquinaUsada && piezasProducidas) {
                    // Permitir guardar sin cronómetro si se llena manualmente el tiempo
                    btnGuardar.disabled = false;
                    btnGuardar.title = '';
                    console.log('verificarCampos: Botón HABILITADO - Campos completos sin tiempo');
                } else {
                    // Si faltan campos, deshabilitar
                    btnGuardar.disabled = true;
                    btnGuardar.title = 'Complete todos los campos requeridos';
                    console.log('verificarCampos: Botón DESHABILITADO - Faltan campos');
                }
            }
        };
        
        const camposRequeridos = ['nombre_operacion', 'maquina_usada', 'piezas_producidas', 'tiempo_total_minutos'];
        camposRequeridos.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                campo.addEventListener('input', verificarCampos);
                campo.addEventListener('change', verificarCampos);
            }
        });
    }
    
    // Cargar operarias existentes del día
    cargarOperariasDelDia();
});

// ==== Gestión de operarias múltiples ====
let timeoutBusquedaOperaria = null;

function crearNuevaOperaria() {
    const modal = new bootstrap.Modal(document.getElementById('modalSeleccionarOperaria'));
    const inputBuscar = document.getElementById('buscarOperaria');
    const listaOperarias = document.getElementById('listaOperarias');
    
    // Limpiar al abrir
    inputBuscar.value = '';
    listaOperarias.innerHTML = '';
    
    // Cargar todas las operarias al abrir
    buscarOperarias('');
    
    // Event listener para búsqueda en tiempo real
    inputBuscar.addEventListener('input', function() {
        clearTimeout(timeoutBusquedaOperaria);
        const termino = this.value.trim();
        timeoutBusquedaOperaria = setTimeout(() => {
            buscarOperarias(termino);
        }, 300);
    });
    
    // Event listener para crear nueva
    document.getElementById('btnCrearNuevaOperaria').onclick = function() {
        const nombre = inputBuscar.value.trim();
        if (!nombre) {
            alert('Por favor, ingresa el nombre de la operaria');
            return;
        }
        modal.hide();
        crearOperariaConNombre(nombre);
    };
    
    modal.show();
}

function buscarOperarias(termino) {
    const listaOperarias = document.getElementById('listaOperarias');
    const inputBuscar = document.getElementById('buscarOperaria');
    
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=buscarOperarias&busqueda=${encodeURIComponent(termino)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.operarias) {
                listaOperarias.innerHTML = '';
                
                if (data.operarias.length === 0 && termino) {
                    listaOperarias.innerHTML = `
                        <div class="list-group-item text-muted text-center">
                            <i class="bi bi-info-circle"></i> No se encontraron operarias con ese nombre
                        </div>
                    `;
                } else if (data.operarias.length > 0) {
                    data.operarias.forEach(operaria => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action';
                        item.innerHTML = `
                            <i class="bi bi-person"></i> ${operaria}
                        `;
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalSeleccionarOperaria'));
                            modal.hide();
                            crearOperariaConNombre(operaria);
                        });
                        listaOperarias.appendChild(item);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error al buscar operarias:', error);
        });
}

function crearOperariaConNombre(nombre) {
    if (!nombre || nombre.trim() === '') {
        return;
    }
    
    // Verificar si ya existe una operaria con ese nombre abierta
    const operariaExistente = Object.values(operariasAbiertas).find(op => op.nombre.toLowerCase() === nombre.toLowerCase());
    if (operariaExistente) {
        const idExistente = Object.keys(operariasAbiertas).find(id => operariasAbiertas[id].nombre.toLowerCase() === nombre.toLowerCase());
        if (idExistente) {
            cambiarOperariaActiva(idExistente);
            return;
        }
    }
    
    contadorOperarias++;
    const id = 'operaria_' + contadorOperarias;
    const nombreOperaria = nombre.trim();
    
    const fechaActual = window.FECHA_ACTUAL || new Date().toISOString().split('T')[0];
    
    console.log('Creando nueva operaria:', id, nombreOperaria, fechaActual);
    
    operariasAbiertas[id] = {
        nombre: nombreOperaria,
        registro: null, // No tiene registro guardado aún
        operaciones: [],
        retrocesos: [],
        datos: {
            fecha: fechaActual,
            turno: 'mañana',
            meta_dia: 0,
            maquina_asignada: '',
            tiempo_total_trabajado: 0,
            tiempo_perdido_retrocesos: 0,
            piezas_producidas: 0,
            eficiencia_promedio: 0
        }
    };
    
    agregarTabOperaria(id, nombreOperaria);
    console.log('Tab agregada, cambiando a operaria activa:', id);
    cambiarOperariaActiva(id);
    // El formulario se mostrará automáticamente al cambiar a esta operaria
}

function agregarTabOperaria(id, nombre) {
    const tabs = document.getElementById('operariasTabs');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-outline-secondary operaria-tab';
    btn.dataset.operariaId = id;
    btn.textContent = nombre;
    btn.addEventListener('click', function() {
        cambiarOperariaActiva(id);
    });
    tabs.appendChild(btn);
}

function cambiarOperariaActiva(nuevaId) {
    if (operariaActivaId) {
        guardarEstadoOperaria(operariaActivaId);
    }
    
    operariaActivaId = nuevaId;
    
    // Asegurarse de que los datos estén actualizados antes de mostrar
    if (operariasAbiertas[nuevaId]) {
        const operaria = operariasAbiertas[nuevaId];
        // Si tiene registro, actualizar datos desde el registro
        if (operaria.registro && operaria.registro.fecha) {
            operaria.datos = {
                fecha: operaria.registro.fecha,
                turno: operaria.registro.turno || 'mañana',
                meta_dia: operaria.registro.meta_dia || 0,
                maquina_asignada: operaria.registro.maquina_asignada || '',
                tiempo_total_trabajado: operaria.registro.tiempo_total_trabajado || 0,
                tiempo_perdido_retrocesos: operaria.registro.tiempo_perdido_retrocesos || 0,
                piezas_producidas: operaria.registro.piezas_producidas || 0,
                eficiencia_promedio: operaria.registro.eficiencia_promedio || 0
            };
        } else {
            // Si no tiene registro, inicializar datos por defecto
            if (!operaria.datos.fecha) {
                operaria.datos.fecha = window.FECHA_ACTUAL || new Date().toISOString().split('T')[0];
            }
        }
    }
    
    mostrarContenidoOperaria(operariaActivaId);
    
    // Marcar pestañas
    document.querySelectorAll('.operaria-tab').forEach(tab => {
        if (tab.dataset.operariaId === operariaActivaId) {
            tab.classList.remove('btn-outline-secondary');
            tab.classList.add('btn-primary', 'active');
        } else {
            tab.classList.remove('btn-primary', 'active');
            tab.classList.add('btn-outline-secondary');
        }
    });
}

function guardarEstadoOperaria(id) {
    if (!operariasAbiertas[id]) return;
    const operaria = operariasAbiertas[id];
    const contenedor = document.querySelector(`[data-operaria-id="${id}"]`);
    if (!contenedor) return;
    
    // Guardar datos del formulario
    const form = contenedor.querySelector('.formRegistroDia');
    if (form) {
        operaria.datos.turno = form.querySelector('.turno').value;
        operaria.datos.meta_dia = parseInt(form.querySelector('.meta_dia').value) || 0;
        operaria.datos.maquina_asignada = form.querySelector('.maquina_asignada').value;
    }
}

function mostrarContenidoOperaria(id) {
    console.log('mostrarContenidoOperaria llamado con id:', id);
    const contenedor = document.getElementById('contenedorOperarias');
    if (!contenedor) {
        console.error('No se encontró el contenedor de operarias');
        return;
    }
    
    console.log('Contenedor encontrado, limpiando...');
    contenedor.innerHTML = '';
    
    if (!operariasAbiertas[id]) {
        console.error('Operaria no encontrada:', id, 'Operarias disponibles:', Object.keys(operariasAbiertas));
        return;
    }
    
    const operaria = operariasAbiertas[id];
    console.log('Creando contenido para operaria:', operaria.nombre, 'Datos:', operaria.datos);
    const div = crearContenidoOperaria(id, operaria);
    console.log('Contenido creado, agregando al contenedor...');
    contenedor.appendChild(div);
    
    // Actualizar botones después de renderizar
    setTimeout(() => {
        actualizarBotonesEstadoFinalizado();
    }, 200);
    
    // Asegurar que el contenido sea visible
    div.style.display = 'block';
    console.log('Contenido de operaria mostrado:', id, operaria.nombre, 'HTML length:', div.innerHTML.length);
}

function crearContenidoOperaria(id, operaria) {
    const div = document.createElement('div');
    div.className = 'operaria-contenido';
    div.setAttribute('data-operaria-id', id);
    
    // Construir objeto registro con todos los datos disponibles (prioridad: registro > datos > defaults)
    const registro = {
        fecha: operaria.registro?.fecha || operaria.datos?.fecha || window.FECHA_ACTUAL,
        turno: operaria.registro?.turno || operaria.datos?.turno || 'mañana',
        meta_dia: parseInt(operaria.registro?.meta_dia ?? operaria.datos?.meta_dia ?? 0),
        maquina_asignada: operaria.registro?.maquina_asignada || operaria.datos?.maquina_asignada || '',
        tiempo_total_trabajado: parseInt(operaria.registro?.tiempo_total_trabajado ?? operaria.datos?.tiempo_total_trabajado ?? 0),
        tiempo_perdido_retrocesos: parseInt(operaria.registro?.tiempo_perdido_retrocesos ?? operaria.datos?.tiempo_perdido_retrocesos ?? 0),
        piezas_producidas: parseInt(operaria.registro?.piezas_producidas ?? operaria.datos?.piezas_producidas ?? 0),
        eficiencia_promedio: parseFloat(operaria.registro?.eficiencia_promedio ?? operaria.datos?.eficiencia_promedio ?? 0)
    };
    
    div.innerHTML = `
        <!-- Formulario de Encabezado del Día -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-day"></i> Datos del Día - ${operaria.nombre}
                </h5>
            </div>
            <div class="card-body">
                <form class="formRegistroDia" onsubmit="event.preventDefault(); guardarRegistroDia('${id}')">
                    <input type="hidden" name="operaria_nombre" value="${operaria.nombre}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Nombre de la Operaria *</label>
                            <input type="text" class="form-control operaria_nombre" 
                                   value="${operaria.nombre}" required readonly disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fecha *</label>
                            <input type="date" class="form-control fecha" name="fecha" 
                                   value="${registro.fecha || window.FECHA_ACTUAL}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Turno *</label>
                            <select class="form-select turno" name="turno" required>
                                <option value="mañana" ${registro.turno === 'mañana' ? 'selected' : ''}>Mañana</option>
                                <option value="tarde" ${registro.turno === 'tarde' ? 'selected' : ''}>Tarde</option>
                                <option value="noche" ${registro.turno === 'noche' ? 'selected' : ''}>Noche</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Meta del Día (piezas)</label>
                            <input type="number" class="form-control meta_dia" name="meta_dia" 
                                   value="${registro.meta_dia || 0}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Especialidad </label>
                            <select class="form-select maquina_asignada" name="maquina_asignada" required>
                                <option value="">Seleccione...</option>
                                <option value="Bordadora" ${registro.maquina_asignada === 'Bordadora' ? 'selected' : ''}>Bordadora</option>
                                <option value="Recubridora" ${registro.maquina_asignada === 'Recubridora' ? 'selected' : ''}>Recubridora</option>
                                <option value="Plana" ${registro.maquina_asignada === 'Plana' ? 'selected' : ''}>Plana</option>
                                <option value="Fileteadora" ${registro.maquina_asignada === 'Fileteadora' ? 'selected' : ''}>Fileteadora</option>
                                <option value="Pulir" ${registro.maquina_asignada === 'Pulir' ? 'selected' : ''}>Pulir</option>
                                <option value="Detalles manuales" ${registro.maquina_asignada === 'Detalles manuales' ? 'selected' : ''}>Detalles manuales</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Registro del Día
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Resumen de Eficiencia -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6><i class="bi bi-clock-history"></i> Tiempo Total Trabajado</h6>
                        <h3 class="tiempo-total">${registro.tiempo_total_trabajado} min</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h6><i class="bi bi-exclamation-triangle"></i> Tiempo Perdido</h6>
                        <h3 class="tiempo-perdido">${registro.tiempo_perdido_retrocesos} min</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6><i class="bi bi-box-seam"></i> Piezas Producidas</h6>
                        <h3 class="piezas-producidas">${registro.piezas_producidas}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6><i class="bi bi-graph-up"></i> Eficiencia Promedio</h6>
                        <h3 class="eficiencia-promedio">${(parseFloat(registro.eficiencia_promedio) || 0).toFixed(2)}%</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Operaciones -->
        <div class="card" id="cardOperaciones_${id}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-task"></i> Operaciones del Día</h5>
                <button type="button" class="btn btn-success" id="btnNuevaOperacion_${id}" 
                        data-bs-toggle="modal" data-bs-target="#modalCronometro" 
                        onclick="abrirModalCronometro('${id}')"
                        ${!operaria.registro || !operaria.registro.id || (typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled' : ''}
                        ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'title="Día finalizado - No se pueden agregar nuevas operaciones"' : ''}>
                    <i class="bi bi-play-circle"></i> Nueva Operación
                </button>
            </div>
            <div class="card-body">
                ${!operaria.registro || !operaria.registro.id ? `
                    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Registro del día no guardado</strong><br>
                            <small>Debe guardar primero el "Registro del Día" antes de poder agregar operaciones.</small>
                        </div>
                    </div>
                ` : ''}
                <div class="table-responsive" ${!operaria.registro || !operaria.registro.id ? 'style="opacity: 0.5; pointer-events: none;"' : ''}>
                    <table class="table table-hover tabla-operaciones">
                        <thead>
                            <tr>
                                <th>Código / Nombre</th>
                                <th>Máquina</th>
                                <th>Hora Inicio</th>
                                <th>Hora Fin</th>
                                <th>Tiempo Total</th>
                                <th>Piezas</th>
                                <th>Eficiencia</th>
                                <th>Pausas</th>
                                <th>Tiempo Pausas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="tbody-operaciones">
                            ${renderizarOperaciones(operaria.operaciones)}
                            <script>
                                // Actualizar botones después de renderizar
                                if (typeof actualizarBotonesEstadoFinalizado === 'function') {
                                    setTimeout(() => actualizarBotonesEstadoFinalizado(), 100);
                                }
                            </script>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    return div;
}

// Función para habilitar la sección de operaciones después de guardar el registro
function habilitarSeccionOperaciones(id) {
    const btnNuevaOperacion = document.getElementById(`btnNuevaOperacion_${id}`);
    const cardOperaciones = document.getElementById(`cardOperaciones_${id}`);
    
    if (btnNuevaOperacion) {
        btnNuevaOperacion.disabled = false;
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
        return '<tr><td colspan="10" class="text-center text-muted">No hay operaciones registradas. Haga clic en "Nueva Operación" para comenzar.</td></tr>';
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
                <button class="btn btn-sm btn-outline-info" onclick="verHistorialOperacion(${op.id})" title="Historial">
                    <i class="bi bi-clock-history"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled' : ''}" 
                        onclick="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'return false;' : `editarOperacion(${op.id})`}" 
                        title="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'Día finalizado - No se puede editar' : 'Editar'}"
                        ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-warning ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled' : ''}" 
                        onclick="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'return false;' : `abrirModalRetroceso(${op.id}, '${operariaActivaId}')`}" 
                        title="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'Día finalizado - No se pueden agregar retrocesos' : 'Retrocesos'}"
                        ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                    <i class="bi bi-exclamation-triangle"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger ${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled' : ''}" 
                        onclick="${(typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'return false;' : `eliminarOperacion(${op.id}, '${operariaActivaId}')`}" 
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
            console.error('Error al cargar info de pausas:', error);
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
    
    console.log('Cargando operarias del día:', fecha);
    
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
            console.warn('No se pudo verificar el estado del día, continuando...');
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
            console.log('Respuesta del servidor:', data);
            if (data.success && data.operarias && data.operarias.length > 0) {
                console.log('Operarias encontradas:', data.operarias.length);
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
                    console.log('Operaria agregada:', op.operaria_nombre, id);
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
                    console.log('No hay operarias para mostrar');
                }
            } else {
                console.log('No se encontraron operarias guardadas para esta fecha');
            }
            return data;
        })
        .catch(error => {
            console.error('Error al cargar operarias:', error);
            throw error;
        });
}

// Función específica para cargar operarias del día cuando es operario
function cargarOperariasDelDiaParaOperario(fecha, nombreOperario) {
    console.log('Cargando operarias del día para operario:', nombreOperario, fecha);
    
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
                console.log('Operaria encontrada para operario:', operariaEncontrada.nombre);
                // Activar la operaria encontrada
                const idEncontrado = Object.keys(operariasAbiertas).find(id => 
                    operariasAbiertas[id].nombre.toLowerCase() === nombreOperarioLower
                );
                if (idEncontrado) {
                    cambiarOperariaActiva(idEncontrado);
                    cargarDatosOperaria(idEncontrado);
                }
            } else {
                console.log('No se encontró registro previo para operario, creando nueva operaria');
                // Si no se encontró, crear una nueva operaria vacía
                crearOperariaConNombre(nombreOperario);
            }
        })
        .catch(error => {
            console.error('Error al cargar operarias del día para operario:', error);
            // Si hay error, crear operaria vacía como fallback
            crearOperariaConNombre(nombreOperario);
        });
}

function cargarDatosOperaria(id) {
    if (!operariasAbiertas[id]) {
        console.error('Operaria no encontrada para cargar datos:', id);
        return;
    }
    
    const operaria = operariasAbiertas[id];
    const fecha = operaria.datos.fecha || operaria.registro?.fecha || window.FECHA_ACTUAL;
    
    // Si ya tenemos el registro completo, asegurarnos de que los datos estén actualizados
    if (operaria.registro && operaria.registro.fecha) {
        operaria.datos = {
            fecha: operaria.registro.fecha,
            turno: operaria.registro.turno || 'mañana',
            meta_dia: operaria.registro.meta_dia || 0,
            maquina_asignada: operaria.registro.maquina_asignada || '',
            tiempo_total_trabajado: operaria.registro.tiempo_total_trabajado || 0,
            tiempo_perdido_retrocesos: operaria.registro.tiempo_perdido_retrocesos || 0,
            piezas_producidas: operaria.registro.piezas_producidas || 0,
            eficiencia_promedio: operaria.registro.eficiencia_promedio || 0
        };
    }
    
    // Si la operaria tiene un registro con ID, cargar operaciones y retrocesos desde ese registro
    if (operaria.registro && operaria.registro.id) {
        // Cargar operaciones
        fetch(`${BASE_URL_PROD}index.php?action=produccion&method=getOperaciones&registro_id=${operaria.registro.id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.operaciones) {
                    operaria.operaciones = data.operaciones;
                    console.log('Operaciones cargadas para', operaria.nombre, ':', data.operaciones.length);
                    if (id === operariaActivaId) {
                        mostrarContenidoOperaria(id);
                    }
                }
            })
            .catch(error => {
                console.error('Error al cargar operaciones:', error);
            });
        
        // Cargar retrocesos
        fetch(`${BASE_URL_PROD}index.php?action=produccion&method=getRetrocesos&registro_id=${operaria.registro.id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.retrocesos) {
                    operaria.retrocesos = data.retrocesos;
                    console.log('Retrocesos cargados para', operaria.nombre, ':', data.retrocesos.length);
                    // Actualizar tiempo perdido en retrocesos
                    const tiempoPerdido = data.retrocesos.reduce((sum, r) => sum + (parseInt(r.minutos_perdidos) || 0), 0);
                    operaria.datos.tiempo_perdido_retrocesos = tiempoPerdido;
                    if (id === operariaActivaId) {
                        mostrarContenidoOperaria(id);
                    }
                }
            })
            .catch(error => {
                console.error('Error al cargar retrocesos:', error);
            });
    } else {
        // Fallback: cargar por fecha y nombre de operaria
        fetch(`${BASE_URL_PROD}index.php?action=produccion&method=getOperaciones&fecha=${fecha}&operaria=${encodeURIComponent(operaria.nombre)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.operaciones) {
                    operaria.operaciones = data.operaciones;
                    console.log('Operaciones cargadas para', operaria.nombre, ':', data.operaciones.length);
                    if (id === operariaActivaId) {
                        mostrarContenidoOperaria(id);
                    }
                }
            })
            .catch(error => {
                console.error('Error al cargar operaciones:', error);
            });
    }
}

// ==== Funciones de registro ====
function guardarRegistroDia(id) {
    console.log('guardarRegistroDia llamado con id:', id);
    
    if (!operariasAbiertas[id]) {
        console.error('Operaria no encontrada:', id);
        alert('Error: Operaria no encontrada');
        return;
    }
    
    const operaria = operariasAbiertas[id];
    console.log('Operaria encontrada:', operaria.nombre);
    
    // Buscar el contenedor de varias formas
    let contenedor = document.querySelector(`[data-operaria-id="${id}"]`);
    if (!contenedor) {
        // Intentar buscar por el contenedor principal
        const contenedorPrincipal = document.getElementById('contenedorOperarias');
        if (contenedorPrincipal) {
            contenedor = contenedorPrincipal.querySelector(`[data-operaria-id="${id}"]`);
        }
    }
    
    if (!contenedor) {
        console.error('Contenedor de operaria no encontrado:', id);
        console.log('Buscando todos los elementos con data-operaria-id...');
        document.querySelectorAll('[data-operaria-id]').forEach(el => {
            console.log('Elemento encontrado:', el.getAttribute('data-operaria-id'), el);
        });
        alert('Error: No se encontró el contenedor de la operaria. ID: ' + id);
        return;
    }
    
    console.log('Contenedor encontrado:', contenedor);
    
    // Buscar el formulario de varias formas
    let form = contenedor.querySelector(`[data-operaria-id="${id}"] .formRegistroDia`);
    if (!form) {
        form = contenedor.querySelector('.formRegistroDia');
    }
    if (!form) {
        // Intentar buscar directamente en el contenedor
        form = contenedor.querySelector('form');
    }
    if (!form) {
        // Intentar buscar en todo el documento (último recurso)
        const allForms = document.querySelectorAll('.formRegistroDia');
        if (allForms.length > 0) {
            form = allForms[allForms.length - 1]; // Tomar el último formulario
            console.log('Usando último formulario encontrado en el documento');
        }
    }
    
    if (!form) {
        console.error('Formulario no encontrado para operaria:', id);
        console.log('ID de operaria:', id);
        console.log('Contenedor:', contenedor);
        console.log('Contenedor HTML (primeros 1000 caracteres):', contenedor.innerHTML.substring(0, 1000));
        console.log('Todos los formularios en el documento:', document.querySelectorAll('form').length);
        alert('Error: No se encontró el formulario. Por favor, recarga la página o intenta guardar nuevamente.');
        return;
    }
    
    console.log('Formulario encontrado:', form);
    
    console.log('Formulario encontrado, validando...');
    if (!form.checkValidity()) {
        console.log('Formulario inválido');
        form.reportValidity();
        return;
    }
    
    console.log('Formulario válido, preparando datos...');
    
    // Guardar el contenido actual antes de enviar (por si hay error)
    const contenidoAnterior = contenedor.innerHTML;
    
    const formData = new FormData(form);
    
    // Asegurar que operaria_nombre esté presente (el campo disabled no se envía)
    if (!formData.get('operaria_nombre')) {
        formData.set('operaria_nombre', operaria.nombre);
    }
    
    formData.append('tiempo_total_trabajado', operaria.datos.tiempo_total_trabajado || 0);
    formData.append('tiempo_perdido_retrocesos', operaria.datos.tiempo_perdido_retrocesos || 0);
    formData.append('piezas_producidas', operaria.datos.piezas_producidas || 0);
    formData.append('eficiencia_promedio', operaria.datos.eficiencia_promedio || 0);
    
    // Mostrar indicador de carga
    const submitBtn = form.querySelector('button[type="submit"]');
    const textoOriginal = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';
    }
    
    // Asegurar que operaria_nombre esté presente (el campo disabled no se envía)
    if (!formData.get('operaria_nombre')) {
        formData.set('operaria_nombre', operaria.nombre);
        console.log('operaria_nombre agregado manualmente:', operaria.nombre);
    }
    
    // Log de datos que se van a enviar
    const datosEnvio = {
        operaria_nombre: formData.get('operaria_nombre'),
        fecha: formData.get('fecha'),
        turno: formData.get('turno'),
        meta_dia: formData.get('meta_dia'),
        maquina_asignada: formData.get('maquina_asignada'),
        tiempo_total_trabajado: formData.get('tiempo_total_trabajado'),
        tiempo_perdido_retrocesos: formData.get('tiempo_perdido_retrocesos'),
        piezas_producidas: formData.get('piezas_producidas'),
        eficiencia_promedio: formData.get('eficiencia_promedio')
    };
    console.log('Enviando datos del registro:', datosEnvio);
    
    if (!datosEnvio.operaria_nombre || !datosEnvio.fecha) {
        alert('Error: Faltan datos obligatorios. Operaria: ' + datosEnvio.operaria_nombre + ', Fecha: ' + datosEnvio.fecha);
        console.error('Datos incompletos:', datosEnvio);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = textoOriginal;
        }
        return;
    }
    
    console.log('Enviando petición POST a:', `${BASE_URL_PROD}index.php?action=produccion&method=guardarRegistro`);
    
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=guardarRegistro`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta del servidor (status):', response.status);
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.text(); // Primero obtener como texto para ver si hay errores
    })
    .then(text => {
        console.log('Respuesta del servidor (texto):', text);
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Error al parsear JSON:', e);
            console.error('Texto recibido:', text);
            throw new Error('Respuesta inválida del servidor');
        }
    })
    .then(data => {
        console.log('Datos parseados:', data);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = textoOriginal;
        }
        
        if (data.success) {
            // Actualizar el registro y datos
            operaria.registro = data.registro;
            operaria.datos = {
                fecha: data.registro.fecha,
                turno: data.registro.turno || 'mañana',
                meta_dia: data.registro.meta_dia || 0,
                maquina_asignada: data.registro.maquina_asignada || '',
                tiempo_total_trabajado: data.registro.tiempo_total_trabajado || 0,
                tiempo_perdido_retrocesos: data.registro.tiempo_perdido_retrocesos || 0,
                piezas_producidas: data.registro.piezas_producidas || 0,
                eficiencia_promedio: data.registro.eficiencia_promedio || 0
            };
            
            // Mostrar mensaje de éxito
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle"></i> Registro del día guardado exitosamente
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            contenedor.insertBefore(alertDiv, contenedor.firstChild);
            
            // Habilitar la sección de operaciones ahora que el registro está guardado
            habilitarSeccionOperaciones(id);
            
            // Recargar todas las operarias del día para asegurar que se muestren correctamente
            setTimeout(() => {
                cargarOperariasDelDia();
            }, 500);
        } else {
            // Restaurar contenido anterior en caso de error
            contenedor.innerHTML = contenidoAnterior;
            alert('Error: ' + (data.error || 'No se pudo guardar el registro'));
        }
    })
    .catch(error => {
        console.error('Error al guardar registro:', error);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = textoOriginal;
        }
        // Restaurar contenido anterior en caso de error
        contenedor.innerHTML = contenidoAnterior;
        alert('Error al guardar el registro. Por favor, intenta nuevamente.');
    });
}

// ==== Funciones del cronómetro ====
function abrirModalCronometro(operariaId, esEdicion = false) {
    if (!operariaId) operariaId = operariaActivaId;
    window.operariaIdCronometro = operariaId;
    
    // Asegurar que el campo de tiempo esté readonly por defecto
    const tiempoInput = document.getElementById('tiempo_total_minutos');
    if (tiempoInput && !esEdicion) {
        tiempoInput.setAttribute('readonly', 'readonly');
        tiempoInput.style.backgroundColor = '#e9ecef';
    }
    
    if (!esEdicion) {
        reiniciarCronometro();
        limpiarFormularioOperacion();
        document.getElementById('operacion_id').value = '';
        
        // Deshabilitar botón solo si el día está finalizado, de lo contrario se habilitará cuando se llenen los campos
        const btnGuardar = document.getElementById('btnGuardarOperacion');
        if (btnGuardar) {
            if (window.DIA_FINALIZADO === true) {
                btnGuardar.disabled = true;
                btnGuardar.title = 'Día finalizado - No se pueden guardar operaciones';
            } else {
                // Iniciar deshabilitado, se habilitará cuando se llenen los campos
                btnGuardar.disabled = true;
                btnGuardar.title = 'Complete todos los campos requeridos';
            }
        }
        
        // Generar código único de operación automáticamente solo para nuevas operaciones
        generarCodigoOperacion();
    }
}

function generarCodigoOperacion() {
    const operariaId = window.operariaIdCronometro || operariaActivaId;
    if (!operariaId || !operariasAbiertas[operariaId]) {
        // Si no hay operaria, usar fecha actual
        const fecha = window.FECHA_ACTUAL || new Date().toISOString().split('T')[0];
        obtenerCodigoOperacion(fecha);
        return;
    }
    
    const operaria = operariasAbiertas[operariaId];
    const fecha = operaria.datos?.fecha || operaria.registro?.fecha || window.FECHA_ACTUAL || new Date().toISOString().split('T')[0];
    
    obtenerCodigoOperacion(fecha);
}

function obtenerCodigoOperacion(fecha) {
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=generarCodigoOperacion&fecha=${fecha}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.codigo_operacion) {
                const codigoInput = document.getElementById('codigo_operacion');
                if (codigoInput) {
                    codigoInput.value = data.codigo_operacion;
                }
            }
        })
        .catch(error => {
            console.error('Error al generar código de operación:', error);
            // En caso de error, generar un código local
            const fechaFormato = fecha.replace(/-/g, '');
            const timestamp = Date.now();
            const codigoLocal = 'OP-' + fechaFormato + '-' + String(timestamp).slice(-3);
            const codigoInput = document.getElementById('codigo_operacion');
            if (codigoInput) {
                codigoInput.value = codigoLocal;
            }
        });
}

function iniciarCronometro() {
    // Limpiar cualquier intervalo anterior para evitar duplicados
    if (cronometroInterval) {
        clearInterval(cronometroInterval);
        cronometroInterval = null;
    }
    
    const operacionId = document.getElementById('operacion_id')?.value;
    
    if (estadoCronometro === 'detenido') {
        // Iniciar desde cero
        tiempoInicio = new Date();
        tiempoAcumulado = 0;
        tiempoTotal = 0;
        estadoCronometro = 'corriendo';
        
        // Registrar inicio en historial si hay operacion_id
        if (operacionId) {
            registrarHistorialCambio(operacionId, 'inicio', {
                hora_inicio: tiempoInicio.toISOString().slice(0, 19).replace('T', ' ')
            });
        }
    } else if (estadoCronometro === 'pausado') {
        // Reanudar: calcular tiempo de pausa y acumularlo
        const tiempoActual = new Date();
        if (tiempoPausaInicio) {
            const tiempoPausa = tiempoActual - tiempoPausaInicio;
            tiempoTotalPausas += tiempoPausa;
            actualizarTiempoPausas();
        }
        tiempoPausaInicio = null;
        
        // Establecer nuevo tiempo de inicio pero mantener tiempo acumulado
        tiempoInicio = new Date();
        estadoCronometro = 'corriendo';
        
        // Registrar reanudar en historial si hay operacion_id
        if (operacionId) {
            registrarHistorialCambio(operacionId, 'reanudar', {
                hora_inicio: tiempoInicio.toISOString().slice(0, 19).replace('T', ' ')
            });
        }
    }
    
    cronometroInterval = setInterval(actualizarCronometro, 1000);
    actualizarCronometro();
    
    document.getElementById('btnIniciar').style.display = 'none';
    document.getElementById('btnPausar').style.display = 'inline-block';
    document.getElementById('btnReiniciar').style.display = 'inline-block';
    document.getElementById('btnFinalizar').style.display = 'inline-block';
    
    // Solo actualizar hora de inicio si es la primera vez
    if (tiempoAcumulado === 0) {
        document.getElementById('horaInicioDisplay').textContent = tiempoInicio.toLocaleTimeString('es-ES');
        document.getElementById('hora_inicio').value = tiempoInicio.toISOString().slice(0, 19).replace('T', ' ');
    }
    
    document.getElementById('cronometroDisplay').classList.add('cronometro-running');
}

function pausarCronometro() {
    if (estadoCronometro === 'corriendo') {
        clearInterval(cronometroInterval);
        cronometroInterval = null;
        
        // Acumular el tiempo transcurrido desde el último inicio/reanudar
        const tiempoActual = new Date();
        const tiempoTranscurrido = tiempoActual - tiempoInicio;
        tiempoAcumulado += tiempoTranscurrido;
        
        // Registrar el inicio de la pausa
        tiempoPausaInicio = tiempoActual;
        cantidadPausas++;
        
        estadoCronometro = 'pausado';
        
        // Actualizar campo de cantidad de pausas
        document.getElementById('cantidad_pausas').value = cantidadPausas;
        
        // Registrar pausa en historial
        const operacionId = document.getElementById('operacion_id')?.value;
        if (operacionId) {
            registrarHistorialCambio(operacionId, 'pausa', {
                tiempo_total_minutos: (tiempoAcumulado / 60000).toFixed(2)
            });
        }
        
        // Actualizar el display con el tiempo acumulado
        actualizarDisplayTiempo(tiempoAcumulado);
        
        document.getElementById('btnIniciar').style.display = 'inline-block';
        document.getElementById('btnPausar').style.display = 'none';
        document.getElementById('cronometroDisplay').classList.remove('cronometro-running');
    }
}

function reiniciarCronometro() {
    clearInterval(cronometroInterval);
    cronometroInterval = null;
    tiempoInicio = null;
    tiempoAcumulado = 0;
    tiempoTotal = 0;
    estadoCronometro = 'detenido';
    cantidadPausas = 0;
    tiempoTotalPausas = 0;
    tiempoPausaInicio = null;
    
    document.getElementById('cronometroDisplay').textContent = '00:00:00';
    document.getElementById('horaInicioDisplay').textContent = '-';
    document.getElementById('horaFinDisplay').textContent = '-';
    document.getElementById('tiempo_total_minutos').value = '';
    document.getElementById('hora_inicio').value = '';
    document.getElementById('hora_fin').value = '';
    document.getElementById('cantidad_pausas').value = '0';
    document.getElementById('tiempo_pausas').value = '0:00:00';
    document.getElementById('tiempo_pausas_minutos').value = '0';
    
    document.getElementById('btnIniciar').style.display = 'inline-block';
    document.getElementById('btnPausar').style.display = 'none';
    document.getElementById('btnReiniciar').style.display = 'none';
    document.getElementById('btnFinalizar').style.display = 'none';
    document.getElementById('cronometroDisplay').classList.remove('cronometro-running');
}

function actualizarCronometro() {
    if (estadoCronometro === 'corriendo' && tiempoInicio) {
        const tiempoActual = new Date();
        const tiempoTranscurrido = tiempoActual - tiempoInicio;
        const tiempoTotalAcumulado = tiempoAcumulado + tiempoTranscurrido;
        
        // Actualizar display
        actualizarDisplayTiempo(tiempoTotalAcumulado);

        // Habilitar botón de guardar si hay datos mínimos (solo si el día no está finalizado)
        if (window.DIA_FINALIZADO !== true) {
            const nombreOperacion = document.getElementById('nombre_operacion').value;
            const maquinaUsada = document.getElementById('maquina_usada').value;
            const piezasProducidas = document.getElementById('piezas_producidas').value;
            
            if (nombreOperacion && maquinaUsada && piezasProducidas && tiempoTotalAcumulado > 0) {
                const btnGuardar = document.getElementById('btnGuardarOperacion');
                if (btnGuardar) {
                    btnGuardar.disabled = false;
                    btnGuardar.title = '';
                }
            }
        }
    }
}

function actualizarDisplayTiempo(tiempoEnMilisegundos) {
    // Convertir milisegundos a segundos (usar Math.round para mayor precisión)
    tiempoTotal = Math.round(tiempoEnMilisegundos / 1000);
    
    // Actualizar el campo tiempo_total_minutos con el formato correcto para que se detecte
    const tiempoInput = document.getElementById('tiempo_total_minutos');
    if (tiempoInput) {
        const horas = Math.floor(tiempoTotal / 3600);
        const minutos = Math.floor((tiempoTotal % 3600) / 60);
        const segundos = tiempoTotal % 60;
        const tiempoFormateado = horas + ':' + String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
        tiempoInput.value = tiempoFormateado;
        
        // Disparar evento change para que se verifiquen los campos
        tiempoInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    const horas = Math.floor(tiempoTotal / 3600);
    const minutos = Math.floor((tiempoTotal % 3600) / 60);
    const segundos = tiempoTotal % 60;
    
    const tiempoFormateado = 
        String(horas).padStart(2, '0') + ' : ' +
        String(minutos).padStart(2, '0') + ' : ' +
        String(segundos).padStart(2, '0');
    
    document.getElementById('cronometroDisplay').textContent = tiempoFormateado;
}

function actualizarTiempoPausas() {
    // Convertir tiempo total de pausas a formato horas:minutos:segundos
    const totalSegundos = Math.round(tiempoTotalPausas / 1000);
    const horas = Math.floor(totalSegundos / 3600);
    const minutos = Math.floor((totalSegundos % 3600) / 60);
    const segundos = totalSegundos % 60;
    const tiempoFormateado = horas + ':' + String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
    
    document.getElementById('tiempo_pausas').value = tiempoFormateado;
    document.getElementById('tiempo_pausas_minutos').value = (tiempoTotalPausas / 60000).toFixed(2);
}

function finalizarCronometro() {
    if (estadoCronometro === 'corriendo' || estadoCronometro === 'pausado') {
        if (cronometroInterval) {
            clearInterval(cronometroInterval);
            cronometroInterval = null;
        }
        
        const tiempoFin = new Date();
        let tiempoTotalFinal;
        
        if (estadoCronometro === 'corriendo') {
            // Si está corriendo, acumular el tiempo transcurrido desde el último inicio/reanudar
            const tiempoTranscurrido = tiempoFin - tiempoInicio;
            tiempoTotalFinal = tiempoAcumulado + tiempoTranscurrido;
        } else {
            // Si está pausado, usar el tiempo acumulado y calcular el tiempo de la última pausa
            tiempoTotalFinal = tiempoAcumulado;
            // Si estaba pausado, calcular el tiempo de la última pausa antes de finalizar
            if (tiempoPausaInicio) {
                const tiempoPausa = tiempoFin - tiempoPausaInicio;
                tiempoTotalPausas += tiempoPausa;
                tiempoPausaInicio = null;
                actualizarTiempoPausas();
            }
        }
        
        // Actualizar tiempo acumulado para que quede guardado
        tiempoAcumulado = tiempoTotalFinal;
        
        // Actualizar display con el tiempo final (esto actualiza tiempoTotal)
        actualizarDisplayTiempo(tiempoTotalFinal);
        
        estadoCronometro = 'finalizado';
        
        document.getElementById('horaFinDisplay').textContent = tiempoFin.toLocaleTimeString('es-ES');
        document.getElementById('hora_fin').value = tiempoFin.toISOString().slice(0, 19).replace('T', ' ');
        
        // Leer el valor exacto del display y mostrarlo en formato horas:minutos:segundos
        const displayText = document.getElementById('cronometroDisplay').textContent.trim();
        const partes = displayText.split(' : ');
        if (partes.length === 3) {
            const horas = parseInt(partes[0]) || 0;
            const minutosDisplay = parseInt(partes[1]) || 0;
            const segundos = parseInt(partes[2]) || 0;
            
            // Mostrar en formato horas:minutos:segundos (ej: "1:15:30" para 1 hora, 15 minutos y 30 segundos)
            const tiempoFormateado = horas + ':' + String(minutosDisplay).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
            const tiempoInput = document.getElementById('tiempo_total_minutos');
            tiempoInput.value = tiempoFormateado;
            
            // Guardar el valor decimal en un atributo data para cuando se envíe el formulario
            const totalSegundos = (horas * 3600) + (minutosDisplay * 60) + segundos;
            const minutosDecimales = (totalSegundos / 60).toFixed(2);
            tiempoInput.setAttribute('data-valor-decimal', minutosDecimales);
            
            // Disparar evento change para que se verifiquen los campos y se habilite el botón
            tiempoInput.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            // Fallback: usar el cálculo desde milisegundos
            const minutosDecimales = (tiempoTotalFinal / 60000).toFixed(2);
            const totalSegundos = Math.round(minutosDecimales * 60);
            const horas = Math.floor(totalSegundos / 3600);
            const minutos = Math.floor((totalSegundos % 3600) / 60);
            const segundos = totalSegundos % 60;
            const tiempoFormateado = horas + ':' + String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
            const tiempoInput = document.getElementById('tiempo_total_minutos');
            tiempoInput.value = tiempoFormateado;
            tiempoInput.setAttribute('data-valor-decimal', minutosDecimales);
            
            // Disparar evento change para que se verifiquen los campos y se habilite el botón
            tiempoInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        document.getElementById('cronometroDisplay').classList.remove('cronometro-running');
        document.getElementById('btnIniciar').style.display = 'none';
        document.getElementById('btnPausar').style.display = 'none';
        document.getElementById('btnReiniciar').style.display = 'inline-block';
        document.getElementById('btnFinalizar').style.display = 'none';
        
        // Verificar si se pueden habilitar los campos para guardar
        setTimeout(() => {
            verificarCamposParaGuardar();
        }, 100);
    }
}

function verificarCamposParaGuardar() {
    const btnGuardar = document.getElementById('btnGuardarOperacion');
    if (!btnGuardar) return;
    
    // Si el día está finalizado, no habilitar el botón
    if (window.DIA_FINALIZADO === true) {
        btnGuardar.disabled = true;
        btnGuardar.title = 'Día finalizado - No se pueden guardar operaciones';
        console.log('verificarCamposParaGuardar: Botón deshabilitado - Día finalizado');
        return;
    }
    
    const nombreOperacion = document.getElementById('nombre_operacion')?.value || '';
    const maquinaUsada = document.getElementById('maquina_usada')?.value || '';
    const piezasProducidas = document.getElementById('piezas_producidas')?.value || '';
    const tiempoTotalMinutos = document.getElementById('tiempo_total_minutos')?.value || '';
    
    // Verificar si hay tiempo (puede estar en formato H:MM:SS o vacío)
    const tieneTiempo = tiempoTotalMinutos && tiempoTotalMinutos.trim() !== '' && tiempoTotalMinutos !== '0:00:00';
    
    console.log('verificarCamposParaGuardar:', {
        nombreOperacion,
        maquinaUsada,
        piezasProducidas,
        tiempoTotalMinutos,
        tieneTiempo,
        diaFinalizado: window.DIA_FINALIZADO
    });
    
    // Habilitar si todos los campos requeridos están llenos y hay tiempo registrado
    if (nombreOperacion && maquinaUsada && piezasProducidas && tieneTiempo) {
        btnGuardar.disabled = false;
        btnGuardar.title = '';
        console.log('verificarCamposParaGuardar: Botón HABILITADO');
    } else if (!tieneTiempo && nombreOperacion && maquinaUsada && piezasProducidas) {
        // Permitir guardar sin cronómetro si se llena manualmente el tiempo
        btnGuardar.disabled = false;
        btnGuardar.title = '';
        console.log('verificarCamposParaGuardar: Botón HABILITADO (sin tiempo, se puede llenar manualmente)');
    } else {
        // Si faltan campos, deshabilitar
        btnGuardar.disabled = true;
        if (!tieneTiempo) {
            btnGuardar.title = 'Complete todos los campos requeridos y registre el tiempo';
        } else {
            btnGuardar.title = 'Complete todos los campos requeridos';
        }
        console.log('verificarCamposParaGuardar: Botón DESHABILITADO - Faltan campos');
    }
}

// ==== Funciones de operaciones ====
function guardarOperacion() {
    const form = document.getElementById('formOperacion');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const operariaId = window.operariaIdCronometro || operariaActivaId;
    if (!operariaId || !operariasAbiertas[operariaId]) {
        alert('Debe seleccionar una operaria primero');
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
            alert('Operación guardada exitosamente');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCronometro'));
            modal.hide();
            cargarDatosOperaria(operariaId);
        } else {
            alert('Error: ' + (data.error || 'No se pudo guardar la operación'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar la operación');
    });
}

function editarOperacion(operacionId) {
    // Verificar si el día está finalizado
    if (window.DIA_FINALIZADO) {
        alert('No se puede editar operaciones de un día que ya está finalizado');
        return;
    }
    if (!operacionId) {
        alert('ID de operación no válido');
        return;
    }
    
    // Cargar datos de la operación existente
    const operariaId = operariaActivaId;
    if (!operariaId || !operariasAbiertas[operariaId]) {
        alert('No se encontró la operaria activa');
        return;
    }
    
    const operaria = operariasAbiertas[operariaId];
    const operacion = operaria.operaciones.find(op => op.id == operacionId);
    
    if (!operacion) {
        alert('No se encontró la operación');
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

function eliminarOperacion(operacionId, operariaId) {
    // Verificar si el día está finalizado
    if (window.DIA_FINALIZADO) {
        alert('No se puede eliminar operaciones de un día que ya está finalizado');
        return;
    }
    if (!confirm('¿Está seguro de eliminar esta operación?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', operacionId);
    
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=eliminarOperacion`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Operación eliminada exitosamente');
            cargarDatosOperaria(operariaId);
        } else {
            alert('Error: ' + (data.error || 'No se pudo eliminar la operación'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar la operación');
    });
}

function verHistorialOperacion(operacionId) {
    if (!operacionId) {
        alert('ID de operación no válido');
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
            console.error('Error al cargar historial:', error);
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

function registrarHistorialCambio(operacionId, tipoCambio, datos = {}) {
    // Esta función se llama para registrar cambios en el historial
    // El historial se registra automáticamente en el servidor cuando se guarda/actualiza la operación
    // Por ahora, solo registramos en consola para debugging
    console.log('Registro de historial:', { operacionId, tipoCambio, datos });
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
        alert('Debe seleccionar una operaria primero');
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
            alert('Retroceso registrado exitosamente');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalRetroceso'));
            modal.hide();
            cargarDatosOperaria(operariaId);
        } else {
            alert('Error: ' + (data.error || 'No se pudo registrar el retroceso'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al registrar el retroceso');
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
                    console.error('Respuesta no es JSON:', text);
                    throw new Error('El servidor devolvió HTML en lugar de JSON');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.DIA_FINALIZADO = data.finalizado;
                console.log('Estado del día finalizado:', window.DIA_FINALIZADO);
                // Actualizar botones si ya están renderizados
                actualizarBotonesEstadoFinalizado();
                return data.finalizado;
            }
            window.DIA_FINALIZADO = false;
            return false;
        })
        .catch(error => {
            console.error('Error al verificar estado del día:', error);
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
        if (esFinalizado) {
            btn.disabled = true;
            btn.classList.add('disabled');
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.title = 'Día finalizado - No se pueden agregar nuevas operaciones';
        } else {
            btn.disabled = false;
            btn.classList.remove('disabled');
            btn.style.opacity = '';
            btn.style.cursor = '';
            btn.title = '';
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
                    btn.setAttribute('onclick', 'alert("No se puede realizar esta acción en un día finalizado"); return false;');
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
    
    console.log('Botones actualizados. Día finalizado:', esFinalizado);
}

// ==== Funciones para Finalizar Día ====
function abrirModalFinalizarDia() {
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
                    console.error('Respuesta no es JSON:', text);
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
            console.error('Error al obtener datos de referencia:', error);
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
        alert('Error: No se pudo obtener la fecha');
        return;
    }
    
    if (prendasTerminadas < 0 || prendasEmpezadas < 0) {
        alert('Por favor, ingrese valores válidos para las prendas');
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
                console.error('Respuesta no es JSON:', text);
                throw new Error('El servidor devolvió HTML en lugar de JSON');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Día finalizado exitosamente. El resumen se ha guardado en la bitácora. Las operaciones de este día ahora están bloqueadas.');
            // Actualizar estado
            window.DIA_FINALIZADO = true;
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalFinalizarDia'));
            modal.hide();
            // Recargar página para ver los cambios
            window.location.reload();
        } else {
            alert('Error al finalizar el día: ' + (data.error || 'Error desconocido'));
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="bi bi-save"></i> Guardar y Finalizar Día';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al finalizar el día: ' + error.message);
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="bi bi-save"></i> Guardar y Finalizar Día';
    });
}
