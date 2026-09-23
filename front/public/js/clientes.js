/**
 * JavaScript para el módulo de Clientes
 * Maneja la creación, edición y búsqueda de clientes
 */

let BASE_URL_CLIENTES = '';

document.addEventListener('DOMContentLoaded', function() {
    BASE_URL_CLIENTES = window.BASE_URL || '';
    
    const form = document.getElementById('formNuevoCliente');
    const btnGuardar = document.getElementById('btnGuardarCliente');
    const btnGuardarTexto = document.getElementById('btnGuardarTexto');
    const modal = new bootstrap.Modal(document.getElementById('modalNuevoCliente'));
    const modalTitle = document.getElementById('modalNuevoClienteLabel');
    let esEdicion = false;
    
    // Función para editar cliente
    window.editarCliente = function(clienteId) {
        esEdicion = true;
        modalTitle.innerHTML = '<i class="bi bi-pencil"></i> Editar Cliente';
        btnGuardarTexto.textContent = 'Actualizar Cliente';
        
        // Cargar datos del cliente
        fetch(`${BASE_URL_CLIENTES || window.BASE_URL || ''}index.php?action=clientes&method=getCliente&id=${clienteId}`)
            .then(response => {
                // Primero obtener el texto de la respuesta para verificar si es HTML
                return response.text().then(text => {
                    // Verificar si la respuesta es HTML (error de PHP)
                    if (text.trim().startsWith('<') || text.includes('<br') || text.includes('<b>')) {
                        console.error('Respuesta HTML recibida:', text);
                        throw new Error('Error del servidor: La respuesta contiene HTML en lugar de JSON. Esto generalmente indica un error de conexión a la base de datos o un error de PHP. Verifique la configuración de la base de datos.');
                    }
                    
                    // Intentar parsear como JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Error al parsear JSON:', text);
                        throw new Error('Error: La respuesta del servidor no es JSON válido. ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                if (data.success && data.cliente) {
                    const c = data.cliente;
                    document.getElementById('cliente_id').value = c.id;
                    document.getElementById('nombre_completo').value = c.nombre_completo || '';
                    document.getElementById('cedula_nit').value = c.cedula_nit || '';
                    document.getElementById('telefono').value = c.telefono || '';
                    document.getElementById('email').value = c.email || '';
                    document.getElementById('direccion').value = c.direccion || '';
                    document.getElementById('fecha_nacimiento').value = c.fecha_nacimiento || '';
                    document.getElementById('observaciones').value = c.observaciones || '';
                    modal.show();
                } else {
                    alert('Error al cargar los datos del cliente: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los datos del cliente:\n\n' + error.message);
            });
    };
    
    btnGuardar.addEventListener('click', function() {
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const clienteId = document.getElementById('cliente_id').value;
        const formData = new FormData();
        
        if (esEdicion && clienteId) {
            // Actualizar cliente existente
            formData.append('id', clienteId);
            formData.append('nombre_completo', document.getElementById('nombre_completo').value);
            formData.append('cedula_nit', document.getElementById('cedula_nit').value);
            formData.append('telefono', document.getElementById('telefono').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('direccion', document.getElementById('direccion').value);
            formData.append('fecha_nacimiento', document.getElementById('fecha_nacimiento').value);
            formData.append('observaciones', document.getElementById('observaciones').value);
            
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Actualizando...';
            
            fetch((BASE_URL_CLIENTES || window.BASE_URL || '') + 'index.php?action=clientes&method=update', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(data => {
                if (data) {
                    // Si hay respuesta, intentar parsear como JSON
                    try {
                        const json = JSON.parse(data);
                        if (json.success) {
                            modal.hide();
                            window.location.reload();
                        } else {
                            alert('Error: ' + (json.error || 'No se pudo actualizar el cliente'));
                            btnGuardar.disabled = false;
                            btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTexto">Actualizar Cliente</span>';
                        }
                    } catch (e) {
                        // Si no es JSON, recargar la página
                        window.location.reload();
                    }
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el cliente. Por favor, intente nuevamente.');
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTexto">Actualizar Cliente</span>';
            });
        } else {
            // Crear nuevo cliente
            formData.append('nombre', document.getElementById('nombre_completo').value);
            formData.append('cedula_nit', document.getElementById('cedula_nit').value);
            formData.append('telefono', document.getElementById('telefono').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('direccion', document.getElementById('direccion').value);
            formData.append('fecha_nacimiento', document.getElementById('fecha_nacimiento').value);
            formData.append('observaciones', document.getElementById('observaciones').value);
            
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Guardando...';
            
            fetch((BASE_URL_CLIENTES || window.BASE_URL || '') + 'index.php?action=clientes&method=crearRapido', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Primero obtener el texto de la respuesta para verificar si es HTML
                return response.text().then(text => {
                    // Verificar si la respuesta es HTML (error de PHP)
                    if (text.trim().startsWith('<') || text.includes('<br') || text.includes('<b>')) {
                        console.error('Respuesta HTML recibida:', text);
                        throw new Error('Error del servidor: La respuesta contiene HTML en lugar de JSON. Esto generalmente indica un error de conexión a la base de datos o un error de PHP.');
                    }
                    
                    // Intentar parsear como JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Error al parsear JSON:', text);
                        throw new Error('Error: La respuesta del servidor no es JSON válido. ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    modal.hide();
                    form.reset();
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo crear el cliente'));
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTexto">Guardar Cliente</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear el cliente:\n\n' + error.message);
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTexto">Guardar Cliente</span>';
            });
        }
    });
    
    // Limpiar formulario al cerrar el modal
    document.getElementById('modalNuevoCliente').addEventListener('hidden.bs.modal', function() {
        form.reset();
        document.getElementById('cliente_id').value = '';
        esEdicion = false;
        modalTitle.innerHTML = '<i class="bi bi-plus-circle"></i> Nuevo Cliente';
        btnGuardarTexto.textContent = 'Guardar Cliente';
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTexto">Guardar Cliente</span>';
    });
    
    // Búsqueda en tiempo real
    const inputBusqueda = document.getElementById('inputBusqueda');
    const btnLimpiar = document.getElementById('btnLimpiarBusqueda');
    let timeoutBusqueda;
    
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', function() {
            clearTimeout(timeoutBusqueda);
            const termino = this.value.trim();
            
            if (termino.length === 0) {
                // Si está vacío, mostrar todos los clientes
                timeoutBusqueda = setTimeout(() => {
                    buscarClientes('');
                }, 300);
            } else if (termino.length >= 2) {
                // Buscar después de 500ms de inactividad
                timeoutBusqueda = setTimeout(() => {
                    buscarClientes(termino);
                }, 500);
            }
        });
    }
    
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            inputBusqueda.value = '';
            buscarClientes('');
        });
    }
    
    function buscarClientes(termino) {
        const baseUrl = BASE_URL_CLIENTES || window.BASE_URL || '';
        const url = termino 
            ? `${baseUrl}index.php?action=clientes&method=buscar&termino=${encodeURIComponent(termino)}`
            : `${baseUrl}index.php?action=clientes&method=buscar&termino=`;
        
        fetch(url)
            .then(r => r.text())
            .then(txt => {
                let data;
                try {
                    data = JSON.parse(txt);
                } catch (e) {
                    console.error('Error al parsear JSON:', e);
                    return;
                }
                
                const tbody = document.getElementById('tablaClientes');
                if (!tbody) return;
                
                let html = '';
                
                if (data.success && data.clientes && data.clientes.length > 0) {
                    data.clientes.forEach(cliente => {
                        const baseUrlEscaped = baseUrl.replace(/"/g, '&quot;');
                        html += `
                            <tr>
                                <td>${escapeHtml(cliente.nombre_completo || '')}</td>
                                <td><code>${escapeHtml(cliente.cedula_nit || '')}</code></td>
                                <td>${escapeHtml(cliente.telefono || 'N/A')}</td>
                                <td>${escapeHtml(cliente.email || 'N/A')}</td>
                                <td><span class="badge bg-info">${cliente.total_compras || 0}</span></td>
                                <td><strong>$${formatearMoneda(cliente.total_gastado || 0)}</strong></td>
                                <td>
                                    <a href="${baseUrlEscaped}index.php?action=clientes&method=historial&id=${cliente.id}" 
                                       class="btn btn-sm btn-outline-info btn-icono" title="Ver Historial">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-icono" 
                                            title="Editar" 
                                            onclick="editarCliente(${cliente.id})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    ${Number(cliente.id) !== 1 ? `
                                        <form method="POST" action="${baseUrlEscaped}index.php?action=clientes&method=delete" class="d-inline form-doble-eliminar" data-titulo="Eliminar cliente" data-detalle="Se borra el cliente y no se puede recuperar." data-codigo="${escapeHtml(cliente.cedula_nit || cliente.nombre_completo || '')}">
                                            <input type="hidden" name="csrf_token" value="${window.CSRF_TOKEN || ''}">
                                            <input type="hidden" name="id" value="${Number(cliente.id)}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icono" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    ` : ''}
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="7" class="text-center text-muted">No se encontraron clientes</td></tr>';
                }
                
                tbody.innerHTML = html;
            })
            .catch(err => {
                console.error('Error al buscar clientes:', err);
            });
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

