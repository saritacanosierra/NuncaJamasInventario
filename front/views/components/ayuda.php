<?php
$ayuda = trim((string) ($ayuda ?? ''));
if ($ayuda !== ''):
?>
<span class="ayuda">
    <button type="button" class="ayuda-icono" aria-label="Ayuda" aria-expanded="false">
        <i class="bi bi-question-lg"></i>
    </button>
    <span class="ayuda-tarjeta" role="tooltip"><?php echo htmlspecialchars($ayuda, ENT_QUOTES, 'UTF-8'); ?></span>
</span>
<?php
endif;
unset($ayuda);
