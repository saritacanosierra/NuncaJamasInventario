<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Sistema de Inventario'; ?> - Ropa Infantil</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- CSS global -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>front/public/css/style.css?v=15">
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
        ];
        if (isset($cssMap[$pageTitle])): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL . 'front/public/css/' . $cssMap[$pageTitle] . '?v=' . ($cssMap[$pageTitle] === 'produccion.css' ? '14' : '9'); ?>">
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
                <i class="bi bi-shop"></i> Inventario Ropa Infantil
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
                    <?php if (puedeVerModulo('clientes')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('clientes')); ?>">
                            <i class="bi bi-people"></i> Clientes
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (puedeVerModulo('gastos')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL . htmlspecialchars(permisos_url_de('gastos')); ?>">
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

