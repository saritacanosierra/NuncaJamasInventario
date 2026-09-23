/**
 * Comprueba la lista que ya resolvió el servidor.
 * resolveFinalPermissionSet repite la misma fórmula solo para la vista previa al editar.
 */

function tienePermisoCliente(slug) {
    return Array.isArray(window.FINAL_PERMISSIONS) && window.FINAL_PERMISSIONS.indexOf(slug) !== -1;
}

function tieneAlgunPermisoCliente(nombreLista) {
    const lista = (window.PERMISOS_ANY && window.PERMISOS_ANY[nombreLista]) || [];
    return lista.some(function (slug) {
        return tienePermisoCliente(slug);
    });
}

function normalizarOverrides(extras, revocados) {
    const extra = [];
    (extras || []).forEach(function (slug) {
        if (extra.indexOf(slug) === -1) {
            extra.push(slug);
        }
    });
    const extraSet = new Set(extra);
    const revocado = [];
    (revocados || []).forEach(function (slug) {
        if (!extraSet.has(slug) && revocado.indexOf(slug) === -1) {
            revocado.push(slug);
        }
    });
    return { extra: extra, revocado: revocado };
}

function resolveFinalPermissionSet(permisosRol, extras, revocados) {
    const normalizado = normalizarOverrides(extras, revocados);
    const unidos = [];
    (permisosRol || []).concat(normalizado.extra).forEach(function (slug) {
        if (unidos.indexOf(slug) === -1) {
            unidos.push(slug);
        }
    });
    const bloqueados = new Set(normalizado.revocado);
    return unidos.filter(function (slug) {
        return !bloqueados.has(slug);
    }).sort();
}
