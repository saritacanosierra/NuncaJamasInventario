<?php
$pageTitle = 'Roles';
require_once BASE_DIR . '/front/views/layout/header.php';

if (!isset($roles) || !is_array($roles)) {
    $roles = [];
}
if (!isset($rol) || !is_array($rol)) {
    $rol = null;
}
$asignadosMapa = array_fill_keys($asignados ?? [], true);
$secciones = [];
foreach (permisos_catalogo() as $grupo) {
    $secciones[$grupo['sectionTitle']][$grupo['workspaceViewKey']][] = $grupo;
}
$puedeEditarRol = tienePermiso('roles_matriz:edit');

function permisos_por_accion($grupo) {
    $porAccion = [];
    foreach ($grupo['items'] as $itemPermiso) {
        $accion = explode(':', $itemPermiso['slug'], 2)[1];
        $porAccion[$accion] = $itemPermiso;
    }
    return $porAccion;
}
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-shield-lock"></i> Roles<?php $ayuda = 'Cada rol es un paquete de permisos. Elige uno a la izquierda, marca lo que puede hacer y guarda. Sin el permiso del menú, el módulo no aparece.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <?php if (puedeVerModulo('configuracion') && tienePermiso('usuarios_lista:view')): ?>
            <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>index.php?action=usuarios">Usuarios</a>
        <?php endif; ?>
        <?php if (tienePermiso('roles_matriz:edit')): ?>
            <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>index.php?action=wordpress">WordPress</a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="list-group mb-3 permisos-lista">
                <?php foreach ($roles as $item): ?>
                    <a class="list-group-item list-group-item-action <?php echo ((int) $item['id'] === (int) ($rol['id'] ?? 0)) ? 'active' : ''; ?>"
                       href="<?php echo BASE_URL; ?>index.php?action=roles&id=<?php echo (int) $item['id']; ?>">
                        <?php echo htmlspecialchars($item['name']); ?>
                        <small class="d-block"><?php echo htmlspecialchars($item['key']); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($puedeEditarRol): ?>
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=roles&method=crear" class="permisos-panel p-3">
                <?php echo csrf_field(); ?>
                <div class="permisos-kicker mb-2">Nuevo rol</div>
                <input type="text" class="form-control mb-2" name="name" placeholder="Nombre" required>
                <input type="text" class="form-control mb-2" name="key" placeholder="clave" pattern="[a-z][a-z0-9_]{1,40}" required>
                <button type="submit" class="btn btn-primary permisos-btn-guardar btn-sm">Crear rol</button>
            </form>
            <?php endif; ?>
        </div>
        <div class="col-lg-9">
            <?php if ($rol): ?>
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=roles&method=guardar" class="permisos-panel permisos-editor">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int) $rol['id']; ?>">
                <div class="permisos-panel-head border-bottom">
                    <h3>Editar rol</h3>
                    <p>Completa nombre y clave del rol, luego activa o desactiva permisos por módulo.</p>
                    <div class="row g-2 mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($rol['name']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Clave</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($rol['key']); ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                        <div>
                            <div class="fw-semibold">Permisos</div>
                            <p class="permisos-ayuda small">Marca para permitir. Desmarca para bloquear. Expande cada módulo para ver la descripción. Permisos activos: <span id="conteoRol"><?php echo count($asignados ?? []); ?></span></p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="expandirTodos" onclick="document.querySelectorAll('.permisos-seccion, .permisos-card').forEach(function (bloque) { bloque.classList.add('abierto'); })">Expandir todos</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="contraerTodos" onclick="document.querySelectorAll('.permisos-seccion, .permisos-card').forEach(function (bloque) { bloque.classList.remove('abierto'); })">Contraer todos</button>
                        </div>
                    </div>

                    <?php foreach ($secciones as $sectionTitle => $workspaces): ?>
                        <?php foreach ($workspaces as $grupos): ?>
                            <?php
                            $vista = null;
                            $resto = [];
                            foreach ($grupos as $grupo) {
                                if ($grupo['groupKind'] === 'workspace') {
                                    $vista = $grupo;
                                } else {
                                    $resto[] = $grupo;
                                }
                            }
                            $tituloWorkspace = $vista['title'] ?? $sectionTitle;
                            $encabezado = strtoupper($sectionTitle);
                            if (strcasecmp($sectionTitle, $tituloWorkspace) !== 0) {
                                $encabezado .= ' · ' . strtoupper($tituloWorkspace);
                            }
                            ?>
                            <section class="permisos-seccion">
                            <button type="button" class="permisos-seccion-titulo permisos-seccion-head" onclick="this.closest('.permisos-seccion').classList.toggle('abierto')">
                                <span><?php echo htmlspecialchars($encabezado); ?></span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="permisos-seccion-body">
                            <?php if ($vista): ?>
                                <div class="permisos-vista">
                                    <h4>Vista · <?php echo htmlspecialchars($vista['title']); ?></h4>
                                    <p class="permisos-ayuda small mb-2">Acceso al módulo en el menú y, debajo, permisos por pantalla. Sin este permiso no aparece el módulo aunque tenga pantallas; sin pantalla tampoco aparece aunque tenga este permiso.</p>
                                    <?php foreach ($vista['items'] as $itemPermiso): ?>
                                        <?php $activo = isset($asignadosMapa[$itemPermiso['slug']]); ?>
                                        <div class="permisos-fila">
                                            <div>
                                                <h5><?php echo htmlspecialchars($itemPermiso['label']); ?></h5>
                                                <p><?php echo htmlspecialchars($itemPermiso['description']); ?></p>
                                            </div>
                                            <label class="permisos-switch">
                                                <input type="checkbox" name="slugs[]" value="<?php echo htmlspecialchars($itemPermiso['slug']); ?>" <?php echo $activo ? 'checked' : ''; ?> <?php echo $puedeEditarRol ? '' : 'disabled'; ?>>
                                                <span class="permisos-switch-texto"><?php echo $activo ? 'Permitido' : 'Bloqueado'; ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="row g-3">
                                <?php foreach ($resto as $grupo): ?>
                                    <?php
                                    $resumen = [];
                                    foreach ($grupo['items'] as $itemPermiso) {
                                        $resumen[] = $itemPermiso['label'];
                                    }
                                    ?>
                                    <div class="col-md-6">
                                        <div class="permisos-card">
                                            <button type="button" class="permisos-card-head" onclick="this.closest('.permisos-card').classList.toggle('abierto')">
                                                <span>
                                                    <strong><?php echo htmlspecialchars($grupo['title']); ?></strong>
                                                    <span class="permisos-card-resumen"><?php echo htmlspecialchars(implode(' · ', $resumen)); ?></span>
                                                </span>
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                            <div class="permisos-card-body">
                                                <?php foreach (permisos_por_accion($grupo) as $accion => $itemPermiso): ?>
                                                    <?php $activo = isset($asignadosMapa[$itemPermiso['slug']]); ?>
                                                    <div class="permisos-fila">
                                                        <div>
                                                            <h5><?php echo htmlspecialchars($itemPermiso['label']); ?></h5>
                                                            <p><?php echo htmlspecialchars($itemPermiso['description']); ?></p>
                                                        </div>
                                                        <label class="permisos-switch">
                                                            <input type="checkbox" name="slugs[]" value="<?php echo htmlspecialchars($itemPermiso['slug']); ?>" <?php echo $activo ? 'checked' : ''; ?> <?php echo $puedeEditarRol ? '' : 'disabled'; ?>>
                                                            <span class="permisos-switch-texto"><?php echo $activo ? 'Permitido' : 'Bloqueado'; ?></span>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            </div>
                            </section>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <div class="permisos-panel-footer border-top">
                    <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>index.php?action=roles&id=<?php echo (int) $rol['id']; ?>">Cancelar</a>
                    <?php if ($puedeEditarRol): ?>
                        <button type="submit" class="btn btn-primary permisos-btn-guardar">Guardar rol</button>
                    <?php endif; ?>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>front/public/js/roles.js?v=3"></script>
<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
