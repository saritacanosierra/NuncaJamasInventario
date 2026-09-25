<?php

/**
 * Datos para enlazar la caja con WooCommerce.
 * La clave no se sube al repositorio: vive en wordpress.local.php.
 */
class WordpressEnlace {
    public static function vacio() {
        return [
            'enabled' => false,
            'url' => '',
            'consumer_key' => '',
            'consumer_secret' => '',
            'webhook_secret' => '',
        ];
    }

    public static function ruta() {
        return BASE_DIR . '/back/config/wordpress.local.php';
    }

    public static function leer() {
        $ruta = self::ruta();
        if (!is_file($ruta)) {
            return self::vacio();
        }
        $cfg = require $ruta;
        if (!is_array($cfg)) {
            return self::vacio();
        }
        return array_merge(self::vacio(), [
            'enabled' => !empty($cfg['enabled']),
            'url' => self::urlLimpia($cfg['url'] ?? ''),
            'consumer_key' => trim((string) ($cfg['consumer_key'] ?? '')),
            'consumer_secret' => trim((string) ($cfg['consumer_secret'] ?? '')),
            'webhook_secret' => trim((string) ($cfg['webhook_secret'] ?? '')),
        ]);
    }

    public static function guardar(array $posteado, array $anterior) {
        $url = self::urlLimpia($posteado['url'] ?? '');
        if ($url === '' || !preg_match('#^https://#', $url)) {
            return 'La dirección de WordPress tiene que empezar por https://';
        }
        $clave = trim((string) ($posteado['consumer_key'] ?? ''));
        $secreto = trim((string) ($posteado['consumer_secret'] ?? ''));
        if ($clave === '') {
            $clave = $anterior['consumer_key'];
        }
        if ($secreto === '') {
            $secreto = $anterior['consumer_secret'];
        }
        if ($clave === '' || $secreto === '') {
            return 'Faltan la clave y el secreto de WooCommerce. Se crean en WordPress, en WooCommerce, Ajustes, Avanzado, API REST.';
        }
        $gancho = trim((string) ($posteado['webhook_secret'] ?? ''));
        if ($gancho === '') {
            $gancho = $anterior['webhook_secret'] !== '' ? $anterior['webhook_secret'] : bin2hex(random_bytes(16));
        }
        $datos = [
            'enabled' => !empty($posteado['enabled']),
            'url' => $url,
            'consumer_key' => $clave,
            'consumer_secret' => $secreto,
            'webhook_secret' => $gancho,
        ];
        $php = "<?php\n\nreturn " . var_export($datos, true) . ";\n";
        if (file_put_contents(self::ruta(), $php) === false) {
            return 'No se pudo guardar la conexión. Revisa que la carpeta back/config se pueda escribir.';
        }
        return '';
    }

    public static function probar(array $cfg) {
        if (empty($cfg['enabled'])) {
            return ['ok' => false, 'mensaje' => 'Activa la conexión y guárdala antes de probar.'];
        }
        if ($cfg['url'] === '' || $cfg['consumer_key'] === '' || $cfg['consumer_secret'] === '') {
            return ['ok' => false, 'mensaje' => 'Faltan la dirección, la clave o el secreto.'];
        }
        $destino = $cfg['url'] . '/wp-json/wc/v3/products?per_page=1';
        $contexto = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Basic ' . base64_encode($cfg['consumer_key'] . ':' . $cfg['consumer_secret']) . "\r\n",
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $cuerpo = @file_get_contents($destino, false, $contexto);
        $codigo = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $codigo = (int) $m[1];
        }
        if ($cuerpo === false || $codigo === 0) {
            return ['ok' => false, 'mensaje' => 'No hubo respuesta de WordPress. Revisa la dirección y que la tienda esté en línea.'];
        }
        if ($codigo === 401 || $codigo === 403) {
            return ['ok' => false, 'mensaje' => 'WordPress rechazó la clave. Revisa la clave y el secreto de la API REST, con permiso de lectura.'];
        }
        if ($codigo < 200 || $codigo >= 300) {
            return ['ok' => false, 'mensaje' => 'WordPress respondió con error ' . $codigo . '.'];
        }
        $lista = json_decode($cuerpo, true);
        if (!is_array($lista)) {
            return ['ok' => false, 'mensaje' => 'La tienda respondió, pero no devolvió el catálogo de WooCommerce.'];
        }
        return ['ok' => true, 'mensaje' => 'La tienda respondió. El enlace está listo para el siguiente paso: descontar el stock cuando se venda allá.'];
    }

    private static function urlLimpia($url) {
        $url = trim((string) $url);
        return rtrim($url, '/');
    }
}
