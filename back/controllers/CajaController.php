<?php
/**
 * Cierre de caja del día.
 */

class CajaController {
    private $cierre;

    public function __construct($db) {
        $this->cierre = new CierreCaja($db);
    }

    public function index() {
        $fecha = trim($_GET['fecha'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }
        $cerrado = $this->cierre->porFecha($fecha);
        $resumen = $cerrado ? null : $this->cierre->resumen($fecha);
        $historial = $this->cierre->historial();
        require_once BASE_DIR . '/front/views/caja/index.php';
    }

    public function cerrar() {
        $fecha = trim($_POST['fecha'] ?? '');
        $destino = $this->destinoTrasCierre($fecha);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=caja');
        }
        exigir_csrf_redirect($destino);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $_SESSION['error'] = 'La fecha del cierre no es válida';
            redirect($destino);
        }

        $resultado = $this->cierre->cerrar([
            'fecha' => $fecha,
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'base' => $_POST['base'] ?? 0,
            'contado' => $_POST['contado'] ?? 0,
            'observacion' => trim($_POST['observacion'] ?? ''),
        ]);

        if ($resultado['success']) {
            $_SESSION['success'] = 'La caja del ' . date('d/m/Y', strtotime($fecha)) . ' quedó cerrada.';
        } else {
            $_SESSION['error'] = $resultado['error'];
        }
        redirect($destino);
    }

    private function destinoTrasCierre($fecha) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }
        if (($_POST['volver'] ?? '') !== 'gastos') {
            return 'index.php?action=caja&fecha=' . urlencode($fecha);
        }
        $qs = 'index.php?action=gastos&fecha_caja=' . urlencode($fecha);
        foreach (['fecha_desde', 'fecha_hasta'] as $clave) {
            $valor = trim((string) ($_POST[$clave] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                $qs .= '&' . $clave . '=' . urlencode($valor);
            }
        }
        $categoria = trim((string) ($_POST['categoria'] ?? ''));
        if ($categoria !== '' && strlen($categoria) <= 100) {
            $qs .= '&categoria=' . urlencode($categoria);
        }
        return $qs;
    }
}
