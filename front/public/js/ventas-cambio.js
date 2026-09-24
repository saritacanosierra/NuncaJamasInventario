/**
 * Elige la talla que reemplaza a la vendida.
 */
document.addEventListener('DOMContentLoaded', function () {
    const buscar = document.getElementById('buscar_talla');
    if (!buscar) {
        return;
    }
    let espera = null;
    buscar.addEventListener('input', function () {
        clearTimeout(espera);
        const termino = buscar.value.trim();
        if (termino.length < 2) {
            return;
        }
        espera = setTimeout(function () {
            const url = (window.BASE_URL || '') + 'index.php?action=productos&method=buscarPorNombre&termino=' + encodeURIComponent(termino);
            fetch(url)
                .then(function (respuesta) { return respuesta.json(); })
                .then(function (data) {
                    const caja = document.getElementById('resultados_talla');
                    caja.innerHTML = '';
                    (data.productos || []).forEach(function (producto) {
                        const tallas = producto.tallas && producto.tallas.length
                            ? producto.tallas
                            : [{ id: 0, talla: producto.talla || '', stock: producto.stock }];
                        tallas.forEach(function (talla) {
                            if ((parseInt(talla.stock, 10) || 0) < 1) {
                                return;
                            }
                            const boton = document.createElement('button');
                            boton.type = 'button';
                            boton.className = 'btn btn-outline-info btn-sm me-2 mb-2';
                            boton.textContent = producto.nombre + ' · talla ' + talla.talla + ' · stock ' + talla.stock;
                            boton.addEventListener('click', function () {
                                document.getElementById('producto_entra_id').value = producto.id;
                                document.getElementById('talla_entra_id').value = talla.id;
                                document.getElementById('talla_elegida').textContent = 'Se lleva: ' + boton.textContent;
                                caja.innerHTML = '';
                            });
                            caja.appendChild(boton);
                        });
                    });
                });
        }, 300);
    });

    const form = buscar.closest('form');
    form.addEventListener('submit', function (evento) {
        if (!document.getElementById('producto_entra_id').value || !document.getElementById('talla_entra_id').value) {
            evento.preventDefault();
            aviso('Elige la talla nueva.');
        }
    });
});
