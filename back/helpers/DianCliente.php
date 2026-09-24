<?php
/**
 * Envía el XML al servicio de la DIAN y devuelve la respuesta sin cambiarla.
 * Aceptada solo si la DIAN dice que el documento es válido.
 */

class DianCliente {
    public static function faltas($resolucion) {
        $faltas = [];
        if (trim((string) ($resolucion['software_id'] ?? '')) === '') {
            $faltas[] = 'el identificador de software';
        }
        if (trim((string) ($resolucion['software_pin'] ?? '')) === '') {
            $faltas[] = 'el PIN del software';
        }
        if ((int) ($resolucion['ambiente'] ?? 2) === 2 && trim((string) ($resolucion['set_pruebas'] ?? '')) === '') {
            $faltas[] = 'el set de pruebas';
        }
        if (!is_file(self::rutaCertificado())) {
            $faltas[] = 'el certificado .p12';
        }
        if (self::claveCertificado() === '') {
            $faltas[] = 'la clave del certificado';
        }
        return $faltas;
    }

    public static function rutaCertificado() {
        return BASE_DIR . '/back/storage/dian/certificado.p12';
    }

    public static function claveCertificado() {
        $ruta = BASE_DIR . '/back/config/dian.local.php';
        if (!is_file($ruta)) {
            return '';
        }
        $datos = require $ruta;
        if (!is_array($datos)) {
            return '';
        }
        return (string) ($datos['cert_pass'] ?? '');
    }

    public static function guardarCertificado($binario, $clave) {
        $certs = [];
        if (!openssl_pkcs12_read($binario, $certs, $clave)) {
            return 'La clave no abre ese certificado.';
        }
        $dir = dirname(self::rutaCertificado());
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            return 'No se pudo guardar el certificado.';
        }
        if (file_put_contents(self::rutaCertificado(), $binario) === false) {
            return 'No se pudo guardar el certificado.';
        }
        $local = BASE_DIR . '/back/config/dian.local.php';
        $php = "<?php\nreturn " . var_export(['cert_pass' => $clave], true) . ";\n";
        if (file_put_contents($local, $php) === false) {
            return 'El certificado se guardó, pero no la clave.';
        }
        return null;
    }

    public static function enviar($xml, $nombre, $resolucion) {
        $faltas = self::faltas($resolucion);
        if ($faltas) {
            return [
                'ok' => false,
                'estado' => 'generada',
                'mensaje' => 'Falta ' . implode(', ', $faltas) . ' para que la DIAN reciba el documento.',
                'respuesta' => '',
                'zip_key' => null,
            ];
        }

        $ambiente = (int) ($resolucion['ambiente'] ?? 2) === 1 ? 1 : 2;
        $archivo = $nombre . '.xml';
        $zip = self::zipBase64($xml, $archivo);
        if ($zip === null) {
            return [
                'ok' => false,
                'estado' => 'generada',
                'mensaje' => 'No se pudo armar el archivo para la DIAN.',
                'respuesta' => '',
                'zip_key' => null,
            ];
        }

        if ($ambiente === 2) {
            $endpoint = 'https://vpfe-hab.dian.gov.co/WcfDianCustomerServices.svc';
            $action = 'http://wcf.dian.colombia/IWcfDianCustomerServices/SendTestSetAsync';
            $cuerpo = self::sobrePruebas($archivo, $zip, (string) $resolucion['set_pruebas']);
        } else {
            $endpoint = 'https://vpfe.dian.gov.co/WcfDianCustomerServices.svc';
            $action = 'http://wcf.dian.colombia/IWcfDianCustomerServices/SendBillSync';
            $cuerpo = self::sobreProduccion($archivo, $zip);
        }

        $envio = self::post($endpoint, $action, $cuerpo);
        return self::interpretar($envio['cuerpo'], $envio['error']);
    }

    private static function zipBase64($xml, $nombre) {
        if (!class_exists('ZipArchive')) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'dian');
        if ($tmp === false) {
            return null;
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            return null;
        }
        $zip->addFromString($nombre, $xml);
        $zip->close();
        $binario = file_get_contents($tmp);
        unlink($tmp);
        if ($binario === false) {
            return null;
        }
        return base64_encode($binario);
    }

    private static function sobrePruebas($archivo, $zip, $setPruebas) {
        return self::sobre(
            '<wcf:SendTestSetAsync>'
            . '<wcf:fileName>' . self::xml($archivo) . '</wcf:fileName>'
            . '<wcf:contentFile>' . $zip . '</wcf:contentFile>'
            . '<wcf:testSetId>' . self::xml($setPruebas) . '</wcf:testSetId>'
            . '</wcf:SendTestSetAsync>'
        );
    }

    private static function sobreProduccion($archivo, $zip) {
        return self::sobre(
            '<wcf:SendBillSync>'
            . '<wcf:fileName>' . self::xml($archivo) . '</wcf:fileName>'
            . '<wcf:contentFile>' . $zip . '</wcf:contentFile>'
            . '</wcf:SendBillSync>'
        );
    }

    private static function sobre($cuerpo) {
        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wcf="http://wcf.dian.colombia">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $cuerpo . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private static function post($endpoint, $action, $cuerpo) {
        if (!function_exists('curl_init')) {
            return ['cuerpo' => '', 'error' => 'El servidor no tiene cURL.'];
        }
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "' . $action . '"',
            ],
        ]);
        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($respuesta === false) {
            return ['cuerpo' => '', 'error' => $error !== '' ? $error : 'sin respuesta'];
        }
        return ['cuerpo' => (string) $respuesta, 'error' => null];
    }

    private static function interpretar($crudo, $errorRed) {
        if ($errorRed !== null) {
            return [
                'ok' => false,
                'estado' => 'generada',
                'mensaje' => 'No se pudo contactar a la DIAN. ' . $errorRed,
                'respuesta' => $errorRed,
                'zip_key' => null,
            ];
        }

        $texto = trim(strip_tags($crudo));
        if (strlen($texto) > 4000) {
            $texto = substr($texto, 0, 4000);
        }

        if (preg_match('/<faultstring[^>]*>(.*?)<\/faultstring>/si', $crudo, $falla)) {
            $mensaje = trim(html_entity_decode(strip_tags($falla[1])));
            return [
                'ok' => false,
                'estado' => 'rechazada',
                'mensaje' => 'La DIAN rechazó el envío: ' . self::corto($mensaje),
                'respuesta' => $texto,
                'zip_key' => null,
            ];
        }

        $zip = null;
        if (preg_match('/<[^>]*ZipKey[^>]*>([^<]+)</', $crudo, $clave)) {
            $zip = trim($clave[1]);
        }

        $valido = false;
        if (preg_match('/<[^>]*IsValid[^>]*>([^<]+)</i', $crudo, $marca)) {
            $valido = strtolower(trim($marca[1])) === 'true';
        }
        if (preg_match('/<[^>]*StatusCode[^>]*>([^<]+)</', $crudo, $codigo) && trim($codigo[1]) === '00') {
            $valido = true;
        }

        if ($valido) {
            return [
                'ok' => true,
                'estado' => 'aceptada',
                'mensaje' => 'La DIAN aceptó el documento.',
                'respuesta' => $texto,
                'zip_key' => $zip,
            ];
        }
        if ($zip) {
            return [
                'ok' => true,
                'estado' => 'enviada',
                'mensaje' => 'La DIAN recibió el archivo. Todavía no confirma que sea válido.',
                'respuesta' => $texto,
                'zip_key' => $zip,
            ];
        }

        return [
            'ok' => false,
            'estado' => 'rechazada',
            'mensaje' => 'La DIAN no aceptó el documento.',
            'respuesta' => $texto !== '' ? $texto : 'Respuesta vacía',
            'zip_key' => null,
        ];
    }

    private static function xml($valor) {
        return htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function corto($texto) {
        if (strlen($texto) > 280) {
            return substr($texto, 0, 280);
        }
        return $texto;
    }
}
