<?php
$pageTitle = 'Usuarios';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people"></i> Gestión de Usuarios</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
            <i class="bi bi-plus-circle"></i> Nuevo Usuario
        </button>
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
                                        <span class="badge <?php 
                                            echo $usuario['rol'] == 'admin' ? 'bg-danger' : 
                                                ($usuario['rol'] == 'cajero' ? 'bg-primary' : 'bg-info'); 
                                        ?>">
                                            <?php echo htmlspecialchars(ucfirst($usuario['rol'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $usuario['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary btnEditarUsuario" 
                                                data-id="<?php echo $usuario['id']; ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=usuarios&method=delete" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este usuario?')">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
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
        </div>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-labelledby="modalNuevoUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoUsuarioLabel">
                    <i class="bi bi-person-plus"></i> Nuevo Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevoUsuario" method="POST" action="<?php echo BASE_URL; ?>index.php?action=usuarios&method=store">
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
                            <option value="cajero">Cajero</option>
                            <option value="operario">Operario</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarUsuarioLabel">
                    <i class="bi bi-pencil-square"></i> Editar Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarUsuario" method="POST" action="<?php echo BASE_URL; ?>index.php?action=usuarios&method=update">
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
                            <option value="cajero">Cajero</option>
                            <option value="operario">Operario</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
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
</script>
<!-- JavaScript del módulo de usuarios -->
<script src="<?php echo BASE_URL; ?>front/public/js/usuarios.js"></script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

