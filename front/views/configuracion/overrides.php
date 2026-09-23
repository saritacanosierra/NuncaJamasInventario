<?php
$prefijo = $prefijo ?? 'nuevo';
$iconosAccion = [
    'view' => 'bi-eye',
    'create' => 'bi-plus-circle',
    'edit' => 'bi-pencil',
    'delete' => 'bi-trash',
    'decide' => 'bi-check-circle',
];
$seccionesOverride = [];
foreach (permisos_catalogo() as $grupo) {
    $seccionesOverride[$grupo['sectionTitle']][$grupo['workspaceViewKey']][] = $grupo;
}
?>
<div data-overrides="<?php echo htmlspecialchars($prefijo); ?>">
    <div class="permisos-activos">
        <div class="fw-semibold">Permisos activos: <span id="contador_<?php echo htmlspecialchars($prefijo); ?>">0</span></div>
        <div class="permisos-chips" id="preview_<?php echo htmlspecialchars($prefijo); ?>"></div>
    </div>
    <p class="small text-muted">El ícono encendido es un permiso activo. Si el rol ya lo trae, apagarlo lo revoca. Si el rol no lo trae, encenderlo lo concede como extra.</p>
    <div class="permisos-matriz">
        <?php foreach ($seccionesOverride as $sectionTitle => $workspaces): ?>
            <?php foreach ($workspaces as $grupos): ?>
                <?php
                $vista = null;
                foreach ($grupos as $grupo) {
                    if ($grupo['groupKind'] === 'workspace') {
                        $vista = $grupo;
                    }
                }
                $tituloWorkspace = $vista['title'] ?? $sectionTitle;
                $encabezado = strtoupper($sectionTitle);
                if (strcasecmp($sectionTitle, $tituloWorkspace) !== 0) {
                    $encabezado .= ' · ' . strtoupper($tituloWorkspace);
                }
                ?>
                <section class="permisos-seccion">
                <button type="button" class="permisos-seccion-titulo permisos-seccion-head" onclick="this.closest('.permisos-seccion').classList.toggle('abierto')">
                    <span><?php echo htmlspecialchars($encabezado); ?></span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="permisos-seccion-body">
                <?php foreach ($grupos as $grupo): ?>
                    <?php
                    $porAccion = [];
                    foreach ($grupo['items'] as $item) {
                        $accion = explode(':', $item['slug'], 2)[1];
                        $porAccion[$accion] = $item;
                    }
                    $nombreFila = ($grupo['groupKind'] === 'workspace' ? 'Vista · ' : '↳ ') . $grupo['title'];
                    ?>
                    <div class="permisos-icono-fila">
                        <span><?php echo htmlspecialchars($nombreFila); ?></span>
                        <span class="permisos-iconos">
                            <?php foreach ($porAccion as $accion => $item): ?>
                                <?php $idBase = $prefijo . '_' . str_replace(':', '_', $item['slug']); ?>
                                <button type="button"
                                        class="permiso-icono accion-<?php echo htmlspecialchars($accion); ?>"
                                        data-slug="<?php echo htmlspecialchars($item['slug']); ?>"
                                        title="<?php echo htmlspecialchars($item['description']); ?>"
                                        aria-pressed="false">
                                    <i class="bi <?php echo $iconosAccion[$accion] ?? 'bi-circle'; ?>"></i>
                                </button>
                                <input class="extra-permiso d-none" type="checkbox" name="extra[]" value="<?php echo htmlspecialchars($item['slug']); ?>" data-slug="<?php echo htmlspecialchars($item['slug']); ?>" id="extra_<?php echo $idBase; ?>">
                                <input class="revocado-permiso d-none" type="checkbox" name="revocado[]" value="<?php echo htmlspecialchars($item['slug']); ?>" data-slug="<?php echo htmlspecialchars($item['slug']); ?>" id="revocado_<?php echo $idBase; ?>">
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                </div>
                </section>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>
