<?php
$paginacionPor = (int) ($paginacionPor ?? 10);
if ($paginacionPor < 1) {
    $paginacionPor = 10;
}
?>
<nav class="paginacion" data-por="<?php echo $paginacionPor; ?>" aria-label="Páginas" hidden>
    <button type="button" class="btn btn-outline-secondary paginacion-flecha paginacion-anterior" title="Anterior" disabled>
        <i class="bi bi-chevron-left"></i>
    </button>
    <span class="paginacion-paginas"></span>
    <button type="button" class="btn btn-outline-secondary paginacion-flecha paginacion-siguiente" title="Siguiente">
        <i class="bi bi-chevron-right"></i>
    </button>
    <span class="paginacion-resumen"></span>
</nav>
<?php unset($paginacionPor); ?>
