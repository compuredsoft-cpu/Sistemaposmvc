<?php
    /**
 * Listado de Compras
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('compras');

    $repo          = new CompraRepository();
    $proveedorRepo = new ProveedorRepository();

    $page    = intval($_GET['page'] ?? 1);
    $filters = [
    'proveedor_id' => $_GET['proveedor_id'] ?? null,
    'estado'       => $_GET['estado'] ?? null,
    'fecha_desde'  => $_GET['fecha_desde'] ?? null,
    'fecha_hasta'  => $_GET['fecha_hasta'] ?? null,
    ];
    $filters = array_filter($filters);

    $compras     = $repo->findAllWithFilters($filters, $page);
    $proveedores = $proveedorRepo->findAllActive();

    ob_start();
?>

<!-- Header Moderno Verde -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="compras-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(17, 153, 142, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i class="bi bi-bag-plus-fill me-2"></i>Compras</h2>
                        <p style="font-size: 0.75rem; margin: 0; opacity: 0.9;">Controla tus compras</p>
                    </div>
                    <a href="nueva.php" class="btn btn-light rounded-pill" style="color: #11998e; font-size: 0.85rem; padding: 6px 12px;">
                        <i class="bi bi-plus-circle me-1"></i>Nueva
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #ecfdf5, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #11998e, #38ef7d);">
                        <i class="bi bi-bag-plus text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total</div>
                        <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?php echo $compras['total']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #ecfdf5, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #10b981, #34d399);">
                        <i class="bi bi-check-circle text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Recibidas</div>
                        <?php
                            $recibidas      = array_filter($compras['items'], fn($c) => $c->estado === 'RECIBIDA');
                            $totalRecibidas = count($recibidas);
                            $montoRecibidas = array_sum(array_column($recibidas, 'total'));
                        ?>
                        <div class="fw-bold text-success" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalRecibidas; ?></div>
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
                            $pendientes      = array_filter($compras['items'], fn($c) => $c->estado === 'PENDIENTE');
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
                            $canceladas      = array_filter($compras['items'], fn($c) => $c->estado === 'CANCELADA');
                            $totalCanceladas = count($canceladas);
                        ?>
                        <div class="fw-bold text-danger" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalCanceladas; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros Modernos -->
    <div class="card border-0 shadow-lg mb-4" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);">
            <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-funnel me-2 text-success"></i>Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Proveedor</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-building text-success"></i></span>
                        <select name="proveedor_id" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            <option value="">Todos los proveedores</option>
                            <?php foreach ($proveedores as $prov): ?>
                            <option value="<?php echo $prov->id; ?>" <?php echo($_GET['proveedor_id'] ?? '') == $prov->id ? 'selected' : ''; ?>>
                                <?php echo $prov->nombre; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Estado</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-tag text-success"></i></span>
                        <select name="estado" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            <option value="">Todos</option>
                            <option value="RECIBIDA" <?php echo($_GET['estado'] ?? '') === 'RECIBIDA' ? 'selected' : ''; ?>>Recibida</option>
                            <option value="PENDIENTE" <?php echo($_GET['estado'] ?? '') === 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="CANCELADA" <?php echo($_GET['estado'] ?? '') === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Desde</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-calendar text-success"></i></span>
                        <input type="date" name="fecha_desde" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Hasta</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-calendar-check text-success"></i></span>
                        <input type="date" name="fecha_hasta" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100 rounded-pill text-white" style="background: linear-gradient(135deg, #11998e, #38ef7d); border: none;">
                        <i class="bi bi-search me-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Moderna -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <h5 class="card-title mb-0 text-white fw-bold"><i class="bi bi-list-check me-2"></i>Listado de Compras</h5>
            <div class="d-flex gap-2">
                <button onclick="exportarExcel('tablaCompras', 'compras')" class="btn btn-light btn-sm rounded-pill" style="color: #11998e;">
                    <i class="bi bi-file-excel me-1"></i>Excel
                </button>
                <button onclick="window.print()" class="btn btn-light btn-sm rounded-pill" style="color: #11998e;">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaCompras" style="background: #ffffff;">
                    <thead style="background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
                        <tr>
                            <th class="fw-bold text-dark" style="border: none;">Código</th>
                            <th class="fw-bold text-dark" style="border: none;">Proveedor</th>
                            <th class="fw-bold text-dark" style="border: none;">Total</th>
                            <th class="fw-bold text-dark" style="border: none;">Fecha</th>
                            <th class="fw-bold text-dark" style="border: none;">Estado</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras['items'] as $compra): ?>
                        <tr style="transition: all 0.2s;">
                            <td class="align-middle">
                                <span class="badge bg-light text-dark border" style="font-family: monospace; font-size: 0.9rem;">
                                    <i class="bi bi-bag-plus me-1 text-success"></i><?php echo $compra->codigo; ?>
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #11998e, #38ef7d);">
                                        <i class="bi bi-building text-white" style="font-size: 0.9rem;"></i>
                                    </div>
                                    <span class="fw-medium"><?php echo $compra->proveedor_nombre; ?></span>
                                </div>
                            </td>
                            <td class="align-middle text-end">
                                <span class="fw-bold text-success fs-5"><?php echo formatCurrency($compra->total); ?></span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-light text-dark rounded-pill me-2 border">
                                        <i class="bi bi-calendar text-success me-1"></i>
                                        <?php echo formatDate($compra->fecha); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $estadoStyles = match ($compra->estado) {
                                        'RECIBIDA'  => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-check-circle'],
                                        'PENDIENTE' => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-clock'],
                                        'CANCELADA' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-x-circle'],
                                        default     => ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-question']
                                    };
                                ?>
                                <span class="badge" style="background: <?php echo $estadoStyles['bg']; ?>; color: <?php echo $estadoStyles['color']; ?>; padding: 8px 12px; border-radius: 20px;">
                                    <i class="bi <?php echo $estadoStyles['icon']; ?> me-1"></i><?php echo $compra->estado; ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="ver.php?id=<?php echo $compra->id; ?>" class="btn btn-info btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($compra->estado !== 'CANCELADA'): ?>
                                    <a href="anular.php?id=<?php echo $compra->id; ?>" class="btn btn-danger btn-sm rounded-circle btn-eliminar" style="width: 35px; height: 35px; padding: 0;" title="Anular compra">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($compras['items'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
                                        <i class="bi bi-inbox fs-1 text-success"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No se encontraron compras</h5>
                                    <p class="text-muted mb-3">Registra tu primera compra para empezar</p>
                                    <a href="nueva.php" class="btn btn-success rounded-pill px-4 text-white" style="background: linear-gradient(135deg, #11998e, #38ef7d); border: none;">
                                        <i class="bi bi-plus-circle me-2"></i>Registrar Compra
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($compras['total_pages'] > 1): ?>
        <div class="card-footer border-0" style="background: #f8f9fa;">
            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $compras['page'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $compras['page'] - 1; ?>&<?php echo http_build_query($filters); ?>" style="border: none;">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $compras['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $compras['page'] ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"
                           style="border: none; <?php echo $i === $compras['page'] ? 'background: linear-gradient(135deg, #11998e, #38ef7d); color: white;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $compras['page'] >= $compras['total_pages'] ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill" href="?page=<?php echo $compras['page'] + 1; ?>&<?php echo http_build_query($filters); ?>" style="border: none;">
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
    #tablaCompras tbody tr:hover {
        background: linear-gradient(135deg, #ecfdf5, #ffffff);
        transform: scale(1.002);
        box-shadow: 0 2px 8px rgba(17, 153, 142, 0.1);
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .compras-header {
            padding: 15px !important;
        }
        .compras-header h2 {
            font-size: 1.2rem;
        }
        .row.g-3.mb-4 > .col-md-3 {
            width: 50% !important;
            padding: 5px !important;
        }
        .card-body.p-4 {
            padding: 0.75rem !important;
        }
        form .col-md-3,
        form .col-md-2 {
            width: 100% !important;
            margin-bottom: 8px;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #tablaCompras {
            font-size: 0.8rem;
            min-width: 700px;
        }
        #tablaCompras th,
        #tablaCompras td {
            padding: 6px 4px;
            white-space: nowrap;
        }
    }

    @media (max-width: 576px) {
        .compras-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
    }
</style>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Compras', $content);
