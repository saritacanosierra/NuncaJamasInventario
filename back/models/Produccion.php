<?php
/**
 * Modelo de Producción
 */

require_once __DIR__ . '/ProduccionRegistros.php';
require_once __DIR__ . '/ProduccionOperaciones.php';
require_once __DIR__ . '/ProduccionCierre.php';

class Produccion {
    private $conn;
    private $tableRegistros = 'registros_produccion';
    private $tableOperaciones = 'operaciones_produccion';
    private $tableRetrocesos = 'retrocesos_produccion';
    private $tableHistorial = 'historial_operaciones_produccion';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    use ProduccionRegistros;
    use ProduccionOperaciones;
    use ProduccionCierre;
}
