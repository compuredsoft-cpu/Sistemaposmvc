<?php
/**
 * Nuevo Producto
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('almacen');

$repo = new ProductoRepository();
$categoriaRepo = new CategoriaRepository();
$proveedorRepo = new ProveedorRepository();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto = new Producto();
    $producto->codigo = $_POST['codigo'] ?? '';
    $producto->codigo_barras = $_POST['codigo_barras'] ?? null;
    $producto->nombre = $_POST['nombre'] ?? '';
    $producto->descripcion = $_POST['descripcion'] ?? null;
    $producto->categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $producto->proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;
    $producto->unidad_medida = $_POST['unidad_medida'] ?? 'UNIDAD';
    $producto->precio_costo = floatval($_POST['precio_costo'] ?? 0);
    $producto->precio_venta = floatval($_POST['precio_venta'] ?? 0);
    $producto->precio_mayorista = floatval($_POST['precio_mayorista'] ?? 0);
    $producto->stock_minimo = intval($_POST['stock_minimo'] ?? 5);
    $producto->stock_maximo = intval($_POST['stock_maximo'] ?? 100);
    $producto->stock_actual = intval($_POST['stock_actual'] ?? 0);
    $producto->ubicacion = $_POST['ubicacion'] ?? null;
    $producto->estado = 1;
    
    if (empty($producto->codigo) || empty($producto->nombre)) {
        $error = 'El código y nombre del producto son obligatorios';
    } elseif ($producto->precio_venta <= 0) {
        $error = 'El precio de venta debe ser mayor a cero';
    } else {
        if ($repo->save($producto, SessionManager::getUserId())) {
            SessionManager::setFlash('success', 'Producto creado correctamente');
            redirect(SITE_URL . '/views/almacen/index.php');
        } else {
            $error = 'Error al guardar el producto. Verifique que el código no esté duplicado.';
        }
    }
}

// Generar código automático
$ultimoId = $repo->count() + 1;
$codigoSugerido = 'PROD' . str_pad((string)$ultimoId, 5, '0', STR_PAD_LEFT);

$categorias = $categoriaRepo->findAllActive();
$proveedores = $proveedorRepo->findAllActive();

ob_start();
?>

<!-- Header Moderno Azul/Indigo -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="almacen-header" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(79, 70, 229, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-box-seam-fill me-3"></i>Nuevo Producto</h2>
                        <p class="mb-0 opacity-75">Registra un nuevo producto en el almacén</p>
                    </div>
                    <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #4f46e5;">
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
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);">
            <div class="stepper-wrapper d-flex justify-content-center">
                <div class="stepper-item active" data-step="1">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white;">
                        <i class="bi bi-upc-scan"></i>
                    </div>
                    <div class="step-name small mt-2 fw-bold text-primary">Identificación</div>
                </div>
                <div class="stepper-item" data-step="2">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-tag"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Clasificación</div>
                </div>
                <div class="stepper-item" data-step="3">
                    <div class="step-counter rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e5e7eb; color: #6b7280;">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="step-name small mt-2 fw-medium text-muted">Precios & Stock</div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <form method="POST" id="formProducto" novalidate>
                
                <!-- Paso 1: Identificación -->
                <div class="step-content" id="step-1">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-upc-scan me-2"></i>Identificación del Producto</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Código <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-hash text-primary"></i></span>
                                <input type="text" name="codigo" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" value="<?php echo $codigoSugerido; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Código de Barras</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-upc text-primary"></i></span>
                                <input type="text" name="codigo_barras" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Escanea o digita">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-box text-primary"></i></span>
                                <input type="text" name="nombre" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Nombre del producto" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descripción</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-text-paragraph text-primary"></i></span>
                                <textarea name="descripcion" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" rows="3" placeholder="Descripción detallada del producto"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Clasificación -->
                <div class="step-content d-none" id="step-2">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-tag me-2"></i>Clasificación y Ubicación</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Categoría</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-folder text-primary"></i></span>
                                <select name="categoria_id" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat->id; ?>"><?php echo $cat->nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Proveedor Principal</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-truck text-primary"></i></span>
                                <select name="proveedor_id" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?php echo $prov->id; ?>"><?php echo $prov->nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Unidad de Medida</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-rulers text-primary"></i></span>
                                <select name="unidad_medida" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                                    <option value="UNIDAD">Unidad</option>
                                    <option value="KG">Kilogramo</option>
                                    <option value="GR">Gramo</option>
                                    <option value="LT">Litro</option>
                                    <option value="ML">Mililitro</option>
                                    <option value="MT">Metro</option>
                                    <option value="CM">Centímetro</option>
                                    <option value="PAR">Par</option>
                                    <option value="DOCENA">Docena</option>
                                    <option value="CAJA">Caja</option>
                                    <option value="BULTO">Bulto</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ubicación en Almacén</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-geo-alt text-primary"></i></span>
                                <input type="text" name="ubicacion" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Ej: Estante A-1, Pasillo 3">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Precios y Stock -->
                <div class="step-content d-none" id="step-3">
                    <h5 class="mb-4 text-primary fw-bold"><i class="bi bi-currency-dollar me-2"></i>Precios y Stock</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Precio Costo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-cash text-primary"></i></span>
                                <input type="number" name="precio_costo" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" step="0.01" min="0" value="0" id="precioCosto">
                            </div>
                            <small class="text-muted">Lo que pagas al proveedor</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Precio Venta <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-tag-fill text-primary"></i></span>
                                <input type="number" name="precio_venta" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" step="0.01" min="0.01" value="0" required id="precioVenta">
                            </div>
                            <small class="text-muted">Precio al público</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Precio Mayorista</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-shop text-primary"></i></span>
                                <input type="number" name="precio_mayorista" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" step="0.01" min="0" value="0" id="precioMayorista">
                            </div>
                            <small class="text-muted">Precio por volumen</small>
                        </div>
                        
                        <!-- Margen de ganancia calculado -->
                        <div class="col-12">
                            <div class="alert alert-light border rounded-pill d-flex justify-content-between align-items-center py-2">
                                <span><i class="bi bi-graph-up text-primary me-2"></i>Margen de Ganancia Estimado:</span>
                                <span class="fw-bold fs-5 text-primary" id="margenGanancia">0%</span>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Stock Inicial</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-boxes text-primary"></i></span>
                                <input type="number" name="stock_actual" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Stock Mínimo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-bell text-warning"></i></span>
                                <input type="number" name="stock_minimo" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" min="0" value="5">
                            </div>
                            <small class="text-muted">Alerta cuando stock baje de este valor</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Stock Máximo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-warehouse text-success"></i></span>
                                <input type="number" name="stock_maximo" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" min="0" value="100">
                            </div>
                            <small class="text-muted">Capacidad máxima de almacenamiento</small>
                        </div>
                    </div>
                </div>

                <!-- Botones de Navegación -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btnPrev" onclick="changeStep(-1)" style="display: none;">
                        <i class="bi bi-arrow-left me-2"></i>Anterior
                    </button>
                    <div></div>
                    <button type="button" class="btn btn-primary rounded-pill px-4 text-white" id="btnNext" onclick="changeStep(1)" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none;">
                        Siguiente<i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 d-none" id="btnSubmit">
                        <i class="bi bi-check-circle me-2"></i>Guardar Producto
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
        background: linear-gradient(135deg, #4f46e5, #7c3aed) !important;
        color: white !important;
    }
    .stepper-item.completed::after,
    .stepper-item.active::after {
        border-bottom: 2px solid #4f46e5;
    }
    .stepper-item.completed::before {
        border-bottom: 2px solid #4f46e5;
    }
    .input-group:focus-within .input-group-text {
        background: #eef2ff !important;
    }
    .input-group:focus-within .form-control,
    .input-group:focus-within .form-select {
        background: #eef2ff !important;
        box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25) !important;
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
                const icons = ['bi-upc-scan', 'bi-tag', 'bi-currency-dollar'];
                counter.innerHTML = `<i class="bi ${icons[index]}"></i>`;
                name.classList.remove('text-muted');
                name.classList.add('text-primary', 'fw-bold');
            } else {
                item.classList.remove('active', 'completed');
                const icons = ['bi-upc-scan', 'bi-tag', 'bi-currency-dollar'];
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
                confirmButtonColor: '#4f46e5'
            });
        }

        return valid;
    }

    // Calcular margen de ganancia
    function calcularMargen() {
        const costo = parseFloat(document.getElementById('precioCosto').value) || 0;
        const venta = parseFloat(document.getElementById('precioVenta').value) || 0;
        
        if (costo > 0 && venta > 0) {
            const margen = ((venta - costo) / costo) * 100;
            document.getElementById('margenGanancia').textContent = margen.toFixed(1) + '%';
            
            // Color según margen
            const margenEl = document.getElementById('margenGanancia');
            if (margen < 10) {
                margenEl.className = 'fw-bold fs-5 text-danger';
            } else if (margen < 30) {
                margenEl.className = 'fw-bold fs-5 text-warning';
            } else {
                margenEl.className = 'fw-bold fs-5 text-success';
            }
        } else {
            document.getElementById('margenGanancia').textContent = '0%';
            document.getElementById('margenGanancia').className = 'fw-bold fs-5 text-muted';
        }
    }

    // Event listeners para cálculo de margen
    document.getElementById('precioCosto').addEventListener('input', calcularMargen);
    document.getElementById('precioVenta').addEventListener('input', calcularMargen);

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
renderLayout('Nuevo Producto', $content);
