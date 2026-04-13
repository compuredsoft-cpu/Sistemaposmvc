<?php
    /**
 * Dashboard Principal
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requireAuth();

    // Cargar repositorios
    $ventaRepo    = new VentaRepository();
    $cajaRepo     = new CajaRepository();
    $productoRepo = new ProductoRepository();
    $cxcRepo      = new CuentaPorCobrarRepository();
    $gastoRepo    = new GastoRepository();
    $clienteRepo  = new ClienteRepository();

    // Estadísticas de hoy
    $statsHoy        = $ventaRepo->getEstadisticasHoy();
    $totalVentasHoy  = $statsHoy['total_ventas'] ?? 0;
    $totalVendidoHoy = $statsHoy['total_vendido'] ?? 0;

    // Verificar caja abierta (cualquiera, no solo la del usuario)
    $cajaAbierta = $cajaRepo->getAnyCajaAbierta();

    // Alertas de stock
    $alertasStock = $productoRepo->getStockAlerts();

    // Cuentas por cobrar pendientes
    $cxcStats = $cxcRepo->getEstadisticas();

    // Balance del mes
    $primerDiaMes = date('Y-m-01');
    $ultimoDiaMes = date('Y-m-t');
    $balanceMes   = $gastoRepo->getBalance($primerDiaMes, $ultimoDiaMes);

    // Clientes con deuda
    $clientesConDeuda = $clienteRepo->getClientesConDeuda();

    ob_start();
?>

<div class="container-fluid">
    <!-- Header Dashboard -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Dashboard</h1>
        <div>
            <span class="text-muted me-3">
                <i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y'); ?>
            </span>
            <?php if ($cajaAbierta): ?>
            <a href="<?php echo SITE_URL; ?>/views/caja/cierre.php" class="btn btn-danger">
                <i class="bi bi-lock me-2"></i>Cerrar Caja
            </a>
            <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/views/caja/apertura.php" class="btn btn-success">
                <i class="bi bi-unlock me-2"></i>Abrir Caja
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div class="ms-auto text-end">
                            <h3 class="stat-value"><?php echo $totalVentasHoy; ?></h3>
                            <p class="stat-label mb-0">Ventas Hoy</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stat-card success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="ms-auto text-end">
                            <h3 class="stat-value"><?php echo formatCurrency($totalVendidoHoy); ?></h3>
                            <p class="stat-label mb-0">Vendido Hoy</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stat-card warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="ms-auto text-end">
                            <h3 class="stat-value"><?php echo formatCurrency($cxcStats['total_pendiente'] ?? 0); ?></h3>
                            <p class="stat-label mb-0">Por Cobrar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stat-card danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div class="ms-auto text-end">
                            <h3 class="stat-value"><?php echo formatCurrency($balanceMes['balance'] ?? 0); ?></h3>
                            <p class="stat-label mb-0">Balance Mes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Ventas por Método de Pago -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bar-chart-line me-2"></i>Ventas Recientes
                    </h5>
                    <a href="<?php echo SITE_URL; ?>/views/ventas/index.php" class="btn btn-sm btn-outline-primary">Ver
                        Todas</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Método</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $ventasRecientes = $ventaRepo->findAllWithFilters([], 1, 5);
                                    foreach ($ventasRecientes['items'] as $venta):
                                ?>
                                <tr>
                                    <td><a
                                            href="<?php echo SITE_URL; ?>/views/ventas/ver.php?id=<?php echo $venta->id; ?>"><?php echo $venta->codigo; ?></a>
                                    </td>
                                    <td><?php echo $venta->cliente_nombre; ?></td>
                                    <td><?php echo formatCurrency($venta->total); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo match ($venta->metodo_pago) {
                                                                      'EFECTIVO'      => 'success',
                                                                      'TARJETA'       => 'info',
                                                                      'TRANSFERENCIA' => 'primary',
                                                                  default         => 'secondary'
                                                              }; ?>">
                                            <?php echo $venta->metodo_pago; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($venta->fecha); ?></td>
                                    <td>
                                        <span
                                            class="badge bg-<?php echo $venta->estado === 'COMPLETADA' ? 'success' : ($venta->estado === 'CANCELADA' ? 'danger' : 'warning'); ?>">
                                            <?php echo $venta->estado; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($ventasRecientes['items'])): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No hay ventas recientes</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <div class="col-lg-4">
            <!-- Stock Alerts -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Stock Bajo
                    </h5>
                    <span class="badge bg-dark"><?php echo count($alertasStock); ?></span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                        <?php foreach (array_slice($alertasStock, 0, 5) as $producto): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?php echo $producto->nombre; ?></div>
                                <small class="text-muted">Stock: <?php echo $producto->stock_actual; ?> / Mín:
                                    <?php echo $producto->stock_minimo; ?></small>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/views/almacen/editar.php?id=<?php echo $producto->id; ?>"
                                class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($alertasStock)): ?>
                        <li class="list-group-item text-center text-muted">No hay alertas de stock</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo SITE_URL; ?>/views/almacen/index.php?stock_alerta=1"
                        class="btn btn-sm btn-outline-warning">Ver Todas</a>
                </div>
            </div>

            <!-- Cuentas Vencidas -->
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>Cuentas Vencidas
                    </h5>
                    <span class="badge bg-white text-danger"><?php echo $cxcStats['cuentas_vencidas'] ?? 0; ?></span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                        <?php
                            $cxcVencidas = $cxcRepo->findAllWithFilters(['vencidas' => 1], 1, 5);
                            foreach ($cxcVencidas['items'] as $cxc):
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?php echo $cxc->cliente_nombre; ?></div>
                                <small class="text-muted"><?php echo formatCurrency($cxc->monto_pendiente); ?> - Venció:
                                    <?php echo formatDate($cxc->fecha_vencimiento); ?></small>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/views/cuentasxcobrar/ver.php?id=<?php echo $cxc->id; ?>"
                                class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-eye"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($cxcVencidas['items'])): ?>
                        <li class="list-group-item text-center text-muted">No hay cuentas vencidas</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo SITE_URL; ?>/views/cuentasxcobrar/index.php?vencidas=1"
                        class="btn btn-sm btn-outline-danger">Ver Todas</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>Accesos Rápidos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php if (SessionManager::hasPermission('ventas')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/ventas/pos.php"
                                class="btn btn-outline-primary w-100 py-3">
                                <i class="bi bi-cart-plus fs-3 d-block mb-2"></i>
                                Nueva Venta
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SessionManager::hasPermission('compras')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/compras/nueva.php"
                                class="btn btn-outline-success w-100 py-3">
                                <i class="bi bi-bag-plus fs-3 d-block mb-2"></i>
                                Nueva Compra
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SessionManager::hasPermission('cotizaciones')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/cotizaciones/nueva.php"
                                class="btn btn-outline-info w-100 py-3">
                                <i class="bi bi-file-text fs-3 d-block mb-2"></i>
                                Nueva Cotización
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SessionManager::hasPermission('almacen')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/almacen/nuevo.php"
                                class="btn btn-outline-warning w-100 py-3">
                                <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
                                Nuevo Producto
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Segunda fila de accesos rápidos -->
                    <div class="row g-3 mt-2">
                        <?php if (SessionManager::hasPermission('clientes')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/clientes/nuevo.php"
                                class="btn btn-outline-secondary w-100 py-3">
                                <i class="bi bi-people fs-3 d-block mb-2"></i>
                                Nuevo Cliente
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SessionManager::hasPermission('proveedores')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/proveedores/nuevo.php"
                                class="btn btn-outline-dark w-100 py-3">
                                <i class="bi bi-truck fs-3 d-block mb-2"></i>
                                Nuevo Proveedor
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SessionManager::hasPermission('caja')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/caja/apertura.php"
                                class="btn btn-outline-success w-100 py-3">
                                <i class="bi bi-cash-coin fs-3 d-block mb-2"></i>
                                Apertura Caja
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SessionManager::hasPermission('cuentas_cobrar')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/cuentasxcobrar/index.php"
                                class="btn btn-outline-danger w-100 py-3">
                                <i class="bi bi-wallet2 fs-3 d-block mb-2"></i>
                                Ctas. por Cobrar
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Tercera fila -->
                    <div class="row g-3 mt-2">
                        <?php if (SessionManager::hasPermission('almacen')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/kardex/index.php"
                                class="btn btn-outline-primary w-100 py-3">
                                <i class="bi bi-clock-history fs-3 d-block mb-2"></i>
                                Kardex
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SessionManager::hasPermission('gastos')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/gastos/index.php"
                                class="btn btn-outline-warning w-100 py-3">
                                <i class="bi bi-graph-down-arrow fs-3 d-block mb-2"></i>
                                Gastos
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SessionManager::hasPermission('ventas')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/ventas/index.php"
                                class="btn btn-outline-info w-100 py-3">
                                <i class="bi bi-cart-check fs-3 d-block mb-2"></i>
                                Listado Ventas
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SessionManager::hasPermission('usuarios')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/views/usuarios/index.php"
                                class="btn btn-outline-secondary w-100 py-3">
                                <i class="bi bi-person-gear fs-3 d-block mb-2"></i>
                                Usuarios
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Responsive styles for Dashboard */
@media (max-width: 768px) {
    .container-fluid h1.h3 {
        font-size: 1.3rem;
    }

    .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start !important;
    }

    .d-flex.justify-content-between .btn-success {
        width: 100%;
        justify-content: center;
    }

    .stat-card .card-body {
        padding: 1rem;
    }

    .stat-icon {
        width: 45px !important;
        height: 45px !important;
        font-size: 1.2rem;
    }

    .stat-value {
        font-size: 1.4rem !important;
    }

    .stat-label {
        font-size: 0.75rem;
    }

    .col-xl-3.col-md-6 {
        width: 50% !important;
    }

    .col-md-3 .btn-outline-primary,
    .col-md-3 .btn-outline-success,
    .col-md-3 .btn-outline-info,
    .col-md-3 .btn-outline-warning {
        padding: 0.75rem 0.5rem !important;
        font-size: 0.85rem;
    }

    .col-md-3 .btn i.fs-3 {
        font-size: 1.5rem !important;
    }

    .card-header h5 {
        font-size: 1rem;
    }

    .table-responsive {
        font-size: 0.85rem;
    }
}

@media (max-width: 576px) {
    .container-fluid h1.h3 {
        font-size: 1.1rem;
    }

    .col-xl-3.col-md-6 {
        width: 100% !important;
    }

    .col-md-3 {
        width: 50% !important;
    }

    .stat-card {
        margin-bottom: 0.5rem;
    }

    .card-title {
        font-size: 0.95rem;
    }

    .table-responsive table {
        font-size: 0.8rem;
    }

    .badge {
        font-size: 0.7rem;
    }
}
</style>

<?php
    $content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Dashboard', $content);