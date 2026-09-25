<?php
$pageTitle = 'Compras';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($categorias) || !is_array($categorias)) {
    $categorias = [];
}
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <h2 class="mb-0"><i class="bi bi-bag-plus"></i> Compras de producto<?php $ayuda = 'Registra lo que compras a un proveedor. Las prendas entran al inventario y el valor queda como inversión. Si no eliges categoría, la prenda va a Accesorios.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <div class="d-flex align-items-center gap-2">
            <?php if (puedeVerModulo('gastos')): ?>
            <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>index.php?action=gastos">
                <i class="bi bi-arrow-left"></i> Gastos
            </a>
            <?php endif; ?>
            <?php if (tienePermiso('compras_kardex:view')): ?>
            <a class="btn btn-outline-info" href="<?php echo BASE_URL; ?>index.php?action=compras&method=kardex">
                <i class="bi bi-journal-text"></i> Kardex
            </a>
            <?php endif; ?>
        </div>
    </div>
    <p class="text-muted mb-4">Esta compra suma como inversión y las prendas entran al inventario.</p>

    <?php if (tienePermiso('compras_registro:create')): ?>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Nueva compra</h5></div>
        <div class="card-body">
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=compras&method=store" id="formCompra">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="lineas" id="lineas_compra" value="[]">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="proveedor">Proveedor</label>
                        <input type="text" class="form-control" id="proveedor" name="proveedor" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="documento">Documento</label>
                        <input type="text" class="form-control" id="documento" name="documento">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="fecha">Fecha</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="sale_de_caja" name="sale_de_caja" value="1">
                            <label class="form-check-label" for="sale_de_caja">Salió de la caja</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="buscar_compra">Prenda</label>
                        <input type="text" class="form-control" id="buscar_compra" placeholder="Nombre de la prenda">
                        <div id="resultados_compra" class="mt-2"></div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="talla_compra">Talla</label>
                        <input type="text" class="form-control" id="talla_compra" placeholder="4">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="color_compra">Color</label>
                        <input type="text" class="form-control" id="color_compra" placeholder="Dorado">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="cantidad_compra">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad_compra" min="1" step="1" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="costo_compra">Costo</label>
                        <input type="number" class="form-control" id="costo_compra" min="0" step="1" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="categoria_compra">Categoría</label>
                        <select class="form-select" id="categoria_compra">
                            <option value="">Opcional</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo (int) $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" id="btn_agregar_compra">Agregar</button>
                    </div>
                    <div class="col-12">
                        <p class="mb-0 text-danger d-none" id="aviso_compra"></p>
                    </div>
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table" id="tabla_lineas_compra">
                                <thead>
                                    <tr>
                                        <th>Prenda</th>
                                        <th>Cantidad</th>
                                        <th>Costo</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="observaciones">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar compra
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Total</th>
                            <th>Caja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($compras)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Todavía no hay compras</td></tr>
                        <?php else: ?>
                        <?php foreach ($compras as $compra): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($compra['numero']); ?></code></td>
                            <td><?php echo date('d/m/Y', strtotime($compra['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($compra['proveedor']); ?></td>
                            <td><?php echo pesos($compra['total']); ?></td>
                            <td><?php echo !empty($compra['sale_de_caja']) ? 'Salió de la caja' : 'No'; ?></td>
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

<script src="<?php echo BASE_URL; ?>front/public/js/compras.js?v=5"></script>
<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
