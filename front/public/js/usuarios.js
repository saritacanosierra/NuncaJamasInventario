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
                return;
            }
            
            fetch(BASE_URL_USUARIOS + 'index.php?action=usuarios&method=getUsuario&id=' + usuarioId)
                .then(response => {
                    // Primero obtener el texto de la respuesta para verificar si es HTML
                    return response.text().then(text => {
                        // Verificar si la respuesta es HTML (error de PHP)
                        if (text.trim().startsWith('<') || text.includes('<br') || text.includes('<b>')) {
                            throw new Error('Error del servidor: La respuesta contiene HTML en lugar de JSON. Esto generalmente indica un error de conexión a la base de datos o un error de PHP. Verifique la configuración de la base de datos.');
                        }
                        
                        // Intentar parsear como JSON
                        try {
                            return JSON.parse(text);
                        } catch (e) {
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
                        document.getElementById('rol_edit').value = u.rol || '';
                        document.getElementById('activo_edit').checked = u.activo == 1;
                        cargaPermisos.edit += 1;
                        slugsPorPrefijo.edit = data.role_slugs || [];
                        slugsListos.edit = true;
                        marcarExcepciones('edit', data.extra || [], data.revocado || []);
                        pintarVistaPrevia('edit');
                        
                        const modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
                        modal.show();
                    } else {
                        alert('Error al cargar usuario: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
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
            marcarExcepciones('edit', [], []);
            pintarVistaPrevia('edit');
        });
    }
    prepararOverrides('nuevo');
    prepararOverrides('edit');
});

const slugsPorPrefijo = { nuevo: [], edit: [] };
const slugsListos = { nuevo: false, edit: false };
const cargaPermisos = { nuevo: 0, edit: 0 };

function marcarExcepciones(prefijo, extras, revocados) {
    const caja = document.querySelector('[data-overrides="' + prefijo + '"]');
    if (!caja) return;
    caja.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
        input.checked = false;
    });
    extras.forEach(function (slug) {
        const input = caja.querySelector('.extra-permiso[data-slug="' + slug + '"]');
        if (input) input.checked = true;
    });
    revocados.forEach(function (slug) {
        const input = caja.querySelector('.revocado-permiso[data-slug="' + slug + '"]');
        if (input) input.checked = true;
    });
}

function leerMarcados(prefijo, clase) {
    const caja = document.querySelector('[data-overrides="' + prefijo + '"]');
    if (!caja) return [];
    return Array.from(caja.querySelectorAll(clase + ':checked')).map(function (input) {
        return input.getAttribute('data-slug');
    });
}

function textoPermiso(valor) {
    return String(valor)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function finalesDe(prefijo) {
    if (typeof resolveFinalPermissionSet !== 'function') return [];
    return resolveFinalPermissionSet(
        slugsPorPrefijo[prefijo] || [],
        leerMarcados(prefijo, '.extra-permiso'),
        leerMarcados(prefijo, '.revocado-permiso')
    );
}

function pintarVistaPrevia(prefijo) {
    const lista = document.getElementById('preview_' + prefijo);
    const contador = document.getElementById('contador_' + prefijo);
    const finales = finalesDe(prefijo);
    if (contador) contador.textContent = String(finales.length);
    const etiquetas = window.CATALOGO_ETIQUETAS || {};
    const abierto = lista && lista.classList.contains('chips-abiertos');
    const limite = abierto ? finales.length : 6;
    if (lista) {
        let html = finales.slice(0, limite).map(function (slug) {
            return '<span class="permiso-chip">' + textoPermiso(etiquetas[slug] || slug) + '</span>';
        }).join('');
        if (!abierto && finales.length > 6) {
            html += '<button type="button" class="permiso-chip permiso-chip-mas" data-prefijo="' + prefijo + '">+' + (finales.length - 6) + ' más</button>';
        }
        lista.innerHTML = html;
    }
    const caja = document.querySelector('[data-overrides="' + prefijo + '"]');
    if (!caja) return;
    const enRol = new Set(slugsPorPrefijo[prefijo] || []);
    const extras = new Set(leerMarcados(prefijo, '.extra-permiso'));
    const revocados = new Set(leerMarcados(prefijo, '.revocado-permiso'));
    caja.querySelectorAll('.permiso-icono').forEach(function (boton) {
        const slug = boton.getAttribute('data-slug');
        const activo = finales.indexOf(slug) !== -1;
        boton.classList.toggle('is-on', activo);
        boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
        if (activo && extras.has(slug)) {
            boton.title = 'Permiso extra';
        } else if (!activo && revocados.has(slug)) {
            boton.title = 'Revocado';
        } else if (enRol.has(slug)) {
            boton.title = activo ? 'Incluido en el rol' : 'No incluido';
        }
    });
    escribirEnvio(prefijo);
}

function campoPorSlug(caja, clase, slug) {
    const campos = caja.querySelectorAll(clase);
    for (let i = 0; i < campos.length; i++) {
        if (campos[i].getAttribute('data-slug') === slug) {
            return campos[i];
        }
    }
    return null;
}

function alternarPermiso(prefijo, slug) {
    const caja = document.querySelector('[data-overrides="' + prefijo + '"]');
    if (!caja || !slugsListos[prefijo]) return;
    const extra = campoPorSlug(caja, '.extra-permiso', slug);
    const revocado = campoPorSlug(caja, '.revocado-permiso', slug);
    const enRol = (slugsPorPrefijo[prefijo] || []).indexOf(slug) !== -1;
    const activo = finalesDe(prefijo).indexOf(slug) !== -1;
    if (activo) {
        if (extra) extra.checked = false;
        if (revocado) revocado.checked = enRol;
    } else {
        if (revocado) revocado.checked = false;
        if (extra) extra.checked = !enRol;
    }
    pintarVistaPrevia(prefijo);
}

function escribirEnvio(prefijo) {
    const caja = document.querySelector('[data-overrides="' + prefijo + '"]');
    if (!caja) return;
    const form = caja.closest('form');
    if (!form) return;
    let campo = form.querySelector('input[name="permisos_json"]');
    if (!slugsListos[prefijo]) {
        if (campo) campo.remove();
        return;
    }
    if (!campo) {
        campo = document.createElement('input');
        campo.type = 'hidden';
        campo.name = 'permisos_json';
        form.appendChild(campo);
    }
    const rol = {};
    (slugsPorPrefijo[prefijo] || []).forEach(function (slug) { rol[slug] = true; });
    const extra = [];
    const revocado = [];
    caja.querySelectorAll('.permiso-icono').forEach(function (boton) {
        const slug = boton.getAttribute('data-slug');
        if (!slug) return;
        const activo = boton.classList.contains('is-on');
        if (activo && !rol[slug]) extra.push(slug);
        if (!activo && rol[slug]) revocado.push(slug);
    });
    campo.value = JSON.stringify({ extra: extra, revocado: revocado });
}

function prepararOverrides(prefijo) {
    const caja = document.querySelector('[data-overrides="' + prefijo + '"]');
    const select = document.getElementById(prefijo === 'edit' ? 'rol_edit' : 'rol');
    if (!caja || !select) return;
    const form = caja.closest('form');
    caja.addEventListener('click', function (e) {
        const icono = e.target.closest('.permiso-icono');
        if (icono && caja.contains(icono)) {
            alternarPermiso(prefijo, icono.getAttribute('data-slug'));
            return;
        }
        const mas = e.target.closest('.permiso-chip-mas');
        if (mas) {
            const lista = document.getElementById('preview_' + prefijo);
            if (lista) lista.classList.add('chips-abiertos');
            pintarVistaPrevia(prefijo);
        }
    });
    caja.addEventListener('change', function (e) {
        const input = e.target;
        if (!input.matches || !input.matches('input[type="checkbox"]')) return;
        const slug = input.getAttribute('data-slug');
        if (input.checked && input.classList.contains('extra-permiso')) {
            const otro = campoPorSlug(caja, '.revocado-permiso', slug);
            if (otro) otro.checked = false;
        }
        if (input.checked && input.classList.contains('revocado-permiso')) {
            const otro = campoPorSlug(caja, '.extra-permiso', slug);
            if (otro) otro.checked = false;
        }
        pintarVistaPrevia(prefijo);
    });
    select.addEventListener('change', function () {
        const roleId = select.options[select.selectedIndex].getAttribute('data-id');
        const ticket = ++cargaPermisos[prefijo];
        slugsListos[prefijo] = false;
        if (!roleId) {
            slugsPorPrefijo[prefijo] = [];
            slugsListos[prefijo] = true;
            pintarVistaPrevia(prefijo);
            return;
        }
        fetch((window.BASE_URL || '') + 'index.php?action=roles&method=slugs&id=' + roleId)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (ticket !== cargaPermisos[prefijo]) return;
                slugsPorPrefijo[prefijo] = data.success ? (data.slugs || []) : (slugsPorPrefijo[prefijo] || []);
                slugsListos[prefijo] = true;
                pintarVistaPrevia(prefijo);
            })
            .catch(function () {
                if (ticket !== cargaPermisos[prefijo]) return;
                slugsListos[prefijo] = true;
                pintarVistaPrevia(prefijo);
            });
    });
    if (form) {
        const boton = form.querySelector('button[type="submit"]');
        if (boton && !boton.getAttribute('data-texto')) {
            boton.setAttribute('data-texto', boton.innerHTML);
        }
        form.addEventListener('submit', function (e) {
            if (!slugsListos[prefijo]) {
                e.preventDefault();
                window.alert('Espere a que carguen los permisos del rol y vuelva a guardar.');
                window.setTimeout(function () {
                    if (!boton) return;
                    boton.disabled = false;
                    boton.innerHTML = boton.getAttribute('data-texto') || boton.innerHTML;
                }, 0);
            } else {
                escribirEnvio(prefijo);
            }
        });
    }
    if (prefijo === 'nuevo' && select.value) {
        select.dispatchEvent(new Event('change'));
    }
}

