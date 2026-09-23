<?php
/**
 * Componente Modal Genérico Reutilizable
 * 
 * @param string $modalId - ID único del modal
 * @param string $title - Título del modal (puede contener HTML)
 * @param string $body - Contenido del body (HTML)
 * @param string $footer - Contenido del footer (HTML, opcional)
 * @param string $size - Tamaño del modal: 'sm', 'lg', 'xl' o vacío para normal
 * @param bool $scrollable - Si el modal es scrollable
 */
$modalId = $modalId ?? 'modal';
$title = $title ?? 'Modal';
$body = $body ?? '';
$footer = $footer ?? '';
$size = $size ?? '';
$scrollable = $scrollable ?? false;

$sizeClass = $size ? 'modal-' . $size : '';
$scrollableClass = $scrollable ? 'modal-dialog-scrollable' : '';
?>

<div class="modal fade" id="<?php echo htmlspecialchars($modalId); ?>" tabindex="-1" aria-labelledby="<?php echo htmlspecialchars($modalId); ?>Label" aria-hidden="true">
    <div class="modal-dialog <?php echo $sizeClass . ' ' . $scrollableClass; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?php echo htmlspecialchars($modalId); ?>Label">
                    <?php echo $title; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo $body; ?>
            </div>
            <?php if ($footer): ?>
            <div class="modal-footer">
                <?php echo $footer; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

