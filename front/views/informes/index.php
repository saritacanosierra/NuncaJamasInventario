<?php
$pageTitle = 'Informe';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($informe) || !is_array($informe)) {
    $informe = [
        'desde' => date('Y-m-01'),
        'hasta' => date('Y-m-t'),
        'ventas_n' => 0,
        'ventas_total' => 0,
        'gastos_n' => 0,
        'gastos_total' => 0,
        'cierres' => [],
        'facturas' => [],
        'notas_n' => 0,
        'notas_total' => 0,
        'fiado' => 0,
    ];
}
if (!isset($mes)) {
    $mes = date('Y-m');
}
$nombresEstado = [
    'generada' => 'En el local',
    'enviada' => 'Enviada',
    'aceptada' => 'Aceptada',
    'rechazada' => 'Rechazada',
];
?>

<div class="main-container informe-mes">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2><i class="bi bi-graph-up"></i> Informe del mes<?php $ayuda = 'Resumen del mes: ventas, gastos, cierres y facturas. Cambia el mes y pulsa Guardar PDF para imprimirlo.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Guardar PDF</button>
    </div>

    <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="row g-3 mb-4 no-print">
        <input type="hidden" name="action" value="informes">
        <div class="col-md-3">
            <label class="form-label" for="mes">Mes</label>
            <input type="month" class="form-control" id="mes" name="mes" value="<?php echo htmlspecialchars($mes); ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-info"><i class="bi bi-search"></i> Ver mes</button>
        </div>
    </form>

    <p class="mb-4">Del <?php echo date('d/m/Y', strtotime($informe['desde'])); ?> al <?php echo date('d/m/Y', strtotime($informe['hasta'])); ?>. Nunca Jamás.</p>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Ventas (<?php echo (int) $informe['ventas_n']; ?>)</div><strong><?php echo pesos($informe['ventas_total']); ?></strong></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Gastos (<?php echo (int) $informe['gastos_n']; ?>)</div><strong><?php echo pesos($informe['gastos_total']); ?></strong></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Notas crédito (<?php echo (int) $informe['notas_n']; ?>)</div><strong><?php echo pesos($informe['notas_total']); ?></strong></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Cartera de fiado a hoy</div><strong><?php echo pesos($informe['fiado']); ?></strong></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Documentos electrónicos</h5></div>
        <div class="card-body">
            <?php if (empty($informe['facturas'])): ?>
            <p class="mb-0">Este mes no se generó ningún documento.</p>
            <?php else: ?>
            <ul class="mb-0">
                <?php foreach ($informe['facturas'] as $fila): ?>
                <li><?php echo htmlspecialchars($nombresEstado[$fila['estado']] ?? $fila['estado']); ?>: <?php echo (int) $fila['n']; ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Cierres de caja</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Esperado</th>
                            <th>Contado</th>
                            <th>Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($informe['cierres'])): ?>
                        <tr><td colspan="4">Este mes no hay cierres de caja.</td></tr>
                        <?php else: ?>
                        <?php foreach ($informe['cierres'] as $cierre): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($cierre['fecha'])); ?></td>
                            <td><?php echo pesos($cierre['esperado']); ?></td>
                            <td><?php echo pesos($cierre['contado']); ?></td>
                            <td><?php echo pesos($cierre['diferencia']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
