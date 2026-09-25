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
    if (window.PRODUCCION_SOLO_PROPIOS && window.USUARIO_NOMBRE) {
        // Para operarios, primero intentar cargar las operarias del día
        // Esto cargará su operaria si ya tiene un registro guardado
        const fecha = window.FECHA_ACTUAL || new Date().toISOString().split('T')[0];
        const nombreOperario = window.USUARIO_NOMBRE.trim();
        
        // Cargar operarias del día y luego verificar si se encontró la del operario
        cargarOperariasDelDiaParaOperario(fecha, nombreOperario);
    } else {
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
                                    } else if (!tieneTiempo && nombreOperacion && maquinaUsada && piezasProducidas) {
                    // Permitir guardar sin cronómetro si se llena manualmente el tiempo
                    btnGuardar.disabled = false;
                    btnGuardar.title = '';
                                    } else {
                    // Si faltan campos, deshabilitar
                    btnGuardar.disabled = true;
                    btnGuardar.title = 'Complete todos los campos requeridos';
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
    
});

