<?php
/**
 * Nuevo Usuario
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('usuarios');

$repo = new UsuarioRepository();
$rolRepo = new RolRepository();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $usuario = new Usuario();
        $usuario->rol_id = intval($_POST['rol_id'] ?? 0);
        $usuario->nombre = $_POST['nombre'] ?? '';
        $usuario->apellido = $_POST['apellido'] ?? '';
        $usuario->email = $_POST['email'] ?? '';
        $usuario->telefono = $_POST['telefono'] ?? null;
        $usuario->direccion = $_POST['direccion'] ?? null;
        $usuario->username = $_POST['username'] ?? '';
        $usuario->password = $_POST['password'] ?? '';
        $usuario->estado = 1;
        
        if ($repo->save($usuario)) {
            header('Location: index.php?success=1');
            exit;
        } else {
            $error = 'Error al guardar el usuario';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$roles = $rolRepo->findAllActive();

ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="usuarios-header" style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(30, 58, 95, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill me-3"></i>Nuevo Usuario</h2>
                        <p class="mb-0 opacity-75">Registra un nuevo usuario en el sistema</p>
                    </div>
                    <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #1e3a5f;">
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
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
            <div class="stepper-wrapper d-flex justify-content-center">
                <div class="stepper-item active" data-step="1">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #1e3a5f, #2563eb); color: white;">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div class="step-name small mt-2 fw-bold text-primary">Información Personal</div>
                </div>
                <div class="stepper-item" data-step="2">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Credenciales</div>
                </div>
                <div class="stepper-item" data-step="3">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Contacto</div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <form method="POST" id="formUsuario" novalidate>
                
                <!-- Paso 1: Información Personal -->
                <div class="step-content" id="step-1">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-person-badge me-2"></i>Información Personal</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-person text-primary"></i></span>
                                <input type="text" name="nombre" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Apellido</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-person text-primary"></i></span>
                                <input type="text" name="apellido" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Apellido" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rol</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-shield text-primary"></i></span>
                                <select name="rol_id" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" required>
                                    <option value="">Seleccione un rol</option>
                                    <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo $rol->id; ?>"><?php echo $rol->nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Estado</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-check-circle text-primary"></i></span>
                                <select name="estado" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" disabled>
                                    <option value="1" selected>Activo</option>
                                </select>
                            </div>
                            <small class="text-muted">Los nuevos usuarios se crean como activos por defecto</small>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Credenciales -->
                <div class="step-content d-none" id="step-2">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-shield-lock me-2"></i>Credenciales de Acceso</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre de Usuario</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-at text-primary"></i></span>
                                <input type="text" name="username" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="username" required>
                            </div>
                            <small class="text-muted">Será usado para iniciar sesión</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-envelope text-primary"></i></span>
                                <input type="email" name="email" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="correo@ejemplo.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-lock text-primary"></i></span>
                                <input type="password" name="password" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Mínimo 6 caracteres" required minlength="6" id="password">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Confirmar Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-lock-fill text-primary"></i></span>
                                <input type="password" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Repetir contraseña" required id="confirmPassword">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Contacto -->
                <div class="step-content d-none" id="step-3">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-telephone me-2"></i>Información de Contacto</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-telephone text-primary"></i></span>
                                <input type="tel" name="telefono" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Teléfono de contacto">
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

                <!-- Botones de Navegación -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btnPrev" onclick="changeStep(-1)" style="display: none;">
                        <i class="bi bi-arrow-left me-2"></i>Anterior
                    </button>
                    <div></div>
                    <button type="button" class="btn btn-primary rounded-pill px-4 text-white" id="btnNext" onclick="changeStep(1)" style="background: linear-gradient(135deg, #1e3a5f, #2563eb); border: none;">
                        Siguiente<i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 d-none" id="btnSubmit">
                        <i class="bi bi-check-circle me-2"></i>Guardar Usuario
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
        background: linear-gradient(135deg, #1e3a5f, #2563eb) !important;
        color: white !important;
    }
    .stepper-item.completed::after,
    .stepper-item.active::after {
        border-bottom: 2px solid #1e3a5f;
    }
    .stepper-item.completed::before {
        border-bottom: 2px solid #1e3a5f;
    }
    .input-group:focus-within .input-group-text {
        background: #eff6ff !important;
    }
    .input-group:focus-within .form-control,
    .input-group:focus-within .form-select {
        background: #eff6ff !important;
        box-shadow: 0 0 0 0.25rem rgba(30, 58, 95, 0.25) !important;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .usuarios-header {
            padding: 15px !important;
        }
        .usuarios-header h2 {
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
    }
    
    @media (max-width: 576px) {
        .usuarios-header .d-flex.justify-content-between {
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
    const totalSteps = 3;

    function changeStep(direction) {
        // Validar paso actual antes de avanzar
        if (direction > 0 && !validateStep(currentStep)) {
            return;
        }

        // Validar contraseñas en paso 2
        if (direction > 0 && currentStep === 2) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Contraseñas no coinciden',
                    text: 'Por favor verifique que las contraseñas sean iguales',
                    confirmButtonColor: '#1e3a5f'
                });
                return;
            }
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
                const icons = ['bi-person-badge', 'bi-shield-lock', 'bi-telephone'];
                counter.innerHTML = `<i class="bi ${icons[index]}"></i>`;
                name.classList.remove('text-muted');
                name.classList.add('text-primary', 'fw-bold');
            } else {
                item.classList.remove('active', 'completed');
                const icons = ['bi-person-badge', 'bi-shield-lock', 'bi-telephone'];
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
                confirmButtonColor: '#1e3a5f'
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
renderLayout('Nuevo Usuario', $content);
