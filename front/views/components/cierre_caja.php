<?php
/**
 * Pasos del cierre de caja de un día.
 *
 * @var string $fechaCaja Y-m-d
 * @var array|null $cierreRegistro
 * @var array|null $cierreResumen
 * @var array $cierreHistorial
 */
if (!isset($fechaCaja) || !is_string($fechaCaja) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCaja)) {
    $fechaCaja = date('Y-m-d');
}
if (!isset($cierreRegistro) || !is_array($cierreRegistro)) {
    $cierreRegistro = null;
}
if (!isset($cierreResumen) || !is_array($cierreResumen)) {
    $cierreResumen = [
        'ventas_efectivo' => 0,
        'gastos' => 0,
        'compras_caja' => 0,
        'ventas_otros' => 0,
        'ventas_fiado' => 0,
        'ventas_contra_entrega' => 0,
    ];
}
if (!isset($cierreHistorial) || !is_array($cierreHistorial)) {
    $cierreHistorial = [];
}

$baseVista = $cierreRegistro ? (float) $cierreRegistro['base'] : 0;
$efectivo = $cierreRegistro ? (float) $cierreRegistro['ventas_efectivo'] : (float) $cierreResumen['ventas_efectivo'];
$gastosDia = $cierreRegistro ? (float) $cierreRegistro['gastos'] : (float) $cierreResumen['gastos'];
$comprasDia = $cierreRegistro ? (float) $cierreRegistro['compras_caja'] : (float) $cierreResumen['compras_caja'];
$otros = $cierreRegistro ? (float) $cierreRegistro['ventas_otros'] : (float) $cierreResumen['ventas_otros'];
$fiado = $cierreRegistro ? (float) $cierreRegistro['ventas_fiado'] : (float) $cierreResumen['ventas_fiado'];
$contra = $cierreRegistro ? (float) $cierreRegistro['ventas_contra_entrega'] : (float) $cierreResumen['ventas_contra_entrega'];
$esperado = $cierreRegistro ? (float) $cierreRegistro['esperado'] : round($baseVista + $efectivo - $gastosDia - $comprasDia, 2);
$diaTexto = date('d/m/Y', strtotime($fechaCaja));
?>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">1. Elige el día<?php $ayuda = 'Elige la fecha y pulsa Ver este día. Si ese día ya se cerró, verás cómo quedó.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h5></div>
    <div class="card-body">
        <p class="text-muted">Abre el día que vas a cerrar. Si ya se cerró, verás cómo quedó.</p>
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="row g-3">
            <input type="hidden" name="action" value="caja">
            <div class="col-md-3">
                <label class="form-label" for="fecha_caja">Día</label>
                <input type="date" class="form-control" id="fecha_caja" name="fecha" value="<?php echo htmlspecialchars($fechaCaja); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-info"><i class="bi bi-search"></i> Ver este día</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">2. Revisa el <?php echo $diaTexto; ?><?php $ayuda = 'El efectivo, los gastos y las compras de caja mueven el cajón. Tarjeta, transferencia, fiado y contra entrega se muestran para no mezclarlos con la plata contada.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h5></div>
    <div class="card-body">
        <p class="text-muted">Solo el efectivo entra y sale del cajón. El resto se anota para que no se mezcle con la plata contada.</p>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">Efectivo vendido</div>
                    <strong><?php echo pesos($efectivo); ?></strong>
                    <p class="mb-0 mt-2 small text-muted">Entró al cajón.</p>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">Tarjeta, transferencia y mixto</div>
                    <strong><?php echo pesos($otros); ?></strong>
                    <p class="mb-0 mt-2 small text-muted">No está en el cajón.</p>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">Fiado</div>
                    <strong><?php echo pesos($fiado); ?></strong>
                    <p class="mb-0 mt-2 small text-muted">Quedó por cobrar. No entra hoy.</p>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">Contra entrega</div>
                    <strong><?php echo pesos($contra); ?></strong>
                    <p class="mb-0 mt-2 small text-muted">Se cobra cuando se entrega.</p>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">Gastos del día</div>
                    <strong><?php echo pesos($gastosDia); ?></strong>
                    <p class="mb-0 mt-2 small text-muted">Salieron del cajón.</p>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">Compras pagadas de la caja</div>
                    <strong><?php echo pesos($comprasDia); ?></strong>
                    <p class="mb-0 mt-2 small text-muted">También salieron del cajón.</p>
                </div></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">3. Cuenta y cierra<?php $ayuda = 'Escribe la base con la que abriste y cuenta el efectivo que hay ahora. Lo esperado es base + efectivo vendido − gastos − compras de la caja. Cerrar queda una sola vez.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h5></div>
    <div class="card-body">
        <?php if ($cierreRegistro): ?>
        <?php
        $diferencia = (float) $cierreRegistro['diferencia'];
        if ($diferencia > 0) {
            $lectura = 'Sobra ' . pesos($diferencia) . ' en el cajón.';
        } elseif ($diferencia < 0) {
            $lectura = 'Faltan ' . pesos(abs($diferencia)) . ' en el cajón.';
        } else {
            $lectura = 'El cajón cuadra.';
        }
        ?>
        <p>El <?php echo $diaTexto; ?> ya está cerrado<?php echo !empty($cierreRegistro['usuario_nombre']) ? ' por ' . htmlspecialchars($cierreRegistro['usuario_nombre']) : ''; ?>.</p>
        <p class="mb-1"><strong>Base:</strong> <?php echo pesos($cierreRegistro['base']); ?></p>
        <p class="mb-1"><strong>Esperado en el cajón:</strong> <?php echo pesos($cierreRegistro['esperado']); ?></p>
        <p class="mb-1"><strong>Efectivo contado:</strong> <?php echo pesos($cierreRegistro['contado']); ?></p>
        <p class="mb-0"><strong>Diferencia:</strong> <?php echo pesos($cierreRegistro['diferencia']); ?>. <?php echo $lectura; ?></p>
        <?php if (!empty($cierreRegistro['observacion'])): ?>
        <p class="mt-3 mb-0"><?php echo htmlspecialchars($cierreRegistro['observacion']); ?></p>
        <?php endif; ?>
        <?php elseif (tienePermiso('caja_cierre:decide')): ?>
        <ol class="text-muted">
            <li>Escribe la base: el efectivo con el que abriste.</li>
            <li>Cuenta billetes y monedas y anota ese total.</li>
            <li>El esperado se calcula solo: base + efectivo vendido − gastos − compras de la caja.</li>
            <li>Cierra el día. Queda una sola vez.</li>
        </ol>
        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=caja&method=cerrar" id="formCierre">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fechaCaja); ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="base">Base del día</label>
                    <input type="number" class="form-control" id="base" name="base" min="0" step="1" value="0" required>
                    <small class="text-muted">Con cuánto abriste el cajón.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="contado">Efectivo contado</label>
                    <input type="number" class="form-control" id="contado" name="contado" min="0" step="1" value="0" required>
                    <small class="text-muted">Lo que hay ahora en el cajón.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Esperado</label>
                    <p class="form-control-plaintext mb-0" id="esperado_caja" data-efectivo="<?php echo (int) round($efectivo); ?>" data-gastos="<?php echo (int) round($gastosDia); ?>" data-compras="<?php echo (int) round($comprasDia); ?>"><?php echo pesos($esperado); ?></p>
                    <small class="text-muted">Base + efectivo − gastos − compras.</small>
                </div>
                <div class="col-12">
                    <p class="mb-0" id="lectura_caja">Con ese conteo, el cajón cuadra.</p>
                </div>
                <div class="col-12">
                    <label class="form-label" for="observacion">Observación</label>
                    <textarea class="form-control" id="observacion" name="observacion" rows="2" placeholder="Opcional. Por ejemplo, si sobró o faltó plata."></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-lock"></i> Cerrar el <?php echo $diaTexto; ?></button>
                </div>
            </div>
        </form>
        <?php else: ?>
        <p class="mb-0">Este día sigue abierto. Quien administra puede cerrarlo.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">4. Días ya cerrados<?php $ayuda = 'Días que ya se cerraron. Abre uno para ver la base, lo contado y si cuadró, sobró o faltó.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h5></div>
    <div class="card-body">
        <p class="text-muted">Abre un día para ver la base, lo contado y si cuadró.</p>
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
                    <?php if (empty($cierreHistorial)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Todavía no hay cierres</td></tr>
                    <?php else: ?>
                    <?php foreach ($cierreHistorial as $filaCierre): ?>
                    <tr>
                        <td><a href="<?php echo BASE_URL; ?>index.php?action=caja&amp;fecha=<?php echo htmlspecialchars($filaCierre['fecha']); ?>"><?php echo date('d/m/Y', strtotime($filaCierre['fecha'])); ?></a></td>
                        <td><?php echo pesos($filaCierre['esperado']); ?></td>
                        <td><?php echo pesos($filaCierre['contado']); ?></td>
                        <td><?php echo pesos($filaCierre['diferencia']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>front/public/js/caja.js?v=2"></script>
