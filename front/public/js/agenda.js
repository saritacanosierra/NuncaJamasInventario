/**
 * JavaScript para el módulo de Agenda
 * Maneja la creación, edición, eliminación y visualización de tareas
 */

let BASE_URL_AGENDA = '';

document.addEventListener('DOMContentLoaded', function() {
    // Obtener BASE_URL con múltiples fallbacks
    BASE_URL_AGENDA = window.BASE_URL || '';
    
    // Si no está definido, intentar detectarlo automáticamente
    if (!BASE_URL_AGENDA) {
        // Método 1: Obtener desde la ubicación actual
        const currentPath = window.location.pathname;
        const pathParts = currentPath.split('/');
        // Remover el último elemento (normalmente 'index.php' o vacío)
        pathParts.pop();
        const basePath = pathParts.join('/');
        BASE_URL_AGENDA = basePath ? basePath + '/' : '/';
        
        // Método 2: Si aún está vacío, usar raíz
        if (!BASE_URL_AGENDA || BASE_URL_AGENDA === '/') {
            BASE_URL_AGENDA = window.location.protocol + '//' + window.location.host + '/';
        }
    }
    
    // Validar que BASE_URL termine con '/'
    if (BASE_URL_AGENDA && !BASE_URL_AGENDA.endsWith('/')) {
        BASE_URL_AGENDA += '/';
    }
    
    // Log para debugging (solo en desarrollo)
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('BASE_URL_AGENDA:', BASE_URL_AGENDA);
    }
    
    const form = document.getElementById('formNuevaTarea');
    const btnGuardar = document.getElementById('btnGuardarTarea');
    const btnGuardarTexto = document.getElementById('btnGuardarTareaTexto');
    const modal = new bootstrap.Modal(document.getElementById('modalNuevaTarea'));
    const modalTitle = document.getElementById('modalNuevaTareaLabel');
    let esEdicion = false;
    
    // Función para editar tarea
    window.editarTarea = function(tareaId) {
        esEdicion = true;
        modalTitle.innerHTML = '<i class="bi bi-pencil"></i> Editar Tarea';
        btnGuardarTexto.textContent = 'Actualizar Tarea';
        
        const baseUrl = BASE_URL_AGENDA || window.BASE_URL || '';
        fetch(`${baseUrl}index.php?action=agenda&method=getTarea&id=${tareaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tarea) {
                    const t = data.tarea;
                    document.getElementById('tarea_id').value = t.id;
                    document.getElementById('titulo').value = t.titulo || '';
                    document.getElementById('descripcion').value = t.descripcion || '';
                    document.getElementById('fecha').value = t.fecha || '';
                    document.getElementById('hora').value = t.hora || '';
                    document.getElementById('prioridad').value = t.prioridad || 'media';
                    document.getElementById('categoria').value = t.categoria || '';
                    modal.show();
                } else {
                    alert('Error al cargar los datos de la tarea');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los datos de la tarea');
            });
    };
    
    // Función para seleccionar fecha en el calendario
    window.seleccionarFecha = function(fecha) {
        const baseUrl = BASE_URL_AGENDA || window.BASE_URL || '';
        window.location.href = `${baseUrl}index.php?action=agenda&fecha=${fecha}&mes=${fecha.substring(0, 7)}`;
    };
    
    // Guardar tarea
    if (btnGuardar) {
        btnGuardar.addEventListener('click', function() {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const tareaId = document.getElementById('tarea_id').value;
            const formData = new FormData();
            
            if (esEdicion && tareaId) {
                formData.append('id', tareaId);
                formData.append('titulo', document.getElementById('titulo').value);
                formData.append('descripcion', document.getElementById('descripcion').value);
                formData.append('fecha', document.getElementById('fecha').value);
                formData.append('hora', document.getElementById('hora').value);
                formData.append('prioridad', document.getElementById('prioridad').value);
                formData.append('categoria', document.getElementById('categoria').value);
                formData.append('estado', 'pendiente');
                
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Actualizando...';
                
                const baseUrlUpdate = BASE_URL_AGENDA || window.BASE_URL || '';
                fetch(`${baseUrlUpdate}index.php?action=agenda&method=update`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modal.hide();
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'No se pudo actualizar la tarea'));
                        btnGuardar.disabled = false;
                        btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTareaTexto">Actualizar Tarea</span>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar la tarea');
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTareaTexto">Actualizar Tarea</span>';
                });
            } else {
                formData.append('titulo', document.getElementById('titulo').value);
                formData.append('descripcion', document.getElementById('descripcion').value);
                formData.append('fecha', document.getElementById('fecha').value);
                formData.append('hora', document.getElementById('hora').value);
                formData.append('prioridad', document.getElementById('prioridad').value);
                formData.append('categoria', document.getElementById('categoria').value);
                
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
                
                const baseUrl = BASE_URL_AGENDA || window.BASE_URL || '';
                fetch(`${baseUrl}index.php?action=agenda&method=store`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Primero obtener el texto de la respuesta para verificar si es HTML
                    return response.text().then(text => {
                        // Verificar si la respuesta es HTML (error de PHP)
                        if (text.trim().startsWith('<') || text.includes('<br') || text.includes('<b>') || text.includes('<!DOCTYPE')) {
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
                    if (data.success) {
                        modal.hide();
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'No se pudo crear la tarea'));
                        btnGuardar.disabled = false;
                        btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTareaTexto">Guardar Tarea</span>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al crear la tarea:\n\n' + error.message);
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTareaTexto">Guardar Tarea</span>';
                });
            }
        });
    }
    
    // Limpiar formulario al cerrar el modal
    document.getElementById('modalNuevaTarea').addEventListener('hidden.bs.modal', function() {
        form.reset();
        document.getElementById('tarea_id').value = '';
        document.getElementById('fecha').value = window.FECHA_SELECCIONADA || '';
        esEdicion = false;
        modalTitle.innerHTML = '<i class="bi bi-plus-circle"></i> Nueva Tarea';
        btnGuardarTexto.textContent = 'Guardar Tarea';
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarTareaTexto">Guardar Tarea</span>';
    });
    
    // Función para toggle de tarea (completar/descompletar)
    window.toggleTarea = function(tareaId, completada) {
        if (completada) {
            const formData = new FormData();
            formData.append('id', tareaId);
            
            const baseUrl = BASE_URL_AGENDA || window.BASE_URL || '';
            fetch(`${baseUrl}index.php?action=agenda&method=completar`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error al completar la tarea');
                    // Revertir checkbox
                    const checkbox = document.querySelector(`input[onchange*="${tareaId}"]`);
                    if (checkbox) checkbox.checked = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al completar la tarea');
                const checkbox = document.querySelector(`input[onchange*="${tareaId}"]`);
                if (checkbox) checkbox.checked = false;
            });
        } else {
            // Si se desmarca, actualizar estado a pendiente
            editarTarea(tareaId);
            // Cambiar estado a pendiente y guardar
            setTimeout(() => {
                const formData = new FormData();
                formData.append('id', tareaId);
                formData.append('titulo', document.getElementById('titulo').value);
                formData.append('descripcion', document.getElementById('descripcion').value);
                formData.append('fecha', document.getElementById('fecha').value);
                formData.append('hora', document.getElementById('hora').value);
                formData.append('prioridad', document.getElementById('prioridad').value);
                formData.append('categoria', document.getElementById('categoria').value);
                formData.append('estado', 'pendiente');
                
                const baseUrlUpdate2 = BASE_URL_AGENDA || window.BASE_URL || '';
                fetch(`${baseUrlUpdate2}index.php?action=agenda&method=update`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    }
                });
            }, 100);
        }
    };
    
    // Función para eliminar tarea
    window.eliminarTarea = function(tareaId, boton) {
        const codigo = boton && boton.getAttribute ? (boton.getAttribute('data-codigo') || '') : '';
        if (!codigo || typeof pedirDobleConfirmacion !== 'function') {
            return;
        }
        pedirDobleConfirmacion({
            titulo: 'Eliminar tarea',
            detalle: 'Se borra la tarea y no se puede recuperar.',
            codigo: codigo,
            alConfirmar: function (escrito) {
        const formData = new FormData();
        formData.append('id', tareaId);
        formData.append('codigo_confirmacion', escrito);
        if (window.CSRF_TOKEN) {
            formData.append('csrf_token', window.CSRF_TOKEN);
        }
        
        const baseUrl = BASE_URL_AGENDA || window.BASE_URL || '';
        fetch(`${baseUrl}index.php?action=agenda&method=delete`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo eliminar la tarea'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la tarea');
        });
            }
        });
    };
});

