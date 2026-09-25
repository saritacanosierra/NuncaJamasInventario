/**
 * Vista previa del efectivo esperado al cerrar la caja.
 */
document.addEventListener('DOMContentLoaded', function () {
    const base = document.getElementById('base');
    const contado = document.getElementById('contado');
    const esperado = document.getElementById('esperado_caja');
    const lectura = document.getElementById('lectura_caja');
    if (!base || !esperado) {
        return;
    }
    const efectivo = parseInt(esperado.dataset.efectivo, 10) || 0;
    const gastos = parseInt(esperado.dataset.gastos, 10) || 0;
    const compras = parseInt(esperado.dataset.compras, 10) || 0;

    function pesos(numero) {
        const redondo = Math.round(numero);
        const signo = redondo < 0 ? '-' : '';
        return signo + '$' + Math.abs(redondo).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function pintar() {
        const esperadoN = Math.round((parseInt(base.value, 10) || 0) + efectivo - gastos - compras);
        const contadoN = contado ? (parseInt(contado.value, 10) || 0) : 0;
        const diferencia = contadoN - esperadoN;
        esperado.textContent = pesos(esperadoN);
        if (!lectura) {
            return;
        }
        if (diferencia === 0) {
            lectura.textContent = 'Con ese conteo, el cajón cuadra.';
        } else if (diferencia > 0) {
            lectura.textContent = 'Con ese conteo, sobran ' + pesos(diferencia) + '.';
        } else {
            lectura.textContent = 'Con ese conteo, faltan ' + pesos(Math.abs(diferencia)) + '.';
        }
    }

    base.addEventListener('input', pintar);
    if (contado) {
        contado.addEventListener('input', pintar);
    }
    pintar();
});
