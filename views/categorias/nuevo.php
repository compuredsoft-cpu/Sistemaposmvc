<?php
/**
 * Nueva Categoría
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('almacen');

$repo = new CategoriaRepository();

// Generar código automático
$codigoSugerido = $repo->generateCode();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria = new Categoria();
    $categoria->codigo = $_POST['codigo'] ?? null;
    $categoria->nombre = $_POST['nombre'] ?? '';
    $categoria->descripcion = $_POST['descripcion'] ?? null;
    $categoria->estado = 1;
    
    if (empty($categoria->nombre)) {
        $error = 'El nombre de la categoría es obligatorio';
    } else {
        if ($repo->save($categoria)) {
            SessionManager::setFlash('success', 'Categoría creada correctamente');
            redirect(SITE_URL . '/views/categorias/index.php');
        } else {
            $error = 'Error al guardar la categoría. Verifique que el código no esté duplicado.';
        }
    }
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-tags me-2"></i>Nueva Categoría
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
            <h5 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Datos de la Categoría</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Código</label>
                        <input type="text" name="codigo" class="form-control" value="<?php echo $codigoSugerido; ?>" readonly>
                        <div class="form-text">Se genera automáticamente (<?php echo $codigoSugerido; ?>).</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" placeholder="Nombre de la categoría" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción opcional de la categoría"></textarea>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Guardar Categoría
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
renderLayout('Nueva Categoría', $content);
