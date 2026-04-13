<?php
/**
 * Registrar Pago a Cuenta por Cobrar
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('cuentas_cobrar');

$repo = new CuentaPorCobrarRepository();
$cajaRepo = new CajaRepository();

// Verificar caja abierta
$caja = $cajaRepo->getCajaAbierta(SessionManager::getUserId());
if (!$caja) {
    SessionManager::setFlash('warning', 'Debe abrir una caja primero');
    redirect(SITE_URL . '/views/caja/apertura.php');
}

$id = intval($_GET['id'] ?? 0);
$cuenta = $repo->findById($id);

if (!$cuenta) {
    SessionManager::setFlash('error', 'Cuenta no encontrada');
    redirect(SITE_URL . '/views/cuentasxcobrar/index.php');
}

if ($cuenta->estado === 'PAGADA') {
    SessionManager::setFlash('info', 'Esta cuenta ya está pagada');
    redirect(SITE_URL . '/views/cuentasxcobrar/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto = floatval($_POST['monto'] ?? 0);
    $metodoPago = $_POST['metodo_pago'] ?? 'EFECTIVO';
    $observaciones = $_POST['observaciones'] ?? '';
    
    if ($monto <= 0) {
        $error = 'El monto debe ser mayor a cero';
    } elseif ($monto > $cuenta->getPorPagar()) {
        $error = 'El monto no puede ser mayor al saldo pendiente (' . formatCurrency($cuenta->getPorPagar()) . ')';
    } else {
        if ($repo->registrarPago($id, $monto, $metodoPago, null, $observaciones, SessionManager::getUserId())) {
            $success = 'Pago registrado correctamente';
            $cuenta = $repo->findById($id); // Recargar
            
            if ($cuenta->estado === 'PAGADA') {
                SessionManager::setFlash('success', 'Cuenta pagada completamente');
                redirect(SITE_URL . '/views/cuentasxcobrar/index.php');
            }
        } else {
            $error = 'Error al registrar el pago';
        }
    }
}

ob_start();
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cash me-2"></i>Registrar Pago
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <!-- Resumen de la cuenta -->
                    <div class="alert alert-info mb-4">
                        <h6>Resumen de la Cuenta #<?php echo $cuenta->id; ?></h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Cliente:</strong><br>
                                <?php echo $cuenta->cliente_nombre; ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Monto Total:</strong><br>
                                <?php echo formatCurrency($cuenta->monto_total); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Saldo Pendiente:</strong><br>
                                <span class="text-danger fw-bold"><?php echo formatCurrency($cuenta->getPorPagar()); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monto a Pagar *</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="monto" class="form-control" 
                                           step="0.01" min="0.01" max="<?php echo $cuenta->getPorPagar(); ?>"
                                           value="<?php echo $cuenta->getPorPagar(); ?>" required>
                                </div>
                                <div class="form-text">Máximo: <?php echo formatCurrency($cuenta->getPorPagar()); ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Método de Pago *</label>
                                <select name="metodo_pago" class="form-select form-select-lg" required>
                                    <option value="EFECTIVO">Efectivo</option>
                                    <option value="TARJETA">Tarjeta</option>
                                    <option value="TRANSFERENCIA">Transferencia</option>
                                    <option value="CHEQUE">Cheque</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-success btn-lg flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Registrar Pago
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-arrow-left me-2"></i>Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Registrar Pago', $content);
