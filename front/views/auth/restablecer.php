<?php
$pageTitle = 'Nueva contraseña';
require_once BASE_DIR . '/front/views/layout/header.php';
$token = $token ?? '';
?>

<div class="main-container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-key display-1 text-primary"></i>
                        <h2 class="mt-3">Nueva contraseña</h2>
                        <p class="text-muted">El enlace sirve una sola vez y vence en una hora.</p>
                    </div>

                    <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=recuperar&method=guardar">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña nueva</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmacion" class="form-label">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="password_confirmacion" name="password_confirmacion" minlength="8" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Guardar contraseña</button>
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
