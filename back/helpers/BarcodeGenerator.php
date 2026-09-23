<?php
/**
 * Generador de códigos de barras EAN-13
 * Sistema de Inventario - Ropa Infantil
 */

class BarcodeGenerator {
    /**
     * Genera un código de barras EAN-13 único
     * @return string Código de barras de 13 dígitos
     */
    public static function generateEAN13() {
        // Prefijo para productos propios (200-299)
        $prefix = '200';
        
        // Generar 9 dígitos aleatorios
        $random = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        
        // Concatenar
        $code = $prefix . $random;
        
        // Calcular dígito de control
        $checkDigit = self::calculateCheckDigit($code);
        
        return $code . $checkDigit;
    }
    
    /**
     * Calcula el dígito de control para EAN-13
     * @param string $code Código de 12 dígitos
     * @return string Dígito de control
     */
    private static function calculateCheckDigit($code) {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$code[$i];
            // Multiplicar por 1 o 3 alternativamente
            $multiplier = ($i % 2 == 0) ? 1 : 3;
            $sum += $digit * $multiplier;
        }
        $remainder = $sum % 10;
        $checkDigit = ($remainder == 0) ? 0 : (10 - $remainder);
        return (string)$checkDigit;
    }
    
    /**
     * Valida un código EAN-13
     * @param string $code Código a validar
     * @return bool True si es válido
     */
    public static function validateEAN13($code) {
        if (strlen($code) != 13 || !ctype_digit($code)) {
            return false;
        }
        
        $checkDigit = self::calculateCheckDigit(substr($code, 0, 12));
        return $checkDigit == $code[12];
    }

    /**
     * Dibuja el código EAN-13 como SVG para verlo, descargarlo e imprimirlo.
     */
    public static function svg($code) {
        $code = preg_replace('/\D/', '', (string) $code);
        if (strlen($code) !== 13) {
            $texto = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            return '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="80" viewBox="0 0 240 80">'
                . '<text x="120" y="46" text-anchor="middle" font-family="monospace" font-size="16">' . $texto . '</text>'
                . '</svg>';
        }

        $bits = self::modulosEAN13($code);
        $module = 2;
        $quiet = 10;
        $alto = 78;
        $altoGuarda = 90;
        $ancho = (strlen($bits) + ($quiet * 2)) * $module;
        $altoTotal = 118;
        $barras = '';
        $largo = strlen($bits);
        for ($i = 0; $i < $largo; $i++) {
            if ($bits[$i] !== '1') {
                continue;
            }
            $esGuarda = $i < 3 || ($i >= 45 && $i < 50) || $i >= $largo - 3;
            $altoBarra = $esGuarda ? $altoGuarda : $alto;
            $x = ($quiet + $i) * $module;
            $barras .= '<rect x="' . $x . '" y="0" width="' . $module . '" height="' . $altoBarra . '" fill="#000"/>';
        }
        $texto = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $altoTotal . '" viewBox="0 0 ' . $ancho . ' ' . $altoTotal . '">'
            . '<rect width="100%" height="100%" fill="#fff"/>'
            . $barras
            . '<text x="' . ($ancho / 2) . '" y="112" text-anchor="middle" font-family="monospace" font-size="16" fill="#000">' . $texto . '</text>'
            . '</svg>';
    }

    private static function modulosEAN13($code) {
        $l = ['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'];
        $g = ['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111'];
        $r = ['1110010','1100110','1101100','1000010','1011100','1001110','1010000','1000100','1001000','1110100'];
        $paridad = ['LLLLLL','LLGLGG','LLGGLG','LLGGGL','LGLLGG','LGGLLG','LGGGLL','LGLGLG','LGLGGL','LGGLGL'];
        $bits = '101';
        $mapa = $paridad[(int) $code[0]];
        for ($i = 1; $i <= 6; $i++) {
            $digito = (int) $code[$i];
            $bits .= $mapa[$i - 1] === 'L' ? $l[$digito] : $g[$digito];
        }
        $bits .= '01010';
        for ($i = 7; $i <= 12; $i++) {
            $bits .= $r[(int) $code[$i]];
        }
        return $bits . '101';
    }
}