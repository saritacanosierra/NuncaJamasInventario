<?php
$pageTitle = 'Recuperar contraseña';
require_once BASE_DIR . '/front/views/layout/header.php';
?>

<div class="main-container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-envelope display-1 text-primary"></i>
                        <h2 class="mt-3">Recuperar contraseña</h2>
                        <p class="text-muted">Te enviaremos un enlace al correo de tu cuenta.</p>
                    </div>

                    <?php if (!empty($_SESSION['recuperacion_enlace'])): ?>
                        <div class="alert alert-info">
                            <a href="<?php echo htmlspecialchars($_SESSION['recuperacion_enlace'], ENT_QUOTES, 'UTF-8'); ?>">Abrir enlace para restablecer</a>
                        </div>
                        <?php unset($_SESSION['recuperacion_enlace']); ?>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=recuperar&method=enviar">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Enviar enlace</button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="<?php echo BASE_URL; ?>index.php?action=login">Volver al inicio de sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
