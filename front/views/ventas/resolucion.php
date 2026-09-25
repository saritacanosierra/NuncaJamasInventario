<?php
$pageTitle = 'Resolución';
require_once BASE_DIR . '/front/views/layout/header.php';
$r = $resolucion ?? null;
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-text"></i> Resolución de facturación<?php $ayuda = 'Datos para armar el número, el CUFE y el XML. La factura solo queda aceptada si la DIAN responde que es válida.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <a href="<?php echo BASE_URL; ?>index.php?action=ventas&method=historial" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Historial
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-0">Con estos datos la venta arma el número, el CUFE y el XML. El envío a la DIAN usa el identificador de software, el set de pruebas y el certificado. La factura solo queda aceptada si la DIAN responde que es válida.</p>
        </div>
    </div>

    <?php if (tienePermiso('ventas_resolucion:edit')): ?>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=ventas&method=guardarResolucion">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="numero">Número de resolución</label>
                        <input type="text" class="form-control" id="numero" name="numero" required value="<?php echo htmlspecialchars($r['numero'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="prefijo">Prefijo</label>
                        <input type="text" class="form-control" id="prefijo" name="prefijo" required maxlength="10" value="<?php echo htmlspecialchars($r['prefijo'] ?? 'FE'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="desde_numero">Desde</label>
                        <input type="number" class="form-control" id="desde_numero" name="desde_numero" min="1" required value="<?php echo (int) ($r['desde_numero'] ?? 1); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="hasta_numero">Hasta</label>
                        <input type="number" class="form-control" id="hasta_numero" name="hasta_numero" min="1" required value="<?php echo (int) ($r['hasta_numero'] ?? 1000); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="fecha_desde">Vigente desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" required value="<?php echo htmlspecialchars($r['fecha_desde'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="fecha_hasta">Vigente hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" required value="<?php echo htmlspecialchars($r['fecha_hasta'] ?? date('Y-12-31')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="nit">NIT emisor</label>
                        <input type="text" class="form-control" id="nit" name="nit" required value="<?php echo htmlspecialchars($r['nit'] ?? '21468472'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="ambiente">Ambiente</label>
                        <select class="form-select" id="ambiente" name="ambiente">
                            <option value="2" <?php echo (int) ($r['ambiente'] ?? 2) === 2 ? 'selected' : ''; ?>>Habilitación</option>
                            <option value="1" <?php echo (int) ($r['ambiente'] ?? 2) === 1 ? 'selected' : ''; ?>>Producción</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="razon_social">Razón social</label>
                        <input type="text" class="form-control" id="razon_social" name="razon_social" required value="<?php echo htmlspecialchars($r['razon_social'] ?? 'Liliana Maria Sierra'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="clave_tecnica">Clave técnica</label>
                        <input type="text" class="form-control" id="clave_tecnica" name="clave_tecnica" required value="<?php echo htmlspecialchars($r['clave_tecnica'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="software_id">Identificador de software</label>
                        <input type="text" class="form-control" id="software_id" name="software_id" value="<?php echo htmlspecialchars($r['software_id'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="software_pin">PIN del software</label>
                        <input type="text" class="form-control" id="software_pin" name="software_pin" value="<?php echo htmlspecialchars($r['software_pin'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="set_pruebas">Set de pruebas</label>
                        <input type="text" class="form-control" id="set_pruebas" name="set_pruebas" value="<?php echo htmlspecialchars($r['set_pruebas'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar resolución</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (tienePermiso('ventas_resolucion:edit')): ?>
    <div class="card mt-4">
        <div class="card-body">
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=ventas&method=guardarCertificado" enctype="multipart/form-data" class="row g-3">
                <?php echo csrf_field(); ?>
                <div class="col-md-6">
                    <label class="form-label" for="certificado">Certificado digital (.p12)</label>
                    <input type="file" class="form-control" id="certificado" name="certificado" accept=".p12,.pfx" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="certificado_clave">Clave del certificado</label>
                    <input type="password" class="form-control" id="certificado_clave" name="certificado_clave" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
