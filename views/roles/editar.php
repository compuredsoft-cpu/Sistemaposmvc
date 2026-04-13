<?php
/**
 * Editar Rol
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('usuarios');

$repo = new RolRepository();

// Lista de permisos disponibles
$permisosDisponibles = [
    'dashboard' => ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'desc' => 'Ver panel principal'],
    'ventas' => ['icon' => 'bi-cart', 'label' => 'Ventas', 'desc' => 'Crear y gestionar ventas'],
    'compras' => ['icon' => 'bi-bag', 'label' => 'Compras', 'desc' => 'Crear y gestionar compras'],
    'almacen' => ['icon' => 'bi-box-seam', 'label' => 'Almacén', 'desc' => 'Gestionar productos y stock'],
    'clientes' => ['icon' => 'bi-people', 'label' => 'Clientes', 'desc' => 'Gestionar clientes'],
    'proveedores' => ['icon' => 'bi-truck', 'label' => 'Proveedores', 'desc' => 'Gestionar proveedores'],
    'categorias' => ['icon' => 'bi-tags', 'label' => 'Categorías', 'desc' => 'Gestionar categorías'],
    'usuarios' => ['icon' => 'bi-person-gear', 'label' => 'Usuarios', 'desc' => 'Gestionar usuarios y roles'],
    'configuracion' => ['icon' => 'bi-gear', 'label' => 'Configuración', 'desc' => 'Configurar sistema'],
    'cotizaciones' => ['icon' => 'bi-file-text', 'label' => 'Cotizaciones', 'desc' => 'Crear cotizaciones'],
    'cuentasxcobrar' => ['icon' => 'bi-cash-stack', 'label' => 'Cuentas por Cobrar', 'desc' => 'Gestionar créditos'],
    'reportes' => ['icon' => 'bi-graph-up', 'label' => 'Reportes', 'desc' => 'Ver reportes y estadísticas'],
];

// Obtener rol a editar
$id = intval($_GET['id'] ?? 0);
$rol = $repo->findById($id);

if (!$rol) {
    header('Location: index.php?error=Rol no encontrado');
    exit;
}

$permisosActuales = $rol->getPermisosArray();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rol->nombre = $_POST['nombre'] ?? '';
        $rol->descripcion = $_POST['descripcion'] ?? null;
        $rol->permisos = json_encode($_POST['permisos'] ?? []);
        
        if ($repo->save($rol)) {
            header('Location: index.php?success=1');
            exit;
        } else {
            $error = 'Error al actualizar el rol.';
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
            <div class="roles-header" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(124, 58, 237, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-shield-check me-3"></i>Editar Rol</h2>
                        <p class="mb-0 opacity-75">Modificar rol: <strong><?php echo htmlspecialchars($rol->nombre); ?></strong></p>
                    </div>
                    <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #7c3aed;">
                        <i class="bi bi-arrow-left me-2"></i>Volver al Listado
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-pill" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Wizard Stepper -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);">
            <div class="stepper-wrapper d-flex justify-content-center">
                <div class="stepper-item active" data-step="1">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #7c3aed, #a855f7); color: white;">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="step-name small mt-2 fw-bold text-primary">Información</div>
                </div>
                <div class="stepper-item" data-step="2">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Permisos</div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <form method="POST" id="formRol" novalidate>
                
                <!-- Paso 1: Información Básica -->
                <div class="step-content" id="step-1">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-info-circle me-2"></i>Información del Rol</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre del Rol <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-shield text-primary"></i></span>
                                <input type="text" name="nombre" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" 
                                       value="<?php echo htmlspecialchars($rol->nombre); ?>" placeholder="Ej: Administrador, Vendedor, Almacenero" required>
                            </div>
                            <small class="text-muted">Nombre descriptivo para identificar el rol</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Estado</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-check-circle text-primary"></i></span>
                                <select name="estado" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                                    <option value="1" <?php echo $rol->estado == 1 ? 'selected' : ''; ?>>Activo</option>
                                    <option value="0" <?php echo $rol->estado == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                            <small class="text-muted">Estado actual del rol</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descripción</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-text-paragraph text-primary"></i></span>
                                <textarea name="descripcion" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" rows="4" placeholder="Describe las funciones y responsabilidades de este rol..."><?php echo htmlspecialchars($rol->descripcion ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info adicional -->
                    <div class="mt-4 p-3 border rounded-3" style="background: linear-gradient(135deg, #faf5ff, #f3e8ff);">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted"><i class="bi bi-calendar me-1"></i>Creado: <strong><?php echo $rol->fecha_creacion ? date('d/m/Y H:i', strtotime($rol->fecha_creacion)) : 'N/A'; ?></strong></small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted"><i class="bi bi-clock-history me-1"></i>Actualizado: <strong><?php echo $rol->fecha_actualizacion ? date('d/m/Y H:i', strtotime($rol->fecha_actualizacion)) : 'N/A'; ?></strong></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview de permisos seleccionados -->
                    <div class="mt-4 p-3 border rounded-3" style="background: #faf5ff;">
                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-eye me-2"></i>Vista previa de permisos seleccionados:</h6>
                        <div id="permisosPreview" class="d-flex flex-wrap gap-2">
                            <span class="text-muted small">Ningún permiso seleccionado aún...</span>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Permisos -->
                <div class="step-content d-none" id="step-2">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-shield-check me-2"></i>Asignar Permisos</h5>
                    <p class="text-muted mb-4">Selecciona los módulos a los que este rol tendrá acceso:</p>
                    
                    <div class="row g-3">
                        <?php foreach ($permisosDisponibles as $key => $permiso): 
                            $isChecked = in_array($key, $permisosActuales);
                        ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="permiso-card card border-0 shadow-sm h-100 <?php echo $isChecked ? 'selected' : ''; ?>" style="border-radius: 16px; cursor: pointer; transition: all 0.2s;" onclick="togglePermiso('<?php echo $key; ?>')">
                                <div class="card-body p-3 text-center">
                                    <div class="form-check d-flex justify-content-center mb-2">
                                        <input class="form-check-input permiso-check" type="checkbox" name="permisos[]" value="<?php echo $key; ?>" id="perm_<?php echo $key; ?>" 
                                               style="width: 20px; height: 20px;" <?php echo $isChecked ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 50px; height: 50px; background: linear-gradient(135deg, #f3e8ff, #e9d5ff);">
                                        <i class="bi <?php echo $permiso['icon']; ?> fs-4" style="color: #7c3aed;"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1" style="color: #7c3aed;"><?php echo $permiso['label']; ?></h6>
                                    <small class="text-muted" style="font-size: 0.8rem;"><?php echo $permiso['desc']; ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Resumen -->
                    <div class="mt-4 p-3 border rounded-3" style="background: #faf5ff;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary"><i class="bi bi-check2-square me-2"></i>Permisos seleccionados:</span>
                            <span class="badge bg-primary rounded-pill fs-6" id="contadorPermisos"><?php echo count($permisosActuales); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Botones de Navegación -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btnPrev" onclick="changeStep(-1)" style="display: none;">
                        <i class="bi bi-arrow-left me-2"></i>Anterior
                    </button>
                    <div></div>
                    <button type="button" class="btn btn-primary rounded-pill px-4 text-white" id="btnNext" onclick="changeStep(1)" style="background: linear-gradient(135deg, #7c3aed, #a855f7); border: none;">
                        Siguiente<i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 d-none" id="btnSubmit">
                        <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .stepper-wrapper {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        max-width: 400px;
        width: 100%;
    }
    .stepper-item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
    }
    .stepper-item::before,
    .stepper-item::after {
        position: absolute;
        content: "";
        border-bottom: 2px solid #e5e7eb;
        width: 100%;
        top: 20px;
        z-index: 2;
    }
    .stepper-item::before {
        left: -50%;
    }
    .stepper-item::after {
        left: 50%;
    }
    .stepper-item:first-child::before {
        content: none;
    }
    .stepper-item:last-child::after {
        content: none;
    }
    .stepper-item.active .step-counter,
    .stepper-item.completed .step-counter {
        background: linear-gradient(135deg, #7c3aed, #a855f7) !important;
        color: white !important;
    }
    .stepper-item.completed::after,
    .stepper-item.active::after {
        border-bottom: 2px solid #7c3aed;
    }
    .stepper-item.completed::before {
        border-bottom: 2px solid #7c3aed;
    }
    .input-group:focus-within .input-group-text {
        background: #f3e8ff !important;
    }
    .input-group:focus-within .form-control,
    .input-group:focus-within .form-select {
        background: #f3e8ff !important;
        box-shadow: 0 0 0 0.25rem rgba(124, 58, 237, 0.25) !important;
    }
    .permiso-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(124, 58, 237, 0.15) !important;
    }
    .permiso-card.selected {
        border: 2px solid #7c3aed !important;
        background: linear-gradient(135deg, #faf5ff, #f3e8ff) !important;
    }
    .permiso-card.selected .permiso-check {
        background-color: #7c3aed;
        border-color: #7c3aed;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .roles-header {
            padding: 15px !important;
        }
        .roles-header h2 {
            font-size: 1.3rem;
        }
        .stepper-wrapper {
            max-width: 100%;
        }
        .stepper-item .step-name {
            font-size: 0.7rem;
        }
        .step-counter {
            width: 32px !important;
            height: 32px !important;
        }
        .card-body.p-4 {
            padding: 1rem !important;
        }
        .permiso-card {
            padding: 1rem !important;
        }
    }
    
    @media (max-width: 576px) {
        .roles-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 15px;
        }
        .stepper-item .step-name {
            font-size: 0.65rem;
        }
    }
</style>

<script>
    let currentStep = 1;
    const totalSteps = 2;
    const permisosDisponibles = <?php echo json_encode($permisosDisponibles); ?>;

    function changeStep(direction) {
        // Validar paso actual antes de avanzar
        if (direction > 0 && !validateStep(currentStep)) {
            return;
        }

        const newStep = currentStep + direction;
        if (newStep < 1 || newStep > totalSteps) return;

        // Ocultar paso actual
        document.getElementById(`step-${currentStep}`).classList.add('d-none');
        
        // Mostrar nuevo paso
        document.getElementById(`step-${newStep}`).classList.remove('d-none');

        // Actualizar stepper visual
        updateStepper(newStep);

        // Actualizar botones
        updateButtons(newStep);

        currentStep = newStep;
        
        // Actualizar preview si estamos en paso 1
        if (currentStep === 1) {
            updatePermisosPreview();
        }
    }

    function updateStepper(step) {
        const items = document.querySelectorAll('.stepper-item');
        items.forEach((item, index) => {
            const stepNum = index + 1;
            const counter = item.querySelector('.step-counter');
            const name = item.querySelector('.step-name');
            
            if (stepNum < step) {
                item.classList.add('completed');
                item.classList.remove('active');
                counter.style.background = 'linear-gradient(135deg, #22c55e, #4ade80)';
                counter.innerHTML = '<i class="bi bi-check"></i>';
                name.classList.remove('text-muted');
                name.classList.add('text-success', 'fw-bold');
            } else if (stepNum === step) {
                item.classList.add('active');
                item.classList.remove('completed');
                const icons = ['bi-info-circle', 'bi-shield-check'];
                counter.innerHTML = `<i class="bi ${icons[index]}"></i>`;
                name.classList.remove('text-muted');
                name.classList.add('text-primary', 'fw-bold');
            } else {
                item.classList.remove('active', 'completed');
                const icons = ['bi-info-circle', 'bi-shield-check'];
                counter.style.background = '#e5e7eb';
                counter.style.color = '#6b7280';
                counter.innerHTML = `<i class="bi ${icons[index]}"></i>`;
                name.classList.add('text-muted');
                name.classList.remove('text-primary', 'text-success', 'fw-bold');
            }
        });
    }

    function updateButtons(step) {
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const btnSubmit = document.getElementById('btnSubmit');

        if (step === 1) {
            btnPrev.style.display = 'none';
        } else {
            btnPrev.style.display = 'inline-block';
        }

        if (step === totalSteps) {
            btnNext.classList.add('d-none');
            btnSubmit.classList.remove('d-none');
        } else {
            btnNext.classList.remove('d-none');
            btnSubmit.classList.add('d-none');
        }
    }

    function validateStep(step) {
        const currentStepEl = document.getElementById(`step-${step}`);
        const inputs = currentStepEl.querySelectorAll('input[required]');
        let valid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                valid = false;
                input.classList.add('is-invalid');
                input.style.background = '#fef2f2';
            } else {
                input.classList.remove('is-invalid');
                input.style.background = '#f8f9fa';
            }
        });

        if (!valid) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Por favor complete todos los campos obligatorios',
                confirmButtonColor: '#7c3aed'
            });
        }

        return valid;
    }

    function togglePermiso(key) {
        const checkbox = document.getElementById('perm_' + key);
        const card = checkbox.closest('.permiso-card');
        
        checkbox.checked = !checkbox.checked;
        
        if (checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
        
        updateContador();
    }

    function updateContador() {
        const checked = document.querySelectorAll('.permiso-check:checked');
        document.getElementById('contadorPermisos').textContent = checked.length;
    }

    function updatePermisosPreview() {
        const checked = document.querySelectorAll('.permiso-check:checked');
        const container = document.getElementById('permisosPreview');
        
        if (checked.length === 0) {
            container.innerHTML = '<span class="text-muted small">Ningún permiso seleccionado aún...</span>';
            return;
        }
        
        let html = '';
        checked.forEach(checkbox => {
            const key = checkbox.value;
            const permiso = permisosDisponibles[key];
            html += `<span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                <i class="bi ${permiso.icon} me-1" style="color: #7c3aed;"></i>${permiso.label}
            </span>`;
        });
        
        container.innerHTML = html;
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        updatePermisosPreview();
    });

    // Event listeners para checkboxes
    document.querySelectorAll('.permiso-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.permiso-card');
            if (this.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            updateContador();
        });
    });

    // Limpiar validación al escribir
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            this.style.background = '#f8f9fa';
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Editar Rol: ' . $rol->nombre, $content);
