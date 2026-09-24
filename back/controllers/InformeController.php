<?php
/**
 * Informe mensual para el contador.
 */

class InformeController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $mes = trim($_GET['mes'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }
        [$anio, $numero] = array_map('intval', explode('-', $mes));
        $informe = (new Informe($this->db))->delMes($anio, $numero);
        require_once BASE_DIR . '/front/views/informes/index.php';
    }
}
