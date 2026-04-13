<?php
    /**
 * Apertura y Cierre de Caja
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('caja');

    $repo      = new CajaRepository();
    $ventaRepo = new VentaRepository();
    $userId    = SessionManager::getUserId();

    $caja      = $repo->getCajaAbierta($userId);
    $historial = $repo->findAllWithUser(['usuario_id' => $userId], 1, 5);

    // Si hay caja abierta, obtener ventas del día
    $ventasHoy = ['items' => [], 'total' => 0];
    if ($caja) {
    $ventasHoy = $ventaRepo->findAllWithFilters(['caja_id' => $caja->id, 'estado' => 'COMPLETADA'], 1, 100);
    }

    ob_start();
?>

<div class="container-fluid" style="max-width: 100%; padding: 10px;">
    <h1 style="font-size: 1.2rem; margin-bottom: 15px; font-weight: bold;">
        <i class="bi bi-cash-coin me-2"></i>Gestión de Caja
    </h1>

    <?php if (! $caja): ?>
    <!-- Sin caja abierta - Formulario de apertura -->
    <div class="card shadow-sm border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="card-title mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>Caja Cerrada
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted">No hay una caja abierta. Debe realizar la apertura para poder procesar ventas.</p>
            <a href="apertura.php" class="btn btn-success btn-lg">
                <i class="bi bi-unlock me-2"></i>Abrir Caja
            </a>
        </div>
    </div>
    <?php else: ?>
    <!-- Caja abierta - Resumen -->
    <div class="card shadow-sm border-success mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-success text-white" style="padding: 12px 15px;">
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <h5 style="font-size: 0.95rem; margin: 0; font-weight: bold;">
                    <i class="bi bi-check-circle me-2"></i>Caja Abierta
                </h5>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <small style="font-size: 0.75rem; opacity: 0.9;"><?php echo formatDateTime($caja->fecha_apertura); ?></small>
                    <span class="badge bg-light text-dark" style="font-size: 0.7rem;">ID: #<?php echo $caja->id; ?></span>
                </div>
            </div>
        </div>
        <div class="card-body" style="padding: 10px;">
            <div class="row g-2">
                <div class="col-6">
                    <div class="card bg-light h-100" style="border-radius: 8px;">
                        <div class="card-body text-center p-2">
                            <div class="fw-bold text-success" style="font-size: 0.95rem;"><?php echo formatCurrency($caja->monto_apertura); ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;">Monto Apertura</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-light h-100" style="border-radius: 8px;">
                        <div class="card-body text-center p-2">
                            <div class="fw-bold text-primary" style="font-size: 0.95rem;"><?php echo formatCurrency($caja->total_ventas); ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;">Total Ventas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-light h-100" style="border-radius: 8px;">
                        <div class="card-body text-center p-2">
                            <div class="fw-bold text-info" style="font-size: 0.95rem;"><?php echo formatCurrency($caja->total_efectivo); ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;">Efectivo</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-light h-100" style="border-radius: 8px;">
                        <div class="card-body text-center p-2">
                            <div class="fw-bold text-warning" style="font-size: 0.95rem;"><?php echo formatCurrency($caja->total_tarjeta); ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;">Tarjetas</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3" style="display: flex; flex-direction: column; gap: 8px;">
                <a href="cierre.php" class="btn btn-danger" style="font-size: 0.9rem; padding: 10px; border-radius: 8px;">
                    <i class="bi bi-lock me-2"></i>Cerrar Caja
                </a>
                <a href="movimientos.php?caja_id=<?php echo $caja->id; ?>" class="btn btn-info" style="font-size: 0.9rem; padding: 10px; border-radius: 8px;">
                    <i class="bi bi-list-ul me-2"></i>Ver Movimientos
                </a>
            </div>
        </div>
    </div>

    <!-- Ventas del día -->
    <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header" style="padding: 12px 15px; background: #f8f9fa;">
            <h5 style="font-size: 0.95rem; margin: 0; font-weight: bold;">
                <i class="bi bi-cart-check me-2"></i>Ventas (<?php echo count($ventasHoy['items']); ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Método</th>
                            <th>Hora</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventasHoy['items'] as $venta): ?>
                        <tr>
                            <td><code><?php echo $venta->codigo; ?></code></td>
                            <td><?php echo $venta->cliente_nombre; ?></td>
                            <td class="text-end fw-bold"><?php echo formatCurrency($venta->total); ?></td>
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
                            <td><?php echo date('H:i', strtotime($venta->fecha)); ?></td>
                            <td class="text-center">
                                <a href="../ventas/ticket.php?id=<?php echo $venta->id; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                    <i class="bi bi-receipt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ventasHoy['items'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">Aún no hay ventas en esta caja</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historial de Cajas -->
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-clock-history me-2"></i>Historial de Cajas
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Fecha Apertura</th>
                            <th>Fecha Cierre</th>
                            <th>Monto Apertura</th>
                            <th>Total Ventas</th>
                            <th>Monto Cierre</th>
                            <th>Diferencia</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial['items'] as $hist): ?>
                        <tr>
                            <td>#<?php echo $hist->id; ?></td>
                            <td><?php echo formatDateTime($hist->fecha_apertura); ?></td>
                            <td><?php echo $hist->fecha_cierre ? formatDateTime($hist->fecha_cierre) : '-'; ?></td>
                            <td><?php echo formatCurrency($hist->monto_apertura); ?></td>
                            <td><?php echo formatCurrency($hist->total_ventas); ?></td>
                            <td><?php echo $hist->monto_cierre > 0 ? formatCurrency($hist->monto_cierre) : '-'; ?></td>
                            <td>
                                <?php if ($hist->diferencia != 0): ?>
                                <span class="badge bg-<?php echo $hist->diferencia >= 0 ? 'success' : 'danger'; ?>">
                                    <?php echo formatCurrency($hist->diferencia); ?>
                                </span>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $hist->estado === 'ABIERTA' ? 'success' : 'secondary'; ?>">
                                    <?php echo $hist->estado; ?>
                                </span>
                            </td>
                            <td>
                                <a href="resumen.php?id=<?php echo $hist->id; ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Responsive styles */
    @media (max-width: 768px) {
        .container-fluid h1.h3 {
            font-size: 1.3rem;
        }
        .card-header h5 {
            font-size: 1rem;
        }
        .row.g-4 > .col-md-3 {
            width: 50% !important;
            padding: 5px !important;
        }
        .row.g-4 .card-body {
            padding: 0.75rem !important;
        }
        .row.g-4 h4 {
            font-size: 1.1rem;
        }
        .row.g-4 p {
            font-size: 0.8rem;
        }
        .d-flex.gap-2 {
            flex-direction: column;
            gap: 10px !important;
        }
        .d-flex.gap-2 .btn-lg {
            font-size: 0.9rem;
            padding: 8px 15px !important;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table {
            font-size: 0.8rem;
            min-width: 700px;
        }
        table th,
        table td {
            padding: 6px 4px;
            white-space: nowrap;
        }
        .row > .col-md-3.mb-2 {
            width: 50% !important;
        }
    }

    @media (max-width: 576px) {
        .container-fluid h1.h3 {
            font-size: 1.1rem;
        }
        .d-flex.justify-content-between.align-items-center {
            flex-direction: column;
            gap: 10px;
            align-items: flex-start !important;
        }
        .row > .col-md-3.mb-2 {
            width: 100% !important;
        }
    }
</style>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Caja', $content);
