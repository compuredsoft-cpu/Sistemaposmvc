<?php
    /**
 * Listado de Ventas
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('ventas');

    $repo       = new VentaRepository();
    $configRepo = new ConfiguracionRepository();
    $config     = $configRepo->getConfig();

    // Filtros
    $page    = intval($_GET['page'] ?? 1);
    $filters = [
    'cliente_id'  => $_GET['cliente_id'] ?? null,
    'estado'      => $_GET['estado'] ?? null,
    'metodo_pago' => $_GET['metodo_pago'] ?? null,
    'fecha_desde' => $_GET['fecha_desde'] ?? null,
    'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
    'busqueda'    => $_GET['busqueda'] ?? null,
    ];

    $filters = array_filter($filters);

    $ventas = $repo->findAllWithFilters($filters, $page);

    ob_start();
?>

<!-- Header Moderno Púrpura -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="ventas-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i class="bi bi-cart-check-fill me-2"></i>Gestión de Ventas</h2>
                        <p style="font-size: 0.8rem; margin: 0; opacity: 0.9;">Controla tus ventas</p>
                    </div>
                    <a href="pos.php" class="btn btn-light rounded-pill" style="color: #667eea; font-size: 0.85rem; padding: 6px 12px;">
                        <i class="bi bi-cart-plus me-1"></i>Nueva Venta
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #ede9fe, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="bi bi-cart-check text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total Ventas</div>
                        <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?php echo $ventas['total']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #dcfce7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #22c55e, #4ade80);">
                        <i class="bi bi-check-circle text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Completadas</div>
                        <?php
                            $completadas      = array_filter($ventas['items'], fn($v) => $v->estado === 'COMPLETADA');
                            $totalCompletadas = count($completadas);
                            $montoCompletadas = array_sum(array_column($completadas, 'total'));
                        ?>
                        <div class="fw-bold text-success" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalCompletadas; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fef3c7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="bi bi-clock text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Pendientes</div>
                        <?php
                            $pendientes      = array_filter($ventas['items'], fn($v) => $v->estado === 'PENDIENTE');
                            $totalPendientes = count($pendientes);
                        ?>
                        <div class="fw-bold" style="font-size: 1rem; color: #f59e0b; line-height: 1.2;"><?php echo $totalPendientes; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fee2e2, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #ef4444, #f87171);">
                        <i class="bi bi-x-circle text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Canceladas</div>
                        <?php
                            $canceladas      = array_filter($ventas['items'], fn($v) => $v->estado === 'CANCELADA');
                            $totalCanceladas = count($canceladas);
                        ?>
                        <div class="fw-bold text-danger" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalCanceladas; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros Modernos -->
    <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header border-0 py-2 py-md-3" style="background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);">
            <h5 class="card-title mb-0 fw-bold text-dark fs-6 fs-md-5"><i class="bi bi-funnel me-2 text-primary"></i>Filtros</h5>
        </div>
        <div class="card-body p-2 p-md-3">
            <form method="GET" class="row g-2 g-md-3">
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold small">Estado</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-tag text-primary"></i></span>
                        <select name="estado" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            <option value="">Todos</option>
                            <option value="COMPLETADA" <?php echo($_GET['estado'] ?? '') === 'COMPLETADA' ? 'selected' : ''; ?>>Completada</option>
                            <option value="PENDIENTE" <?php echo($_GET['estado'] ?? '') === 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="CANCELADA" <?php echo($_GET['estado'] ?? '') === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold small">Método Pago</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-credit-card text-primary"></i></span>
                        <select name="metodo_pago" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            <option value="">Todos</option>
                            <option value="EFECTIVO" <?php echo($_GET['metodo_pago'] ?? '') === 'EFECTIVO' ? 'selected' : ''; ?>>Efectivo</option>
                            <option value="TARJETA" <?php echo($_GET['metodo_pago'] ?? '') === 'TARJETA' ? 'selected' : ''; ?>>Tarjeta</option>
                            <option value="TRANSFERENCIA" <?php echo($_GET['metodo_pago'] ?? '') === 'TRANSFERENCIA' ? 'selected' : ''; ?>>Transferencia</option>
                            <option value="CREDITO" <?php echo($_GET['metodo_pago'] ?? '') === 'CREDITO' ? 'selected' : ''; ?>>Crédito</option>
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold small">Desde</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-calendar text-primary"></i></span>
                        <input type="date" name="fecha_desde" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold small">Hasta</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-calendar-check text-primary"></i></span>
                        <input type="date" name="fecha_hasta" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold small">Búsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-search text-primary"></i></span>
                        <input type="text" name="busqueda" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Código, cliente..." value="<?php echo $_GET['busqueda'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill text-white btn-sm" style="background: linear-gradient(135deg, #667eea, #764ba2); border: none;">
                        <i class="bi bi-search me-1 me-md-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Moderna -->
    <div class="card border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header border-0 py-2 py-md-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h5 class="card-title mb-0 text-white fw-bold fs-6 fs-md-5"><i class="bi bi-list-check me-2"></i>Listado de Ventas</h5>
            <div class="d-flex gap-2">
                <button onclick="exportarExcel('tablaVentas', 'ventas')" class="btn btn-light btn-sm rounded-pill" style="color: #667eea; padding: 4px 10px; font-size: 0.8rem;">
                    <i class="bi bi-file-excel"></i><span class="d-none d-md-inline ms-1">Excel</span>
                </button>
                <button onclick="window.print()" class="btn btn-light btn-sm rounded-pill" style="color: #667eea; padding: 4px 10px; font-size: 0.8rem;">
                    <i class="bi bi-printer"></i><span class="d-none d-md-inline ms-1">PDF</span>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaVentas" style="background: #ffffff;">
                    <thead style="background: linear-gradient(135deg, #ede9fe, #ddd6fe);">
                        <tr>
                            <th class="fw-bold text-dark" style="border: none;">Código</th>
                            <th class="fw-bold text-dark" style="border: none;">Cliente</th>
                            <th class="fw-bold text-dark" style="border: none;">Total</th>
                            <th class="fw-bold text-dark" style="border: none;">Método</th>
                            <th class="fw-bold text-dark" style="border: none;">Fecha</th>
                            <th class="fw-bold text-dark" style="border: none;">Estado</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas['items'] as $venta): ?>
                        <tr style="transition: all 0.2s;">
                            <td class="align-middle">
                                <span class="badge bg-light text-dark border" style="font-family: monospace; font-size: 0.9rem;">
                                    <i class="bi bi-cart-check me-1 text-primary"></i><?php echo $venta->codigo; ?>
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #667eea, #764ba2);">
                                        <i class="bi bi-person text-white" style="font-size: 0.9rem;"></i>
                                    </div>
                                    <span class="fw-medium"><?php echo $venta->cliente_nombre; ?></span>
                                </div>
                            </td>
                            <td class="align-middle text-end">
                                <span class="fw-bold text-primary fs-5"><?php echo formatCurrency($venta->total); ?></span>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $metodoStyles = match ($venta->metodo_pago) {
                                        'EFECTIVO'      => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-cash'],
                                        'TARJETA'       => ['bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'bi-credit-card'],
                                        'TRANSFERENCIA' => ['bg' => '#ede9fe', 'color' => '#6b21a8', 'icon' => 'bi-bank'],
                                        'CHEQUE'        => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-journal-text'],
                                        'CREDITO'       => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-clock-history'],
                                        default         => ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-question']
                                    };
                                ?>
                                <span class="badge" style="background: <?php echo $metodoStyles['bg']; ?>; color: <?php echo $metodoStyles['color']; ?>; padding: 6px 10px; border-radius: 20px;">
                                    <i class="bi <?php echo $metodoStyles['icon']; ?> me-1"></i><?php echo $venta->metodo_pago; ?>
                                </span>
                                <?php if ($venta->es_credito): ?>
                                <span class="badge bg-dark rounded-pill ms-1" style="font-size: 0.7rem;">
                                    <i class="bi bi-calendar me-1"></i><?php echo $venta->cuotas; ?>c
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-light text-dark rounded-pill border">
                                        <i class="bi bi-calendar text-primary me-1"></i>
                                        <?php echo formatDateTime($venta->fecha); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $estadoStyles = match ($venta->estado) {
                                        'COMPLETADA' => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-check-circle'],
                                        'PENDIENTE'  => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-clock'],
                                        'CANCELADA'  => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-x-circle'],
                                        default      => ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-question']
                                    };
                                ?>
                                <span class="badge" style="background: <?php echo $estadoStyles['bg']; ?>; color: <?php echo $estadoStyles['color']; ?>; padding: 8px 12px; border-radius: 20px;">
                                    <i class="bi <?php echo $estadoStyles['icon']; ?> me-1"></i><?php echo $venta->estado; ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="ver.php?id=<?php echo $venta->id; ?>" class="btn btn-info btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="ticket.php?id=<?php echo $venta->id; ?>" class="btn btn-secondary btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Imprimir ticket" target="_blank">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    <?php if ($venta->estado !== 'CANCELADA'): ?>
                                    <a href="anular.php?id=<?php echo $venta->id; ?>" class="btn btn-danger btn-sm rounded-circle btn-eliminar" style="width: 35px; height: 35px; padding: 0;" title="Anular venta">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ventas['items'])): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #ede9fe, #ddd6fe);">
                                        <i class="bi bi-inbox fs-1 text-primary"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No se encontraron ventas</h5>
                                    <p class="text-muted mb-3">Realiza tu primera venta para empezar</p>
                                    <a href="pos.php" class="btn btn-primary rounded-pill px-4 text-white" style="background: linear-gradient(135deg, #667eea, #764ba2); border: none;">
                                        <i class="bi bi-cart-plus me-2"></i>Ir al POS
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación Moderna -->
        <?php if ($ventas['total_pages'] > 1): ?>
        <div class="card-footer border-0" style="background: #f8f9fa;">
            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo ! $ventas['has_prev'] ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $ventas['page'] - 1; ?>&<?php echo http_build_query($filters); ?>" style="border: none;">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $ventas['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $ventas['page'] ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"
                           style="border: none; <?php echo $i === $ventas['page'] ? 'background: linear-gradient(135deg, #667eea, #764ba2); color: white;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo ! $ventas['has_next'] ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill" href="?page=<?php echo $ventas['page'] + 1; ?>&<?php echo http_build_query($filters); ?>" style="border: none;">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    #tablaVentas tbody tr:hover {
        background: linear-gradient(135deg, #ede9fe, #ffffff);
        transform: scale(1.002);
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .ventas-header {
            padding: 15px !important;
        }
        .ventas-header h2 {
            font-size: 1.2rem;
        }
        .row.g-3.mb-4 > .col-md-3 {
            width: 50% !important;
            padding: 5px !important;
        }
        .card-body.p-4 {
            padding: 0.75rem !important;
        }
        form .col-md-2,
        form .col-md-3 {
            width: 100% !important;
            margin-bottom: 8px;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #tablaVentas {
            font-size: 0.8rem;
            min-width: 800px;
        }
        #tablaVentas th,
        #tablaVentas td {
            padding: 6px 4px;
            white-space: nowrap;
        }
        .pagination .page-link {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 576px) {
        .ventas-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        .ventas-header .btn-lg {
            width: 100%;
            justify-content: center;
        }
        #tablaVentas td .badge {
            font-size: 0.65rem;
            padding: 3px 6px;
        }
    }
</style>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Listado de Ventas', $content, [SITE_URL . '/assets/js/main.js']);
