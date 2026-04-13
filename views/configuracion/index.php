<?php
/**
 * Configuración del Sistema
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('configuracion');

$repo = new ConfiguracionRepository();
$config = $repo->getConfig();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $config->nombre_empresa = $_POST['nombre_empresa'] ?? '';
        $config->nit = $_POST['nit'] ?? '';
        $config->direccion = $_POST['direccion'] ?? '';
        $config->telefono = $_POST['telefono'] ?? '';
        $config->email = $_POST['email'] ?? '';
        $config->moneda = $_POST['moneda'] ?? 'COP';
        $config->impuesto_porcentaje = floatval($_POST['impuesto_porcentaje'] ?? 19);
        $config->prefijo_factura = $_POST['prefijo_factura'] ?? 'F-';
        $config->numero_factura_inicial = intval($_POST['numero_factura_inicial'] ?? 1);
        
        // Manejar logo
        if (!empty($_FILES['logo']['tmp_name'])) {
            $logoName = 'logo_' . time() . '.png';
            $uploadPath = rtrim(UPLOADS_PATH, '/') . '/' . $logoName;
            
            // Crear carpeta uploads si no existe
            if (!is_dir(UPLOADS_PATH)) {
                mkdir(UPLOADS_PATH, 0755, true);
            }
            
            move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath);
            $config->logo = $logoName;
        }
        
        if ($repo->save($config)) {
            $success = 'Configuración guardada correctamente';
            $config = $repo->getConfig(); // Recargar
        } else {
            $error = 'Error al guardar la configuración';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

ob_start();
?>

<!-- Header Moderno Gris/Plateado -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="config-header" style="background: linear-gradient(135deg, #475569 0%, #64748b 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(71, 85, 105, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-gear-fill me-3"></i>Configuración del Sistema</h2>
                        <p class="mb-0 opacity-75">Personaliza los ajustes de tu empresa y facturación</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($config->logo): ?>
                        <img src="<?php echo SITE_URL . '/uploads/' . $config->logo; ?>" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                        <?php endif; ?>
                        <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                            <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($config->nombre_empresa ?: 'Sin configurar'); ?>
                        </span>
                    </div>
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
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-pill" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Wizard Stepper -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);">
            <div class="stepper-wrapper d-flex justify-content-center">
                <div class="stepper-item active" data-step="1">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #475569, #64748b); color: white;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="step-name small mt-2 fw-bold text-primary">Empresa</div>
                </div>
                <div class="stepper-item" data-step="2">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Contacto</div>
                </div>
                <div class="stepper-item" data-step="3">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Facturación</div>
                </div>
                <div class="stepper-item" data-step="4">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-image"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Logo</div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <form method="POST" id="formConfig" action="" enctype="multipart/form-data" novalidate>
                
                <!-- Paso 1: Información de la Empresa -->
                <div class="step-content" id="step-1">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-building me-2"></i>Información de la Empresa</h5>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Nombre de la Empresa <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-building text-primary"></i></span>
                                <input type="text" name="nombre_empresa" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" 
                                       value="<?php echo htmlspecialchars($config->nombre_empresa); ?>" required placeholder="Nombre comercial de la empresa">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">NIT / RUT</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-card-text text-primary"></i></span>
                                <input type="text" name="nit" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" 
                                       value="<?php echo htmlspecialchars($config->nit); ?>" placeholder="Número de identificación tributaria">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Información de Contacto -->
                <div class="step-content d-none" id="step-2">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-telephone me-2"></i>Información de Contacto</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-telephone text-primary"></i></span>
                                <input type="text" name="telefono" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" 
                                       value="<?php echo htmlspecialchars($config->telefono); ?>" placeholder="Teléfono principal">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Corporativo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-envelope text-primary"></i></span>
                                <input type="email" name="email" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" 
                                       value="<?php echo htmlspecialchars($config->email); ?>" placeholder="correo@empresa.com">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Dirección Completa</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-geo-alt text-primary"></i></span>
                                <textarea name="direccion" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" rows="3" placeholder="Dirección física de la empresa"><?php echo htmlspecialchars($config->direccion); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Configuración de Facturación -->
                <div class="step-content d-none" id="step-3">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-receipt me-2"></i>Configuración de Facturación</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Moneda Principal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-currency-dollar text-primary"></i></span>
                                <select name="moneda" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" required>
                                    <option value="COP" <?php echo $config->moneda === 'COP' ? 'selected' : ''; ?>>🇨🇴 Peso Colombiano (COP)</option>
                                    <option value="USD" <?php echo $config->moneda === 'USD' ? 'selected' : ''; ?>>🇺🇸 Dólar (USD)</option>
                                    <option value="EUR" <?php echo $config->moneda === 'EUR' ? 'selected' : ''; ?>>🇪🇺 Euro (EUR)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">% IVA por Defecto</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-percent text-primary"></i></span>
                                <select name="impuesto_porcentaje" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                                    <?php foreach (IVA_OPCIONES as $iva): ?>
                                    <option value="<?php echo $iva; ?>" <?php echo $config->impuesto_porcentaje == $iva ? 'selected' : ''; ?>>
                                        <?php echo $iva; ?>%
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <small class="text-muted">Este valor se aplicará por defecto en nuevas ventas</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prefijo de Factura</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-tag text-primary"></i></span>
                                <input type="text" name="prefijo_factura" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" 
                                       value="<?php echo htmlspecialchars($config->prefijo_factura); ?>" placeholder="Ej: F-, FACT-, VT-">
                            </div>
                            <small class="text-muted">Prefijo que aparecerá antes del número de factura</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Número de Factura Inicial</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-hash text-primary"></i></span>
                                <input type="number" name="numero_factura_inicial" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" 
                                       value="<?php echo $config->numero_factura_inicial; ?>" min="1" placeholder="1">
                            </div>
                            <small class="text-muted">Número desde el cual comenzará la numeración</small>
                        </div>
                    </div>
                </div>

                <!-- Paso 4: Logo y Apariencia -->
                <div class="step-content d-none" id="step-4">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-image me-2"></i>Logo de la Empresa</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Logo Actual</label>
                            <div class="border rounded-3 p-4 text-center" style="background: #f8f9fa; min-height: 200px;">
                                <?php if ($config->logo): ?>
                                <img src="<?php echo SITE_URL . '/uploads/' . $config->logo; ?>" class="img-fluid mb-3" style="max-height: 150px;">
                                <p class="text-muted small mb-0">Logo configurado</p>
                                <?php else: ?>
                                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; background: linear-gradient(135deg, #e2e8f0, #cbd5e1);">
                                    <i class="bi bi-image text-secondary fs-1"></i>
                                </div>
                                <p class="text-muted mb-0">No hay logo configurado</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Subir Nuevo Logo</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-upload text-primary"></i></span>
                                <input type="file" name="logo" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" accept="image/*" id="logoInput">
                            </div>
                            <div class="alert alert-light border rounded-3">
                                <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Recomendaciones:</h6>
                                <ul class="small text-muted mb-0">
                                    <li>Formato: PNG, JPG o JPEG</li>
                                    <li>Tamaño máximo: 2MB</li>
                                    <li>Dimensiones recomendadas: 300x300px</li>
                                    <li>Fondo transparente para mejor resultado</li>
                                </ul>
                            </div>
                            <!-- Vista previa del nuevo logo -->
                            <div id="previewContainer" class="d-none">
                                <label class="form-label fw-bold">Vista Previa</label>
                                <div class="border rounded-3 p-3 text-center" style="background: #f8f9fa;">
                                    <img id="logoPreview" class="img-fluid" style="max-height: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Navegación -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btnPrev" onclick="changeStep(-1)" style="display: none;">
                        <i class="bi bi-arrow-left me-2"></i>Anterior
                    </button>
                    <div></div>
                    <button type="button" class="btn btn-primary rounded-pill px-4 text-white" id="btnNext" onclick="changeStep(1)" style="background: linear-gradient(135deg, #475569, #64748b); border: none;">
                        Siguiente<i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 d-none" id="btnSubmit">
                        <i class="bi bi-check-circle me-2"></i>Guardar Configuración
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
        max-width: 700px;
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
        background: linear-gradient(135deg, #475569, #64748b) !important;
        color: white !important;
    }
    .stepper-item.completed::after,
    .stepper-item.active::after {
        border-bottom: 2px solid #475569;
    }
    .stepper-item.completed::before {
        border-bottom: 2px solid #475569;
    }
    .input-group:focus-within .input-group-text {
        background: #f1f5f9 !important;
    }
    .input-group:focus-within .form-control,
    .input-group:focus-within .form-select {
        background: #f1f5f9 !important;
        box-shadow: 0 0 0 0.25rem rgba(71, 85, 105, 0.25) !important;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .config-header {
            padding: 15px !important;
        }
        .config-header h2 {
            font-size: 1.3rem;
        }
        .config-header p {
            font-size: 0.85rem;
        }
        .config-header .btn {
            font-size: 0.9rem;
            padding: 8px 15px !important;
        }
        .config-header img {
            width: 40px !important;
            height: 40px !important;
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
        .stepper-item::before,
        .stepper-item::after {
            top: 16px;
        }
        .card-body.p-4 {
            padding: 1rem !important;
        }
        .d-flex.justify-content-between.align-items-center {
            flex-direction: column;
            gap: 10px;
        }
        .d-flex.align-items-center.gap-3 {
            flex-wrap: wrap;
            justify-content: center;
        }
    }
    
    @media (max-width: 576px) {
        .config-header .d-flex.justify-content-between {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        .stepper-item .step-name {
            font-size: 0.65rem;
        }
    }
</style>

<script>
    let currentStep = 1;
    const totalSteps = 4;

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
                const icons = ['bi-building', 'bi-telephone', 'bi-receipt', 'bi-image'];
                counter.innerHTML = `<i class="bi ${icons[index]}"></i>`;
                name.classList.remove('text-muted');
                name.classList.add('text-primary', 'fw-bold');
            } else {
                item.classList.remove('active', 'completed');
                const icons = ['bi-building', 'bi-telephone', 'bi-receipt', 'bi-image'];
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
        const inputs = currentStepEl.querySelectorAll('input[required], select[required]');
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
                confirmButtonColor: '#475569'
            });
        }

        return valid;
    }

    // Vista previa del logo
    document.getElementById('logoInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreview').src = e.target.result;
                document.getElementById('previewContainer').classList.remove('d-none');
            }
            reader.readAsDataURL(file);
        }
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
renderLayout('Configuración', $content);
