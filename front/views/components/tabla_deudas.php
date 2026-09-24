<?php
if (!isset($facturasPendientes) || !is_array($facturasPendientes)) {
    $facturasPendientes = [];
}
?>
<?php if (empty($facturasPendientes)): ?>
<p class="mb-0 text-muted">Nadie tiene saldo pendiente.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover mb-0" id="tablaDeudas">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Factura</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Abonado</th>
                <th>Falta</th>
                <th>Abonos de esta factura</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($facturasPendientes as $fila): ?>
            <?php
                $busca = strtolower(
                    ($fila['nombre_completo'] ?? '') . ' '
                    . ($fila['cedula_nit'] ?? '') . ' '
                    . ($fila['numero_factura'] ?? '')
                );
            ?>
            <tr data-busca="<?php echo htmlspecialchars($busca); ?>">
                <td>
                    <?php echo htmlspecialchars($fila['nombre_completo']); ?>
                    <div><code><?php echo htmlspecialchars($fila['cedula_nit']); ?></code></div>
                </td>
                <td><code><?php echo htmlspecialchars($fila['numero_factura']); ?></code></td>
                <td><?php echo date('d/m/Y', strtotime($fila['fecha_venta'])); ?></td>
                <td><?php echo pesos($fila['total']); ?></td>
                <td><?php echo pesos($fila['abonado']); ?></td>
                <td><strong><?php echo pesos($fila['saldo']); ?></strong></td>
                <td>
                    <?php if (empty($fila['abonos'])): ?>
                    <span class="text-muted">Sin abonos</span>
                    <?php else: ?>
                        <?php foreach ($fila['abonos'] as $abono): ?>
                        <div>
                            <?php echo date('d/m/Y', strtotime($abono['creado_en'])); ?>
                            · <?php echo pesos($abono['monto']); ?>
                            <?php if ($abono['nota'] !== '' && $abono['nota'] !== null): ?>
                            · <?php echo htmlspecialchars($abono['nota']); ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="btn btn-primary" href="<?php echo BASE_URL; ?>index.php?action=clientes&method=historial&id=<?php echo (int) $fila['cliente_id']; ?>#abonos">
                        <i class="bi bi-save"></i> Abonar
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr id="deudasSinCoincidencia" class="d-none">
                <td colspan="8" class="text-center text-muted">Ningún cliente con deuda coincide con la búsqueda.</td>
            </tr>
        </tbody>
    </table>
</div>
<?php endif; ?>
