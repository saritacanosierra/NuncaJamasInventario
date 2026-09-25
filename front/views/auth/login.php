<?php
$pageTitle = 'Iniciar Sesión';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?php echo BASE_URL; ?>front/public/img/logo-nunca-jamas.jpg" alt="Nunca Jamás" class="logo-acceso">
                    </div>
                    
                    <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=login&method=doLogin">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope"></i> Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock"></i> Contraseña
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                            </button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="<?php echo BASE_URL; ?>index.php?action=recuperar">¿Olvidaste tu contraseña?</a>
                    </div>
                    <div class="mt-2 text-center">
                        <a data-instalar="pc" href="<?php echo BASE_URL; ?>index.php?action=escritorio&method=descargar">Descargar para este PC</a>
                        <button type="button" class="btn btn-outline-primary w-100 d-none" data-instalar="movil">
                            <i class="bi bi-phone"></i> Instalar en este celular
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

