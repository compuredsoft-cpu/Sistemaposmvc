<?php
/**
 * Movimientos de Caja
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('caja');

$cajaRepo = new CajaRepository();
$usuario = SessionManager::getUserData();

// Obtener caja
$cajaId = intval($_GET['caja_id'] ?? 0);
$caja = $cajaRepo->findById($cajaId);

if (!$caja) {
    header('Location: index.php?error=Caja no encontrada');
    exit;
}

// Obtener movimientos (ventas y gastos) de esta caja
$db = Database::getConnection();

// Ventas de la caja
$ventasStmt = $db->prepare("
    SELECT v.*, c.nombre as cliente_nombre 
    FROM ventas v 
    LEFT JOIN clientes c ON v.cliente_id = c.id 
    WHERE v.caja_id = ? AND v.estado != 'CANCELADA'
    ORDER BY v.fecha DESC
");
$ventasStmt->execute([$cajaId]);
$ventas = $ventasStmt->fetchAll();

// Gastos/Ingresos de la caja
$gastosStmt = $db->prepare("
    SELECT g.*, tg.nombre as tipo_gasto_nombre, tg.tipo as tipo_movimiento
    FROM gastos g
    JOIN tipos_gasto tg ON g.tipo_gasto_id = tg.id
    WHERE g.caja_id = ? AND g.estado = 1
    ORDER BY g.fecha DESC
");
$gastosStmt->execute([$cajaId]);
$gastos = $gastosStmt->fetchAll();

// Combinar y ordenar movimientos
$movimientos = [];

foreach ($ventas as $v) {
    $movimientos[] = [
        'tipo' => 'VENTA',
        'fecha' => $v['fecha'],
        'concepto' => 'Venta #' . $v['id'] . ($v['cliente_nombre'] ? ' - ' . $v['cliente_nombre'] : ''),
        'metodo_pago' => $v['metodo_pago'],
        'monto' => $v['total'],
        'estado' => $v['estado'],
        'id' => $v['id']
    ];
}

foreach ($gastos as $g) {
    $movimientos[] = [
        'tipo' => $g['tipo'] == 'INGRESO' ? 'INGRESO' : 'GASTO',
        'fecha' => $g['fecha'],
        'concepto' => $g['concepto'] . ' (' . $g['tipo_gasto_nombre'] . ')',
        'metodo_pago' => $g['metodo_pago'],
        'monto' => $g['monto'],
        'estado' => 'ACTIVO',
        'id' => $g['id']
    ];
}

// Ordenar por fecha descendente
usort($movimientos, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

// Calcular totales
$totalVentas = 0;
$totalGastos = 0;
$totalIngresos = 0;

foreach ($movimientos as $m) {
    if ($m['tipo'] == 'VENTA') $totalVentas += $m['monto'];
    elseif ($m['tipo'] == 'INGRESO') $totalIngresos += $m['monto'];
    elseif ($m['tipo'] == 'GASTO') $totalGastos += $m['monto'];
}

ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="caja-header" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(217, 119, 6, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-cash-register me-3"></i>Movimientos de Caja</h2>
                        <p class="mb-0 opacity-75">
                            Caja #<?php echo $caja->id; ?> | 
                            Estado: <span class="badge bg-<?php echo $caja->estado == 'ABIERTA' ? 'success' : 'secondary'; ?> rounded-pill"><?php echo $caja->estado; ?></span>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #d97706;">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                <div class="card-body text-center">
                    <h4 class="fw-bold mb-1 text-primary">$<?php echo number_format($caja->monto_apertura, 2); ?></h4>
                    <small class="text-muted">Monto Apertura</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
                <div class="card-body text-center">
                    <h4 class="fw-bold mb-1 text-success">$<?php echo number_format($totalVentas + $totalIngresos, 2); ?></h4>
                    <small class="text-muted">Entradas (Ventas + Ingresos)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #fef2f2, #fee2e2);">
                <div class="card-body text-center">
                    <h4 class="fw-bold mb-1 text-danger">$<?php echo number_format($totalGastos, 2); ?></h4>
                    <small class="text-muted">Salidas (Gastos)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #faf5ff, #f3e8ff);">
                <div class="card-body text-center">
                    <h4 class="fw-bold mb-1" style="color: #7c3aed;">$<?php echo number_format($caja->monto_apertura + $totalVentas + $totalIngresos - $totalGastos, 2); ?></h4>
                    <small class="text-muted">Saldo Estimado</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Caja -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block">Fecha Apertura</small>
                    <strong><?php echo $caja->fecha_apertura ? date('d/m/Y H:i', strtotime($caja->fecha_apertura)) : 'N/A'; ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Fecha Cierre</small>
                    <strong><?php echo $caja->fecha_cierre ? date('d/m/Y H:i', strtotime($caja->fecha_cierre)) : 'Sin cerrar'; ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Monto Cierre</small>
                    <strong class="<?php echo $caja->monto_cierre > 0 ? 'text-success' : 'text-muted'; ?>">
                        $<?php echo number_format($caja->monto_cierre, 2); ?>
                    </strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Diferencia</small>
                    <strong class="<?php echo $caja->diferencia != 0 ? ($caja->diferencia > 0 ? 'text-success' : 'text-danger') : 'text-muted'; ?>">
                        $<?php echo number_format($caja->diferencia, 2); ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Movimientos -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="bi bi-list-ul me-2"></i>Listado de Movimientos
                <span class="badge bg-dark rounded-pill ms-2"><?php echo count($movimientos); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: #f9fafb;">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Método de Pago</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No se encontraron movimientos en esta caja
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($m['fecha'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $m['tipo'] == 'VENTA' ? 'primary' : 
                                            ($m['tipo'] == 'INGRESO' ? 'success' : 'danger'); 
                                    ?> rounded-pill">
                                        <i class="bi bi-<?php 
                                            echo $m['tipo'] == 'VENTA' ? 'cart' : 
                                                ($m['tipo'] == 'INGRESO' ? 'arrow-up-circle' : 'arrow-down-circle'); 
                                        ?> me-1"></i>
                                        <?php echo $m['tipo']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($m['concepto']); ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo $m['metodo_pago']; ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold <?php echo $m['tipo'] == 'GASTO' ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo $m['tipo'] == 'GASTO' ? '-' : '+'; ?>$<?php echo number_format($m['monto'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot style="background: #f9fafb;">
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">TOTAL NETO:</td>
                            <td class="text-end fs-5 <?php echo ($totalVentas + $totalIngresos - $totalGastos) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                $<?php echo number_format($totalVentas + $totalIngresos - $totalGastos, 2); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .caja-header {
        background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
    }
    .table th {
        font-weight: 600;
        color: #374151;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .caja-header {
            padding: 15px !important;
        }
        .caja-header h2 {
            font-size: 1.2rem;
        }
        .caja-header p {
            font-size: 0.85rem;
        }
        .row.mb-4 > .col-md-3 {
            width: 50% !important;
            padding: 5px !important;
        }
        .row.mb-4 .card-body {
            padding: 0.75rem !important;
        }
        .row.mb-4 h4 {
            font-size: 1.1rem;
        }
        .row.mb-4 small {
            font-size: 0.7rem;
        }
        .card-body .row > .col-md-3 {
            width: 50% !important;
            margin-bottom: 10px;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table {
            font-size: 0.8rem;
            min-width: 600px;
        }
        table th,
        table td {
            padding: 6px 4px;
            white-space: nowrap;
        }
    }
    
    @media (max-width: 576px) {
        .caja-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
        }
        .row.mb-4 > .col-md-3 {
            width: 100% !important;
        }
        .card-header h5 {
            font-size: 0.9rem;
        }
        table td .badge {
            font-size: 0.65rem;
            padding: 3px 6px;
        }
    }
</style>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Movimientos Caja #' . $caja->id, $content);
