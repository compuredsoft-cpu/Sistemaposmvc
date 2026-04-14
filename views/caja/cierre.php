<?php
    /**
 * Cierre de Caja
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('caja');

    $cajaRepo  = new CajaRepository();
    $usuario   = SessionManager::getUserData();
    $usuarioId = SessionManager::getUserId();

    // Verificar que hay una caja abierta
    $caja = $cajaRepo->getAnyCajaAbierta();

    if (! $caja) {
    header('Location: index.php?error=No hay caja abierta para cerrar');
    exit;
    }

    $error   = '';
    $success = '';

    // Obtener resumen de ventas por método de pago
    $db         = Database::getConnection();
    $ventasStmt = $db->prepare("
    SELECT
        metodo_pago,
        COUNT(*) as cantidad,
        SUM(total) as total
    FROM ventas
    WHERE caja_id = ? AND estado = 'COMPLETADA'
    GROUP BY metodo_pago
");
    $ventasStmt->execute([$caja->id]);
    $ventasPorMetodo = $ventasStmt->fetchAll();

    // Calcular totales
    $totalEfectivo      = 0;
    $totalTarjeta       = 0;
    $totalTransferencia = 0;
    $totalCheque        = 0;
    $totalCredito       = 0;
    $totalVentas        = 0;

    foreach ($ventasPorMetodo as $v) {
    $totalVentas += $v['total'];
    switch ($v['metodo_pago']) {
        case 'EFECTIVO':$totalEfectivo += $v['total'];
            break;
        case 'TARJETA':$totalTarjeta += $v['total'];
            break;
        case 'TRANSFERENCIA':$totalTransferencia += $v['total'];
            break;
        case 'CHEQUE':$totalCheque += $v['total'];
            break;
        case 'CREDITO':$totalCredito += $v['total'];
            break;
    }
    }

    // Obtener gastos e ingresos
    $gastosStmt  = $db->prepare("
    SELECT
        tipo,
        SUM(monto) as total
    FROM gastos
    WHERE caja_id = ? AND estado = 1
    GROUP BY tipo
");
    $gastosStmt->execute([$caja->id]);
    $movimientos  = $gastosStmt->fetchAll();

    $totalGastos   = 0;
    $totalIngresos = 0;

    foreach ($movimientos as $m) {
    if ($m['tipo'] == 'GASTO') {
        $totalGastos += $m['total'];
    } else {
        $totalIngresos += $m['total'];
    }
    }

    // Calcular totales esperados
    $efectivoEsperado = $caja->monto_apertura + $totalEfectivo - $totalGastos;

    // Procesar cierre
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $caja->monto_cierre         = floatval($_POST['monto_cierre'] ?? 0);
        $caja->observaciones_cierre = $_POST['observaciones_cierre'] ?? null;

        if ($caja->monto_cierre < 0) {
            throw new Exception('El monto de cierre no puede ser negativo');
        }

        if ($cajaRepo->cerrarCaja($caja)) {
            header('Location: index.php?success=Caja cerrada correctamente');
            exit;
        } else {
            $error = 'Error al cerrar la caja';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    }

    ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="caja-header" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(220, 38, 38, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-x-circle me-3"></i>Cierre de Caja</h2>
                        <p class="mb-0 opacity-75">Caja #<?php echo $caja->id; ?> | Abierta desde: <?php echo date('d/m/Y H:i', strtotime($caja->fecha_apertura)); ?></p>
                    </div>
                    <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #dc2626;">
                        <i class="bi bi-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-pill" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Resumen de Ventas -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                <div class="card-body">
                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-cash me-2"></i>Efectivo</h6>
                    <h4 class="fw-bold mb-1">$<?php echo number_format($totalEfectivo, 2); ?></h4>
                    <small class="text-muted">Apertura: $<?php echo number_format($caja->monto_apertura, 2); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #faf5ff, #f3e8ff);">
                <div class="card-body">
                    <h6 class="fw-bold mb-3" style="color: #7c3aed;"><i class="bi bi-credit-card me-2"></i>Otros Métodos</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Tarjeta</small>
                            <strong>$<?php echo number_format($totalTarjeta, 2); ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Transf.</small>
                            <strong>$<?php echo number_format($totalTransferencia, 2); ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Cheque</small>
                            <strong>$<?php echo number_format($totalCheque, 2); ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Crédito</small>
                            <strong>$<?php echo number_format($totalCredito, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
                <div class="card-body">
                    <h6 class="text-success fw-bold mb-3"><i class="bi bi-calculator me-2"></i>Totales</h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Total Ventas</small>
                        <h4 class="fw-bold mb-0 text-success">$<?php echo number_format($totalVentas, 2); ?></h4>
                    </div>
                    <div>
                        <small class="text-muted d-block">Transacciones</small>
                        <strong><?php echo array_sum(array_column($ventasPorMetodo, 'cantidad')); ?> ventas</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Movimientos Adicionales -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #fef3c7, #fde68a);">
                <div class="card-body text-center">
                    <h6 class="fw-bold mb-2 text-warning"><i class="bi bi-arrow-up-circle me-2"></i>Ingresos Adicionales</h6>
                    <h4 class="fw-bold mb-0 text-warning">$<?php echo number_format($totalIngresos, 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #fef2f2, #fee2e2);">
                <div class="card-body text-center">
                    <h6 class="fw-bold mb-2 text-danger"><i class="bi bi-arrow-down-circle me-2"></i>Gastos/Salidas</h6>
                    <h4 class="fw-bold mb-0 text-danger">$<?php echo number_format($totalGastos, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de Cierre -->
    <form method="POST" id="formCierre">
        <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);">
                <h5 class="mb-0 fw-bold text-danger">
                    <i class="bi bi-x-circle me-2"></i>Confirmar Cierre
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Monto Esperado -->
                    <div class="col-md-4">
                        <div class="p-4 border rounded-3" style="background: #f8f9fa;">
                            <label class="form-label fw-bold text-muted">Efectivo Esperado</label>
                            <h3 class="fw-bold mb-0 text-primary">$<?php echo number_format($efectivoEsperado, 2); ?></h3>
                            <small class="text-muted">Monto Apertura + Ventas Efectivo - Gastos</small>
                        </div>
                    </div>

                    <!-- Monto Real -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Efectivo en Caja <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-currency-dollar text-danger fs-4"></i></span>
                            <input type="number" name="monto_cierre" id="montoCierre" class="form-control border-0 shadow-none fs-4"
                                   style="border-radius: 0 12px 12px 0; background: #f8f9fa;"
                                   step="0.01" min="0" required placeholder="0.00"
                                   value="<?php echo number_format($efectivoEsperado, 2, '.', ''); ?>">
                        </div>
                        <small class="text-muted">Ingrese el monto real contado en caja</small>
                    </div>

                    <!-- Diferencia -->
                    <div class="col-md-4">
                        <div class="p-4 border rounded-3 h-100" id="diferenciaBox" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
                            <label class="form-label fw-bold text-muted">Diferencia</label>
                            <h3 class="fw-bold mb-0 text-success" id="diferenciaValor">$0.00</h3>
                            <small class="text-muted" id="diferenciaTexto">Sin diferencia</small>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="col-12">
                        <label class="form-label fw-bold">Observaciones del Cierre</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-chat-text text-danger"></i></span>
                            <textarea name="observaciones_cierre" class="form-control border-0 shadow-none"
                                      style="border-radius: 0 12px 12px 0; background: #f8f9fa;" rows="3"
                                      placeholder="Notas sobre el cierre, diferencias encontradas, etc..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top-0 py-4" style="background: #f8f9fa;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Una vez cerrada, la caja no podrá reabrirse. Asegúrese de contar correctamente el efectivo.
                    </div>
                    <button type="button" id="btnCerrarCaja" class="btn btn-danger btn-lg rounded-pill px-5">
                        <i class="bi bi-x-circle me-2"></i>Cerrar Caja
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .caja-header {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    }
    .input-group:focus-within .input-group-text {
        background: #fee2e2 !important;
    }
    .input-group:focus-within .form-control {
        background: #fee2e2 !important;
        box-shadow: 0 0 0 0.25rem rgba(220, 38, 38, 0.25) !important;
    }
</style>

<script>
    const efectivoEsperado = <?php echo $efectivoEsperado; ?>;

    function calcularDiferencia() {
        const montoCierre = parseFloat(document.getElementById('montoCierre').value) || 0;
        const diferencia = montoCierre - efectivoEsperado;

        const diferenciaValor = document.getElementById('diferenciaValor');
        const diferenciaTexto = document.getElementById('diferenciaTexto');
        const diferenciaBox = document.getElementById('diferenciaBox');

        diferenciaValor.textContent = (diferencia >= 0 ? '+' : '') + '$' + Math.abs(diferencia).toLocaleString('es-CO', {minimumFractionDigits: 2});

        if (diferencia === 0) {
            diferenciaValor.className = 'fw-bold mb-0 text-success';
            diferenciaTexto.textContent = 'Sin diferencia ✓';
            diferenciaBox.style.background = 'linear-gradient(135deg, #ecfdf5, #d1fae5)';
        } else if (diferencia > 0) {
            diferenciaValor.className = 'fw-bold mb-0 text-warning';
            diferenciaTexto.textContent = 'Sobrante en caja';
            diferenciaBox.style.background = 'linear-gradient(135deg, #fef3c7, #fde68a)';
        } else {
            diferenciaValor.className = 'fw-bold mb-0 text-danger';
            diferenciaTexto.textContent = 'Faltante en caja';
            diferenciaBox.style.background = 'linear-gradient(135deg, #fef2f2, #fee2e2)';
        }
    }

    document.getElementById('btnCerrarCaja').addEventListener('click', function() {
        const montoCierre = parseFloat(document.getElementById('montoCierre').value) || 0;
        const diferencia = montoCierre - efectivoEsperado;

        let texto = 'Esta acción no se puede deshacer.';
        if (diferencia !== 0) {
            texto = 'Diferencia detectada: ' + (diferencia > 0 ? 'Sobrante' : 'Faltante') + ' de $' + Math.abs(diferencia).toLocaleString('es-CO', {minimumFractionDigits: 2}) + '\n\n' + texto;
        }

        showConfirm({
            title: '¿Está seguro de cerrar la caja?',
            text: texto,
            icon: 'warning',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, cerrar caja',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formCierre').submit();
            }
        });
    });

    // Calcular diferencia al cargar y al cambiar
    document.getElementById('montoCierre').addEventListener('input', calcularDiferencia);
    document.addEventListener('DOMContentLoaded', calcularDiferencia);
</script>

<?php
    $content  = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Cierre de Caja #' . $caja->id, $content);
