<?php
if (empty($scriptPartes) || !is_array($scriptPartes)) {
    return;
}
$scriptVersion = isset($scriptVersion) ? (string) $scriptVersion : '1';
foreach ($scriptPartes as $relativa) {
    $ruta = ltrim(str_replace('\\', '/', (string) $relativa), '/');
    echo '<script src="' . htmlspecialchars(BASE_URL . $ruta . '?v=' . $scriptVersion) . '"></script>' . "\n";
}
unset($scriptPartes, $scriptVersion);
