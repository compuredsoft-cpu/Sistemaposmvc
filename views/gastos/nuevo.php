<?php
/**
 * Nuevo Gasto/Ingreso
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('gastos');

$gastoRepo = new GastoRepository();
$usuario = SessionManager::getUserData();

$error = '';
$success = '';

// Obtener tipos de gasto activos directamente de la tabla tipos_gasto
$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM tipos_gasto WHERE estado = 1 ORDER BY nombre ASC");
$stmt->execute();
$tiposGasto = [];
while ($row = $stmt->fetch()) {
    $tipo = new stdClass();
    $tipo->id = $row['id'];
    $tipo->nombre = $row['nombre'];
    $tipo->descripcion = $row['descripcion'] ?? null;
    $tipo->tipo = $row['tipo'] ?? 'GASTO';
    $tipo->estado = $row['estado'];
    $tiposGasto[] = $tipo;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $gasto = new Gasto();
        $gasto->tipo_gasto_id = intval($_POST['tipo_gasto_id'] ?? 0);
        $gasto->fecha = $_POST['fecha'] ?? date('Y-m-d H:i:s');
        $gasto->concepto = $_POST['concepto'] ?? '';
        $gasto->monto = floatval($_POST['monto'] ?? 0);
        $gasto->metodo_pago = $_POST['metodo_pago'] ?? 'EFECTIVO';
        $gasto->referencia = $_POST['referencia'] ?? null;
        $gasto->proveedor = $_POST['proveedor'] ?? null;
        $gasto->descripcion = $_POST['descripcion'] ?? null;
        $gasto->tipo = $_POST['tipo'] ?? 'GASTO';
        $gasto->estado = 1;
        
        if (empty($gasto->tipo_gasto_id)) {
            throw new Exception('Debe seleccionar un tipo de gasto');
        }
        if (empty($gasto->concepto)) {
            throw new Exception('El concepto es obligatorio');
        }
        if ($gasto->monto <= 0) {
            throw new Exception('El monto debe ser mayor a cero');
        }
        
        if ($gastoRepo->save($gasto, $usuario['id'] ?? null)) {
            header('Location: index.php?success=1');
            exit;
        } else {
            $error = 'Error al guardar el registro';
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
            <div class="gastos-header" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(5, 150, 105, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-3"></i>Nuevo Movimiento</h2>
                        <p class="mb-0 opacity-75">Registrar gasto o ingreso</p>
                    </div>
                    <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #059669;">
                        <i class="bi bi-arrow-left me-2"></i>Volver al Listado
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

    <!-- Wizard Stepper -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);">
            <div class="stepper-wrapper d-flex justify-content-center">
                <div class="stepper-item active" data-step="1">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #059669, #10b981); color: white;">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="step-name small mt-2 fw-bold text-primary">Tipo & Concepto</div>
                </div>
                <div class="stepper-item" data-step="2">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-cash"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Monto & Pago</div>
                </div>
                <div class="stepper-item" data-step="3">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Confirmar</div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <form method="POST" id="formGasto" novalidate>
                
                <!-- Paso 1: Tipo y Concepto -->
                <div class="step-content" id="step-1">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-info-circle me-2"></i>Información del Movimiento</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo de Movimiento <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check flex-fill">
                                    <input class="form-check-input" type="radio" name="tipo" id="tipoGasto" value="GASTO" checked onchange="updateTipoGastoLabel()">
                                    <label class="form-check-label p-3 border rounded-3 w-100 text-center" for="tipoGasto" style="cursor: pointer; transition: all 0.2s;">
                                        <i class="bi bi-arrow-down-circle text-danger fs-3 d-block mb-2"></i>
                                        <span class="fw-bold">GASTO</span>
                                        <small class="d-block text-muted">Salida de dinero</small>
                                    </label>
                                </div>
                                <div class="form-check flex-fill">
                                    <input class="form-check-input" type="radio" name="tipo" id="tipoIngreso" value="INGRESO" onchange="updateTipoGastoLabel()">
                                    <label class="form-check-label p-3 border rounded-3 w-100 text-center" for="tipoIngreso" style="cursor: pointer; transition: all 0.2s;">
                                        <i class="bi bi-arrow-up-circle text-success fs-3 d-block mb-2"></i>
                                        <span class="fw-bold">INGRESO</span>
                                        <small class="d-block text-muted">Entrada de dinero</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Categoría <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-tags text-primary"></i></span>
                                <select name="tipo_gasto_id" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($tiposGasto as $tipo): ?>
                                    <option value="<?php echo $tipo->id; ?>" data-tipo="<?php echo $tipo->tipo ?? 'GASTO'; ?>">
                                        <?php echo htmlspecialchars($tipo->nombre); ?> 
                                        <?php if (!empty($tipo->tipo)): ?>
                                        <small>(<?php echo $tipo->tipo; ?>)</small>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <small class="text-muted">Seleccione la categoría del movimiento</small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Concepto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-card-text text-primary"></i></span>
                                <input type="text" name="concepto" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" required placeholder="Ej: Pago de servicios, Compra de insumos, Venta extra...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-calendar text-primary"></i></span>
                                <input type="datetime-local" name="fecha" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" value="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descripción Adicional</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-text-paragraph text-primary"></i></span>
                                <textarea name="descripcion" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" rows="2" placeholder="Detalles adicionales del movimiento..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Monto y Método de Pago -->
                <div class="step-content d-none" id="step-2">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-cash me-2"></i>Monto y Forma de Pago</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Monto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-currency-dollar text-primary"></i></span>
                                <input type="number" name="monto" id="montoInput" class="form-control border-0 shadow-none fs-4" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                            <small class="text-muted">Ingrese el valor del movimiento</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Método de Pago <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-wallet2 text-primary"></i></span>
                                <select name="metodo_pago" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" required>
                                    <option value="EFECTIVO">💵 Efectivo</option>
                                    <option value="TRANSFERENCIA">🏦 Transferencia</option>
                                    <option value="TARJETA">💳 Tarjeta</option>
                                    <option value="CHEQUE">📝 Cheque</option>
                                    <option value="OTRO">📋 Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Referencia / Número de Comprobante</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-hash text-primary"></i></span>
                                <input type="text" name="referencia" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="N° de factura, comprobante, etc.">
                            </div>
                            <small class="text-muted">Opcional: Número de referencia o comprobante</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Proveedor / Tercero</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-building text-primary"></i></span>
                                <input type="text" name="proveedor" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Nombre del proveedor o tercero">
                            </div>
                            <small class="text-muted">Opcional: Persona o empresa relacionada</small>
                        </div>
                    </div>
                    
                    <!-- Resumen visual -->
                    <div class="mt-4 p-4 border rounded-3" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-success mb-1"><i class="bi bi-eye me-2"></i>Vista previa:</h6>
                                <p class="mb-0 text-muted" id="previewConcepto">Concepto no ingresado</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="text-muted">Monto:</span>
                                <h3 class="fw-bold mb-0" style="color: #059669;" id="previewMonto">$0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Confirmación -->
                <div class="step-content d-none" id="step-3">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-check-circle me-2"></i>Confirmar Movimiento</h5>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card border-0 shadow" style="border-radius: 20px; background: linear-gradient(135deg, #f0fdf4, #dcfce7);">
                                <div class="card-body p-4">
                                    <div class="text-center mb-4">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #059669, #10b981);">
                                            <i class="bi bi-cash-coin text-white fs-1" id="confirmIcon"></i>
                                        </div>
                                        <h4 class="fw-bold mb-1" id="confirmTipo">GASTO</h4>
                                        <p class="text-muted mb-0">Revise los datos antes de guardar</p>
                                    </div>
                                    
                                    <div class="border-top pt-3">
                                        <div class="row mb-2">
                                            <div class="col-6 text-muted">Concepto:</div>
                                            <div class="col-6 fw-bold text-end" id="confirmConcepto">-</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 text-muted">Categoría:</div>
                                            <div class="col-6 fw-bold text-end" id="confirmCategoria">-</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 text-muted">Método de Pago:</div>
                                            <div class="col-6 fw-bold text-end" id="confirmMetodo">-</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 text-muted">Fecha:</div>
                                            <div class="col-6 fw-bold text-end" id="confirmFecha">-</div>
                                        </div>
                                        <div class="row border-top pt-2 mt-2">
                                            <div class="col-6 text-muted fs-5">Monto Total:</div>
                                            <div class="col-6 fw-bold fs-4 text-end" id="confirmMonto" style="color: #059669;">$0.00</div>
                                        </div>
                                    </div>
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
                    <button type="button" class="btn btn-primary rounded-pill px-4 text-white" id="btnNext" onclick="changeStep(1)" style="background: linear-gradient(135deg, #059669, #10b981); border: none;">
                        Siguiente<i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 d-none" id="btnSubmit">
                        <i class="bi bi-check-circle me-2"></i>Guardar Movimiento
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
        max-width: 500px;
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
        background: linear-gradient(135deg, #059669, #10b981) !important;
        color: white !important;
    }
    .stepper-item.completed::after,
    .stepper-item.active::after {
        border-bottom: 2px solid #059669;
    }
    .stepper-item.completed::before {
        border-bottom: 2px solid #059669;
    }
    .input-group:focus-within .input-group-text {
        background: #d1fae5 !important;
    }
    .input-group:focus-within .form-control,
    .input-group:focus-within .form-select {
        background: #d1fae5 !important;
        box-shadow: 0 0 0 0.25rem rgba(5, 150, 105, 0.25) !important;
    }
    .form-check-input:checked + .form-check-label {
        border-color: #059669 !important;
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .gastos-header {
            padding: 15px !important;
        }
        .gastos-header h2 {
            font-size: 1.2rem;
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
        .d-flex.gap-3 {
            flex-direction: column;
            gap: 10px !important;
        }
        .form-check.flex-fill {
            width: 100%;
        }
        .form-check-label {
            padding: 1rem !important;
        }
        .mt-4.p-4.border.rounded-3 {
            padding: 1rem !important;
        }
        .row.g-3 > .col-md-6 {
            width: 100% !important;
        }
        .row.justify-content-center .col-md-8 {
            width: 100% !important;
        }
        .card.shadow .card-body.p-4 {
            padding: 0.75rem !important;
        }
        .card.shadow h4 {
            font-size: 1.1rem;
        }
        #confirmMonto {
            font-size: 1.3rem;
        }
    }
    
    @media (max-width: 576px) {
        .gastos-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
        }
        .gastos-header .btn-lg {
            font-size: 0.9rem;
            padding: 8px 15px !important;
        }
        .stepper-item .step-name {
            font-size: 0.65rem;
        }
        .d-flex.justify-content-between.mt-4 {
            flex-direction: column;
            gap: 10px;
        }
        .d-flex.justify-content-between button {
            width: 100%;
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

        const newStep = currentStep + direction;
        if (newStep < 1 || newStep > totalSteps) return;

        // Actualizar datos de confirmación si vamos al paso 3
        if (newStep === 3) {
            updateConfirmacion();
        }

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
                const icons = ['bi-info-circle', 'bi-cash', 'bi-check-circle'];
                counter.innerHTML = `<i class="bi ${icons[index]}"></i>`;
                name.classList.remove('text-muted');
                name.classList.add('text-primary', 'fw-bold');
            } else {
                item.classList.remove('active', 'completed');
                const icons = ['bi-info-circle', 'bi-cash', 'bi-check-circle'];
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
                confirmButtonColor: '#059669'
            });
        }

        return valid;
    }

    function updateConfirmacion() {
        const tipo = document.querySelector('input[name="tipo"]:checked').value;
        const concepto = document.querySelector('input[name="concepto"]').value;
        const categoriaSelect = document.querySelector('select[name="tipo_gasto_id"]');
        const categoria = categoriaSelect.options[categoriaSelect.selectedIndex].text;
        const monto = document.querySelector('input[name="monto"]').value;
        const metodoSelect = document.querySelector('select[name="metodo_pago"]');
        const metodo = metodoSelect.options[metodoSelect.selectedIndex].text;
        const fecha = document.querySelector('input[name="fecha"]').value;

        document.getElementById('confirmTipo').textContent = tipo;
        document.getElementById('confirmConcepto').textContent = concepto || '-';
        document.getElementById('confirmCategoria').textContent = categoria || '-';
        document.getElementById('confirmMetodo').textContent = metodo;
        document.getElementById('confirmFecha').textContent = fecha ? new Date(fecha).toLocaleString('es-CO') : '-';
        document.getElementById('confirmMonto').textContent = '$' + (parseFloat(monto) || 0).toLocaleString('es-CO', {minimumFractionDigits: 2});
        
        // Cambiar icono según tipo
        const icon = document.getElementById('confirmIcon');
        if (tipo === 'INGRESO') {
            icon.className = 'bi bi-arrow-up-circle text-white fs-1';
        } else {
            icon.className = 'bi bi-arrow-down-circle text-white fs-1';
        }
    }

    function updateTipoGastoLabel() {
        const tipo = document.querySelector('input[name="tipo"]:checked').value;
        // Aquí se puede agregar lógica adicional si es necesario
    }

    // Actualizar vista previa en tiempo real
    document.getElementById('montoInput').addEventListener('input', function() {
        const monto = parseFloat(this.value) || 0;
        document.getElementById('previewMonto').textContent = '$' + monto.toLocaleString('es-CO', {minimumFractionDigits: 2});
        
        // Cambiar color según el tipo seleccionado
        const tipo = document.querySelector('input[name="tipo"]:checked').value;
        const previewMonto = document.getElementById('previewMonto');
        if (tipo === 'INGRESO') {
            previewMonto.style.color = '#16a34a';
        } else {
            previewMonto.style.color = '#059669';
        }
    });

    document.querySelector('input[name="concepto"]').addEventListener('input', function() {
        document.getElementById('previewConcepto').textContent = this.value || 'Concepto no ingresado';
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
renderLayout('Nuevo Gasto/Ingreso', $content);
