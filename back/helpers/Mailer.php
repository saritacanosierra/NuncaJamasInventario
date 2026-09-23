<?php

class Mailer {
    public static function enviar($destino, $asunto, $texto) {
        $config = self::config();
        $fromEmail = $config['from_email'] ?? '';
        $fromName = $config['from_name'] ?? 'Ropa Infantil';
        if ($fromEmail === '' || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!empty($config['smtp_host']) && !empty($config['smtp_password'])) {
            return self::enviarSmtp($config, $destino, $asunto, $texto, $fromEmail, $fromName);
        }

        error_log('Mailer: falta smtp_host o smtp_password en config/mail.php');
        return false;
    }

    private static function config() {
        $archivo = BASE_DIR . '/back/config/mail.php';
        if (!is_file($archivo)) {
            return [];
        }
        $config = require $archivo;
        if (!is_array($config)) {
            return [];
        }
        $local = BASE_DIR . '/back/config/mail.local.php';
        if (is_file($local)) {
            $extra = require $local;
            if (is_array($extra)) {
                $config = array_merge($config, $extra);
            }
        }
        return $config;
    }

    private static function encabezado($nombre, $email) {
        $limpio = str_replace(["\r", "\n"], '', $nombre);
        return $limpio . ' <' . $email . '>';
    }

    private static function enviarSmtp($config, $destino, $asunto, $texto, $fromEmail, $fromName) {
        $host = $config['smtp_host'];
        $port = (int) ($config['smtp_port'] ?? 587);
        $secure = $config['smtp_secure'] ?? 'tls';
        $remoto = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($remoto, $errno, $error, 15, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            error_log('Mailer SMTP: ' . $error);
            return false;
        }
        stream_set_timeout($socket, 15);

        $leer = function () use ($socket) {
            $respuesta = '';
            while (($linea = fgets($socket, 515)) !== false) {
                $respuesta .= $linea;
                if (isset($linea[3]) && $linea[3] === ' ') {
                    break;
                }
            }
            return $respuesta;
        };
        $enviar = function ($comando) use ($socket, $leer) {
            fwrite($socket, $comando . "\r\n");
            return $leer();
        };

        $saludo = $leer();
        if (strpos($saludo, '220') !== 0) {
            fclose($socket);
            return false;
        }

        $enviar('EHLO localhost');
        if ($secure === 'tls') {
            $tls = $enviar('STARTTLS');
            if (strpos($tls, '220') !== 0) {
                fclose($socket);
                return false;
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return false;
            }
            $enviar('EHLO localhost');
        }

        $usuario = $config['smtp_user'] ?? '';
        $clave = $config['smtp_password'] ?? '';
        if ($usuario !== '') {
            $auth = $enviar('AUTH LOGIN');
            if (strpos($auth, '334') !== 0) {
                fclose($socket);
                return false;
            }
            $enviar(base64_encode($usuario));
            $claveResp = $enviar(base64_encode($clave));
            if (strpos($claveResp, '235') !== 0) {
                error_log('Mailer SMTP: autenticación rechazada');
                fclose($socket);
                return false;
            }
        }

        $correo = $enviar('MAIL FROM:<' . $fromEmail . '>');
        $rcpt = $enviar('RCPT TO:<' . $destino . '>');
        $data = $enviar('DATA');
        if (strpos($correo, '250') !== 0 || strpos($rcpt, '250') !== 0 || strpos($data, '354') !== 0) {
            fclose($socket);
            return false;
        }

        $mensaje = 'From: ' . self::encabezado($fromName, $fromEmail) . "\r\n";
        $mensaje .= 'To: <' . $destino . ">\r\n";
        $mensaje .= 'Subject: =?UTF-8?B?' . base64_encode($asunto) . "?=\r\n";
        $mensaje .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $mensaje .= "\r\n" . str_replace("\n.", "\n..", $texto) . "\r\n.";
        $cierre = $enviar($mensaje);
        $enviar('QUIT');
        fclose($socket);
        return strpos($cierre, '250') === 0;
    }
}
