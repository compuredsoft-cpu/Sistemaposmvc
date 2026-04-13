<?php
/**
 * Nuevo Proveedor
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('proveedores');

$repo = new ProveedorRepository();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor = new Proveedor();
    $proveedor->tipo_documento = $_POST['tipo_documento'] ?? 'NIT';
    $proveedor->documento = $_POST['documento'] ?? '';
    $proveedor->nombre = $_POST['nombre'] ?? '';
    $proveedor->contacto = $_POST['contacto'] ?? null;
    $proveedor->telefono = $_POST['telefono'] ?? null;
    $proveedor->email = $_POST['email'] ?? null;
    $proveedor->direccion = $_POST['direccion'] ?? null;
    $proveedor->ciudad = $_POST['ciudad'] ?? null;
    $proveedor->observaciones = $_POST['observaciones'] ?? null;
    $proveedor->estado = 1;
    
    if (empty($proveedor->nombre)) {
        $error = 'El nombre del proveedor es obligatorio';
    } elseif (empty($proveedor->documento)) {
        $error = 'El documento/NIT es obligatorio';
    } else {
        if ($repo->save($proveedor, SessionManager::getUserId())) {
            SessionManager::setFlash('success', 'Proveedor creado correctamente');
            redirect(SITE_URL . '/views/proveedores/index.php');
        } else {
            $error = 'Error al guardar el proveedor. El documento podría estar duplicado.';
        }
    }
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-truck me-2"></i>Nuevo Proveedor
        </h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Datos del Proveedor</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row g-3">
                    <!-- Tipo y Número de Documento -->
                    <div class="col-md-3">
                        <label class="form-label">Tipo Documento</label>
                        <select name="tipo_documento" class="form-select">
                            <option value="NIT" selected>NIT</option>
                            <option value="CC">Cédula</option>
                            <option value="CE">Cédula Extranjería</option>
                            <option value="PAS">Pasaporte</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Número Documento <span class="text-danger">*</span></label>
                        <input type="text" name="documento" class="form-control" placeholder="Ej: 901234567-8" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre/Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" placeholder="Nombre del proveedor" required>
                    </div>
                    
                    <!-- Contacto -->
                    <div class="col-md-6">
                        <label class="form-label">Persona de Contacto</label>
                        <input type="text" name="contacto" class="form-control" placeholder="Nombre del contacto">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" placeholder="Ej: 3001234567">
                    </div>
                    
                    <!-- Email y Ubicación -->
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="proveedor@email.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad" class="form-control" placeholder="Bogotá, Medellín...">
                    </div>
                    
                    <!-- Dirección -->
                    <div class="col-12">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control" placeholder="Dirección completa del proveedor">
                    </div>
                    
                    <!-- Observaciones -->
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3" placeholder="Notas adicionales sobre el proveedor"></textarea>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Guardar Proveedor
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Nuevo Proveedor', $content);
