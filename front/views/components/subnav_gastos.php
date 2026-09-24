<?php
$vistaGastos = $vistaGastos ?? 'gastos';
?>
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php if (puedeVerModulo('gastos')): ?>
    <a class="btn <?php echo $vistaGastos === 'gastos' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo BASE_URL; ?>index.php?action=gastos">
        <i class="bi bi-cash-stack"></i> Gastos e inversiones
    </a>
    <?php endif; ?>
    <?php if (tienePermiso('compras_registro:view') || tienePermiso('compras:view')): ?>
    <a class="btn <?php echo $vistaGastos === 'compras' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo BASE_URL; ?>index.php?action=compras">
        <i class="bi bi-bag-plus"></i> Compras de producto
    </a>
    <?php endif; ?>
    <?php if (tienePermiso('compras_kardex:view')): ?>
    <a class="btn <?php echo $vistaGastos === 'kardex' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo BASE_URL; ?>index.php?action=compras&method=kardex">
        <i class="bi bi-journal-text"></i> Kardex
    </a>
    <?php endif; ?>
</div>
