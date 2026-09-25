function buscarPorCodigo() {
    const codigo = document.getElementById('codigo_barras_input').value.trim();
    const lista = document.getElementById('resultados_busqueda');
    if (!codigo) return;
    if (lista) lista.innerHTML = '<p class="text-muted">Buscando...</p>';

    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=productos&method=buscarPorCodigo&codigo=${encodeURIComponent(codigo)}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarProducto(data.producto);
                document.getElementById('resultados_busqueda').innerHTML = '';
            } else {
                filtrarProductos(codigo);
            }
        })
        .catch(() => filtrarProductos(codigo));
}

function filtrarPorCodigo() {
    const codigo = document.getElementById('codigo_barras_input').value.trim();
    if (!codigo) {
        document.getElementById('resultados_busqueda').innerHTML = '';
        return;
    }
    filtrarProductos(codigo);
}

function buscarPorNombre() {
    const termino = document.getElementById('buscar_nombre').value.trim();
    filtrarProductos(termino);
}

function filtrarProductos(termino) {
    const lista = document.getElementById('resultados_busqueda');
    if (!termino) {
        lista.innerHTML = '';
        return;
    }
    lista.innerHTML = '<p class="text-muted">Buscando...</p>';

    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=productos&method=buscarPorNombre&termino=${encodeURIComponent(termino)}`)
        .then(r => r.text())
        .then(txt => {
                        let data;
            try {
                data = JSON.parse(txt);
            } catch (e) {
                document.getElementById('resultados_busqueda').innerHTML =
                    '<p class="text-danger">No se pudo leer la respuesta del servidor.</p>';
                return;
            }

            let html = '';
            if (data.success && data.productos && data.productos.length > 0) {
                html = '<div class="list-group">';
                data.productos.forEach(p => {
                    const codigoLista = String(p.codigo_barras || '').replace(/'/g, '');
                    const tallasTxt = (p.tallas || []).map(function (t) {
                        return t.talla + ' (' + t.stock + ')';
                    }).join(', ') || (p.talla || '');
                    html += `<a href="#" class="list-group-item list-group-item-action pos-resultado" onclick="seleccionarProductoDesdeLista('${codigoLista}'); return false;">
                        <span class="pos-resultado-nombre">${p.nombre}</span>
                        <span class="pos-resultado-meta">${p.codigo_barras} · ${p.color} · ${tallasTxt}</span>
                    </a>`;
                });
                html += '</div>';
            } else {
                html = '<p class="text-muted">No se encontraron productos</p>';
            }
            document.getElementById('resultados_busqueda').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('resultados_busqueda').innerHTML =
                '<p class="text-danger">No se pudo buscar las prendas. Intenta de nuevo.</p>';
        });
}

function seleccionarProductoDesdeLista(codigo) {
    document.getElementById('codigo_barras_input').value = codigo;
    buscarPorCodigo();
    document.getElementById('resultados_busqueda').innerHTML = '';
    document.getElementById('buscar_nombre').value = '';
}

function mostrarProducto(producto) {
    // Asegurar que el campo producto_id esté limpio antes de asignar nuevo valor
    const productoIdInput = document.getElementById('producto_id');
    if (productoIdInput) {
        productoIdInput.value = ''; // Limpiar primero para evitar problemas
        productoIdInput.value = producto.id.toString(); // Asignar nuevo ID como string
    }
    
    document.getElementById('producto_nombre').textContent = producto.nombre || '';
    document.getElementById('producto_color').textContent = producto.color || '';
    const precioPesos = Math.round(parseFloat(producto.precio_venta) || 0);
    const ivaPesos = ivaIncluidoEn(precioPesos);
    const basePesos = Math.max(0, precioPesos - ivaPesos);
    const precioEl = document.getElementById('producto_precio');
    precioEl.textContent = formatearNumero(precioPesos);
    precioEl.dataset.valor = String(precioPesos);
    precioEl.dataset.base = String(basePesos);
    precioEl.dataset.iva = String(ivaPesos);
    const ivaEl = document.getElementById('producto_iva');
    if (ivaEl) ivaEl.textContent = 'IVA incluido $' + formatearNumero(ivaPesos);

    const stockBadge = document.getElementById('producto_stock');
    const selectorTalla = document.getElementById('producto_talla_sel');
    const tallas = Array.isArray(producto.tallas) && producto.tallas.length
        ? producto.tallas
        : [{ id: 0, talla: producto.talla || '', stock: producto.stock || 0 }];
    selectorTalla.innerHTML = '';
    const cajaTallas = document.getElementById('pos_tallas');
    if (cajaTallas) cajaTallas.innerHTML = '';
    tallas.forEach(function (talla, indice) {
        const opcion = document.createElement('option');
        opcion.value = talla.id;
        opcion.dataset.stock = talla.stock;
        opcion.dataset.talla = talla.talla;
        opcion.textContent = talla.talla + ' · ' + talla.stock + ' en stock';
        selectorTalla.appendChild(opcion);
        if (!cajaTallas) return;
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'pos-talla';
        chip.textContent = talla.talla || 'Única';
        chip.addEventListener('click', function () {
            selectorTalla.selectedIndex = indice;
            pintarTallaElegida();
        });
        cajaTallas.appendChild(chip);
    });
    function pintarTallaElegida() {
        const elegida = selectorTalla.selectedOptions[0];
        const stock = elegida ? parseInt(elegida.dataset.stock, 10) || 0 : 0;
        stockBadge.textContent = stock;
        stockBadge.className = 'badge ' + (stock <= (producto.stock_minimo || 0) ? 'bg-warning' : 'bg-success');
        document.getElementById('producto_talla').textContent = elegida ? elegida.dataset.talla : '';
        document.getElementById('cantidad_producto').max = stock;
        if (!cajaTallas) return;
        Array.from(cajaTallas.children).forEach(function (chip, indice) {
            chip.classList.toggle('is-activo', indice === selectorTalla.selectedIndex);
        });
    }
    selectorTalla.onchange = pintarTallaElegida;
    pintarTallaElegida();
    
    const img = document.getElementById('producto_foto');
    if (producto.foto) {
        img.src = (BASE_URL_VENTAS || window.BASE_URL || '') + 'front/public/uploads/productos/' + producto.foto;
        img.classList.remove('d-none');
    } else {
        img.classList.add('d-none');
    }
    
    const cardInfo = document.getElementById('producto_info');
    cardInfo.classList.remove('card-producto-oculto');
    document.getElementById('cantidad_producto').value = 1;
    
    // Debug en producción
    }

function agregarAlCarrito() {
    // Obtener el ID del producto ANTES de cualquier otra operación
    const productoIdInput = document.getElementById('producto_id');
    const productoIdRaw = productoIdInput ? productoIdInput.value.trim() : '';
    
    // Validar que hay un producto seleccionado
    if (!productoIdRaw) {
        alert('Por favor, seleccione un producto primero');
        return;
    }
    
    // Convertir productoId a número para comparación consistente
    const productoIdNum = parseInt(productoIdRaw);
    if (isNaN(productoIdNum) || productoIdNum <= 0) {
        alert('Error: ID de producto inválido. Por favor, seleccione un producto nuevamente.');
        return;
    }
    
    const cantidad = parseInt(document.getElementById('cantidad_producto').value) || 0;
    if (cantidad <= 0) {
        alert('Por favor, ingrese una cantidad válida');
        return;
    }
    
    // Asegurar que hay una venta activa
    if (!ventaActivaId || !ventasAbiertas[ventaActivaId]) {
        crearNuevaVenta();
    }
    
    // Obtener datos del producto desde la interfaz
    const productoNombre = document.getElementById('producto_nombre').textContent.trim();
    const precioEl = document.getElementById('producto_precio');
    const productoPrecio = parseInt(precioEl.dataset.valor, 10);
    const productoBase = parseInt(precioEl.dataset.base, 10) || productoPrecio;
    const productoIva = parseInt(precioEl.dataset.iva, 10) || 0;
    const tallaElegida = document.getElementById('producto_talla_sel').selectedOptions[0];
    const tallaId = tallaElegida ? parseInt(tallaElegida.value, 10) || 0 : 0;
    const tallaNombre = tallaElegida ? tallaElegida.dataset.talla : '';
    const productoStock = tallaElegida ? parseInt(tallaElegida.dataset.stock, 10) || 0 : parseInt(document.getElementById('producto_stock').textContent);
    
    if (!productoNombre || !productoPrecio || isNaN(productoStock)) {
        alert('Error: Datos del producto incompletos. Por favor, seleccione el producto nuevamente.');
        return;
    }
    
    if (cantidad > productoStock) {
        alert('No hay suficiente stock. Stock disponible: ' + productoStock);
        document.getElementById('cantidad_producto').value = productoStock;
        return;
    }
    
    // Obtener una COPIA del carrito actual (no referencia) - CRÍTICO para producción
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        // Crear una copia profunda del carrito usando JSON para evitar referencias
        try {
            carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
        } catch (e) {
            carritoActual = [];
        }
    }
    
    // Debug en producción (comentar después de verificar)
            
    // Verificar si el producto YA EXISTE en el carrito usando comparación estricta por ID
    const productoExistenteIndex = carritoActual.findIndex(item => {
        if (!item || !item.id) return false;
        return parseInt(item.id) === productoIdNum && (parseInt(item.talla_id, 10) || 0) === tallaId;
    });
    
    if (productoExistenteIndex >= 0) {
        // El producto YA EXISTE en el carrito - solo incrementar cantidad
        const productoExistente = carritoActual[productoExistenteIndex];
        const nuevaCantidad = productoExistente.cantidad + cantidad;
        
        if (nuevaCantidad > productoStock) {
            alert('No hay suficiente stock. Stock disponible: ' + productoStock);
            return;
        }
        
        // Actualizar solo la cantidad del producto existente
        carritoActual[productoExistenteIndex].cantidad = nuevaCantidad;
        carritoActual[productoExistenteIndex].stock = productoStock;
    } else {
        const nuevoItem = {
            id: productoIdNum.toString(),
            talla_id: tallaId,
            talla: tallaNombre,
            nombre: productoNombre + (tallaNombre ? ' · talla ' + tallaNombre : ''),
            precio: productoPrecio,
            base: productoBase,
            iva: productoIva,
            cantidad: cantidad,
            stock: productoStock
        };
        carritoActual.push(nuevoItem);
    }
    
    // Actualizar el carrito en ventasAbiertas - CREAR COPIA, NO REFERENCIA
    if (ventaActivaId && ventasAbiertas[ventaActivaId]) {
        // Guardar copia profunda en ventasAbiertas
        ventasAbiertas[ventaActivaId].carrito = JSON.parse(JSON.stringify(carritoActual));
        // Sincronizar también la variable global con copia
        carrito = JSON.parse(JSON.stringify(carritoActual));
        
        // Debug en producción
                    }
    
    // Actualizar la visualización del carrito
    actualizarCarrito();
    
    // LIMPIAR TODOS los campos después de agregar exitosamente
    // Usar setTimeout para asegurar que la limpieza ocurra después de que se complete la actualización
    setTimeout(() => {
        document.getElementById('codigo_barras_input').value = '';
        document.getElementById('buscar_nombre').value = '';
        document.getElementById('resultados_busqueda').innerHTML = '';
        
        // Limpiar el campo producto_id CRÍTICO para evitar que se reutilice
        if (productoIdInput) {
            productoIdInput.value = '';
            // Verificar que se limpió correctamente
            if (productoIdInput.value !== '') {
                productoIdInput.value = ''; // Forzar limpieza
            }
        }
        
        // Limpiar también los campos de visualización del producto
        const productoNombreEl = document.getElementById('producto_nombre');
        const productoColorEl = document.getElementById('producto_color');
        const productoTallaEl = document.getElementById('producto_talla');
        const productoPrecioEl = document.getElementById('producto_precio');
        const productoStockEl = document.getElementById('producto_stock');
        const cantidadProductoEl = document.getElementById('cantidad_producto');
        
        if (productoNombreEl) productoNombreEl.textContent = '';
        if (productoColorEl) productoColorEl.textContent = '';
        if (productoTallaEl) productoTallaEl.textContent = '';
        if (productoPrecioEl) productoPrecioEl.textContent = '';
        if (productoStockEl) productoStockEl.textContent = '';
        if (cantidadProductoEl) cantidadProductoEl.value = '1';
        
        // Ocultar la tarjeta de información del producto
        const productoInfoEl = document.getElementById('producto_info');
        if (productoInfoEl) {
            productoInfoEl.classList.add('card-producto-oculto');
        }
        
        // Enfocar en el campo de búsqueda para siguiente producto
        const codigoBarrasInput = document.getElementById('codigo_barras_input');
        if (codigoBarrasInput) {
            codigoBarrasInput.focus();
        }
    }, 100); // Pequeño delay para asegurar que la actualización del carrito se complete primero
}

function actualizarCarrito() {
    const tbody = document.getElementById('carrito_tbody');
    
    // Obtener el carrito actualizado desde ventasAbiertas - CREAR COPIA, NO REFERENCIA
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        // Crear copia profunda para evitar problemas de referencia
        try {
            carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
        } catch (e) {
            carritoActual = [];
        }
    }
    
    // Sincronizar la variable global carrito con copia
    carrito = JSON.parse(JSON.stringify(carritoActual));
    
    // Limpiar el tbody completamente antes de renderizar
    tbody.innerHTML = '';
    
    if (!carritoActual || carritoActual.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="4" class="pos-vacio">Escanea o toca una prenda.</td>';
        tbody.appendChild(row);
        const botonCobro = document.getElementById('btn_procesar_venta');
        if (botonCobro) botonCobro.disabled = true;
    } else {
        // Crear una fila para cada ítem del carrito
        carritoActual.forEach((item, index) => {
            // Validar que el item tenga los datos necesarios
            if (!item || !item.id || !item.nombre) {
                return;
            }
            
            const row = document.createElement('tr');
            const subtotal = (parseInt(item.precio, 10) || baseDeItem(item)) * parseInt(item.cantidad || 0);
            
            // Escapar el nombre del producto para evitar problemas con caracteres especiales
            const nombreEscapado = (item.nombre || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            
            row.innerHTML = `
                <td class="pos-linea-nombre">${nombreEscapado}</td>
                <td class="pos-linea-cant">
                    <button type="button" class="pos-paso" onclick="cambiarCantidad(${index}, -1)" aria-label="Menos">−</button>
                    <span>${parseInt(item.cantidad || 0)}</span>
                    <button type="button" class="pos-paso" onclick="cambiarCantidad(${index}, 1)" aria-label="Más">+</button>
                </td>
                <td class="pos-linea-sub">$${formatearNumero(subtotal)}</td>
                <td><button type="button" class="pos-paso pos-paso-quitar" onclick="eliminarDelCarrito(${index})" title="Quitar"><i class="bi bi-trash"></i></button></td>
            `;
            tbody.appendChild(row);
        });
        const botonCobro = document.getElementById('btn_procesar_venta');
        if (botonCobro) botonCobro.disabled = false;
    }

    const cuenta = document.getElementById('pos_cuenta');
    if (cuenta) {
        const piezas = carritoActual.reduce(function (suma, item) {
            return suma + (parseInt(item.cantidad, 10) || 0);
        }, 0);
        cuenta.textContent = piezas === 1 ? '1 prenda' : piezas + ' prendas';
    }
    
    actualizarTotal();
}

function cambiarCantidad(index, delta) {
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
    }
    const item = carritoActual[index];
    if (!item) return;
    const nueva = (parseInt(item.cantidad, 10) || 0) + delta;
    if (nueva <= 0) {
        eliminarDelCarrito(index);
        return;
    }
    const tope = parseInt(item.stock, 10);
    if (!isNaN(tope) && tope > 0 && nueva > tope) {
        alert('No hay suficiente stock. Stock disponible: ' + tope);
        return;
    }
    item.cantidad = nueva;
    ventasAbiertas[ventaActivaId].carrito = JSON.parse(JSON.stringify(carritoActual));
    carrito = JSON.parse(JSON.stringify(carritoActual));
    actualizarCarrito();
}

function eliminarDelCarrito(index) {
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
    }
    const item = carritoActual[index];
    if (!item || typeof pedirDobleConfirmacion !== 'function') {
        return;
    }
    pedirDobleConfirmacion({
        titulo: 'Quitar producto',
        detalle: 'Se quita este producto de la venta.',
        codigo: item.nombre || '',
        alConfirmar: function () {
            quitarDelCarrito(index);
        }
    });
}

function quitarDelCarrito(index) {
    // Obtener copia del carrito actual
    let carritoActual = [];
    if (ventaActivaId && ventasAbiertas[ventaActivaId] && ventasAbiertas[ventaActivaId].carrito) {
        carritoActual = JSON.parse(JSON.stringify(ventasAbiertas[ventaActivaId].carrito));
    }
    
    if (index >= 0 && index < carritoActual.length) {
        carritoActual.splice(index, 1);
        
        // Guardar copia en ventasAbiertas
        if (ventaActivaId && ventasAbiertas[ventaActivaId]) {
            ventasAbiertas[ventaActivaId].carrito = JSON.parse(JSON.stringify(carritoActual));
            carrito = JSON.parse(JSON.stringify(carritoActual));
        }
        
        actualizarCarrito();
    }
}

function baseDeItem(item) {
    if (item && item.base != null && item.base !== '') {
        return Math.round(parseFloat(item.base) || 0);
    }
    return Math.round(parseFloat(item && item.precio) || 0);
}

