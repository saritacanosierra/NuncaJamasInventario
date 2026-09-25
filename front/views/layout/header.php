<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $pageTitle ?? 'Sistema de Inventario'; ?> - Ropa Infantil</title>
    <link rel="manifest" href="<?php echo BASE_URL; ?>manifest.webmanifest">
    <link rel="icon" href="<?php echo BASE_URL; ?>front/public/img/icon-192.png" sizes="192x192">
    <meta name="theme-color" content="#FCD1D1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Nunca Jamás">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>front/public/img/icon-192.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- CSS global -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>front/public/css/style.css?v=19">
    <!-- CSS por vista -->
    <?php if (!empty($pageTitle)): ?>
        <?php
        $cssMap = [
            'Productos'    => 'productos.css',
            'Dashboard'    => 'dashboard.css',
            'Punto de Venta' => 'ventas.css',
            'Gastos'       => 'gastos.css',
            'Agenda'       => 'agenda.css',
            'Producción'   => 'produccion.css',
            'Usuarios'     => 'configuracion.css',
            'Roles'        => 'configuracion.css',
            'WordPress'    => 'configuracion.css',
            'Compras'      => 'compras.css',
            'Kardex'       => 'compras.css',
            'Caja'         => 'caja.css',
            'Informe'      => 'informes.css',
            'Factura'      => 'factura.css',
            'Rótulo de Envío' => 'rotulo-envio.css',
            'Pago por pieza' => 'produccion.css',
            'Cambio de talla' => 'ventas.css',
            'Resolución'   => 'ventas.css',
        ];
        $versionesCss = ['produccion.css' => '14', 'compras.css' => '1', 'caja.css' => '1', 'ventas.css' => '19', 'factura.css' => '1', 'rotulo-envio.css' => '3', 'informes.css' => '1', 'productos.css' => '10'];
        if (isset($cssMap[$pageTitle])):
            $versionCss = $versionesCss[$cssMap[$pageTitle]] ?? '9';
        ?>
            <link rel="stylesheet" href="<?php echo BASE_URL . 'front/public/css/' . $cssMap[$pageTitle] . '?v=' . $versionCss; ?>">
        <?php endif; ?>
    <?php endif; ?>
</head>
<body>
    <script>window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;</script>
    <script>
        window.FINAL_PERMISSIONS = <?php echo json_encode(array_values($_SESSION['final_permissions'] ?? []), JSON_UNESCAPED_UNICODE); ?>;
        window.PERMISOS_ANY = <?php echo json_encode(permisos_listas_any(), JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <?php if (isset($_SESSION['usuario_id'])): ?>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="main-container">
            <?php
            $paginaInicio = permisos_url_inicio();
            ?>
            <a class="navbar-brand" href="<?php echo BASE_URL . htmlspecialchars($paginaInicio); ?>">
                <img src="<?php echo BASE_URL; ?>front/public/img/logo-nunca-jamas.jpg" alt="Nunca Jamás" class="logo-marca">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (puedeVerModulo('dashboard')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('dashboard')); ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (puedeVerModulo('productos')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('productos')); ?>">
                            <i class="bi bi-box-seam"></i> Productos
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (puedeVerModulo('ventas')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('ventas')); ?>">
                            <i class="bi bi-cart-check"></i> Ventas
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (puedeVerModulo('caja')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('caja')); ?>">
                            <i class="bi bi-safe"></i> Caja
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (puedeVerModulo('informes')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('informes')); ?>">
                            <i class="bi bi-graph-up"></i> Informes
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (puedeVerModulo('clientes')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('clientes')); ?>">
                            <i class="bi bi-people"></i> Clientes
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (puedeVerModulo('gastos') || tienePermiso('compras_registro:view') || tienePermiso('compras:view')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(puedeVerModulo('gastos') ? permisos_url_de('gastos') : 'index.php?action=compras'); ?>">
                            <i class="bi bi-cash-stack"></i> Gastos
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (puedeVerModulo('agenda')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('agenda')); ?>">
                            <i class="bi bi-calendar-check"></i> Agenda
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (puedeVerModulo('produccion')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('produccion')); ?>">
                            <i class="bi bi-gear-wide-connected"></i> Producción
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-instalar="pc" href="<?php echo BASE_URL; ?>index.php?action=escritorio&method=descargar">
                            <i class="bi bi-pc-display"></i> App para PC
                        </a>
                        <button type="button" class="nav-link d-none" data-instalar="movil">
                            <i class="bi bi-phone"></i> App del celular
                        </button>
                    </li>
                    <?php if (puedeVerModulo('configuracion')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('configuracion')); ?>" title="Configuración">
                            <i class="bi bi-gear"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text"><small><?php echo htmlspecialchars($_SESSION['usuario_email']); ?></small></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>index.php?action=logout">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Mensajes de alerta -->
    <div class="main-container mt-3">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

