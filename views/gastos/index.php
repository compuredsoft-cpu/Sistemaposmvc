<?php
/**
 * Gastos y Ganancias
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('gastos');

$repo = new GastoRepository();

// Filtros de fecha
$fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-t');

$balance = $repo->getBalance($fechaDesde, $fechaHasta);
$gastosPorCategoria = $repo->getGastosPorCategoria($fechaDesde, $fechaHasta);
$gastosData = $repo->findAllWithFilters([
    'fecha_desde' => $fechaDesde,
    'fecha_hasta' => $fechaHasta,
    'tipo' => 'GASTO'
], 1, 100);
$gastos = $gastosData['items'];

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-graph-down-arrow me-2"></i>Gastos y Ganancias
        </h1>
        <a href="nuevo.php" class="btn btn-danger">
            <i class="bi bi-plus-circle me-2"></i>Registrar Gasto
        </a>
    </div>
    
    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-calendar me-2"></i>Período</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fechaDesde; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fechaHasta; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resumen -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo formatCurrency($balance['ventas'] ?? 0); ?></h3>
                    <p class="mb-0">Total Ventas</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3><?php echo formatCurrency($balance['gastos'] ?? 0); ?></h3>
                    <p class="mb-0">Total Gastos</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-<?php echo ($balance['balance'] ?? 0) >= 0 ? 'info' : 'warning'; ?> text-white">
                <div class="card-body text-center">
                    <h3><?php echo formatCurrency($balance['balance'] ?? 0); ?></h3>
                    <p class="mb-0">Balance</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gastos por Categoría -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Gastos por Categoría</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gastosPorCategoria as $cat): ?>
                        <tr>
                            <td><?php echo $cat['tipo_nombre']; ?></td>
                            <td class="text-end fw-bold"><?php echo formatCurrency($cat['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($gastosPorCategoria)): ?>
                        <tr>
                            <td colspan="2" class="text-center text-muted">No hay gastos en este período</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Listado de Gastos -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <h5 class="card-title mb-0">Detalle de Gastos</h5>
            <button onclick="exportarExcel('tablaGastos', 'gastos')" class="btn btn-success btn-sm">
                <i class="bi bi-file-excel me-2"></i>Excel
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="tablaGastos">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Categoría</th>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gastos as $gasto): ?>
                        <tr>
                            <td><?php echo formatDate($gasto->fecha); ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $gasto->tipo_nombre ?? 'Sin tipo'; ?></span>
                            </td>
                            <td><?php echo $gasto->concepto; ?></td>
                            <td class="text-end fw-bold text-danger"><?php echo formatCurrency($gasto->monto); ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="editar.php?id=<?php echo $gasto->id; ?>" class="btn btn-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="eliminar.php?id=<?php echo $gasto->id; ?>" class="btn btn-danger btn-eliminar" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($gastos)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No se encontraron gastos en este período
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Gastos y Ganancias', $content);
