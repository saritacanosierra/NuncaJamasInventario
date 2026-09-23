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
}