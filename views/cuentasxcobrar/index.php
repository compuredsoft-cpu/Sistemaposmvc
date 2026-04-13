<?php
    /**
 * Cuentas por Cobrar (Créditos)
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('cuentas_cobrar');

    $repo        = new CuentaPorCobrarRepository();
    $clienteRepo = new ClienteRepository();

    $page    = intval($_GET['page'] ?? 1);
    $filters = [
    'cliente_id' => $_GET['cliente_id'] ?? null,
    'estado'     => $_GET['estado'] ?? null,
    'vencidas'   => isset($_GET['vencidas']) ? 1 : null,
    ];
    $filters = array_filter($filters);

    $cuentas  = $repo->findAllWithFilters($filters, $page);
    $clientes = $clienteRepo->findAllActive();
    $stats    = $repo->getEstadisticas();

    ob_start();
?>

<!-- Header Moderno Rojo/Dinero -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="cuentas-header" style="background: linear-gradient(135deg, #dc2626 0%, #f59e0b 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(220, 38, 38, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i class="bi bi-wallet2-fill me-2"></i>Cuentas x Cobrar</h2>
                        <p style="font-size: 0.75rem; margin: 0; opacity: 0.9;">Gestiona créditos y pagos</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="exportarExcel('tablaCuentas', 'cuentas_cobrar')" class="btn btn-light btn-sm rounded-pill" style="color: #dc2626; font-size: 0.8rem; padding: 4px 10px;">
                            <i class="bi bi-file-excel me-1"></i>Excel
                        </button>
                        <button onclick="window.print()" class="btn btn-light btn-sm rounded-pill" style="color: #dc2626; font-size: 0.8rem; padding: 4px 10px;">
                            <i class="bi bi-printer me-1"></i>PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards Modernos -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fef2f2, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #dc2626, #ef4444);">
                        <i class="bi bi-cash-coin text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Pendiente</div>
                        <div class="fw-bold text-danger" style="font-size: 0.9rem; line-height: 1.2;"><?php echo formatCurrency($stats['total_pendiente'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fef3c7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="bi bi-exclamation-diamond text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Vencido</div>
                        <div class="fw-bold" style="font-size: 0.9rem; color: #f59e0b; line-height: 1.2;"><?php echo formatCurrency($stats['total_vencido'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #dbeafe, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                        <i class="bi bi-file-text text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Activas</div>
                        <div class="fw-bold" style="font-size: 1rem; color: #3b82f6; line-height: 1.2;"><?php echo $stats['total_cuentas'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fee2e2, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #ef4444, #f87171);">
                        <i class="bi bi-calendar-x text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Vencidas</div>
                        <div class="fw-bold text-danger" style="font-size: 1rem; line-height: 1.2;"><?php echo $stats['cuentas_vencidas'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros Modernos -->
    <div class="card border-0 shadow-lg mb-4" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);">
            <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-funnel me-2 text-danger"></i>Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Cliente</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-person text-danger"></i></span>
                        <select name="cliente_id" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            <option value="">Todos los clientes</option>
                            <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente->id; ?>" <?php echo($_GET['cliente_id'] ?? '') == $cliente->id ? 'selected' : ''; ?>>
                                <?php echo $cliente->getNombreCompleto(); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Estado</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-tag text-danger"></i></span>
                        <select name="estado" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            <option value="">Todos</option>
                            <option value="PENDIENTE" <?php echo($_GET['estado'] ?? '') === 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="PAGADA" <?php echo($_GET['estado'] ?? '') === 'PAGADA' ? 'selected' : ''; ?>>Pagada</option>
                            <option value="VENCIDA" <?php echo($_GET['estado'] ?? '') === 'VENCIDA' ? 'selected' : ''; ?>>Vencida</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Opciones</label>
                    <div class="form-check d-flex align-items-center" style="height: 38px;">
                        <input class="form-check-input border-danger" type="checkbox" name="vencidas" id="vencidas" value="1" <?php echo isset($_GET['vencidas']) ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
                        <label class="form-check-label ms-2 fw-medium text-danger" for="vencidas">
                            <i class="bi bi-exclamation-diamond me-1"></i>Solo vencidas
                        </label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-danger w-100 rounded-pill text-white" style="background: linear-gradient(135deg, #dc2626, #ef4444); border: none;">
                        <i class="bi bi-search me-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Moderna -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #dc2626 0%, #f59e0b 100%);">
            <h5 class="card-title mb-0 text-white fw-bold"><i class="bi bi-list-check me-2"></i>Listado de Cuentas (<?php echo $cuentas['total']; ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaCuentas" style="background: #ffffff;">
                    <thead style="background: linear-gradient(135deg, #fef2f2, #fee2e2);">
                        <tr>
                            <th class="fw-bold text-dark" style="border: none;">ID</th>
                            <th class="fw-bold text-dark" style="border: none;">Cliente</th>
                            <th class="fw-bold text-dark text-end" style="border: none;">Monto Total</th>
                            <th class="fw-bold text-dark text-end" style="border: none;">Pagado</th>
                            <th class="fw-bold text-dark text-end" style="border: none;">Pendiente</th>
                            <th class="fw-bold text-dark" style="border: none;">Vencimiento</th>
                            <th class="fw-bold text-dark" style="border: none;">Estado</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuentas['items'] as $cxc): ?>
                        <?php
                            $vencida  = $cxc->estaVencida();
                            $porPagar = $cxc->getPorPagar();
                        ?>
                        <tr style="transition: all 0.2s;" class="<?php echo $vencida ? 'vencida' : ''; ?>">
                            <td class="align-middle">
                                <span class="badge bg-light text-dark border" style="font-family: monospace; font-size: 0.9rem;">
                                    <i class="bi bi-hash me-1 text-danger"></i><?php echo $cxc->id; ?>
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #dc2626, #f59e0b);">
                                        <i class="bi bi-person text-white" style="font-size: 0.9rem;"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark"><?php echo $cxc->cliente_nombre; ?></span>
                                        <br><small class="text-muted"><i class="bi bi-cart me-1"></i><?php echo $cxc->venta_codigo; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle text-end">
                                <span class="fw-medium"><?php echo formatCurrency($cxc->monto_total); ?></span>
                            </td>
                            <td class="align-middle text-end">
                                <span class="fw-bold text-success"><?php echo formatCurrency($cxc->monto_pagado); ?></span>
                            </td>
                            <td class="align-middle text-end">
                                <span class="fw-bold text-danger fs-5"><?php echo formatCurrency($porPagar); ?></span>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $diasVencida = $vencida ? floor((time() - strtotime($cxc->fecha_vencimiento)) / 86400) : 0;
                                ?>
                                <div class="d-flex flex-column">
                                    <span class="badge bg-light text-dark rounded-pill border mb-1">
                                        <i class="bi bi-calendar text-danger me-1"></i><?php echo formatDate($cxc->fecha_vencimiento); ?>
                                    </span>
                                    <?php if ($vencida): ?>
                                    <span class="badge bg-danger rounded-pill" style="font-size: 0.7rem;">
                                        <i class="bi bi-exclamation-triangle me-1"></i>VENCIDA <?php echo $diasVencida; ?> días
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $estadoStyles = match ($cxc->estado) {
                                        'PAGADA'  => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-check-circle'],
                                        'VENCIDA' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-calendar-x'],
                                        default   => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-clock']
                                    };
                                ?>
                                <span class="badge" style="background: <?php echo $estadoStyles['bg']; ?>; color: <?php echo $estadoStyles['color']; ?>; padding: 8px 12px; border-radius: 20px;">
                                    <i class="bi <?php echo $estadoStyles['icon']; ?> me-1"></i><?php echo $cxc->estado; ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="ver.php?id=<?php echo $cxc->id; ?>" class="btn btn-info btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($cxc->estado !== 'PAGADA'): ?>
                                    <a href="pago.php?id=<?php echo $cxc->id; ?>" class="btn btn-success btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Registrar pago">
                                        <i class="bi bi-cash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($cuentas['items'])): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #fef2f2, #fee2e2);">
                                        <i class="bi bi-inbox fs-1 text-danger"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No se encontraron cuentas por cobrar</h5>
                                    <p class="text-muted mb-3">Las cuentas aparecerán cuando registres ventas a crédito</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación Moderna -->
        <?php if ($cuentas['total_pages'] > 1): ?>
        <div class="card-footer border-0" style="background: #f8f9fa;">
            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $cuentas['page'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $cuentas['page'] - 1; ?>&<?php echo http_build_query($filters); ?>" style="border: none;">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $cuentas['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $cuentas['page'] ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"
                           style="border: none; <?php echo $i === $cuentas['page'] ? 'background: linear-gradient(135deg, #dc2626, #f59e0b); color: white;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $cuentas['page'] >= $cuentas['total_pages'] ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill" href="?page=<?php echo $cuentas['page'] + 1; ?>&<?php echo http_build_query($filters); ?>" style="border: none;">
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
    #tablaCuentas tbody tr:hover {
        background: linear-gradient(135deg, #fef2f2, #ffffff);
        transform: scale(1.002);
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);
    }
    #tablaCuentas tbody tr.vencida {
        background: linear-gradient(135deg, #fef2f2, #ffffff);
    }
    #tablaCuentas tbody tr.vencida:hover {
        background: linear-gradient(135deg, #fee2e2, #ffffff);
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .cxc-header {
            padding: 15px !important;
        }
        .cxc-header h2 {
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
        #tablaCuentas {
            font-size: 0.8rem;
            min-width: 700px;
        }
        #tablaCuentas th,
        #tablaCuentas td {
            padding: 6px 4px;
            white-space: nowrap;
        }
    }

    @media (max-width: 576px) {
        .cxc-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
    }
</style>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Cuentas por Cobrar', $content);
