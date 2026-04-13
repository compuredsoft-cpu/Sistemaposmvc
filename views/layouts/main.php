<?php
    /**
 * Layout Principal del Sistema
 *
 * @param string $title Título de la página
 * @param string $content Contenido HTML de la página
 * @param array $extraScripts Scripts adicionales
 */
    function renderLayout(string $title, string $content, array $extraScripts = []): void
    {
    $userData   = SessionManager::getUserData();
    $userName   = $userData['nombre'] ?? 'Usuario';
    $userAvatar = $userData['avatar'] ?? null;

    $configRepo = new ConfiguracionRepository();
    $config     = $configRepo->getConfig();
    ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo $title; ?> - <?php echo $config->nombre_empresa; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS - v=2 para forzar recarga -->
    <link href="<?php echo SITE_URL; ?>/assets/css/main.css?v=2" rel="stylesheet">
    <!-- Responsive CSS - DEBE cargarse después de Bootstrap - v=2 para forzar recarga -->
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css?v=2" rel="stylesheet">

    <!-- ESTILOS RESPONSIVE INLINE - GARANTIZADO -->
    <style>
    /* ===================== MOVIL ===================== */
    @media (max-width: 768px) {

        /* Headers - forzar apilamiento */
        div[class*="header"] .d-flex.justify-content-between,
        .ventas-header .d-flex,
        .almacen-header .d-flex,
        .clientes-header .d-flex,
        .proveedores-header .d-flex,
        .caja-header .d-flex,
        .usuarios-header .d-flex,
        .cxc-header .d-flex,
        .cotizaciones-header .d-flex,
        .compras-header .d-flex,
        .categorias-header .d-flex,
        .roles-header .d-flex {
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
            gap: 10px !important;
        }

        /* Headers - padding y fuentes */
        div[class*="header"] {
            padding: 12px 15px !important;
            border-radius: 12px !important;
        }

        div[class*="header"] h2 {
            font-size: 1.1rem !important;
            margin-bottom: 5px !important;
        }

        div[class*="header"] p {
            font-size: 0.8rem !important;
        }

        /* Botones en headers */
        div[class*="header"] .btn {
            font-size: 0.85rem !important;
            padding: 6px 12px !important;
            width: auto !important;
            max-width: 200px !important;
        }

        /* Stats cards - respetar col-6 (50%) o col-12 (100%), solo ajustar col-md-* sin col-* */
        .row.g-3.mb-4>.col-md-3:not([class*="col-6"]):not([class*="col-12"]),
        .row.g-3.mb-4>.col-md-4:not([class*="col-6"]):not([class*="col-12"]),
        .row.g-4.mb-4>.col-md-3:not([class*="col-6"]):not([class*="col-12"]),
        .row.g-4.mb-4>.col-md-4:not([class*="col-6"]):not([class*="col-12"]) {
            width: 50% !important;
            padding: 5px !important;
        }

        /* Stats cards que tienen col-6 deben mantener 50% */
        .row.g-3.mb-4>.col-6,
        .row.g-4.mb-4>.col-6 {
            width: 50% !important;
            padding: 5px !important;
        }

        /* Stats cards col-12 deben ser 100% */
        .row.g-3.mb-4>.col-12,
        .row.g-4.mb-4>.col-12 {
            width: 100% !important;
            padding: 5px !important;
        }

        /* Stats cards - padding y fuentes */
        .row.g-3 .card-body,
        .row.g-4 .card-body {
            padding: 10px !important;
        }

        .row.g-3 .rounded-circle,
        .row.g-4 .rounded-circle {
            width: 40px !important;
            height: 40px !important;
            margin-right: 8px !important;
        }

        .row.g-3 h6,
        .row.g-4 h6 {
            font-size: 0.7rem !important;
            margin-bottom: 2px !important;
        }

        .row.g-3 h3,
        .row.g-3 h4,
        .row.g-4 h3,
        .row.g-4 h4 {
            font-size: 1rem !important;
            margin-bottom: 0 !important;
        }

        /* Filtros - apilados */
        form .col-md-2,
        form .col-md-3,
        form .col-md-4,
        form .col-md-6 {
            width: 100% !important;
            margin-bottom: 8px !important;
        }

        /* Tablas - scroll horizontal */
        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        table {
            font-size: 0.8rem !important;
        }

        /* Card headers */
        .card-header {
            padding: 10px 15px !important;
        }

        .card-header h5 {
            font-size: 0.95rem !important;
        }
    }

    /* ===================== MOVIL PEQUEÑO ===================== */
    @media (max-width: 576px) {

        /* Stats cards - 2 por fila en col-6, 1 por fila solo en col-md- sin col-6/4 */
        .row.g-3.mb-4>.col-md-3:not(.col-6):not(.col-4),
        .row.g-3.mb-4>.col-md-4:not(.col-6):not(.col-12),
        .row.g-4.mb-4>.col-md-3:not(.col-6):not(.col-4),
        .row.g-4.mb-4>.col-md-4:not(.col-6):not(.col-12) {
            width: 100% !important;
        }

        /* Headers más compactos */
        div[class*="header"] h2 {
            font-size: 1rem !important;
        }

        /* Card headers de tablas - apilar contenido */
        .card-header.d-flex {
            flex-direction: column !important;
            gap: 8px !important;
            align-items: flex-start !important;
        }

        .card-header.d-flex .d-flex.gap-2 {
            flex-wrap: wrap !important;
            width: 100% !important;
        }

        .card-header.d-flex .d-flex.gap-2 .btn {
            flex: 1 !important;
            min-width: 80px !important;
            font-size: 0.75rem !important;
            padding: 4px 8px !important;
        }
    }
    </style>

    <?php foreach ($extraScripts as $script): ?>
    <?php if (strpos($script, '.css') !== false): ?>
    <link href="<?php echo $script; ?>" rel="stylesheet">
    <?php endif; ?>
    <?php endforeach; ?>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="bi bi-shop"></i>
                    <span class="brand-text"><?php echo SITE_NAME; ?></span>
                </div>
                <button id="sidebarCollapse" class="btn btn-sm btn-outline-light d-lg-none">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="sidebar-user">
                <div class="user-avatar">
                    <?php if ($userAvatar): ?>
                    <img src="<?php echo SITE_URL . '/uploads/' . $userAvatar; ?>" alt="Avatar">
                    <?php else: ?>
                    <div class="avatar-placeholder">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo $userName; ?></div>
                    <div class="user-role"><?php echo SessionManager::getUserRole(); ?></div>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="menu-header">Principal</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/dashboard/index.php" class="menu-link">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="menu-header">Ventas</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/ventas/pos.php" class="menu-link">
                        <i class="bi bi-cart-plus"></i>
                        <span>Nueva Venta (POS)</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/ventas/index.php" class="menu-link">
                        <i class="bi bi-cart-check"></i>
                        <span>Listado de Ventas</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/cotizaciones/index.php" class="menu-link">
                        <i class="bi bi-file-text"></i>
                        <span>Cotizaciones</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/cuentasxcobrar/index.php" class="menu-link">
                        <i class="bi bi-wallet2"></i>
                        <span>Cuentas por Cobrar</span>
                    </a>
                </li>

                <li class="menu-header">Inventario</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/almacen/index.php" class="menu-link">
                        <i class="bi bi-box-seam"></i>
                        <span>Productos</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/compras/index.php" class="menu-link">
                        <i class="bi bi-bag-plus"></i>
                        <span>Compras</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/kardex/index.php" class="menu-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Kardex / Historial</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/categorias/index.php" class="menu-link">
                        <i class="bi bi-tags"></i>
                        <span>Categorías</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/proveedores/index.php" class="menu-link">
                        <i class="bi bi-truck"></i>
                        <span>Proveedores</span>
                    </a>
                </li>

                <li class="menu-header">Gestión</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/clientes/index.php" class="menu-link">
                        <i class="bi bi-people"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/caja/index.php" class="menu-link">
                        <i class="bi bi-cash-coin"></i>
                        <span>Apertura/Cierre Caja</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/gastos/index.php" class="menu-link">
                        <i class="bi bi-graph-down-arrow"></i>
                        <span>Gastos y Ganancias</span>
                    </a>
                </li>

                <li class="menu-header">Administración</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/usuarios/index.php" class="menu-link">
                        <i class="bi bi-person-gear"></i>
                        <span>Usuarios</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/roles/index.php" class="menu-link">
                        <i class="bi bi-shield-lock"></i>
                        <span>Roles y Permisos</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/configuracion/index.php" class="menu-link">
                        <i class="bi bi-gear"></i>
                        <span>Configuración</span>
                    </a>
                </li>

                <li class="menu-header">Sesión</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/views/auth/logout.php" class="menu-link text-danger">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Content -->
        <div class="main-content">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
                <div class="container-fluid">
                    <button id="sidebarToggle" class="btn btn-outline-primary d-lg-none">
                        <i class="bi bi-list"></i>
                    </button>

                    <nav aria-label="breadcrumb" class="d-none d-lg-block">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a
                                    href="<?php echo SITE_URL; ?>/views/dashboard/index.php">Inicio</a></li>
                            <li class="breadcrumb-item active"><?php echo $title; ?></li>
                        </ol>
                    </nav>

                    <div class="d-flex align-items-center">
                        <div class="dropdown me-3">
                            <button class="btn btn-link text-dark position-relative" data-bs-toggle="dropdown">
                                <i class="bi bi-bell fs-5"></i>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    0
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <h6 class="dropdown-header">Notificaciones</h6>
                                </li>
                                <li><a class="dropdown-item" href="#">Sin notificaciones</a></li>
                            </ul>
                        </div>

                        <div class="dropdown">
                            <button
                                class="btn btn-link text-dark text-decoration-none dropdown-toggle d-flex align-items-center"
                                data-bs-toggle="dropdown">
                                <span class="me-2"><?php echo $userName; ?></span>
                                <div class="user-avatar-sm">
                                    <?php if ($userAvatar): ?>
                                    <img src="<?php echo SITE_URL . '/uploads/' . $userAvatar; ?>" alt="Avatar">
                                    <?php else: ?>
                                    <div class="avatar-placeholder-sm">
                                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/usuarios/perfil.php"><i
                                            class="bi bi-person me-2"></i>Mi Perfil</a></li>
                                <li><a class="dropdown-item"
                                        href="<?php echo SITE_URL; ?>/views/configuracion/index.php"><i
                                            class="bi bi-gear me-2"></i>Configuración</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger"
                                        href="<?php echo SITE_URL; ?>/views/auth/logout.php"><i
                                            class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="content-wrapper">
                <?php echo $content; ?>
            </main>

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $config->nombre_empresa; ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-0 text-muted">Sistema POS v<?php echo APP_VERSION; ?></p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Main JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

    <?php foreach ($extraScripts as $script): ?>
    <?php if (strpos($script, '.js') !== false): ?>
    <script src="<?php echo $script; ?>"></script>
    <?php endif; ?>
    <?php endforeach; ?>
</body>

</html>
<?php
}
?>