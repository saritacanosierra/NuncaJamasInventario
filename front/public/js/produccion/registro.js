function cargarDatosOperaria(id) {
    if (!operariasAbiertas[id]) {
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
                                        if (id === operariaActivaId) {
                        mostrarContenidoOperaria(id);
                    }
                }
            })
            .catch(error => {
            });
        
        // Cargar retrocesos
        fetch(`${BASE_URL_PROD}index.php?action=produccion&method=getRetrocesos&registro_id=${operaria.registro.id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.retrocesos) {
                    operaria.retrocesos = data.retrocesos;
                                        // Actualizar tiempo perdido en retrocesos
                    const tiempoPerdido = data.retrocesos.reduce((sum, r) => sum + (parseInt(r.minutos_perdidos) || 0), 0);
                    operaria.datos.tiempo_perdido_retrocesos = tiempoPerdido;
                    if (id === operariaActivaId) {
                        mostrarContenidoOperaria(id);
                    }
                }
            })
            .catch(error => {
            });
    } else {
        // Fallback: cargar por fecha y nombre de operaria
        fetch(`${BASE_URL_PROD}index.php?action=produccion&method=getOperaciones&fecha=${fecha}&operaria=${encodeURIComponent(operaria.nombre)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.operaciones) {
                    operaria.operaciones = data.operaciones;
                                        if (id === operariaActivaId) {
                        mostrarContenidoOperaria(id);
                    }
                }
            })
            .catch(error => {
            });
    }
}

// ==== Funciones de registro ====
function guardarRegistroDia(id) {
        
    if (!operariasAbiertas[id]) {
        aviso('Error: Operaria no encontrada');
        return;
    }
    
    const operaria = operariasAbiertas[id];
        
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
                document.querySelectorAll('[data-operaria-id]').forEach(el => {
                    });
        aviso('Error: No se encontró el contenedor de la operaria. ID: ' + id);
        return;
    }
    
        
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
                    }
    }
    
    if (!form) {
                                        aviso('Error: No se encontró el formulario. Por favor, recarga la página o intenta guardar nuevamente.');
        return;
    }
    
        
        if (!form.checkValidity()) {
                form.reportValidity();
        return;
    }
    
        
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
        
    if (!datosEnvio.operaria_nombre || !datosEnvio.fecha) {
        aviso('Error: Faltan datos obligatorios. Operaria: ' + datosEnvio.operaria_nombre + ', Fecha: ' + datosEnvio.fecha);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = textoOriginal;
        }
        return;
    }
    
        
    fetch(`${BASE_URL_PROD}index.php?action=produccion&method=guardarRegistro`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
                if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.text(); // Primero obtener como texto para ver si hay errores
    })
    .then(text => {
                try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Respuesta inválida del servidor');
        }
    })
    .then(data => {
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
            aviso('Error: ' + (data.error || 'No se pudo guardar el registro'));
        }
    })
    .catch(error => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = textoOriginal;
        }
        // Restaurar contenido anterior en caso de error
        contenedor.innerHTML = contenidoAnterior;
        aviso('Error al guardar el registro. Por favor, intenta nuevamente.');
    });
}

// ==== Funciones del cronómetro ====
