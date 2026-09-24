/**
 * Vista previa del efectivo esperado al cerrar la caja.
 */
document.addEventListener('DOMContentLoaded', function () {
    const base = document.getElementById('base');
    const esperado = document.getElementById('esperado_caja');
    if (!base || !esperado) {
        return;
    }
    const efectivo = parseInt(esperado.dataset.efectivo, 10) || 0;
    const gastos = parseInt(esperado.dataset.gastos, 10) || 0;
    const compras = parseInt(esperado.dataset.compras, 10) || 0;

    function pintar() {
        const redondo = Math.round((parseInt(base.value, 10) || 0) + efectivo - gastos - compras);
        const signo = redondo < 0 ? '-' : '';
        esperado.textContent = signo + '$' + Math.abs(redondo).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    base.addEventListener('input', pintar);
});
