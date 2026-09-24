<?php
$pageTitle = 'Caja';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($fecha) || !is_string($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}
if (!isset($cerrado)) {
    $cerrado = null;
}
if (!isset($resumen) || !is_array($resumen)) {
    $resumen = [
        'ventas_efectivo' => 0,
        'gastos' => 0,
        'compras_caja' => 0,
        'ventas_otros' => 0,
        'ventas_fiado' => 0,
        'ventas_contra_entrega' => 0,
    ];
}
$baseVista = $cerrado ? (float) $cerrado['base'] : 0;
$efectivo = $cerrado ? (float) $cerrado['ventas_efectivo'] : (float) $resumen['ventas_efectivo'];
$gastosDia = $cerrado ? (float) $cerrado['gastos'] : (float) $resumen['gastos'];
$comprasDia = $cerrado ? (float) $cerrado['compras_caja'] : (float) $resumen['compras_caja'];
$otros = $cerrado ? (float) $cerrado['ventas_otros'] : (float) $resumen['ventas_otros'];
$fiado = $cerrado ? (float) $cerrado['ventas_fiado'] : (float) $resumen['ventas_fiado'];
$contra = $cerrado ? (float) $cerrado['ventas_contra_entrega'] : (float) $resumen['ventas_contra_entrega'];
$esperado = $cerrado ? (float) $cerrado['esperado'] : round($baseVista + $efectivo - $gastosDia - $comprasDia, 2);
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-safe"></i> Cierre de caja</h2>
    </div>

    <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="row g-3 mb-4">
        <input type="hidden" name="action" value="caja">
        <div class="col-md-3">
            <label class="form-label" for="fecha">Día</label>
            <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-info"><i class="bi bi-search"></i> Ver día</button>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Efectivo vendido</div><strong><?php echo pesos($efectivo); ?></strong></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Tarjeta, transferencia y mixto</div><strong><?php echo pesos($otros); ?></strong></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Fiado</div><strong><?php echo pesos($fiado); ?></strong></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Contra entrega</div><strong><?php echo pesos($contra); ?></strong></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Gastos del día</div><strong><?php echo pesos($gastosDia); ?></strong></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Compras pagadas de la caja</div><strong><?php echo pesos($comprasDia); ?></strong></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-3">El esperado es la base, más el efectivo, menos los gastos y las compras que salieron de la caja. El fiado y el pago contra entrega no entran en el cajón.</p>
            <?php if ($cerrado): ?>
            <p class="mb-1"><strong>Esperado:</strong> <?php echo pesos($cerrado['esperado']); ?></p>
            <p class="mb-1"><strong>Contado:</strong> <?php echo pesos($cerrado['contado']); ?></p>
            <p class="mb-0"><strong>Diferencia:</strong> <?php echo pesos($cerrado['diferencia']); ?></p>
            <?php if (!empty($cerrado['observacion'])): ?>
            <p class="mt-2 mb-0"><?php echo htmlspecialchars($cerrado['observacion']); ?></p>
            <?php endif; ?>
            <p class="mt-3 mb-0">Este día ya está cerrado<?php echo !empty($cerrado['usuario_nombre']) ? ' por ' . htmlspecialchars($cerrado['usuario_nombre']) : ''; ?>.</p>
            <?php elseif (tienePermiso('caja_cierre:decide')): ?>
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=caja&method=cerrar" id="formCierre">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="base">Base del día</label>
                        <input type="number" class="form-control" id="base" name="base" min="0" step="1" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="contado">Efectivo contado</label>
                        <input type="number" class="form-control" id="contado" name="contado" min="0" step="1" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Esperado</label>
                        <p class="form-control-plaintext mb-0" id="esperado_caja" data-efectivo="<?php echo (int) round($efectivo); ?>" data-gastos="<?php echo (int) round($gastosDia); ?>" data-compras="<?php echo (int) round($comprasDia); ?>"><?php echo pesos($esperado); ?></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="observacion">Observación</label>
                        <textarea class="form-control" id="observacion" name="observacion" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-lock"></i> Cerrar caja</button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Cierres anteriores</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Esperado</th>
                            <th>Contado</th>
                            <th>Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historial)): ?>
                        <tr><td colspan="4" class="text-center text-muted">Todavía no hay cierres</td></tr>
                        <?php else: ?>
                        <?php foreach ($historial as $fila): ?>
                        <tr>
                            <td><a href="<?php echo BASE_URL; ?>index.php?action=caja&fecha=<?php echo htmlspecialchars($fila['fecha']); ?>"><?php echo date('d/m/Y', strtotime($fila['fecha'])); ?></a></td>
                            <td><?php echo pesos($fila['esperado']); ?></td>
                            <td><?php echo pesos($fila['contado']); ?></td>
                            <td><?php echo pesos($fila['diferencia']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>front/public/js/caja.js?v=1"></script>
<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
