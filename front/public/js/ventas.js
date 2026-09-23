/**
 * JavaScript para el módulo de Ventas
 * Maneja múltiples ventas simultáneas, búsqueda de productos, clientes y procesamiento de ventas
 */

// Estructura para manejar múltiples ventas en paralelo
let ventasAbiertas = {};
let ventaActivaId = null;
let contadorVentas = 0;
let carrito = []; // seguirá representando el carrito de la venta activa
let BASE_URL_VENTAS = '';

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Obtener BASE_URL de la variable global definida en la vista
    BASE_URL_VENTAS = window.BASE_URL || '';
    
    // Crear la primera venta por defecto
    crearNuevaVenta();

    // Eventos de búsqueda de producto
    document.getElementById('btn_buscar_codigo').addEventListener('click', buscarPorCodigo);
    document.getElementById('codigo_barras_input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') buscarPorCodigo();
    });

    // Buscar por nombre
    let timeoutBusqueda;
    document.getElementById('buscar_nombre').addEventListener('input', function() {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(buscarPorNombre, 500);
    });

    // Buscar cliente existente
    let timeoutCliente;
    document.getElementById('buscar_cliente').addEventListener('input', function() {
        clearTimeout(timeoutCliente);
        timeoutCliente = setTimeout(buscarCliente, 500);
    });

    // Agregar al carrito
    document.getElementById('btn_agregar_carrito').addEventListener('click', agregarAlCarrito);

    // Procesar venta
    document.getElementById('btn_procesar_venta').addEventListener('click', procesarVenta);

    // Actualizar total cuando cambia el descuento
    document.getElementById('descuento_venta').addEventListener('input', actualizarTotal);

    // Nueva venta
    document.getElementById('btnNuevaVenta').addEventListener('click', crearNuevaVenta);

    // El botón "Nuevo cliente rápido" ya existe en el HTML y usa data-bs-toggle="modal"
    // Solo necesitamos resetear el formulario cuando se abre el modal
    const modalNuevoCliente = document.getElementById('modalNuevoClienteRapido');
    if (modalNuevoCliente) {
        modalNuevoCliente.addEventListener('show.bs.modal', function() {
            document.getElementById('formNuevoClienteRapido').reset();
        });
    }

    // Guardar cliente rápido
    document.getElementById('btnGuardarClienteRapido').addEventListener('click', guardarClienteRapido);
    
    // Mostrar/ocultar campo de observaciones según checkbox de domicilio
    // El estado de pago siempre está visible
    document.getElementById('con_domicilio').addEventListener('change', function() {
        const divObservaciones = document.getElementById('div_observaciones_domicilio');
        if (this.checked) {
            divObservaciones.style.display = 'block';
        } else {
            divObservaciones.style.display = 'none';
            document.getElementById('observaciones_domicilio').value = '';
        }
    });
});

// ==== Gestión de ventas múltiples ====
function crearNuevaVenta() {
    contadorVentas++;
    const id = 'venta_' + contadorVentas;

    ventasAbiertas[id] = {
        clienteId: 1,
        clienteNombre: 'Cliente General',
        carrito: [],
        descuento: 0,
        metodoPago: 'Efectivo',
        conDomicilio: false,
        observacionesDomicilio: '',
        pagoContraEntrega: false
    };

    agregarTabVenta(id);
    cambiarVentaActiva(id);
}

function agregarTabVenta(id) {
    const tabs = document.getElementById('ventasTabs');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-outline-secondary venta-tab';
    btn.dataset.ventaId = id;
    btn.textContent = 'Venta ' + id.split('_')[1];
    btn.addEventListener('click', function() {
        cambiarVentaActiva(id);
    });
    tabs.appendChild(btn);
}

function cambiarVentaActiva(nuevaId) {
    if (ventaActivaId && ventasAbiertas[ventaActivaId]) {
        guardarEstadoVenta(ventaActivaId);
    }

    ventaActivaId = nuevaId;
    
    // Asegurar que la venta existe
    if (!ventasAbiertas[ventaActivaId]) {
        console.error('Venta activa no existe:', ventaActivaId);
        return;
    }
    
    // Sincronizar carrito con la venta activa - CREAR COPIA, NO REFERENCIA
    const carritoVenta = ventasAbiertas[ventaActivaId].carrito || [];
    carrito = JSON.parse(JSON.stringify(carritoVenta)); // Crear copia profunda
    ventasAbiertas[ventaActivaId].carrito = JSON.parse(JSON.stringify(carrito)); // Asegurar copia también en ventasAbiertas
    
    refrescarUiDesdeVenta(ventaActivaId);

    // Marcar pestañas
    document.querySelectorAll('.venta-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.ventaId === ventaActivaId);
    });
}

function guardarEstadoVenta(id) {
    if (!ventasAbiertas[id]) return;
    const venta = ventasAbiertas[id];
    // Guardar COPIA del carrito, no referencia
    venta.carrito = JSON.parse(JSON.stringify(carrito || []));
    venta.descuento = parseFloat(document.getElementById('descuento_venta').value) || 0;
    venta.metodoPago = document.getElementById('metodo_pago').value;
    venta.clienteId = parseInt(document.getElementById('cliente_id').value) || 1;
    venta.clienteNombre = document.querySelector('#cliente_seleccionado strong')?.nextSibling?.textContent?.trim() || 'Cliente General';
    venta.conDomicilio = document.getElementById('con_domicilio').checked;
    venta.observacionesDomicilio = document.getElementById('observaciones_domicilio').value.trim();
    const estadoPago = document.querySelector('input[name="estado_pago"]:checked')?.value || 'pagado';
    venta.pagoContraEntrega = estadoPago === 'contra_entrega';
}

function refrescarUiDesdeVenta(id) {
    const venta = ventasAbiertas[id];
    if (!venta) return;

    // Cliente
    document.getElementById('cliente_id').value = venta.clienteId;
    document.getElementById('cliente_seleccionado').innerHTML =
        `<p class="mb-0"><strong>Cliente:</strong> ${venta.clienteNombre}</p>`;

    // Método de pago y descuento
    document.getElementById('metodo_pago').value = venta.metodoPago || 'Efectivo';
    document.getElementById('descuento_venta').value = venta.descuento || 0;
    
    // Domicilio
    document.getElementById('con_domicilio').checked = venta.conDomicilio || false;
    document.getElementById('observaciones_domicilio').value = venta.observacionesDomicilio || '';
    document.getElementById('div_observaciones_domicilio').style.display = 
        (venta.conDomicilio) ? 'block' : 'none';
    // Estado de pago siempre visible
    if (venta.pagoContraEntrega) {
        document.getElementById('pago_contra_entrega').checked = true;
    } else {
        document.getElementById('pago_pagado').checked = true;
    }

    // Carrito
    actualizarCarrito();
}

function buscarPorCodigo() {
    const codigo = document.getElementById('codigo_barras_input').value.trim();
    if (!codigo) return;
    
    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=productos&method=buscarPorCodigo&codigo=${codigo}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarProducto(data.producto);
            } else {
                alert('Producto no encontrado');
            }
        });
}

function buscarPorNombre() {
    const termino = document.getElementById('buscar_nombre').value.trim();
    if (!termino) {
        document.getElementById('resultados_busqueda').innerHTML = '';
        return;
    }
    
    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=productos&method=buscarPorNombre&termino=${encodeURIComponent(termino)}`)
        .then(r => r.text())
        .then(txt => {
            console.log('Respuesta buscarPorNombre (texto crudo):', txt);
            let data;
            try {
                data = JSON.parse(txt);
            } catch (e) {
                console.error('No se pudo parsear JSON en buscarPorNombre:', e);
                document.getElementById('resultados_busqueda').innerHTML =
                    '<p class="text-danger">Respuesta no válida del servidor. Revisa la consola.</p>';
                return;
            }

            let html = '';
            if (data.success && data.productos && data.productos.length > 0) {
                html = '<div class="list-group">';
                data.productos.forEach(p => {
                    html += `<a href="#" class="list-group-item list-group-item-action" onclick="seleccionarProductoDesdeLista('${p.codigo_barras}'); return false;">
                        <strong>${p.nombre}</strong> - ${p.color} - ${p.talla} - Stock: ${p.stock}
                    </a>`;
                });
                html += '</div>';
            } else {
                html = '<p class="text-muted">No se encontraron productos</p>';
            }
            document.getElementById('resultados_busqueda').innerHTML = html;
        })
        .catch(err => {
            console.error('Error al buscar por nombre (fetch):', err);
            document.getElementById('resultados_busqueda').innerHTML =
                '<p class="text-danger">Error al buscar productos. Revisa la consola.</p>';
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
    document.getElementById('producto_talla').textContent = producto.talla || '';
    document.getElementById('producto_precio').textContent = formatearNumero(producto.precio_venta || 0);
    
    const stockBadge = document.getElementById('producto_stock');
    stockBadge.textContent = producto.stock || 0;
    stockBadge.className = 'badge ' + (producto.stock <= (producto.stock_minimo || 0) ? 'bg-warning' : 'bg-success');
    
    const img = document.getElementById('producto_foto');
    if (producto.foto) {
        img.src = (BASE_URL_VENTAS || window.BASE_URL || '') + 'front/public/uploads/productos/' + producto.foto;
        img.classList.remove('d-none');
    } else {
        img.classList.add('d-none');
    }
    
    const cardInfo = document.getElementById('producto_info');
    cardInfo.classList.remove('card-producto-oculto');
    const cantidadInput = document.getElementById('cantidad_producto');
    cantidadInput.max = producto.stock || 0;
    cantidadInput.value = 1;
    
    // Debug en producción
    console.log('Producto mostrado - ID:', producto.id, 'Nombre:', producto.nombre);
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
        console.error('Producto ID inválido:', productoIdRaw);
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
    const productoPrecio = parseFloat(document.getElementById('producto_precio').textContent);
    const productoStock = parseInt(document.getElementById('producto_stock').textContent);
    
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
            console.error('Error al copiar carrito:', e);
            carritoActual = [];
        }
    }
    
    // Debug en producción (comentar después de verificar)
    console.log('Producto a agregar - ID:', productoIdNum, 'Nombre:', productoNombre);
    console.log('Carrito actual antes de agregar:', JSON.stringify(carritoActual));
    
    // Verificar si el producto YA EXISTE en el carrito usando comparación estricta por ID
    const productoExistenteIndex = carritoActual.findIndex(item => {
        if (!item || !item.id) return false;
        const itemId = parseInt(item.id);
        const comparacion = itemId === productoIdNum;
        if (comparacion) {
            console.log('Producto encontrado en carrito - ID:', itemId, 'Nombre:', item.nombre);
        }
        return comparacion;
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
    } else {
        // El producto NO EXISTE en el carrito - agregar como nuevo ítem
        const nuevoItem = {
            id: productoIdNum.toString(),
            nombre: productoNombre,
            precio: productoPrecio,
            cantidad: cantidad
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
        console.log('Carrito después de agregar:', JSON.stringify(carritoActual));
        console.log('Total items en carrito:', carritoActual.length);
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
                console.warn('El campo producto_id no se limpió correctamente');
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
            console.error('Error al copiar carrito en actualizarCarrito:', e);
            carritoActual = [];
        }
    }
    
    // Sincronizar la variable global carrito con copia
    carrito = JSON.parse(JSON.stringify(carritoActual));
    
    // Limpiar el tbody completamente antes de renderizar
    tbody.innerHTML = '';
    
    if (!carritoActual || carritoActual.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="5" class="text-center text-muted">El carrito está vacío</td>';
        tbody.appendChild(row);
        document.getElementById('btn_procesar_venta').disabled = true;
    } else {
        // Crear una fila para cada ítem del carrito
        carritoActual.forEach((item, index) => {
            // Validar que el item tenga los datos necesarios
            if (!item || !item.id || !item.nombre) {
                console.warn('Item inválido en carrito:', item);
                return;
            }
            
            const row = document.createElement('tr');
            const subtotal = parseFloat(item.precio || 0) * parseInt(item.cantidad || 0);
            
            // Escapar el nombre del producto para evitar problemas con caracteres especiales
            const nombreEscapado = (item.nombre || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            
            row.innerHTML = `
                <td>${nombreEscapado}</td>
                <td>${parseInt(item.cantidad || 0)}</td>
                <td>$${formatearNumero(item.precio || 0)}</td>
                <td>$${formatearNumero(subtotal)}</td>
                <td><button class="btn btn-sm btn-danger" onclick="eliminarDelCarrito(${index})" title="Eliminar"><i class="bi bi-trash"></i></button></td>
            `;
            tbody.appendChild(row);
        });
        document.getElementById('btn_procesar_venta').disabled = false;
    }
    
    actualizarTotal();
}

function eliminarDelCarrito(index) {
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

function actualizarTotal() {
    const subtotal = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    const descuento = parseFloat(document.getElementById('descuento_venta').value) || 0;
    const total = subtotal - descuento;
    
    document.getElementById('subtotal_carrito').textContent = '$' + formatearNumero(subtotal);
    document.getElementById('descuento_aplicado').textContent = '$' + formatearNumero(descuento);
    document.getElementById('total_carrito').textContent = '$' + formatearNumero(total > 0 ? total : 0);
}

function buscarCliente() {
    const termino = document.getElementById('buscar_cliente').value.trim();
    if (!termino) {
        document.getElementById('resultados_cliente').innerHTML = '';
        return;
    }
    
    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=clientes&method=buscar&termino=${encodeURIComponent(termino)}`)
        .then(r => r.text())
        .then(txt => {
            console.log('Respuesta buscarCliente (texto crudo):', txt);
            let data;
            try {
                data = JSON.parse(txt);
            } catch (e) {
                console.error('No se pudo parsear JSON en buscarCliente:', e);
                document.getElementById('resultados_cliente').innerHTML =
                    '<p class="text-danger">Respuesta no válida del servidor al buscar cliente. Revisa la consola.</p>';
                return;
            }

            let html = '';
            if (data.success && data.clientes && data.clientes.length > 0) {
                html = '<div class="list-group">';
                data.clientes.forEach(c => {
                    const nombre = (c.nombre_completo || '').replace(/'/g, "\\'");
                    html += `<a href="#" class="list-group-item list-group-item-action" onclick="seleccionarCliente(${c.id}, '${nombre}'); return false;">
                        <strong>${c.nombre_completo}</strong> - ${c.cedula_nit}
                    </a>`;
                });
                html += '</div>';
            } else {
                html = '<p class="text-muted">No se encontraron clientes con ese dato. Puedes usar "Cliente General" o crear uno nuevo rápido.</p>';
            }
            document.getElementById('resultados_cliente').innerHTML = html;
        })
        .catch(err => {
            console.error('Error al buscar clientes (fetch):', err);
            document.getElementById('resultados_cliente').innerHTML =
                '<p class="text-danger">Error al buscar clientes. Revisa la consola.</p>';
        });
}

function seleccionarCliente(id, nombre) {
    document.getElementById('cliente_id').value = id;
    document.getElementById('cliente_seleccionado').innerHTML = `<p class="mb-0"><strong>Cliente:</strong> ${nombre}</p>`;
    document.getElementById('resultados_cliente').innerHTML = '';
}

function procesarVenta() {
    if (!ventaActivaId || carrito.length === 0) {
        alert('El carrito está vacío');
        return;
    }
    
    const formData = new FormData();
    formData.append('carrito', JSON.stringify(carrito));
    formData.append('cliente_id', document.getElementById('cliente_id').value);
    formData.append('descuento', document.getElementById('descuento_venta').value);
    formData.append('metodo_pago', document.getElementById('metodo_pago').value);
    formData.append('con_domicilio', document.getElementById('con_domicilio').checked ? '1' : '0');
    formData.append('observaciones_domicilio', document.getElementById('observaciones_domicilio').value.trim());
    
    const btn = document.getElementById('btn_procesar_venta');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
    
    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=ventas&method=procesar`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => {
        if (r.ok) {
            return r.json();
        }
        throw new Error('Error en la respuesta del servidor');
    })
    .then(data => {
        if (data.success) {
            window.location.href = `${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=ventas&method=factura&id=${data.venta_id}`;
        } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Procesar Venta';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la venta. Por favor, intente nuevamente.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Procesar Venta';
    });
}

// Crear cliente rápido vía AJAX
function guardarClienteRapido() {
    const nombre = document.getElementById('cliente_nombre_rapido').value.trim();
    const cedula = document.getElementById('cliente_cedula_rapido').value.trim();
    const telefono = document.getElementById('cliente_telefono_rapido').value.trim();
    const email = document.getElementById('cliente_email_rapido').value.trim();
    const direccion = document.getElementById('cliente_direccion_rapido').value.trim();
    const fechaNac = document.getElementById('cliente_fecha_rapido').value || null;
    const observaciones = document.getElementById('cliente_observaciones_rapido').value.trim();

    if (!nombre || !cedula) {
        alert('El nombre y la cédula/NIT son obligatorios');
        return;
    }

    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('cedula_nit', cedula);
    formData.append('telefono', telefono);
    formData.append('email', email);
    formData.append('direccion', direccion);
    if (fechaNac) {
        formData.append('fecha_nacimiento', fechaNac);
    }
    formData.append('observaciones', observaciones);

    fetch(`${BASE_URL_VENTAS || window.BASE_URL || ''}index.php?action=clientes&method=crearRapido`, {
        method: 'POST',
        body: formData
    })
    .then(r => {
        // Leer como texto primero para depuración
        return r.text().then(text => {
            console.log('Respuesta crearRapido (texto crudo):', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                console.error('Texto recibido:', text);
                throw new Error('La respuesta del servidor no es JSON válido: ' + text.substring(0, 200));
            }
        });
    })
    .then(data => {
        if (!data.success) {
            alert(data.error || 'Error al crear el cliente');
            return;
        }

        const cliente = data.cliente;
        
        // Actualizar el estado de la venta activa
        if (ventaActivaId && ventasAbiertas[ventaActivaId]) {
            ventasAbiertas[ventaActivaId].clienteId = cliente.id;
            ventasAbiertas[ventaActivaId].clienteNombre = cliente.nombre_completo;
        }
        
        // Seleccionar el cliente en la interfaz (igual que cuando se selecciona desde la búsqueda)
        seleccionarCliente(cliente.id, cliente.nombre_completo);
        
        // Limpiar campo de búsqueda de cliente
        document.getElementById('buscar_cliente').value = '';

        // Cerrar modal y limpiar formulario
        const modalEl = document.getElementById('modalNuevoClienteRapido');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        document.getElementById('formNuevoClienteRapido').reset();
        
        // Mostrar mensaje de éxito
        console.log('Cliente creado y seleccionado:', cliente.nombre_completo);
    })
    .catch(err => {
        console.error('Error al crear cliente (fetch):', err);
        alert('Error al crear el cliente: ' + err.message);
    });
}

