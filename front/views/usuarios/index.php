<?php
$pageTitle = 'Usuarios';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($roles) || !is_array($roles)) {
    $roles = [];
}
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people"></i> Usuarios<?php $ayuda = 'Personas que entran al sistema. Nuevo Usuario crea la cuenta. Roles define qué puede ver y hacer cada uno.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <div>
            <?php if (tienePermiso('roles_matriz:view')): ?>
                <a class="btn btn-outline-secondary me-2" href="<?php echo BASE_URL; ?>index.php?action=roles">Roles</a>
            <?php endif; ?>
            <?php if (tienePermiso('usuarios_lista:create')): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                <i class="bi bi-plus-circle"></i> Nuevo Usuario
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Tabla de usuarios -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No hay usuarios registrados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($usuario['rol_nombre'] ?? $usuario['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $usuario['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                                    <td>
                                        <?php if (tienePermiso('usuarios_lista:edit')): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary btn-icono btnEditarUsuario" 
                                                data-id="<?php echo $usuario['id']; ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (tienePermiso('usuarios_lista:delete') && $usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=usuarios&method=delete" class="d-inline form-doble-eliminar" data-titulo="Eliminar usuario" data-detalle="Se borra el usuario y no se puede recuperar." data-codigo="<?php echo htmlspecialchars($usuario['nombre']); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icono" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php require BASE_DIR . '/front/views/components/paginacion.php'; ?>
        </div>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-labelledby="modalNuevoUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoUsuarioLabel">
                    <i class="bi bi-plus-circle"></i> Nuevo Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevoUsuario" method="POST" action="<?php echo BASE_URL; ?>index.php?action=usuarios&method=store">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol *</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <?php foreach ($roles as $rolItem): ?>
                                <option value="<?php echo htmlspecialchars($rolItem['key']); ?>" data-id="<?php echo (int) $rolItem['id']; ?>">
                                    <?php echo htmlspecialchars($rolItem['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php $prefijo = 'nuevo'; require BASE_DIR . '/front/views/configuracion/overrides.php'; ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">
                                Usuario Activo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarUsuarioLabel">
                    <i class="bi bi-pencil"></i> Editar Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarUsuario" method="POST" action="<?php echo BASE_URL; ?>index.php?action=usuarios&method=update">
                <?php echo csrf_field(); ?>
                <input type="hidden" id="usuario_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre_edit" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre_edit" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="email_edit" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email_edit" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_edit" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="password_edit" name="password" placeholder="Dejar vacío para mantener la actual">
                        <small class="text-muted">Solo complete si desea cambiar la contraseña</small>
                    </div>
                    <div class="mb-3">
                        <label for="rol_edit" class="form-label">Rol *</label>
                        <select class="form-select" id="rol_edit" name="rol" required>
                            <?php foreach ($roles as $rolItem): ?>
                                <option value="<?php echo htmlspecialchars($rolItem['key']); ?>" data-id="<?php echo (int) $rolItem['id']; ?>">
                                    <?php echo htmlspecialchars($rolItem['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php $prefijo = 'edit'; require BASE_DIR . '/front/views/configuracion/overrides.php'; ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo_edit" name="activo">
                            <label class="form-check-label" for="activo_edit">
                                Usuario Activo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pasar BASE_URL al JavaScript -->
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
    window.CATALOGO_ETIQUETAS = <?php
        $etiquetas = [];
        foreach (permisos_catalogo() as $grupo) {
            foreach ($grupo['items'] as $item) {
                $etiquetas[$item['slug']] = $grupo['title'] . ' · ' . $item['label'];
            }
        }
        echo json_encode($etiquetas, JSON_UNESCAPED_UNICODE);
    ?>;
</script>
<!-- JavaScript del módulo de usuarios -->
<script src="<?php echo BASE_URL; ?>front/public/js/usuarios.js?v=5"></script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

