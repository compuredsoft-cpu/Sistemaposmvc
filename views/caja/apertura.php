<?php
/**
 * Apertura de Caja
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('caja');

$repo = new CajaRepository();

// Verificar si ya hay caja abierta
if ($repo->hayCajaAbierta(SessionManager::getUserId())) {
    SessionManager::setFlash('warning', 'Ya tiene una caja abierta');
    redirect(SITE_URL . '/views/caja/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto = floatval($_POST['monto_apertura'] ?? 0);
    $observaciones = $_POST['observaciones'] ?? '';
    
    $caja = new Caja();
    $caja->usuario_id = SessionManager::getUserId();
    $caja->monto_apertura = $monto;
    $caja->observaciones_apertura = $observaciones;
    
    if ($repo->save($caja)) {
        SessionManager::setFlash('success', 'Caja abierta correctamente');
        redirect(SITE_URL . '/views/caja/index.php');
    } else {
        $error = 'Error al abrir la caja';
    }
}

ob_start();
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-unlock me-2"></i>Apertura de Caja
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Monto de Apertura</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">$</span>
                                <input type="number" name="monto_apertura" class="form-control" 
                                       placeholder="Ingrese el monto inicial en caja" required min="0" step="100">
                            </div>
                            <div class="form-text">Ingrese la cantidad de dinero con la que inicia la caja.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" 
                                      placeholder="Observaciones opcionales sobre la apertura..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Al abrir la caja, podrá registrar ventas y otros movimientos. 
                            Recuerde cerrarla al finalizar su turno.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-unlock me-2"></i>Abrir Caja
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
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
renderLayout('Apertura de Caja', $content);
