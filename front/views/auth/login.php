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
                        <i class="bi bi-shop display-1 text-primary"></i>
                        <h2 class="mt-3">Sistema de Inventario</h2>
                        <p class="text-muted">Ropa Infantil</p>
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
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>

