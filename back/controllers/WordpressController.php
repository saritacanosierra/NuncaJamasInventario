<?php

/**
 * Pantalla de configuración para enlazar la caja con WordPress.
 */
class WordpressController {
    public function index() {
        $enlace = WordpressEnlace::leer();
        $prueba = $_SESSION['wordpress_prueba'] ?? null;
        unset($_SESSION['wordpress_prueba']);
        require_once BASE_DIR . '/front/views/configuracion/wordpress.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=wordpress');
        }
        exigir_csrf_redirect('index.php?action=wordpress');
        $error = WordpressEnlace::guardar($_POST, WordpressEnlace::leer());
        if ($error !== '') {
            $_SESSION['error'] = $error;
        } else {
            $_SESSION['success'] = 'La conexión con WordPress quedó guardada.';
        }
        redirect('index.php?action=wordpress');
    }

    public function probar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=wordpress');
        }
        exigir_csrf_redirect('index.php?action=wordpress');
        $_SESSION['wordpress_prueba'] = WordpressEnlace::probar(WordpressEnlace::leer());
        redirect('index.php?action=wordpress');
    }
}
