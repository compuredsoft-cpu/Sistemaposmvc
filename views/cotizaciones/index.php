<?php
    /**
 * Listado de Cotizaciones
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('cotizaciones');

    $repo        = new CotizacionRepository();
    $clienteRepo = new ClienteRepository();

    $page    = intval($_GET['page'] ?? 1);
    $filters = [
    'cliente_id' => $_GET['cliente_id'] ?? null,
    'estado'     => $_GET['estado'] ?? null,
    ];
    $filters = array_filter($filters);

    $cotizaciones = $repo->findAllWithFilters($filters, $page);
    $clientes     = $clienteRepo->findAllActive();

    ob_start();
?>

<!-- Header Moderno Naranja/Dorado -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="cotizaciones-header"
                style="background: linear-gradient(135deg, #f97316 0%, #fbbf24 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(249, 115, 22, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i
                                class="bi bi-file-earmark-text me-2"></i>Cotizaciones</h2>
                        <p style="font-size: 0.75rem; margin: 0; opacity: 0.9;">Gestiona tus presupuestos</p>
                    </div>
                    <a href="nueva.php" class="btn btn-light rounded-pill"
                        style="color: #f97316; font-size: 0.85rem; padding: 6px 12px;">
                        <i class="bi bi-plus-circle me-1"></i>Nueva
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"
                style="border-radius: 10px; background: linear-gradient(135deg, #fff7ed, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2"
                        style="width: 32px; height: 32px; background: linear-gradient(135deg, #f97316, #fbbf24);">
                        <i class="bi bi-file-earmark-text text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total</div>
                        <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;">
                            <?php echo $cotizaciones['total']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"
                style="border-radius: 10px; background: linear-gradient(135deg, #fef3c7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2"
                        style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="bi bi-clock text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Pendientes</div>
                        <?php
                            $pendientes      = array_filter($cotizaciones['items'], fn($c) => $c->estado === 'PENDIENTE');
                            $totalPendientes = count($pendientes);
                        ?>
                        <div class="fw-bold" style="font-size: 1rem; color: #f59e0b; line-height: 1.2;">
                            <?php echo $totalPendientes; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"
                style="border-radius: 10px; background: linear-gradient(135deg, #dbeafe, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2"
                        style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                        <i class="bi bi-check-circle text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Aprobadas</div>
                        <?php
                            $aprobadas      = array_filter($cotizaciones['items'], fn($c) => $c->estado === 'APROBADA');
                            $totalAprobadas = count($aprobadas);
                        ?>
                        <div class="fw-bold" style="font-size: 1rem; color: #3b82f6; line-height: 1.2;">
                            <?php echo $totalAprobadas; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"
                style="border-radius: 10px; background: linear-gradient(135deg, #dcfce7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2"
                        style="width: 32px; height: 32px; background: linear-gradient(135deg, #22c55e, #4ade80);">
                        <i class="bi bi-cart-check text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Convertidas</div>
                        <?php
                            $convertidas      = array_filter($cotizaciones['items'], fn($c) => $c->estado === 'CONVERTIDA');
                            $totalConvertidas = count($convertidas);
                        ?>
                        <div class="fw-bold text-success" style="font-size: 1rem; line-height: 1.2;">
                            <?php echo $totalConvertidas; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros Modernos -->
    <div class="card border-0 shadow-lg mb-4" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);">
            <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-funnel me-2 text-warning"></i>Filtros de
                Búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Cliente</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i
                                class="bi bi-person text-warning"></i></span>
                        <select name="cliente_id" class="form-select border-0 shadow-none"
                            style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            <option value="">Todos los clientes</option>
                            <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente->id; ?>"
                                <?php echo($_GET['cliente_id'] ?? '') == $cliente->id ? 'selected' : ''; ?>>
                                <?php echo $cliente->getNombreCompleto(); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Estado</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i
                                class="bi bi-tag text-warning"></i></span>
                        <select name="estado" class="form-select border-0 shadow-none"
                            style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            <option value="">Todos</option>
                            <option value="PENDIENTE"
                                <?php echo($_GET['estado'] ?? '') === 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente
                            </option>
                            <option value="APROBADA"
                                <?php echo($_GET['estado'] ?? '') === 'APROBADA' ? 'selected' : ''; ?>>Aprobada</option>
                            <option value="RECHAZADA"
                                <?php echo($_GET['estado'] ?? '') === 'RECHAZADA' ? 'selected' : ''; ?>>Rechazada
                            </option>
                            <option value="CONVERTIDA"
                                <?php echo($_GET['estado'] ?? '') === 'CONVERTIDA' ? 'selected' : ''; ?>>Convertida
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Rango de Fechas</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i
                                class="bi bi-calendar text-warning"></i></span>
                        <input type="date" class="form-control border-0 shadow-none"
                            style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning w-100 rounded-pill text-white"
                        style="background: linear-gradient(135deg, #f97316, #fbbf24); border: none;">
                        <i class="bi bi-search me-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Moderna -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center"
            style="background: linear-gradient(135deg, #f97316 0%, #fbbf24 100%);">
            <h5 class="card-title mb-0 text-white fw-bold"><i class="bi bi-list-check me-2"></i>Listado de Cotizaciones
            </h5>
            <div class="d-flex gap-2">
                <button onclick="exportarExcel('tablaCotizaciones', 'cotizaciones')"
                    class="btn btn-light btn-sm rounded-pill" style="color: #f97316;">
                    <i class="bi bi-file-excel me-1"></i>Excel
                </button>
                <button onclick="window.print()" class="btn btn-light btn-sm rounded-pill" style="color: #f97316;">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaCotizaciones" style="background: #ffffff;">
                    <thead style="background: linear-gradient(135deg, #fff7ed, #ffedd5);">
                        <tr>
                            <th class="fw-bold text-dark" style="border: none;">Código</th>
                            <th class="fw-bold text-dark" style="border: none;">Cliente</th>
                            <th class="fw-bold text-dark" style="border: none;">Total</th>
                            <th class="fw-bold text-dark" style="border: none;">Válida hasta</th>
                            <th class="fw-bold text-dark" style="border: none;">Estado</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotizaciones['items'] as $cot): ?>
                        <tr style="transition: all 0.2s;">
                            <td class="align-middle">
                                <span class="badge bg-light text-dark border"
                                    style="font-family: monospace; font-size: 0.9rem;">
                                    <i class="bi bi-file-earmark-text me-1 text-warning"></i><?php echo $cot->codigo; ?>
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                        style="width: 35px; height: 35px; background: linear-gradient(135deg, #f97316, #fbbf24);">
                                        <i class="bi bi-person text-white" style="font-size: 0.9rem;"></i>
                                    </div>
                                    <span class="fw-medium"><?php echo $cot->cliente_nombre; ?></span>
                                </div>
                            </td>
                            <td class="align-middle text-end">
                                <span
                                    class="fw-bold text-warning fs-5"><?php echo formatCurrency($cot->total); ?></span>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $diasRestantes = (strtotime($cot->fecha_vencimiento) - time()) / 86400;
                                    $badgeClass    = $diasRestantes < 0 ? 'bg-danger' : ($diasRestantes < 7 ? 'bg-warning' : 'bg-success');
                                    $iconClass     = $diasRestantes < 0 ? 'bi-calendar-x' : ($diasRestantes < 7 ? 'bi-calendar-week' : 'bi-calendar-check');
                                ?>
                                <div class="d-flex align-items-center">
                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill me-2">
                                        <i class="bi <?php echo $iconClass; ?> me-1"></i>
                                        <?php echo $diasRestantes < 0 ? 'Vencida' : floor($diasRestantes) . ' días'; ?>
                                    </span>
                                    <small class="text-muted"><?php echo formatDate($cot->fecha_vencimiento); ?></small>
                                </div>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $estadoStyles = match ($cot->estado) {
                                        'PENDIENTE'  => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-clock'],
                                        'APROBADA'   => ['bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'bi-check-circle'],
                                        'RECHAZADA'  => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-x-circle'],
                                        'CONVERTIDA' => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-cart-check'],
                                        default      => ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-question']
                                    };
                                ?>
                                <span class="badge"
                                    style="background: <?php echo $estadoStyles['bg']; ?>; color: <?php echo $estadoStyles['color']; ?>; padding: 8px 12px; border-radius: 20px;">
                                    <i
                                        class="bi <?php echo $estadoStyles['icon']; ?> me-1"></i><?php echo $cot->estado; ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="ver.php?id=<?php echo $cot->id; ?>"
                                        class="btn btn-info btn-sm rounded-circle"
                                        style="width: 35px; height: 35px; padding: 0;" title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($cot->estado === 'PENDIENTE'): ?>
                                    <a href="convertir.php?id=<?php echo $cot->id; ?>"
                                        class="btn btn-success btn-sm rounded-circle"
                                        style="width: 35px; height: 35px; padding: 0;" title="Convertir a venta">
                                        <i class="bi bi-cart-check"></i>
                                    </a>
                                    <a href="eliminar.php?id=<?php echo $cot->id; ?>"
                                        class="btn btn-danger btn-sm rounded-circle btn-eliminar"
                                        style="width: 35px; height: 35px; padding: 0;" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($cotizaciones['items'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3"
                                        style="width: 80px; height: 80px; background: linear-gradient(135deg, #fff7ed, #ffedd5);">
                                        <i class="bi bi-inbox fs-1 text-warning"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No se encontraron cotizaciones</h5>
                                    <p class="text-muted mb-3">Crea tu primera cotización para empezar</p>
                                    <a href="nueva.php" class="btn btn-warning rounded-pill px-4 text-white"
                                        style="background: linear-gradient(135deg, #f97316, #fbbf24); border: none;">
                                        <i class="bi bi-plus-circle me-2"></i>Crear Cotización
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($cotizaciones['total_pages'] > 1): ?>
        <div class="card-footer border-0" style="background: #f8f9fa;">
            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $cotizaciones['page'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill me-1"
                            href="?page=<?php echo $cotizaciones['page'] - 1; ?>&<?php echo http_build_query($filters); ?>"
                            style="border: none;">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $cotizaciones['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $cotizaciones['page'] ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill me-1"
                            href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"
                            style="border: none; <?php echo $i === $cotizaciones['page'] ? 'background: linear-gradient(135deg, #f97316, #fbbf24); color: white;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li
                        class="page-item <?php echo $cotizaciones['page'] >= $cotizaciones['total_pages'] ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill"
                            href="?page=<?php echo $cotizaciones['page'] + 1; ?>&<?php echo http_build_query($filters); ?>"
                            style="border: none;">
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
#tablaCotizaciones tbody tr:hover {
    background: linear-gradient(135deg, #fff7ed, #ffffff);
    transform: scale(1.002);
    box-shadow: 0 2px 8px rgba(249, 115, 22, 0.1);
}

/* Responsive styles */
@media (max-width: 768px) {
    .cotizaciones-header {
        padding: 15px !important;
    }

    .cotizaciones-header h2 {
        font-size: 1.2rem;
    }

    .row.g-3.mb-4>.col-md-3 {
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

    #tablaCotizaciones {
        font-size: 0.8rem;
        min-width: 700px;
    }

    #tablaCotizaciones th,
    #tablaCotizaciones td {
        padding: 6px 4px;
        white-space: nowrap;
    }
}

@media (max-width: 576px) {
    .cotizaciones-header .d-flex.justify-content-between {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}
</style>

<?php
    $content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Cotizaciones', $content);