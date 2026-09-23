<?php
/**
 * Controlador de Autenticación
 */

class AuthController {
    private $db;
    private $usuarioModel;
    
    public function __construct($db) {
        $this->db = $db;
        $this->usuarioModel = new Usuario($db);
    }
    
    /**
     * Mostrar formulario de login
     */
    public function login() {
        // Si ya está autenticado, redirigir según su rol
        if (isset($_SESSION['usuario_id'])) {
            if (permisos_cargar_en_sesion($this->db, (int) $_SESSION['usuario_id'])) {
                redirect(permisos_url_inicio());
            }
            $_SESSION = [];
        }
        
        require_once BASE_DIR . '/front/views/auth/login.php';
    }
    
    /**
     * Procesar login
     */
    public function doLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?action=login');
        }
        exigir_csrf_redirect('index.php?action=login');
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Por favor, complete todos los campos';
            redirect('index.php?action=login');
        }
        
        $usuario = $this->usuarioModel->login($email, $password);
        
        if ($usuario) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];
            
            // Verificar que el rol esté configurado
            $rol = trim($usuario['rol'] ?? '');
            if (empty($rol)) {
                $_SESSION['error'] = 'Error: El rol del usuario no está configurado. Por favor, contacte al administrador.';
                redirect('index.php?action=login');
            }
            
            $_SESSION['usuario_rol'] = $rol;

            if (!permisos_cargar_en_sesion($this->db, (int) $usuario['id'])) {
                $_SESSION = [];
                $_SESSION['error'] = 'Su usuario no tiene un rol asignado. Contacte al administrador.';
                redirect('index.php?action=login');
            }

            redirect(permisos_url_inicio());
        } else {
            $_SESSION['error'] = 'Credenciales incorrectas';
            redirect('index.php?action=login');
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        session_destroy();
        redirect('index.php?action=login');
    }

    public function recuperar() {
        if (isset($_SESSION['usuario_id'])) {
            redirect('index.php?action=dashboard');
        }
        require_once BASE_DIR . '/front/views/auth/recuperar.php';
    }

    public function enviarRecuperacion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=recuperar');
        }

        $email = trim($_POST['email'] ?? '');
        $mensaje = 'Si el correo está registrado, recibirás un enlace para restablecer la contraseña.';
        $usuario = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $this->usuarioModel->getActivoPorEmail($email)
            : false;

        if ($usuario) {
            $recuperacion = new RecuperacionClave($this->db);
            $token = $recuperacion->crear((int) $usuario['id']);
            if ($token) {
                $enlace = BASE_URL . 'index.php?action=recuperar&method=restablecer&token=' . urlencode($token);
                $texto = "Hola {$usuario['nombre']},\n\n"
                    . "Recibimos una solicitud para restablecer la contraseña del inventario.\n\n"
                    . "Abre este enlace durante la próxima hora:\n{$enlace}\n\n"
                    . "Si no fuiste tú, ignora este mensaje.\n";
                if (!Mailer::enviar($usuario['email'], 'Restablecer contraseña', $texto)) {
                    if (ENVIRONMENT === 'development') {
                        $_SESSION['recuperacion_enlace'] = $enlace;
                        $_SESSION['success'] = 'Este equipo no tiene correo saliente. Use el enlace de abajo. Caduca en una hora.';
                        redirect('index.php?action=recuperar');
                    }
                    $recuperacion->descartar($token);
                    error_log('No se pudo enviar la recuperación a ' . $usuario['email']);
                    $_SESSION['error'] = 'No se pudo enviar el correo. Revise la configuración de correo e intente de nuevo.';
                    redirect('index.php?action=recuperar');
                }
            }
        }

        $_SESSION['success'] = $mensaje;
        redirect('index.php?action=login');
    }

    public function restablecer() {
        $token = $_GET['token'] ?? '';
        $recuperacion = new RecuperacionClave($this->db);
        if (!$recuperacion->buscarValido($token)) {
            $_SESSION['error'] = 'El enlace no es válido o ya venció. Solicite uno nuevo.';
            redirect('index.php?action=recuperar');
        }
        require_once BASE_DIR . '/front/views/auth/restablecer.php';
    }

    public function guardarClave() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
            $_SESSION['error'] = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
            redirect('index.php?action=recuperar');
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmacion = $_POST['password_confirmacion'] ?? '';
        $recuperacion = new RecuperacionClave($this->db);
        $registro = $recuperacion->buscarValido($token);

        if (!$registro) {
            $_SESSION['error'] = 'El enlace no es válido o ya venció. Solicite uno nuevo.';
            redirect('index.php?action=recuperar');
        }
        if (strlen($password) < 8 || $password !== $confirmacion) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres y coincidir en ambos campos.';
            redirect('index.php?action=recuperar&method=restablecer&token=' . urlencode($token));
        }

        $this->usuarioModel->cambiarClave((int) $registro['usuario_id'], $password);
        $recuperacion->consumir((int) $registro['id']);
        $_SESSION['success'] = 'Contraseña actualizada. Ya puede iniciar sesión.';
        redirect('index.php?action=login');
    }
}