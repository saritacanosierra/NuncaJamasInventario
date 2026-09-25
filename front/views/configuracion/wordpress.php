<?php
$pageTitle = 'WordPress';
require_once BASE_DIR . '/front/views/layout/header.php';
if (!isset($enlace) || !is_array($enlace)) {
    $enlace = [
        'enabled' => false,
        'url' => '',
        'consumer_key' => '',
        'consumer_secret' => '',
        'webhook_secret' => '',
    ];
}
$prueba = (isset($prueba) && is_array($prueba)) ? $prueba : null;
$hayClave = $enlace['consumer_key'] !== '';
$haySecreto = $enlace['consumer_secret'] !== '';
?>

<div class="main-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-wordpress"></i> WordPress<?php $ayuda = 'Aquí se guarda cómo hablar con la tienda. La prenda se reconoce por el mismo código: el código de barras de esta app es el SKU de WooCommerce. Esta pantalla aún no descuenta el stock; solo deja el enlace listo y permite probarlo.'; require BASE_DIR . '/front/views/components/ayuda.php'; ?></h2>
        <div class="d-flex gap-2">
            <?php if (tienePermiso('usuarios_lista:view')): ?>
            <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>index.php?action=usuarios">Usuarios</a>
            <?php endif; ?>
            <?php if (tienePermiso('roles_matriz:view')): ?>
            <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>index.php?action=roles">Roles</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=wordpress&method=guardar" class="card">
                <?php echo csrf_field(); ?>
                <div class="card-header"><h5 class="mb-0">Datos de la tienda</h5></div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="wp_enabled" name="enabled" value="1" <?php echo !empty($enlace['enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="wp_enabled">Usar esta conexión</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="wp_url">Dirección de WordPress</label>
                        <input class="form-control" type="url" id="wp_url" name="url" required placeholder="https://tu-tienda.com" value="<?php echo htmlspecialchars($enlace['url']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="wp_key">Clave de WooCommerce</label>
                        <input class="form-control" type="text" id="wp_key" name="consumer_key" autocomplete="off" placeholder="<?php echo $hayClave ? 'Ya hay una clave. Escríbela solo para cambiarla.' : 'ck_...'; ?>" value="">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="wp_secret">Secreto de WooCommerce</label>
                        <input class="form-control" type="password" id="wp_secret" name="consumer_secret" autocomplete="new-password" placeholder="<?php echo $haySecreto ? 'Ya hay un secreto. Escríbelo solo para cambiarlo.' : 'cs_...'; ?>" value="">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="wp_hook">Secreto para el aviso de WordPress</label>
                        <input class="form-control" type="text" id="wp_hook" name="webhook_secret" value="<?php echo htmlspecialchars($enlace['webhook_secret']); ?>" placeholder="Se crea al guardar si lo dejas vacío">
                        <p class="small text-muted mb-0">Este texto se pega en el webhook de WooCommerce. WordPress lo enviará cuando una venta quede pagada.</p>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar conexión</button>
                </div>
            </form>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Qué se enlaza</h5></div>
                <div class="card-body">
                    <p>El código de barras de la prenda en esta app tiene que ser el mismo SKU del producto en WooCommerce.</p>
                    <p class="mb-0">Cuando la venta en la web quede pagada, el siguiente paso descontará esa talla aquí. Si el stock llega a cero, la tienda la marca agotada y la oculta. No se borra la ficha.</p>
                </div>
            </div>
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=wordpress&method=probar" class="card">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    <button type="submit" class="btn btn-outline-primary">Probar conexión</button>
                    <?php if (is_array($prueba)): ?>
                    <p class="mt-3 mb-0 <?php echo !empty($prueba['ok']) ? 'text-success' : 'text-danger'; ?>"><?php echo htmlspecialchars($prueba['mensaje'] ?? ''); ?></p>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_DIR . '/front/views/layout/footer.php'; ?>
