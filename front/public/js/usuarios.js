/**
 * JavaScript para el módulo de Usuarios
 */

document.addEventListener('DOMContentLoaded', function() {
    const BASE_URL_USUARIOS = window.BASE_URL || '';
    
    // Editar usuario
    document.addEventListener('click', function(e) {
        const btnEditar = e.target.closest('.btnEditarUsuario');
        if (btnEditar) {
            e.preventDefault();
            e.stopPropagation();
            
            const usuarioId = btnEditar.getAttribute('data-id');
            if (!usuarioId) {
                console.error('No se encontró el ID del usuario');
                return;
            }
            
            fetch(BASE_URL_USUARIOS + 'index.php?action=usuarios&method=getUsuario&id=' + usuarioId)
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
                    if (data.success) {
                        const u = data.usuario;
                        
                        document.getElementById('usuario_id').value = u.id;
                        document.getElementById('nombre_edit').value = u.nombre || '';
                        document.getElementById('email_edit').value = u.email || '';
                        document.getElementById('rol_edit').value = u.rol || 'cajero';
                        document.getElementById('activo_edit').checked = u.activo == 1;
                        
                        const modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
                        modal.show();
                    } else {
                        alert('Error al cargar usuario: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el usuario:\n\n' + error.message);
                });
        }
    });
    
    // Limpiar formulario al cerrar modal nuevo usuario
    const modalNuevo = document.getElementById('modalNuevoUsuario');
    if (modalNuevo) {
        modalNuevo.addEventListener('hidden.bs.modal', function() {
            document.getElementById('formNuevoUsuario').reset();
        });
    }
    
    // Limpiar formulario al cerrar modal editar usuario
    const modalEditar = document.getElementById('modalEditarUsuario');
    if (modalEditar) {
        modalEditar.addEventListener('hidden.bs.modal', function() {
            document.getElementById('formEditarUsuario').reset();
            document.getElementById('password_edit').value = '';
        });
    }
});

