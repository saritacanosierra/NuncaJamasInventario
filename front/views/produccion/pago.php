<?php
$pageTitle = 'Pago por pieza';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($liquidacion) || !is_array($liquidacion)) {
    $liquidacion = [];
}
if (!isset($tarifas) || !is_array($tarifas)) {
    $tarifas = [];
}
if (!isset($codigos) || !is_array($codigos)) {
    $codigos = [];
}
if (!isset($desde)) {
    $desde = date('Y-m-01');
}
if (!isset($hasta)) {
    $hasta = date('Y-m-d');
}
$totales = [];
foreach ($liquidacion as $fila) {
    $nombre = $fila['operaria'];
    if (!isset($totales[$nombre])) {
        $totales[$nombre] = 0;
    }
    if ($fila['pago'] !== null) {
        $totales[$nombre] += $fila['pago'];
    }
}
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cash-coin"></i> Pago por pieza</h2>
        <a href="<?php echo BASE_URL; ?>index.php?action=produccion" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Producción
        </a>
    </div>

    <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="row g-3 mb-4">
        <input type="hidden" name="action" value="produccion">
        <input type="hidden" name="method" value="pago">
        <div class="col-md-3">
            <label class="form-label" for="desde">Desde</label>
            <input type="date" class="form-control" id="desde" name="desde" value="<?php echo htmlspecialchars($desde); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="hasta">Hasta</label>
            <input type="date" class="form-control" id="hasta" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-info"><i class="bi bi-search"></i> Ver pago</button>
        </div>
    </form>

    <?php if (tienePermiso('produccion_pago:edit')): ?>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Tarifa</h5></div>
        <div class="card-body">
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=produccion&method=guardarTarifa" class="row g-3">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="desde" value="<?php echo htmlspecialchars($desde); ?>">
                <input type="hidden" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>">
                <div class="col-md-3">
                    <label class="form-label" for="codigo">Código de la operación</label>
                    <input type="text" class="form-control" id="codigo" name="codigo" list="codigos_operacion" required maxlength="40">
                    <datalist id="codigos_operacion">
                        <?php foreach ($codigos as $codigo): ?>
                        <option value="<?php echo htmlspecialchars($codigo['codigo']); ?>"><?php echo htmlspecialchars($codigo['nombre']); ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="120">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="valor_pieza">Valor por pieza</label>
                    <input type="number" class="form-control" id="valor_pieza" name="valor_pieza" min="0" step="1" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
            <?php if ($tarifas): ?>
            <ul class="mt-3 mb-0">
                <?php foreach ($tarifas as $tarifa): ?>
                <li><?php echo htmlspecialchars($tarifa['nombre']); ?> (<?php echo htmlspecialchars($tarifa['codigo']); ?>): <?php echo pesos($tarifa['valor_pieza']); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Operaria</th>
                            <th>Operación</th>
                            <th>Piezas</th>
                            <th>Valor</th>
                            <th>Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$liquidacion): ?>
                        <tr><td colspan="5">En estas fechas no hay piezas terminadas.</td></tr>
                        <?php else: ?>
                        <?php foreach ($liquidacion as $fila): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fila['operaria']); ?></td>
                            <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                            <td><?php echo (int) $fila['piezas']; ?></td>
                            <td><?php echo $fila['valor'] === null ? 'Sin tarifa' : pesos($fila['valor']); ?></td>
                            <td><?php echo $fila['pago'] === null ? 'Sin tarifa' : pesos($fila['pago']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($totales): ?>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Total a pagar</h5></div>
        <div class="card-body">
            <ul class="mb-0">
                <?php foreach ($totales as $operaria => $total): ?>
                <li><?php echo htmlspecialchars($operaria); ?>: <?php echo pesos($total); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
