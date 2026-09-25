function abrirModalCronometro(operariaId, esEdicion = false) {
    if (!operariaId) operariaId = operariaActivaId;
    window.operariaIdCronometro = operariaId;
    
    // Asegurar que el campo de tiempo esté readonly por defecto
    const tiempoInput = document.getElementById('tiempo_total_minutos');
    if (tiempoInput && !esEdicion) {
        tiempoInput.setAttribute('readonly', 'readonly');
        tiempoInput.style.backgroundColor = '#ece2e1';
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
        return;
    }
    
    const nombreOperacion = document.getElementById('nombre_operacion')?.value || '';
    const maquinaUsada = document.getElementById('maquina_usada')?.value || '';
    const piezasProducidas = document.getElementById('piezas_producidas')?.value || '';
    const tiempoTotalMinutos = document.getElementById('tiempo_total_minutos')?.value || '';
    
    // Verificar si hay tiempo (puede estar en formato H:MM:SS o vacío)
    const tieneTiempo = tiempoTotalMinutos && tiempoTotalMinutos.trim() !== '' && tiempoTotalMinutos !== '0:00:00';

    if (nombreOperacion && maquinaUsada && piezasProducidas && tieneTiempo) {
        btnGuardar.disabled = false;
        btnGuardar.title = '';
    } else if (!tieneTiempo && nombreOperacion && maquinaUsada && piezasProducidas) {
        btnGuardar.disabled = false;
        btnGuardar.title = '';
    } else {
        // Si faltan campos, deshabilitar
        btnGuardar.disabled = true;
        if (!tieneTiempo) {
            btnGuardar.title = 'Complete todos los campos requeridos y registre el tiempo';
        } else {
            btnGuardar.title = 'Complete todos los campos requeridos';
        }
    }
}

// ==== Funciones de operaciones ====
