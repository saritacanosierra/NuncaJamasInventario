<?php
/**
 * Resolución de permisos.
 * Regla única: si un slug está en extra y en revocado, el extra gana y sale de revocado.
 * Después, permisos_finales = (permisos_del_rol ∪ extras) − revocados.
 * Un revocado que no está en extra gana sobre el rol.
 */

function normalizarOverrides(array $extras, array $revocados) {
    $extras = array_values(array_unique(array_filter($extras, 'is_string')));
    $extraSet = array_fill_keys($extras, true);
    $revocadosLimpios = [];
    foreach ($revocados as $slug) {
        if (!is_string($slug) || isset($extraSet[$slug]) || in_array($slug, $revocadosLimpios, true)) {
            continue;
        }
        $revocadosLimpios[] = $slug;
    }
    return [$extras, $revocadosLimpios];
}

function resolveFinalPermissionSet(array $permisosRol, array $extras, array $revocados) {
    [$extras, $revocados] = normalizarOverrides($extras, $revocados);
    $unidos = [];
    foreach (array_merge($permisosRol, $extras) as $slug) {
        if (is_string($slug) && !in_array($slug, $unidos, true)) {
            $unidos[] = $slug;
        }
    }
    $bloqueados = array_fill_keys($revocados, true);
    $final = [];
    foreach ($unidos as $slug) {
        if (!isset($bloqueados[$slug])) {
            $final[] = $slug;
        }
    }
    sort($final, SORT_STRING);
    return $final;
}

function filtrarSlugsValidos(array $slugs) {
    $validos = [];
    foreach ($slugs as $slug) {
        if (permiso_slug_existe($slug) && !in_array($slug, $validos, true)) {
            $validos[] = $slug;
        }
    }
    return $validos;
}

function permiso_slug_existe($slug) {
    return permisos_slug_valido($slug);
}

function tienePermiso($slug) {
    $finales = $_SESSION['final_permissions'] ?? [];
    return is_array($finales) && in_array($slug, $finales, true);
}

function tieneAlgunPermiso(array $slugs) {
    foreach ($slugs as $slug) {
        if (tienePermiso($slug)) {
            return true;
        }
    }
    return false;
}

function tieneTodosPermisos(array $slugs) {
    foreach ($slugs as $slug) {
        if (!tienePermiso($slug)) {
            return false;
        }
    }
    return true;
}

function permisos_subvistas_vista($workspaceKey) {
    $slugs = [];
    foreach (permisos_catalogo() as $grupo) {
        if ($grupo['workspaceViewKey'] !== $workspaceKey) {
            continue;
        }
        if (!in_array($grupo['groupKind'], ['subview', 'config'], true)) {
            continue;
        }
        foreach ($grupo['items'] as $item) {
            if (str_ends_with($item['slug'], ':view')) {
                $slugs[] = $item['slug'];
            }
        }
    }
    return $slugs;
}

function puedeVerModulo($workspaceKey) {
    if (!tienePermiso($workspaceKey . ':view')) {
        return false;
    }
    return tieneAlgunPermiso(permisos_subvistas_vista($workspaceKey));
}

function permisos_url_de($workspaceKey) {
    $entradas = permisos_entradas()[$workspaceKey] ?? [];
    foreach ($entradas as $slug => $url) {
        if (tienePermiso($slug)) {
            return $url;
        }
    }
    return null;
}

function permisos_url_inicio() {
    $orden = ['dashboard', 'productos', 'ventas', 'clientes', 'gastos', 'agenda', 'produccion', 'configuracion'];
    foreach ($orden as $modulo) {
        if (puedeVerModulo($modulo)) {
            $url = permisos_url_de($modulo);
            if ($url) {
                return $url;
            }
        }
    }
    return 'index.php?action=login';
}

function ve_produccion_ajena() {
    return tienePermiso('produccion_todas:view');
}

function permisos_cargar_en_sesion($db, $usuarioId) {
    $rol = new Rol($db);
    $resuelto = $rol->resolverUsuario((int) $usuarioId);
    if (!$resuelto) {
        return false;
    }
    $_SESSION['usuario_rol'] = $resuelto['role']['key'];
    $_SESSION['usuario_role_id'] = $resuelto['role']['id'];
    $_SESSION['final_permissions'] = $resuelto['final_permissions'];
    return true;
}

function denegar_permiso($json = false) {
    if ($json) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'No tiene permisos para realizar esta acción']);
        exit;
    }
    $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
    redirect(permisos_url_inicio());
}

function exigir_permiso($slug, $json = false) {
    requireAuth();
    if (!tienePermiso($slug)) {
        denegar_permiso($json);
    }
}

function exigir_algun_permiso(array $slugs, $json = false) {
    requireAuth();
    if (!tieneAlgunPermiso($slugs)) {
        denegar_permiso($json);
    }
}

function exigir_ruta($action, $method) {
    requireAuth();
    $rutas = permisos_rutas();
    $def = $rutas[$action][$method] ?? null;
    if ($def === null) {
        denegar_permiso(false);
    }
    $json = !empty($def['json']);
    if (!empty($def['all']) && !tieneTodosPermisos($def['all'])) {
        denegar_permiso($json);
    }
    if (!empty($def['any'])) {
        $lista = is_array($def['any']) ? $def['any'] : permisos_lista($def['any']);
        if (!tieneAlgunPermiso($lista)) {
            denegar_permiso($json);
        }
    }
    if (!empty($def['slug']) && !tienePermiso($def['slug'])) {
        denegar_permiso($json);
    }
}

function permisos_etiqueta($slug) {
    foreach (permisos_catalogo() as $grupo) {
        foreach ($grupo['items'] as $item) {
            if ($item['slug'] === $slug) {
                return $grupo['title'] . ' · ' . $item['label'];
            }
        }
    }
    return $slug;
}
