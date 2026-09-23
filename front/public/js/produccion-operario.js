/**
 * Pantalla de piso para quien solo ve su propia producción.
 * Tres pasos: máquina, reloj y piezas. El tiempo sigue con la hora real.
 */
(function () {
    if (!window.PRODUCCION_SOLO_PROPIOS) {
        return;
    }

    window.PISO_OPERARIO = true;

    const MAQUINAS = ['Bordadora', 'Recubridora', 'Plana', 'Fileteadora', 'Pulir', 'Detalles manuales'];
    const DEFECTOS = ['Hilo suelto', 'Puntada corrida', 'Fallo de máquina', 'Error humano', 'Desalineación', 'Mancha'];
    const CLAVE = 'pisoOperacionV1';

    const estado = {
        fase: 'listo',
        maquina: '',
        nombre: '',
        inicio: 0,
        pausaAcum: 0,
        pausaDesde: null,
        pausas: 0,
        piezas: 1,
        problema: false,
        defecto: '',
        minutosProblema: 1,
        aviso: '',
        codigo: ''
    };

    let relojTimer = null;

    function raiz() {
        return document.getElementById('pisoOperario');
    }

    function puede(slug) {
        return (window.FINAL_PERMISSIONS || []).indexOf(slug) !== -1;
    }

    function operariaActual() {
        if (!operariaActivaId || !operariasAbiertas[operariaActivaId]) {
            return null;
        }
        return operariasAbiertas[operariaActivaId];
    }

    function guardarLocal() {
        if (estado.fase === 'listo' || estado.fase === 'cerrado') {
            sessionStorage.removeItem(CLAVE);
            return;
        }
        sessionStorage.setItem(CLAVE, JSON.stringify({
            fase: estado.fase,
            maquina: estado.maquina,
            nombre: estado.nombre,
            inicio: estado.inicio,
            pausaAcum: estado.pausaAcum,
            pausaDesde: estado.pausaDesde,
            pausas: estado.pausas,
            piezas: estado.piezas,
            problema: estado.problema,
            defecto: estado.defecto,
            minutosProblema: estado.minutosProblema,
            codigo: estado.codigo
        }));
    }

    function leerLocal() {
        try {
            const dato = JSON.parse(sessionStorage.getItem(CLAVE) || 'null');
            if (!dato || !dato.inicio) {
                return;
            }
            estado.fase = dato.fase || 'corriendo';
            estado.maquina = dato.maquina || '';
            estado.nombre = dato.nombre || '';
            estado.inicio = dato.inicio;
            estado.pausaAcum = dato.pausaAcum || 0;
            estado.pausaDesde = dato.pausaDesde || null;
            estado.pausas = dato.pausas || 0;
            estado.piezas = dato.piezas || 1;
            estado.problema = !!dato.problema;
            estado.defecto = dato.defecto || '';
            estado.minutosProblema = dato.minutosProblema || 1;
            estado.codigo = dato.codigo || '';
        } catch (error) {
            sessionStorage.removeItem(CLAVE);
        }
    }

    function milisegundos() {
        const fin = estado.pausaDesde || Date.now();
        return Math.max(0, fin - estado.inicio - estado.pausaAcum);
    }

    function textoReloj(ms) {
        const total = Math.floor(ms / 1000);
        const horas = Math.floor(total / 3600);
        const minutos = Math.floor((total % 3600) / 60);
        const segundos = total % 60;
        return String(horas).padStart(2, '0') + ':' +
            String(minutos).padStart(2, '0') + ':' +
            String(segundos).padStart(2, '0');
    }

    function textoLlevas(ms) {
        const minutos = Math.max(1, Math.round(ms / 60000));
        if (ms < 60000) {
            return 'Menos de 1 minuto';
        }
        return 'Llevas ' + minutos + (minutos === 1 ? ' minuto' : ' minutos');
    }

    function fechaHoyTexto() {
        const fecha = window.FECHA_ACTUAL ? new Date(window.FECHA_ACTUAL + 'T12:00:00') : new Date();
        const texto = fecha.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' });
        return texto.charAt(0).toUpperCase() + texto.slice(1);
    }

    function nombreVisible() {
        return String(window.USUARIO_NOMBRE || '').trim().replace(/\S+/g, function (parte) {
            return parte.charAt(0).toUpperCase() + parte.slice(1).toLowerCase();
        });
    }

    function piezasDeHoy() {
        const operaria = operariaActual();
        return operaria && operaria.datos ? (parseInt(operaria.datos.piezas_producidas, 10) || 0) : 0;
    }

    function turnoDeAhora() {
        const hora = new Date().getHours();
        if (hora < 12) return 'mañana';
        if (hora < 18) return 'tarde';
        return 'noche';
    }

    function escapar(texto) {
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function listaHoy() {
        const operaria = operariaActual();
        const operaciones = (operaria && operaria.operaciones) || [];
        if (!operaciones.length) {
            return '<p class="piso-hoy">Todavía no hay trabajos guardados hoy.</p>';
        }
        const borrar = puede('produccion_operaciones:delete');
        return operaciones.map(function (op) {
            const minutos = Math.round(parseFloat(op.tiempo_total_minutos) || 0);
            const piezas = parseInt(op.piezas_producidas, 10) || 0;
            return '<div class="piso-item"><div><strong>' + escapar(op.maquina_usada || op.nombre_operacion || 'Trabajo') +
                '</strong><br>' + escapar(op.nombre_operacion || '') + ' · ' + piezas + ' piezas · ' + minutos + ' min</div>' +
                (borrar ? '<button type="button" class="piso-borrar" data-accion="borrar" data-id="' + op.id + '" data-codigo="' + escapar(op.codigo_operacion || op.nombre_operacion || '') + '" aria-label="Borrar" title="Borrar"><i class="bi bi-trash"></i></button>' : '') +
                '</div>';
        }).join('');
    }

    function saludoHoy() {
        const piezas = piezasDeHoy();
        const etiqueta = piezas === 1 ? 'pieza hoy' : 'piezas hoy';
        return '<header class="piso-saludo">' +
            '<div><h2 class="piso-hola">Hola, ' + escapar(nombreVisible()) + '</h2>' +
            '<p class="piso-fecha">' + escapar(fechaHoyTexto()) + '</p>' +
            '<span class="piso-chip">Día abierto</span></div>' +
            '<div class="piso-cuenta"><span class="piso-cuenta-num">' + piezas + '</span>' +
            '<span class="piso-cuenta-etiq">' + etiqueta + '</span></div></header>';
    }

    function pintar() {
        const caja = raiz();
        if (!caja) return;

        if (window.DIA_FINALIZADO === true && (estado.fase === 'listo' || estado.fase === 'cerrado')) {
            caja.innerHTML = '<header class="piso-saludo">' +
                '<div><h2 class="piso-hola">Hola, ' + escapar(nombreVisible()) + '</h2>' +
                '<p class="piso-fecha">' + escapar(fechaHoyTexto()) + '</p></div>' +
                '<div class="piso-cuenta"><span class="piso-cuenta-num">' + piezasDeHoy() + '</span>' +
                '<span class="piso-cuenta-etiq">' + (piezasDeHoy() === 1 ? 'pieza hoy' : 'piezas hoy') + '</span></div></header>' +
                '<div class="piso-cerrado"><i class="bi bi-lock"></i>' +
                '<p class="piso-paso">El día ya se cerró</p>' +
                '<p class="piso-hoy">No puedes anotar más trabajos. Si falta algo, pide que abran el día.</p></div>' +
                '<div class="piso-lista"><p class="piso-paso">Lo que hiciste hoy</p>' + listaHoy() + '</div>';
            return;
        }

        if (estado.fase === 'corriendo' || estado.fase === 'pausado') {
            const pausado = estado.fase === 'pausado';
            caja.innerHTML =
                '<div class="piso-reloj-caja' + (pausado ? ' pausado' : '') + '">' +
                    '<div class="piso-reloj" id="pisoReloj">' + textoReloj(milisegundos()) + '</div>' +
                    '<div class="piso-llevas" id="pisoLlevas">' + (pausado ? 'En pausa' : textoLlevas(milisegundos())) + '</div>' +
                    '<div class="piso-en">' + escapar(estado.maquina) + (estado.nombre ? ' · ' + escapar(estado.nombre) : '') + '</div>' +
                '</div>' +
                '<div class="piso-fila">' +
                    (pausado
                        ? '<button type="button" class="piso-accion piso-seguir" data-accion="seguir">Seguir</button>'
                        : '<button type="button" class="piso-accion piso-pause" data-accion="pausa">Pausa</button>') +
                    '<button type="button" class="piso-accion piso-termine" data-accion="termine">Ya terminé</button>' +
                '</div>' +
                '<p class="piso-hoy">El reloj sigue aunque salgas de esta pantalla. Al volver, el tiempo está igual.</p>';
            return;
        }

        if (estado.fase === 'piezas' || estado.fase === 'guardando') {
            const defectos = DEFECTOS.map(function (defecto) {
                return '<button type="button" class="piso-tile' + (estado.defecto === defecto ? ' is-on' : '') +
                    '" data-accion="defecto" data-valor="' + escapar(defecto) + '">' + escapar(defecto) + '</button>';
            }).join('');
            caja.innerHTML =
                (estado.aviso ? '<div class="piso-aviso">' + escapar(estado.aviso) + '</div>' : '') +
                '<div class="piso-reloj-caja listo-tiempo">' +
                    '<div class="piso-reloj">' + textoReloj(milisegundos()) + '</div>' +
                    '<div class="piso-llevas">Tiempo de este trabajo</div>' +
                    '<div class="piso-en">' + escapar(estado.maquina) + '</div>' +
                '</div>' +
                '<p class="piso-paso">¿Cuántas piezas hiciste?</p>' +
                '<div class="piso-piezas">' +
                    '<button type="button" class="piso-step" data-accion="menos" aria-label="Quitar una">−</button>' +
                    '<div class="piso-numero" id="pisoNumero">' + estado.piezas + '</div>' +
                    '<button type="button" class="piso-step" data-accion="mas" aria-label="Sumar una">+</button>' +
                '</div>' +
                (puede('produccion_retrocesos:create')
                    ? '<label class="piso-problema"><input type="checkbox" data-accion="problema"' + (estado.problema ? ' checked' : '') + '> Tuve un problema</label>' +
                      (estado.problema
                        ? '<div class="piso-defectos">' + defectos + '</div>' +
                          '<p class="piso-paso">Minutos perdidos: ' + estado.minutosProblema + '</p>' +
                          '<div class="piso-fila">' +
                            '<button type="button" class="piso-accion" data-accion="min-menos">Menos minuto</button>' +
                            '<button type="button" class="piso-accion" data-accion="min-mas">Más minuto</button>' +
                          '</div>'
                        : '')
                    : '') +
                '<button type="button" class="piso-accion piso-guardar" data-accion="guardar"' +
                    (estado.fase === 'guardando' ? ' disabled' : '') + '>' +
                    (estado.fase === 'guardando' ? 'Guardando...' : 'Guardar') +
                '</button>';
            return;
        }

        const tiles = MAQUINAS.map(function (maquina) {
            return '<button type="button" class="piso-tile' + (estado.maquina === maquina ? ' is-on' : '') +
                '" data-accion="maquina" data-valor="' + escapar(maquina) + '">' + escapar(maquina) + '</button>';
        }).join('');

        caja.innerHTML =
            (estado.aviso ? '<div class="piso-aviso">' + escapar(estado.aviso) + '</div>' : '') +
            saludoHoy() +
            '<p class="piso-paso">1. Elige la máquina</p>' +
            '<div class="piso-maquinas">' + tiles + '</div>' +
            '<p class="piso-paso">2. ¿Qué vas a hacer?</p>' +
            '<input class="form-control piso-que" id="pisoQue" value="' + escapar(estado.nombre) + '" placeholder="Ejemplo: pegar botones">' +
            '<p class="piso-hoy">Si lo dejas vacío, se guarda el nombre de la máquina.</p>' +
            '<button type="button" class="piso-accion piso-empezar" data-accion="empezar"' + (estado.maquina ? '' : ' disabled') + '>Empezar</button>' +
            '<div class="piso-lista"><p class="piso-paso">Lo que hiciste hoy</p>' + listaHoy() + '</div>';
    }

    function tick() {
        if (estado.fase !== 'corriendo') return;
        const reloj = document.getElementById('pisoReloj');
        const llevas = document.getElementById('pisoLlevas');
        if (reloj) reloj.textContent = textoReloj(milisegundos());
        if (llevas) llevas.textContent = textoLlevas(milisegundos());
    }

    function asegurarReloj() {
        if (relojTimer) clearInterval(relojTimer);
        relojTimer = setInterval(function () {
            tick();
            if (estado.fase === 'corriendo' && Date.now() % 5000 < 400) {
                guardarLocal();
            }
        }, 400);
    }

    function asegurarRegistro() {
        const operaria = operariaActual();
        if (!operaria) {
            return Promise.reject(new Error('Espera un momento y toca Empezar otra vez.'));
        }
        if (operaria.registro && operaria.registro.id) {
            return Promise.resolve(operaria);
        }
        const datos = new FormData();
        datos.set('operaria_nombre', operaria.nombre);
        datos.set('fecha', operaria.datos.fecha || window.FECHA_ACTUAL);
        datos.set('turno', turnoDeAhora());
        datos.set('meta_dia', '0');
        datos.set('maquina_asignada', estado.maquina);
        datos.set('tiempo_total_trabajado', '0');
        datos.set('tiempo_perdido_retrocesos', '0');
        datos.set('piezas_producidas', '0');
        datos.set('eficiencia_promedio', '0');
        return fetch((window.BASE_URL || '') + 'index.php?action=produccion&method=guardarRegistro', {
            method: 'POST',
            body: datos
        })
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (data) {
            if (!data.success) {
                throw new Error(data.error || 'No se pudo abrir el día');
            }
            operaria.registro = data.registro;
            return operaria;
        });
    }

    function empezar() {
        const que = document.getElementById('pisoQue');
        estado.nombre = (que && que.value.trim()) || estado.maquina;
        estado.aviso = '';
        const boton = raiz().querySelector('[data-accion="empezar"]');
        if (boton) boton.disabled = true;
        asegurarRegistro()
            .then(function () {
                estado.fase = 'corriendo';
                estado.inicio = Date.now();
                estado.pausaAcum = 0;
                estado.pausaDesde = null;
                estado.pausas = 0;
                estado.piezas = 1;
                estado.problema = false;
                estado.defecto = '';
                estado.codigo = 'OP-' + String(window.FECHA_ACTUAL || '').replace(/-/g, '') + '-' + String(Date.now()).slice(-3);
                guardarLocal();
                pintar();
                if (navigator.vibrate) navigator.vibrate(30);
            })
            .catch(function (error) {
                const mensaje = error && error.message ? error.message : '';
                estado.aviso = (mensaje && mensaje.indexOf('Unexpected') === -1)
                    ? mensaje
                    : 'No se pudo empezar. Revisa tu conexión e intenta otra vez.';
                pintar();
            });
    }

    function llenarFormulario() {
        const ms = milisegundos();
        const totalSegundos = Math.round(ms / 1000);
        const horas = Math.floor(totalSegundos / 3600);
        const minutos = Math.floor((totalSegundos % 3600) / 60);
        const segundos = totalSegundos % 60;
        const texto = horas + ':' + String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
        const inicio = new Date(estado.inicio);
        const fin = new Date();
        const aSql = function (fecha) {
            return fecha.toISOString().slice(0, 19).replace('T', ' ');
        };
        document.getElementById('operacion_id').value = '';
        document.getElementById('codigo_operacion').value = estado.codigo;
        document.getElementById('nombre_operacion').value = estado.nombre || estado.maquina;
        document.getElementById('maquina_usada').value = estado.maquina;
        document.getElementById('tiempo_estandar_por_pieza').value = '';
        document.getElementById('piezas_producidas').value = String(estado.piezas);
        const tiempo = document.getElementById('tiempo_total_minutos');
        tiempo.value = texto;
        tiempo.setAttribute('data-valor-decimal', (totalSegundos / 60).toFixed(2));
        document.getElementById('hora_inicio').value = aSql(inicio);
        document.getElementById('hora_fin').value = aSql(fin);
        document.getElementById('cantidad_pausas').value = String(estado.pausas);
        document.getElementById('tiempo_pausas_minutos').value = (estado.pausaAcum / 60000).toFixed(2);
        cantidadPausas = estado.pausas;
        window.operariaIdCronometro = operariaActivaId;
    }

    function guardarProblema(operacionId) {
        if (!estado.problema || !estado.defecto || !operacionId) {
            return Promise.resolve();
        }
        const operaria = operariaActual();
        const datos = new FormData();
        datos.set('operacion_id', String(operacionId));
        datos.set('tipo_defecto', estado.defecto);
        datos.set('maquina', estado.maquina);
        datos.set('minutos_perdidos', String(estado.minutosProblema));
        datos.set('accion_correctiva', '');
        datos.set('fecha', (operaria && operaria.datos && operaria.datos.fecha) || window.FECHA_ACTUAL);
        datos.set('operaria_nombre', operaria ? operaria.nombre : window.USUARIO_NOMBRE);
        return fetch((window.BASE_URL || '') + 'index.php?action=produccion&method=guardarRetroceso', {
            method: 'POST',
            body: datos
        }).then(function (respuesta) { return respuesta.json(); });
    }

    window.pisoOperarioAlGuardar = function (ok, data) {
        if (!ok) {
            estado.fase = 'piezas';
            estado.aviso = (data && data.error) || 'No se pudo guardar. Intenta otra vez.';
            pintar();
            return;
        }
        const id = data && data.operacion_id;
        guardarProblema(id).finally(function () {
            estado.fase = 'listo';
            estado.maquina = '';
            estado.nombre = '';
            estado.aviso = 'Quedó guardado.';
            sessionStorage.removeItem(CLAVE);
            pintar();
            if (operariaActivaId && typeof cargarDatosOperaria === 'function') {
                cargarDatosOperaria(operariaActivaId);
            }
        });
    };

    function onClick(evento) {
        const boton = evento.target.closest('[data-accion]');
        if (!boton || !raiz().contains(boton)) return;
        const accion = boton.getAttribute('data-accion');

        if (accion === 'maquina') {
            estado.maquina = boton.getAttribute('data-valor');
            estado.aviso = '';
            pintar();
            return;
        }
        if (accion === 'empezar') {
            empezar();
            return;
        }
        if (accion === 'pausa') {
            estado.pausaDesde = Date.now();
            estado.pausas += 1;
            estado.fase = 'pausado';
            guardarLocal();
            pintar();
            return;
        }
        if (accion === 'seguir') {
            if (estado.pausaDesde) {
                estado.pausaAcum += Date.now() - estado.pausaDesde;
            }
            estado.pausaDesde = null;
            estado.fase = 'corriendo';
            guardarLocal();
            pintar();
            return;
        }
        if (accion === 'termine') {
            if (estado.fase === 'corriendo') {
                estado.pausaDesde = Date.now();
            }
            estado.fase = 'piezas';
            guardarLocal();
            pintar();
            return;
        }
        if (accion === 'menos') {
            estado.piezas = Math.max(0, estado.piezas - 1);
            guardarLocal();
            const numero = document.getElementById('pisoNumero');
            if (numero) numero.textContent = String(estado.piezas);
            return;
        }
        if (accion === 'mas') {
            estado.piezas += 1;
            guardarLocal();
            const numeroMas = document.getElementById('pisoNumero');
            if (numeroMas) numeroMas.textContent = String(estado.piezas);
            return;
        }
        if (accion === 'problema') {
            estado.problema = boton.checked;
            if (!estado.problema) estado.defecto = '';
            guardarLocal();
            pintar();
            return;
        }
        if (accion === 'defecto') {
            estado.defecto = boton.getAttribute('data-valor');
            guardarLocal();
            pintar();
            return;
        }
        if (accion === 'min-menos') {
            estado.minutosProblema = Math.max(1, estado.minutosProblema - 1);
            guardarLocal();
            pintar();
            return;
        }
        if (accion === 'min-mas') {
            estado.minutosProblema += 1;
            guardarLocal();
            pintar();
            return;
        }
        if (accion === 'guardar') {
            if (estado.problema && !estado.defecto) {
                estado.aviso = 'Elige qué problema hubo.';
                estado.fase = 'piezas';
                pintar();
                return;
            }
            estado.fase = 'guardando';
            pintar();
            llenarFormulario();
            guardarOperacion();
            return;
        }
        if (accion === 'borrar') {
            const id = boton.getAttribute('data-id');
            if (id && typeof eliminarOperacion === 'function') {
                eliminarOperacion(id, operariaActivaId, boton.getAttribute('data-codigo') || '');
            }
        }
    }

    function esperarYPintar(intentos) {
        if (operariaActivaId && operariasAbiertas[operariaActivaId]) {
            pintar();
            return;
        }
        if (intentos > 40) {
            pintar();
            return;
        }
        setTimeout(function () { esperarYPintar(intentos + 1); }, 150);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const caja = raiz();
        if (!caja) return;
        leerLocal();
        caja.addEventListener('click', onClick);
        caja.addEventListener('change', onClick);
        if (typeof mostrarContenidoOperaria === 'function') {
            const original = mostrarContenidoOperaria;
            window.mostrarContenidoOperaria = function (id) {
                const resultado = original.apply(this, arguments);
                if (estado.fase === 'listo' || estado.fase === 'cerrado') {
                    pintar();
                }
                return resultado;
            };
        }
        asegurarReloj();
        esperarYPintar(0);
    });
})();
