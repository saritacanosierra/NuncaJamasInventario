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
            aviso('Por favor, ingresa el nombre de la operaria');
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
        });
}

function crearOperariaConNombre(nombre) {
    if (window.DIA_FINALIZADO === true) {
        aviso('Este día está cerrado. Ábrelo primero si necesitas sumar a alguien.');
        return;
    }
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
        const contenedor = document.getElementById('contenedorOperarias');
    if (!contenedor) {
        return;
    }
    
        contenedor.innerHTML = '';
    
    if (!operariasAbiertas[id]) {
        return;
    }
    
    const operaria = operariasAbiertas[id];
        const div = crearContenidoOperaria(id, operaria);
        contenedor.appendChild(div);
    
    // Actualizar botones después de renderizar
    setTimeout(() => {
        actualizarBotonesEstadoFinalizado();
    }, 200);
    
    // Asegurar que el contenido sea visible
    div.style.display = 'block';
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
                    <i class="bi bi-calendar-day"></i> En el día — ${operaria.nombre}
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
                            <label class="form-label">Máquina *</label>
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
                            <i class="bi bi-save"></i> Guardar en el día
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
                        <h6><i class="bi bi-clock-history"></i> Tiempo trabajado</h6>
                        <h3 class="tiempo-total">${registro.tiempo_total_trabajado} min</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h6><i class="bi bi-exclamation-triangle"></i> Tiempo perdido</h6>
                        <h3 class="tiempo-perdido">${registro.tiempo_perdido_retrocesos} min</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6><i class="bi bi-box-seam"></i> Piezas</h6>
                        <h3 class="piezas-producidas">${registro.piezas_producidas}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6><i class="bi bi-graph-up"></i> Ritmo</h6>
                        <h3 class="eficiencia-promedio">${(parseFloat(registro.eficiencia_promedio) || 0).toFixed(2)}%</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Operaciones -->
        <div class="card" id="cardOperaciones_${id}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-task"></i> Trabajos del día</h5>
                <button type="button" class="btn btn-success" id="btnNuevaOperacion_${id}" 
                        data-en-dia="${operaria.registro && operaria.registro.id ? '1' : '0'}"
                        data-bs-toggle="modal" data-bs-target="#modalCronometro" 
                        onclick="abrirModalCronometro('${id}')"
                        ${!operaria.registro || !operaria.registro.id || (typeof window.DIA_FINALIZADO !== 'undefined' && window.DIA_FINALIZADO) ? 'disabled' : ''}>
                    <i class="bi bi-play-circle"></i> Nueva operación
                </button>
            </div>
            <div class="card-body">
                ${!operaria.registro || !operaria.registro.id ? `
                    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle me-2" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Esta persona todavía no está en el día</strong><br>
                            <small>Elige el turno y la máquina, y toca Guardar en el día. Después podrás anotar sus trabajos.</small>
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
