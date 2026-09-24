<?php
/**
 * Lista canónica de permisos de Nunca Jamás.
 * Los slugs salen solo de aquí. La matriz y el seed leen este mismo arreglo.
 */

function permisos_acciones() {
    return ['view', 'create', 'edit', 'delete', 'archive', 'collect', 'approve', 'decide'];
}

function permisos_columnas() {
    return [
        'view' => 'Ver',
        'create' => 'Crear',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'decide' => 'Decidir',
    ];
}

function permisos_catalogo() {
    return [
        grupo_permiso('dashboard', 'Dashboard', 'Operación', 'dashboard', 'workspace', [
            item_permiso('dashboard:view', 'Ver menú', 'Muestra el ítem Dashboard en el menú. No abre cifras ni la meta del día.'),
        ]),
        grupo_permiso('dashboard_resumen', 'Resumen', 'Operación', 'dashboard', 'subview', [
            item_permiso('dashboard_resumen:view', 'Ver resumen', 'Abre el tablero: inventario, ventas, gastos, margen, punto de equilibrio, meta diaria y productos más vendidos, incluida la consulta de la meta.'),
        ]),

        grupo_permiso('productos', 'Productos', 'Operación', 'productos', 'workspace', [
            item_permiso('productos:view', 'Ver menú', 'Muestra el ítem Productos en el menú. No abre el catálogo ni las categorías.'),
        ]),
        grupo_permiso('productos_catalogo', 'Catálogo', 'Operación', 'productos', 'subview', [
            item_permiso('productos_catalogo:view', 'Ver catálogo', 'Lista productos, filtra, busca por nombre o código y abre la foto y el código de barras.'),
            item_permiso('productos_catalogo:create', 'Crear producto', 'Crea un producto y genera un código de barras nuevo.'),
            item_permiso('productos_catalogo:edit', 'Editar producto', 'Abre la ficha y guarda cambios de un producto.'),
            item_permiso('productos_catalogo:delete', 'Eliminar producto', 'Elimina un producto del catálogo.'),
        ]),
        grupo_permiso('productos_categorias', 'Categorías de producto', 'Operación', 'productos', 'subview', [
            item_permiso('productos_categorias:view', 'Ver categorías', 'Lista las categorías de producto.'),
            item_permiso('productos_categorias:create', 'Crear categoría', 'Crea una categoría de producto.'),
            item_permiso('productos_categorias:delete', 'Eliminar categoría', 'Elimina una categoría de producto.'),
        ]),

        grupo_permiso('ventas', 'Ventas', 'Operación', 'ventas', 'workspace', [
            item_permiso('ventas:view', 'Ver menú', 'Muestra el ítem Ventas en el menú. No abre el punto de venta ni el historial.'),
        ]),
        grupo_permiso('ventas_punto', 'Punto de venta', 'Operación', 'ventas', 'subview', [
            item_permiso('ventas_punto:view', 'Ver punto de venta', 'Abre el punto de venta y busca prendas por código o nombre.'),
            item_permiso('ventas_punto:create', 'Registrar venta', 'Cobra la venta, crea la factura y descuenta el stock.'),
        ]),
        grupo_permiso('ventas_punto_cliente', 'Cliente en la venta', 'Operación', 'ventas', 'capability', [
            item_permiso('ventas_punto_cliente:view', 'Buscar cliente', 'Busca y carga un cliente desde el punto de venta, sin abrir el módulo de clientes.'),
            item_permiso('ventas_punto_cliente:create', 'Crear cliente rápido', 'Crea un cliente desde el punto de venta, sin abrir el módulo de clientes.'),
        ]),
        grupo_permiso('ventas_historial', 'Historial de ventas', 'Operación', 'ventas', 'subview', [
            item_permiso('ventas_historial:view', 'Ver historial', 'Lista facturas y el resumen de ventas por mes y por año.'),
            item_permiso('ventas_historial:edit', 'Editar factura', 'Abre una factura y guarda cambios de cliente, ítems y totales, ajustando el stock.'),
            item_permiso('ventas_historial:delete', 'Eliminar factura', 'Elimina la factura si el código coincide y devuelve las prendas al inventario.'),
        ]),
        grupo_permiso('ventas_factura', 'Factura', 'Operación', 'ventas', 'subview', [
            item_permiso('ventas_factura:view', 'Ver factura', 'Abre e imprime la factura de una venta.'),
        ]),
        grupo_permiso('ventas_rotulo', 'Rótulo de envío', 'Operación', 'ventas', 'subview', [
            item_permiso('ventas_rotulo:view', 'Ver rótulo', 'Abre e imprime el rótulo de envío de una venta con domicilio.'),
        ]),
        grupo_permiso('ventas_cambio', 'Cambio de talla', 'Operación', 'ventas', 'capability', [
            item_permiso('ventas_cambio:create', 'Cambiar talla', 'Cambia la talla de una prenda ya vendida y ajusta el stock.'),
        ]),
        grupo_permiso('ventas_dian', 'Factura electrónica', 'Operación', 'ventas', 'subview', [
            item_permiso('ventas_dian:view', 'Ver documento', 'Ve el CUFE y el estado del documento electrónico de una venta.'),
            item_permiso('ventas_dian:create', 'Generar documento', 'Arma el número, el CUFE y el XML de una venta.'),
            item_permiso('ventas_dian:decide', 'Enviar a la DIAN', 'Envía el XML al ambiente configurado y guarda la respuesta de la DIAN.'),
        ]),
        grupo_permiso('ventas_nota', 'Nota crédito', 'Operación', 'ventas', 'capability', [
            item_permiso('ventas_nota:create', 'Crear nota crédito', 'Anula el documento, devuelve la prenda al stock y deja la nota para enviarla.'),
        ]),
        grupo_permiso('ventas_resolucion', 'Resolución DIAN', 'Operación', 'ventas', 'capability', [
            item_permiso('ventas_resolucion:edit', 'Guardar resolución', 'Guarda el rango, la clave técnica y el NIT con los que se arma el documento.'),
        ]),

        grupo_permiso('clientes', 'Clientes', 'Operación', 'clientes', 'workspace', [
            item_permiso('clientes:view', 'Ver menú', 'Muestra el ítem Clientes en el menú. No abre el listado ni el historial de compras.'),
        ]),
        grupo_permiso('clientes_lista', 'Listado', 'Operación', 'clientes', 'subview', [
            item_permiso('clientes_lista:view', 'Ver clientes', 'Lista clientes y abre la ficha de uno.'),
            item_permiso('clientes_lista:create', 'Crear cliente', 'Registra un cliente desde el módulo de clientes.'),
            item_permiso('clientes_lista:edit', 'Editar cliente', 'Guarda cambios de un cliente.'),
            item_permiso('clientes_lista:delete', 'Eliminar cliente', 'Elimina un cliente que no sea el cliente general.'),
        ]),
        grupo_permiso('clientes_historial', 'Historial de compras', 'Operación', 'clientes', 'subview', [
            item_permiso('clientes_historial:view', 'Ver compras', 'Abre las compras de un cliente y, desde ahí, la factura de cada compra.'),
        ]),
        grupo_permiso('clientes_fiado', 'Fiado', 'Operación', 'clientes', 'subview', [
            item_permiso('clientes_fiado:view', 'Ver fiado', 'Ve lo que cada cliente debe.'),
            item_permiso('clientes_fiado:create', 'Abonar fiado', 'Registra un abono sobre la deuda del cliente.'),
        ]),

        grupo_permiso('gastos', 'Gastos', 'Operación', 'gastos', 'workspace', [
            item_permiso('gastos:view', 'Ver menú', 'Muestra el ítem Gastos en el menú. No abre registros, inversiones ni categorías.'),
        ]),
        grupo_permiso('gastos_registro', 'Gastos', 'Operación', 'gastos', 'subview', [
            item_permiso('gastos_registro:view', 'Ver gastos', 'Lista los gastos del periodo y sus totales.'),
            item_permiso('gastos_registro:create', 'Crear gasto', 'Registra un gasto.'),
            item_permiso('gastos_registro:edit', 'Editar gasto', 'Abre un gasto y guarda cambios.'),
            item_permiso('gastos_registro:delete', 'Eliminar gasto', 'Elimina un gasto.'),
        ]),
        grupo_permiso('gastos_inversiones', 'Inversiones', 'Operación', 'gastos', 'subview', [
            item_permiso('gastos_inversiones:view', 'Ver inversiones', 'Lista las inversiones del periodo y sus totales.'),
            item_permiso('gastos_inversiones:create', 'Crear inversión', 'Registra una inversión.'),
            item_permiso('gastos_inversiones:edit', 'Editar inversión', 'Abre una inversión y guarda cambios.'),
            item_permiso('gastos_inversiones:delete', 'Eliminar inversión', 'Elimina una inversión.'),
        ]),
        grupo_permiso('gastos_categorias', 'Categorías de gasto', 'Operación', 'gastos', 'subview', [
            item_permiso('gastos_categorias:view', 'Ver categorías', 'Lista las categorías de gasto e inversión.'),
            item_permiso('gastos_categorias:create', 'Crear categoría', 'Crea una categoría de gasto.'),
            item_permiso('gastos_categorias:edit', 'Editar categoría', 'Guarda cambios de una categoría de gasto.'),
            item_permiso('gastos_categorias:delete', 'Eliminar categoría', 'Elimina una categoría de gasto.'),
        ]),
        grupo_permiso('gastos_historial', 'Historial de gastos', 'Operación', 'gastos', 'subview', [
            item_permiso('gastos_historial:view', 'Ver historial', 'Abre el resumen de gastos e inversiones por mes y por año.'),
        ]),

        grupo_permiso('agenda', 'Agenda', 'Operación', 'agenda', 'workspace', [
            item_permiso('agenda:view', 'Ver menú', 'Muestra el ítem Agenda en el menú. No abre las tareas.'),
        ]),
        grupo_permiso('agenda_tareas', 'Tareas', 'Operación', 'agenda', 'subview', [
            item_permiso('agenda_tareas:view', 'Ver tareas', 'Abre el calendario y las tareas del día o del mes.'),
            item_permiso('agenda_tareas:create', 'Crear tarea', 'Crea una tarea.'),
            item_permiso('agenda_tareas:edit', 'Editar tarea', 'Abre una tarea y guarda cambios.'),
            item_permiso('agenda_tareas:delete', 'Eliminar tarea', 'Elimina una tarea.'),
            item_permiso('agenda_tareas:decide', 'Completar tarea', 'Marca una tarea como completada o pendiente.'),
        ]),

        grupo_permiso('produccion', 'Producción', 'Operación', 'produccion', 'workspace', [
            item_permiso('produccion:view', 'Ver menú', 'Muestra el ítem Producción en el menú. No abre la jornada ni el tablero.'),
        ]),
        grupo_permiso('produccion_jornada', 'Jornada', 'Operación', 'produccion', 'subview', [
            item_permiso('produccion_jornada:view', 'Ver jornada', 'Abre el registro del día, las operarias visibles para esa persona y si el día ya está cerrado.'),
            item_permiso('produccion_jornada:create', 'Registrar operaria', 'Crea o actualiza el registro diario de una operaria.'),
        ]),
        grupo_permiso('produccion_operaciones', 'Operaciones', 'Operación', 'produccion', 'subview', [
            item_permiso('produccion_operaciones:view', 'Ver operaciones', 'Lista las operaciones del día y el historial de una operación.'),
            item_permiso('produccion_operaciones:create', 'Registrar operación', 'Guarda una operación y genera su código.'),
            item_permiso('produccion_operaciones:delete', 'Eliminar operación', 'Elimina una operación del día.'),
        ]),
        grupo_permiso('produccion_retrocesos', 'Retrocesos', 'Operación', 'produccion', 'subview', [
            item_permiso('produccion_retrocesos:view', 'Ver retrocesos', 'Lista los retrocesos de una operación.'),
            item_permiso('produccion_retrocesos:create', 'Registrar retroceso', 'Guarda un retroceso de producción.'),
        ]),
        grupo_permiso('produccion_dashboard', 'Tablero de operaciones', 'Operación', 'produccion', 'subview', [
            item_permiso('produccion_dashboard:view', 'Ver tablero', 'Abre el tablero de operaciones por rango de fechas.'),
        ]),
        grupo_permiso('produccion_todas', 'Ver a todas las operarias', 'Operación', 'produccion', 'capability', [
            item_permiso('produccion_todas:view', 'Ver el trabajo de todas', 'Ve y registra el trabajo de cualquier operaria. Sin este permiso solo se ve el propio nombre.'),
        ]),
        grupo_permiso('produccion_cierre', 'Cierre del día', 'Operación', 'produccion', 'capability', [
            item_permiso('produccion_cierre:decide', 'Cerrar y abrir el día', 'Cierra la jornada de toda la empresa una sola vez, y puede abrirla de nuevo.'),
        ]),
        grupo_permiso('produccion_pago', 'Pago por pieza', 'Operación', 'produccion', 'subview', [
            item_permiso('produccion_pago:view', 'Ver pago', 'Ve las piezas del periodo y lo que se le debe a cada operaria.'),
            item_permiso('produccion_pago:edit', 'Editar tarifa', 'Guarda cuánto se paga por cada pieza de una operación.'),
        ]),

        grupo_permiso('compras', 'Compras', 'Operación', 'compras', 'workspace', [
            item_permiso('compras:view', 'Ver menú', 'Muestra el ítem Compras en el menú.'),
        ]),
        grupo_permiso('compras_registro', 'Registro de compras', 'Operación', 'compras', 'subview', [
            item_permiso('compras_registro:view', 'Ver compras', 'Lista las compras de mercancía.'),
            item_permiso('compras_registro:create', 'Registrar compra', 'Registra una compra, sube el stock y actualiza el costo.'),
        ]),
        grupo_permiso('compras_kardex', 'Kardex', 'Operación', 'compras', 'subview', [
            item_permiso('compras_kardex:view', 'Ver kardex', 'Lista las entradas y salidas de cada prenda.'),
        ]),

        grupo_permiso('caja', 'Caja', 'Operación', 'caja', 'workspace', [
            item_permiso('caja:view', 'Ver menú', 'Muestra el ítem Caja en el menú.'),
        ]),
        grupo_permiso('caja_cierre', 'Cierre de caja', 'Operación', 'caja', 'subview', [
            item_permiso('caja_cierre:view', 'Ver cierre', 'Ve el efectivo esperado del día y los cierres anteriores.'),
            item_permiso('caja_cierre:decide', 'Cerrar caja', 'Cierra la caja de un día una sola vez.'),
        ]),

        grupo_permiso('informes', 'Informes', 'Operación', 'informes', 'workspace', [
            item_permiso('informes:view', 'Ver menú', 'Muestra el ítem Informes en el menú.'),
        ]),
        grupo_permiso('informes_mes', 'Informe del mes', 'Operación', 'informes', 'subview', [
            item_permiso('informes_mes:view', 'Ver informe', 'Ve ventas, gastos, caja, documentos y fiado de un mes, listo para imprimir.'),
        ]),

        grupo_permiso('configuracion', 'Configuración', 'Configuración', 'configuracion', 'workspace', [
            item_permiso('configuracion:view', 'Ver menú', 'Muestra el engranaje de Configuración. No abre usuarios ni la matriz de roles.'),
        ]),
        grupo_permiso('usuarios_lista', 'Usuarios', 'Configuración', 'configuracion', 'config', [
            item_permiso('usuarios_lista:view', 'Ver usuarios', 'Lista los usuarios y su rol.'),
            item_permiso('usuarios_lista:create', 'Crear usuario', 'Crea un usuario con rol y, si se indican, permisos extra o revocados.'),
            item_permiso('usuarios_lista:edit', 'Editar usuario', 'Cambia datos, rol, permisos extra y permisos revocados de un usuario.'),
            item_permiso('usuarios_lista:delete', 'Eliminar usuario', 'Elimina un usuario distinto del que tiene la sesión abierta.'),
        ]),
        grupo_permiso('roles_matriz', 'Roles', 'Configuración', 'configuracion', 'config', [
            item_permiso('roles_matriz:view', 'Ver matriz', 'Abre la matriz de permisos de cada rol.'),
            item_permiso('roles_matriz:edit', 'Editar matriz', 'Guarda los permisos marcados en un rol y crea roles nuevos.'),
        ]),
    ];
}

function grupo_permiso($id, $title, $sectionTitle, $workspaceViewKey, $groupKind, $items) {
    return [
        'id' => $id,
        'title' => $title,
        'sectionTitle' => $sectionTitle,
        'workspaceViewKey' => $workspaceViewKey,
        'groupKind' => $groupKind,
        'items' => $items,
    ];
}

function item_permiso($slug, $label, $description) {
    return [
        'slug' => $slug,
        'label' => $label,
        'description' => $description,
    ];
}

function permisos_slugs() {
    $slugs = [];
    foreach (permisos_catalogo() as $grupo) {
        foreach ($grupo['items'] as $item) {
            $slugs[] = $item['slug'];
        }
    }
    return $slugs;
}

function permisos_slug_valido($slug) {
    if (!is_string($slug) || !in_array($slug, permisos_slugs(), true)) {
        return false;
    }
    $partes = explode(':', $slug, 2);
    if (count($partes) !== 2) {
        return false;
    }
    return preg_match('/^[a-z0-9_]+$/', $partes[0]) === 1
        && in_array($partes[1], permisos_acciones(), true);
}

function permisos_listas_any() {
    return [
        'buscar_producto' => ['productos_catalogo:view', 'ventas_punto:view'],
        'ver_categorias_producto' => ['productos_categorias:view', 'productos_catalogo:view', 'productos_catalogo:create', 'productos_catalogo:edit'],
        'buscar_cliente' => ['clientes_lista:view', 'ventas_punto_cliente:view'],
        'crear_cliente' => ['clientes_lista:create', 'ventas_punto_cliente:create'],
        'ver_factura' => ['ventas_factura:view', 'clientes_historial:view'],
        'entrar_gastos' => ['gastos_registro:view', 'gastos_inversiones:view', 'gastos_categorias:view', 'gastos_historial:view'],
        'ver_categorias_gasto' => ['gastos_categorias:view', 'gastos_registro:create', 'gastos_registro:edit', 'gastos_inversiones:create', 'gastos_inversiones:edit'],
        'ver_rol_para_usuario' => ['roles_matriz:view', 'usuarios_lista:edit', 'usuarios_lista:create'],
    ];
}

function permisos_lista($nombre) {
    $listas = permisos_listas_any();
    return $listas[$nombre] ?? [];
}

function permisos_semilla_roles() {
    $cajero = [
        'productos:view',
        'productos_catalogo:view',
        'ventas:view',
        'ventas_punto:view',
        'ventas_punto:create',
        'ventas_punto_cliente:view',
        'ventas_punto_cliente:create',
        'ventas_historial:view',
        'ventas_historial:edit',
        'ventas_historial:delete',
        'ventas_factura:view',
        'ventas_rotulo:view',
        'ventas_cambio:create',
        'ventas_dian:view',
        'ventas_dian:create',
        'ventas_nota:create',
        'clientes:view',
        'clientes_lista:view',
        'clientes_fiado:view',
        'clientes_fiado:create',
        'compras:view',
        'compras_registro:view',
        'compras_registro:create',
        'compras_kardex:view',
        'caja:view',
        'caja_cierre:view',
        'caja_cierre:decide',
    ];
    $operario = [
        'produccion:view',
        'produccion_jornada:view',
        'produccion_operaciones:view',
        'produccion_operaciones:create',
        'produccion_operaciones:delete',
        'produccion_retrocesos:view',
        'produccion_retrocesos:create',
    ];

    return [
        'admin' => ['name' => 'Administrador', 'slugs' => permisos_slugs()],
        'cajero' => ['name' => 'Cajero', 'slugs' => $cajero],
        'operario' => ['name' => 'Operario', 'slugs' => $operario],
    ];
}

function permisos_entradas() {
    return [
        'dashboard' => ['dashboard_resumen:view' => 'index.php?action=dashboard'],
        'productos' => ['productos_catalogo:view' => 'index.php?action=productos', 'productos_categorias:view' => 'index.php?action=productos'],
        'ventas' => [
            'ventas_punto:view' => 'index.php?action=ventas',
            'ventas_historial:view' => 'index.php?action=ventas&method=historial',
            'ventas_factura:view' => 'index.php?action=ventas&method=historial',
            'ventas_rotulo:view' => 'index.php?action=ventas&method=historial',
            'ventas_dian:view' => 'index.php?action=ventas&method=resolucion',
        ],
        'compras' => [
            'compras_registro:view' => 'index.php?action=compras',
            'compras_kardex:view' => 'index.php?action=compras&method=kardex',
        ],
        'caja' => [
            'caja_cierre:view' => 'index.php?action=caja',
        ],
        'informes' => [
            'informes_mes:view' => 'index.php?action=informes',
        ],
        'clientes' => [
            'clientes_lista:view' => 'index.php?action=clientes',
            'clientes_historial:view' => 'index.php?action=clientes',
        ],
        'gastos' => [
            'gastos_registro:view' => 'index.php?action=gastos',
            'gastos_inversiones:view' => 'index.php?action=gastos',
            'gastos_categorias:view' => 'index.php?action=gastos',
            'gastos_historial:view' => 'index.php?action=gastos',
        ],
        'agenda' => ['agenda_tareas:view' => 'index.php?action=agenda'],
        'produccion' => [
            'produccion_jornada:view' => 'index.php?action=produccion',
            'produccion_operaciones:view' => 'index.php?action=produccion',
            'produccion_retrocesos:view' => 'index.php?action=produccion',
            'produccion_dashboard:view' => 'index.php?action=produccion&method=dashboardOperaciones',
            'produccion_pago:view' => 'index.php?action=produccion&method=pago',
        ],
        'configuracion' => [
            'usuarios_lista:view' => 'index.php?action=usuarios',
            'roles_matriz:view' => 'index.php?action=roles',
        ],
    ];
}

/**
 * Permiso de cada ruta. all = todos. any = nombre de permisos_listas_any().
 */
function permisos_rutas() {
    $json = true;
    return [
        'dashboard' => [
            'index' => ['all' => ['dashboard:view', 'dashboard_resumen:view']],
            'getMetaDiaria' => ['all' => ['dashboard:view', 'dashboard_resumen:view'], 'json' => $json],
        ],
        'productos' => [
            'index' => ['all' => ['productos:view', 'productos_catalogo:view']],
            'create' => ['slug' => 'productos_catalogo:create'],
            'store' => ['slug' => 'productos_catalogo:create'],
            'edit' => ['slug' => 'productos_catalogo:edit', 'json' => $json],
            'update' => ['slug' => 'productos_catalogo:edit'],
            'delete' => ['slug' => 'productos_catalogo:delete'],
            'buscarPorCodigo' => ['any' => 'buscar_producto', 'json' => $json],
            'buscarPorNombre' => ['any' => 'buscar_producto', 'json' => $json],
            'generarCodigo' => ['slug' => 'productos_catalogo:create', 'json' => $json],
            'codigoBarras' => ['any' => 'buscar_producto'],
            'obtenerCategorias' => ['any' => 'ver_categorias_producto', 'json' => $json],
            'crearCategoria' => ['slug' => 'productos_categorias:create', 'json' => $json],
            'eliminarCategoria' => ['slug' => 'productos_categorias:delete', 'json' => $json],
        ],
        'ventas' => [
            'index' => ['all' => ['ventas:view', 'ventas_punto:view']],
            'procesar' => ['slug' => 'ventas_punto:create', 'json' => $json],
            'historial' => ['all' => ['ventas:view', 'ventas_historial:view']],
            'obtenerHistorial' => ['all' => ['ventas:view', 'ventas_historial:view'], 'json' => $json],
            'edit' => ['slug' => 'ventas_historial:edit'],
            'update' => ['slug' => 'ventas_historial:edit'],
            'delete' => ['slug' => 'ventas_historial:delete'],
            'factura' => ['any' => 'ver_factura'],
            'rotuloEnvio' => ['slug' => 'ventas_rotulo:view'],
            'cambiarTalla' => ['slug' => 'ventas_cambio:create'],
            'resolucion' => ['slug' => 'ventas_dian:view'],
            'guardarResolucion' => ['slug' => 'ventas_resolucion:edit'],
            'emitirDian' => ['slug' => 'ventas_dian:create'],
            'guardarCertificado' => ['slug' => 'ventas_resolucion:edit'],
            'enviarDian' => ['slug' => 'ventas_dian:decide'],
            'notaCredito' => ['slug' => 'ventas_nota:create'],
            'enviarNota' => ['slug' => 'ventas_dian:decide'],
        ],
        'clientes' => [
            'index' => ['all' => ['clientes:view', 'clientes_lista:view']],
            'store' => ['slug' => 'clientes_lista:create'],
            'update' => ['slug' => 'clientes_lista:edit'],
            'delete' => ['slug' => 'clientes_lista:delete'],
            'historial' => ['all' => ['clientes:view', 'clientes_historial:view']],
            'getCliente' => ['any' => 'buscar_cliente', 'json' => $json],
            'buscar' => ['any' => 'buscar_cliente', 'json' => $json],
            'crearRapido' => ['any' => 'crear_cliente', 'json' => $json],
            'abonar' => ['slug' => 'clientes_fiado:create'],
            'deudas' => ['slug' => 'clientes_fiado:view'],
        ],
        'gastos' => [
            'index' => ['all' => ['gastos:view'], 'any' => 'entrar_gastos'],
            'store' => ['slug' => 'gastos_registro:create'],
            'getGasto' => ['slug' => 'gastos_registro:edit', 'json' => $json],
            'update' => ['slug' => 'gastos_registro:edit'],
            'delete' => ['slug' => 'gastos_registro:delete'],
            'storeInversion' => ['slug' => 'gastos_inversiones:create'],
            'getInversion' => ['slug' => 'gastos_inversiones:edit', 'json' => $json],
            'updateInversion' => ['slug' => 'gastos_inversiones:edit'],
            'deleteInversion' => ['slug' => 'gastos_inversiones:delete'],
            'obtenerCategorias' => ['any' => 'ver_categorias_gasto', 'json' => $json],
            'crearCategoria' => ['slug' => 'gastos_categorias:create', 'json' => $json],
            'actualizarCategoria' => ['slug' => 'gastos_categorias:edit', 'json' => $json],
            'eliminarCategoria' => ['slug' => 'gastos_categorias:delete', 'json' => $json],
            'getCategoria' => ['slug' => 'gastos_categorias:edit', 'json' => $json],
            'obtenerHistorial' => ['all' => ['gastos:view', 'gastos_historial:view'], 'json' => $json],
        ],
        'agenda' => [
            'index' => ['all' => ['agenda:view', 'agenda_tareas:view']],
            'store' => ['slug' => 'agenda_tareas:create'],
            'update' => ['slug' => 'agenda_tareas:edit'],
            'delete' => ['slug' => 'agenda_tareas:delete', 'json' => $json],
            'completar' => ['slug' => 'agenda_tareas:decide', 'json' => $json],
            'getTarea' => ['slug' => 'agenda_tareas:edit', 'json' => $json],
            'getTareasPorFecha' => ['all' => ['agenda:view', 'agenda_tareas:view'], 'json' => $json],
        ],
        'produccion' => [
            'index' => ['all' => ['produccion:view', 'produccion_jornada:view']],
            'guardarRegistro' => ['any' => ['produccion_jornada:create', 'produccion_operaciones:create'], 'json' => $json],
            'guardarOperacion' => ['slug' => 'produccion_operaciones:create', 'json' => $json],
            'eliminarOperacion' => ['slug' => 'produccion_operaciones:delete', 'json' => $json],
            'getOperaciones' => ['all' => ['produccion:view', 'produccion_operaciones:view'], 'json' => $json],
            'getHistorialOperacion' => ['all' => ['produccion:view', 'produccion_operaciones:view'], 'json' => $json],
            'generarCodigoOperacion' => ['slug' => 'produccion_operaciones:create', 'json' => $json],
            'guardarRetroceso' => ['slug' => 'produccion_retrocesos:create', 'json' => $json],
            'getRetrocesos' => ['all' => ['produccion:view', 'produccion_retrocesos:view'], 'json' => $json],
            'getOperariasDelDia' => ['all' => ['produccion:view', 'produccion_jornada:view'], 'json' => $json],
            'buscarOperarias' => ['all' => ['produccion:view', 'produccion_jornada:view'], 'json' => $json],
            'getResumenDia' => ['all' => ['produccion:view', 'produccion_jornada:view'], 'json' => $json],
            'verificarDiaFinalizado' => ['all' => ['produccion:view', 'produccion_jornada:view'], 'json' => $json],
            'finalizarDia' => ['slug' => 'produccion_cierre:decide', 'json' => $json],
            'reabrirDia' => ['slug' => 'produccion_cierre:decide', 'json' => $json],
            'dashboardOperaciones' => ['all' => ['produccion:view', 'produccion_dashboard:view']],
            'pago' => ['slug' => 'produccion_pago:view'],
            'guardarTarifa' => ['slug' => 'produccion_pago:edit'],
        ],
        'compras' => [
            'index' => ['all' => ['compras:view', 'compras_registro:view']],
            'store' => ['slug' => 'compras_registro:create'],
            'kardex' => ['all' => ['compras:view', 'compras_kardex:view']],
            'buscarProducto' => ['slug' => 'compras_registro:create', 'json' => $json],
        ],
        'caja' => [
            'index' => ['all' => ['caja:view', 'caja_cierre:view']],
            'cerrar' => ['slug' => 'caja_cierre:decide'],
        ],
        'informes' => [
            'index' => ['all' => ['informes:view', 'informes_mes:view']],
        ],
        'usuarios' => [
            'index' => ['all' => ['configuracion:view', 'usuarios_lista:view']],
            'store' => ['slug' => 'usuarios_lista:create'],
            'getUsuario' => ['slug' => 'usuarios_lista:edit', 'json' => $json],
            'update' => ['slug' => 'usuarios_lista:edit'],
            'delete' => ['slug' => 'usuarios_lista:delete'],
        ],
        'roles' => [
            'index' => ['all' => ['configuracion:view', 'roles_matriz:view']],
            'guardar' => ['slug' => 'roles_matriz:edit'],
            'crear' => ['slug' => 'roles_matriz:edit'],
            'slugs' => ['any' => 'ver_rol_para_usuario', 'json' => $json],
        ],
        'sesion' => [
            'me' => ['json' => $json],
        ],
    ];
}
