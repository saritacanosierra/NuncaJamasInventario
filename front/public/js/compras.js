/**
 * Líneas de una compra.
 */
document.addEventListener('DOMContentLoaded', function () {
    const buscar = document.getElementById('buscar_compra');
    const form = document.getElementById('formCompra');
    if (!buscar || !form) {
        return;
    }

    const lineas = [];
    let espera = null;
    let productoElegido = null;

    function decirCompra(mensaje) {
        const caja = document.getElementById('aviso_compra');
        if (!caja) {
            return;
        }
        caja.textContent = mensaje;
        caja.classList.toggle('d-none', mensaje === '');
    }

    function agregarLinea() {
        const nombre = buscar.value.trim();
        const talla = document.getElementById('talla_compra').value.trim();
        const color = document.getElementById('color_compra').value.trim();
        const cantidad = parseInt(document.getElementById('cantidad_compra').value, 10) || 0;
        const costo = parseFloat(document.getElementById('costo_compra').value) || 0;
        const categoria = document.getElementById('categoria_compra').value;
        const productoId = productoElegido ? parseInt(productoElegido.id, 10) : 0;
        if (nombre === '' || talla === '' || cantidad < 1) {
            decirCompra('Escribe la prenda, la talla y la cantidad.');
            return;
        }
        decirCompra('');
        lineas.push({
            producto_id: productoId,
            talla_id: productoElegido ? productoElegido.talla_id : 0,
            nombre: nombre,
            talla: talla,
            color: color,
            categoria_id: categoria,
            cantidad: cantidad,
            costo: costo
        });
        pintar();
        buscar.value = '';
        document.getElementById('talla_compra').value = '';
        document.getElementById('color_compra').value = '';
        document.getElementById('cantidad_compra').value = '1';
        document.getElementById('resultados_compra').innerHTML = '';
        productoElegido = null;
    }

    document.getElementById('btn_agregar_compra').addEventListener('click', agregarLinea);
    ['buscar_compra', 'talla_compra', 'color_compra', 'cantidad_compra', 'costo_compra'].forEach(function (id) {
        document.getElementById(id).addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter') {
                evento.preventDefault();
                agregarLinea();
            }
        });
    });

        buscar.addEventListener('input', function () {
        productoElegido = null;
        clearTimeout(espera);
        const termino = buscar.value.trim();
        if (termino.length < 2) {
            document.getElementById('resultados_compra').innerHTML = '';
            return;
        }
        espera = setTimeout(function () {
            fetch((window.BASE_URL || '') + 'index.php?action=compras&method=buscarProducto&termino=' + encodeURIComponent(termino))
                .then(function (respuesta) { return respuesta.json(); })
                .then(function (data) {
                    const caja = document.getElementById('resultados_compra');
                    caja.innerHTML = '';
                    if (!(data.productos || []).length) {
                        caja.innerHTML = '<p class="mb-0 text-muted">No está en el inventario. Completa talla, cantidad y categoría para crearla como comprada.</p>';
                    }
                    (data.productos || []).forEach(function (producto) {
                        const tallas = producto.tallas && producto.tallas.length
                            ? producto.tallas
                            : [{ id: 0, talla: producto.talla || '', stock: producto.stock }];
                        tallas.forEach(function (talla) {
                            const boton = document.createElement('button');
                            boton.type = 'button';
                            boton.className = 'btn btn-outline-info btn-sm me-2 mb-2';
                            boton.textContent = producto.nombre + ' · talla ' + talla.talla + ' · stock ' + talla.stock;
                            boton.addEventListener('click', function () {
                                productoElegido = { id: producto.id, talla_id: talla.id };
                                buscar.value = producto.nombre;
                                document.getElementById('talla_compra').value = talla.talla || '';
                                document.getElementById('costo_compra').value = producto.precio_costo || 0;
                                if (producto.color) {
                                    document.getElementById('color_compra').value = producto.color;
                                }
                                caja.innerHTML = '';
                                document.getElementById('cantidad_compra').focus();
                            });
                            caja.appendChild(boton);
                        });
                    });
                });
        }, 300);
    });

    form.addEventListener('submit', function (evento) {
        if (lineas.length === 0) {
            evento.preventDefault();
            aviso('Agrega al menos una prenda a la compra.');
            return;
        }
        document.getElementById('lineas_compra').value = JSON.stringify(lineas);
    });

    function pintar() {
        const cuerpo = document.querySelector('#tabla_lineas_compra tbody');
        cuerpo.innerHTML = '';
        lineas.forEach(function (linea, indice) {
            const fila = document.createElement('tr');
            fila.innerHTML = '<td></td><td><input type="number" class="form-control" min="1" step="1"></td><td><input type="number" class="form-control" min="0" step="0.01"></td><td><button type="button" class="btn btn-sm btn-outline-danger btn-icono" title="Quitar"><i class="bi bi-trash"></i></button></td>';
            fila.children[0].textContent = linea.nombre + ' · talla ' + linea.talla;
            const cantidad = fila.querySelectorAll('input')[0];
            const costo = fila.querySelectorAll('input')[1];
            cantidad.value = linea.cantidad;
            costo.value = linea.costo;
            cantidad.addEventListener('input', function () { linea.cantidad = parseInt(cantidad.value, 10) || 1; });
            costo.addEventListener('input', function () { linea.costo = parseFloat(costo.value) || 0; });
            fila.querySelector('button').addEventListener('click', function () {
                lineas.splice(indice, 1);
                pintar();
            });
            cuerpo.appendChild(fila);
        });
    }
});
