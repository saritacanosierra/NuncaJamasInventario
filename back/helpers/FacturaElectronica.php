<?php
/**
 * CUFE y XML local de una factura de venta.
 * El cálculo sigue la cadena del anexo técnico de la DIAN.
 * Este documento no se envía a la DIAN: falta el certificado y el software autorizado.
 */

class FacturaElectronica {
    public static function cufe($datos) {
        $cadena = $datos['numero']
            . $datos['fecha']
            . $datos['hora']
            . self::monto($datos['val_fac'])
            . '01'
            . self::monto($datos['val_imp1'])
            . '04'
            . self::monto(0)
            . '03'
            . self::monto(0)
            . self::monto($datos['val_tot'])
            . $datos['nit_ofe']
            . $datos['num_adq']
            . $datos['clave_tecnica']
            . $datos['ambiente'];
        return hash('sha384', $cadena);
    }

    public static function xml($datos) {
        $e = static function ($valor) {
            return htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        };
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Invoice>'
            . '<ID>' . $e($datos['numero']) . '</ID>'
            . '<IssueDate>' . $e($datos['fecha']) . '</IssueDate>'
            . '<IssueTime>' . $e($datos['hora']) . '</IssueTime>'
            . '<Supplier>' . $e($datos['razon_social']) . '</Supplier>'
            . '<SupplierNIT>' . $e($datos['nit_ofe']) . '</SupplierNIT>'
            . '<Customer>' . $e($datos['cliente_nombre']) . '</Customer>'
            . '<CustomerID>' . $e($datos['num_adq']) . '</CustomerID>'
            . '<LineExtensionAmount>' . $e(self::monto($datos['val_fac'])) . '</LineExtensionAmount>'
            . '<TaxAmount>' . $e(self::monto($datos['val_imp1'])) . '</TaxAmount>'
            . '<PayableAmount>' . $e(self::monto($datos['val_tot'])) . '</PayableAmount>'
            . '<UUID>' . $e($datos['cufe']) . '</UUID>'
            . '<Resolution>' . $e($datos['resolucion']) . '</Resolution>'
            . '</Invoice>';
    }

    public static function xmlNota($datos) {
        $e = static function ($valor) {
            return htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        };
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CreditNote>'
            . '<ID>' . $e($datos['numero']) . '</ID>'
            . '<IssueDate>' . $e($datos['fecha']) . '</IssueDate>'
            . '<IssueTime>' . $e($datos['hora']) . '</IssueTime>'
            . '<BillingReference>' . $e($datos['cufe_factura']) . '</BillingReference>'
            . '<Note>' . $e($datos['motivo']) . '</Note>'
            . '<Supplier>' . $e($datos['razon_social']) . '</Supplier>'
            . '<SupplierNIT>' . $e($datos['nit_ofe']) . '</SupplierNIT>'
            . '<Customer>' . $e($datos['cliente_nombre']) . '</Customer>'
            . '<CustomerID>' . $e($datos['num_adq']) . '</CustomerID>'
            . '<LineExtensionAmount>' . $e(self::monto($datos['val_fac'])) . '</LineExtensionAmount>'
            . '<PayableAmount>' . $e(self::monto($datos['val_tot'])) . '</PayableAmount>'
            . '<UUID>' . $e($datos['cufe']) . '</UUID>'
            . '</CreditNote>';
    }

    public static function documento($valor) {
        $limpio = preg_replace('/\D+/', '', (string) $valor);
        return $limpio === null ? '' : $limpio;
    }

    private static function monto($valor) {
        return number_format((float) $valor, 2, '.', '');
    }
}
