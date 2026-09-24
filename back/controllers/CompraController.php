<?php
/**
 * Compras y kardex.
 */

class CompraController {
    private $db;
    private $compra;
    private $kardex;
    private $producto;

    public function __construct($db) {
        $this->db = $db;
        $this->compra = new Compra($db);
        $this->kardex = new Kardex($db);
        $this->producto = new Producto($db);
    }

    public function index() {
        $compras = $this->compra->recientes();
        $categorias = (new Categoria($this->db))->getAll();
        require_once BASE_DIR . '/front/views/compras/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=compras');
        }
        exigir_csrf_redirect('index.php?action=compras');

        $lineasEntrada = json_decode($_POST['lineas'] ?? '[]', true);
        if (!is_array($lineasEntrada) || $lineasEntrada === []) {
            $_SESSION['error'] = 'La compra no tiene prendas';
            redirect('index.php?action=compras');
        }

        $proveedor = trim($_POST['proveedor'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        if ($proveedor === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $_SESSION['error'] = 'El proveedor y la fecha son obligatorios';
            redirect('index.php?action=compras');
        }

        $lineas = [];
        $total = 0;
        foreach ($lineasEntrada as $linea) {
            $productoId = (int) ($linea['producto_id'] ?? 0);
            $cantidad = (int) ($linea['cantidad'] ?? 0);
            $costo = round((float) ($linea['costo'] ?? 0), 2);
            $nombre = trim($linea['nombre'] ?? '');
            $talla = trim($linea['talla'] ?? '');
            $categoriaId = (int) ($linea['categoria_id'] ?? 0);
            if ($cantidad < 1 || $costo < 0 || ($productoId < 1 && ($nombre === '' || $talla === '' || $categoriaId < 1))) {
                $_SESSION['error'] = 'Cada prenda necesita talla, cantidad y, si es nueva, una categoría.';
                redirect('index.php?action=compras');
            }
            $subtotal = round($costo * $cantidad, 2);
            $total += $subtotal;
            $lineas[] = [
                'producto_id' => $productoId,
                'talla_id' => (int) ($linea['talla_id'] ?? 0),
                'nombre' => $nombre,
                'talla' => $talla,
                'color' => trim($linea['color'] ?? ''),
                'categoria_id' => $categoriaId,
                'cantidad' => $cantidad,
                'costo_unitario' => $costo,
                'subtotal' => $subtotal,
            ];
        }

        $resultado = $this->compra->crear([
            'proveedor' => $proveedor,
            'documento' => trim($_POST['documento'] ?? ''),
            'fecha' => $fecha,
            'total' => round($total, 2),
            'sale_de_caja' => isset($_POST['sale_de_caja']) && $_POST['sale_de_caja'] === '1',
            'usuario_id' => (int) $_SESSION['usuario_id'],
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'lineas' => $lineas,
        ]);

        if ($resultado['success']) {
            $_SESSION['success'] = 'Compra ' . $resultado['numero'] . ' registrada. Entró al inventario y suma como inversión de producto.';
        } else {
            $_SESSION['error'] = 'No se pudo guardar la compra';
        }
        redirect('index.php?action=compras');
    }

    public function kardex() {
        $productoId = (int) ($_GET['producto_id'] ?? 0);
        $movimientos = $this->kardex->listar($productoId);
        $producto = $productoId > 0 ? $this->producto->getById($productoId) : null;
        require_once BASE_DIR . '/front/views/compras/kardex.php';
    }

    public function buscarProducto() {
        header('Content-Type: application/json; charset=utf-8');
        $termino = trim($_GET['termino'] ?? '');
        if ($termino === '') {
            echo json_encode(['success' => true, 'productos' => []]);
            exit;
        }
        echo json_encode([
            'success' => true,
            'productos' => $this->producto->buscarParaCompra($termino),
        ]);
        exit;
    }
}
