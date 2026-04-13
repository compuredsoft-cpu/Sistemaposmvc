<?php
/**
 * Nuevo Cliente
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('clientes');

$repo = new ClienteRepository();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cliente = new Cliente();
        $cliente->tipo_documento = $_POST['tipo_documento'] ?? 'CC';
        $cliente->documento = $_POST['documento'] ?? '';
        $cliente->nombre = $_POST['nombre'] ?? '';
        $cliente->apellido = $_POST['apellido'] ?? null;
        $cliente->razon_social = $_POST['razon_social'] ?? null;
        $cliente->telefono = $_POST['telefono'] ?? null;
        $cliente->email = $_POST['email'] ?? null;
        $cliente->direccion = $_POST['direccion'] ?? null;
        $cliente->ciudad = $_POST['ciudad'] ?? null;
        $cliente->fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
        $cliente->limite_credito = floatval($_POST['limite_credito'] ?? 0);
        $cliente->observaciones = $_POST['observaciones'] ?? null;
        $cliente->estado = 1;
        
        if ($repo->save($cliente)) {
            header('Location: index.php?success=1');
            exit;
        } else {
            $error = 'Error al guardar el cliente';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$tiposDocumento = ['CC' => 'Cédula de Ciudadanía', 'CE' => 'Cédula de Extranjería', 'NIT' => 'NIT', 'TI' => 'Tarjeta de Identidad', 'PP' => 'Pasaporte', 'Otro' => 'Otro'];

ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="clientes-header" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(14, 165, 233, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill me-3"></i>Nuevo Cliente</h2>
                        <p class="mb-0 opacity-75">Registra un nuevo cliente en el sistema</p>
                    </div>
                    <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #0ea5e9;">
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
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #f0f9ff 0%, #cffafe 100%);">
            <div class="stepper-wrapper d-flex justify-content-center">
                <div class="stepper-item active" data-step="1">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white;">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div class="step-name small mt-2 fw-bold text-primary">Información Básica</div>
                </div>
                <div class="stepper-item" data-step="2">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Contacto</div>
                </div>
                <div class="stepper-item" data-step="3">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Crédito</div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <form method="POST" id="formCliente" novalidate>
                
                <!-- Paso 1: Información Básica -->
                <div class="step-content" id="step-1">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-person-badge me-2"></i>Información Básica</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tipo de Documento</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-card-list text-primary"></i></span>
                                <select name="tipo_documento" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                                    <?php foreach ($tiposDocumento as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Número de Documento</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-card-text text-primary"></i></span>
                                <input type="text" name="documento" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Ej: 1234567890" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Nombre</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-person text-primary"></i></span>
                                <input type="text" name="nombre" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Nombre" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Apellido</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-person text-primary"></i></span>
                                <input type="text" name="apellido" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Apellido">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Razón Social (si es empresa)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-building text-primary"></i></span>
                                <input type="text" name="razon_social" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Nombre de la empresa">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Fecha de Nacimiento</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-calendar text-primary"></i></span>
                                <input type="date" name="fecha_nacimiento" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ciudad</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-geo-alt text-primary"></i></span>
                                <input type="text" name="ciudad" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Ciudad">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Contacto -->
                <div class="step-content d-none" id="step-2">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-telephone me-2"></i>Información de Contacto</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-telephone text-primary"></i></span>
                                <input type="tel" name="telefono" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Teléfono de contacto">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-envelope text-primary"></i></span>
                                <input type="email" name="email" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="correo@ejemplo.com">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Dirección</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-geo text-primary"></i></span>
                                <textarea name="direccion" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" rows="3" placeholder="Dirección completa"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Crédito -->
                <div class="step-content d-none" id="step-3">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-credit-card me-2"></i>Información de Crédito</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Límite de Crédito</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-cash-stack text-primary"></i></span>
                                <input type="number" name="limite_credito" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="0.00" min="0" step="0.01">
                            </div>
                            <small class="text-muted">Dejar en 0 si el cliente siempre compra de contado</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Observaciones</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-sticky text-primary"></i></span>
                                <textarea name="observaciones" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" rows="4" placeholder="Notas o comentarios sobre el cliente"></textarea>
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
                    <button type="button" class="btn btn-primary rounded-pill px-4 text-white" id="btnNext" onclick="changeStep(1)" style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); border: none;">
                        Siguiente<i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 d-none" id="btnSubmit">
                        <i class="bi bi-check-circle me-2"></i>Guardar Cliente
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
        max-width: 600px;
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
        background: linear-gradient(135deg, #0ea5e9, #06b6d4) !important;
        color: white !important;
    }
    .stepper-item.completed::after,
    .stepper-item.active::after {
        border-bottom: 2px solid #0ea5e9;
    }
    .stepper-item.completed::before {
        border-bottom: 2px solid #0ea5e9;
    }
    .input-group:focus-within .input-group-text {
        background: #f0f9ff !important;
    }
    .input-group:focus-within .form-control,
    .input-group:focus-within .form-select {
        background: #f0f9ff !important;
        box-shadow: 0 0 0 0.25rem rgba(14, 165, 233, 0.25) !important;
    }
</style>

<script>
    let currentStep = 1;
    const totalSteps = 3;

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
                const icons = ['bi-person-badge', 'bi-telephone', 'bi-credit-card'];
                counter.innerHTML = `<i class="bi ${icons[index]}"></i>`;
                name.classList.remove('text-muted');
                name.classList.add('text-primary', 'fw-bold');
            } else {
                item.classList.remove('active', 'completed');
                const icons = ['bi-person-badge', 'bi-telephone', 'bi-credit-card'];
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
                confirmButtonColor: '#0ea5e9'
            });
        }

        return valid;
    }

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
renderLayout('Nuevo Cliente', $content);
