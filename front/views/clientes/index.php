<?php
$pageTitle = 'Clientes';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people"></i> Gestión de Clientes<?php $ayuda = 'Personas que compran. Nuevo Cliente las registra. Quienes deben abre las facturas fiadas que aún no se han pagado.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <div class="d-flex align-items-center gap-2">
            <?php if (tienePermiso('clientes_fiado:view')): ?>
            <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>index.php?action=clientes&method=deudas">
                <i class="bi bi-wallet2"></i> Quienes deben
            </a>
            <?php endif; ?>
            <?php if (tienePermiso('clientes_lista:create')): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
                <i class="bi bi-plus-circle"></i> Nuevo Cliente
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Buscador -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="<?php echo tienePermiso('clientes_fiado:view') ? 'col-md-7' : 'col-md-10'; ?>">
                    <input type="text" class="form-control" 
                           placeholder="Buscar por nombre, cédula/NIT o teléfono..." 
                           value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>"
                           id="inputBusqueda">
                </div>
                <?php if (tienePermiso('clientes_fiado:view')): ?>
                <div class="col-md-3">
                    <select class="form-select" id="filtroDeuda" aria-label="Filtrar quienes deben">
                        <option value="todos">Todos los clientes</option>
                        <option value="deben">Quienes deben</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <button type="button" class="btn btn-secondary w-100" id="btnLimpiarBusqueda">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de clientes -->
    <div class="card" id="cardClientes">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Cédula/NIT</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Total Compras</th>
                            <th>Total Gastado</th>
                            <?php if (tienePermiso('clientes_fiado:view')): ?>
                            <th id="columnaDebe">Debe</th>
                            <?php endif; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaClientes">
                        <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="<?php echo tienePermiso('clientes_fiado:view') ? 8 : 7; ?>" class="text-center text-muted">No se encontraron clientes</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cliente['nombre_completo']); ?></td>
                                    <td><code><?php echo htmlspecialchars($cliente['cedula_nit']); ?></code></td>
                                    <td><?php echo htmlspecialchars($cliente['telefono'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['email'] ?? 'N/A'); ?></td>
                                    <td><span class="badge bg-info"><?php echo $cliente['total_compras'] ?? 0; ?></span></td>
                                    <td><strong><?php echo pesos($cliente['total_gastado'] ?? 0); ?></strong></td>
                                    <?php if (tienePermiso('clientes_fiado:view')): ?>
                                    <td><?php echo pesos($saldosFiado[$cliente['id']] ?? 0); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if (tienePermiso('clientes_historial:view')): ?>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=clientes&method=historial&id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-sm btn-outline-info btn-icono" title="Ver Historial">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (tienePermiso('clientes_lista:edit')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-icono" 
                                                title="Editar" 
                                                onclick="editarCliente(<?php echo $cliente['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (tienePermiso('clientes_lista:delete') && $cliente['id'] != 1): ?>
                                            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=clientes&method=delete" class="d-inline form-doble-eliminar" data-titulo="Eliminar cliente" data-detalle="Se borra el cliente y no se puede recuperar." data-codigo="<?php echo htmlspecialchars(trim($cliente['cedula_nit'] ?? '') !== '' ? $cliente['cedula_nit'] : $cliente['nombre_completo']); ?>">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?php echo (int) $cliente['id']; ?>">
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
    <?php if (tienePermiso('clientes_fiado:view')): ?>
    <div class="card d-none" id="cardDeudas">
        <div class="card-body">
            <?php require BASE_DIR . '/front/views/components/tabla_deudas.php'; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Nuevo Cliente -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-labelledby="modalNuevoClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoClienteLabel">
                    <i class="bi bi-plus-circle"></i> Nuevo Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoCliente">
                    <input type="hidden" id="cliente_id" name="id" value="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cedula_nit" class="form-label">Cédula/NIT *</label>
                                <input type="text" class="form-control" id="cedula_nit" name="cedula_nit" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                    </div>
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarCliente">
                    <i class="bi bi-save"></i> <span id="btnGuardarTexto">Guardar Cliente</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Pasar BASE_URL al JavaScript -->
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<!-- JavaScript del módulo de clientes -->
<script src="<?php echo BASE_URL; ?>front/public/js/clientes.js?v=6"></script>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
