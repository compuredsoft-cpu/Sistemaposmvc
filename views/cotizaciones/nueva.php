<?php
/**
 * Nueva Cotización - Diseño Wizard/Stepper Moderno
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('cotizaciones');

$repo = new CotizacionRepository();
$clienteRepo = new ClienteRepository();
$productoRepo = new ProductoRepository();
$configRepo = new ConfiguracionRepository();
$config = $configRepo->getConfig();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cotizacion = new Cotizacion();
    $cotizacion->cliente_id = intval($_POST['cliente_id'] ?? 0);
    $cotizacion->fecha = $_POST['fecha'] ?? date('Y-m-d');
    $cotizacion->fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
    $cotizacion->impuesto_porcentaje = floatval($_POST['impuesto_porcentaje'] ?? $config->impuesto_porcentaje);
    $cotizacion->descuento = floatval($_POST['descuento'] ?? 0);
    $cotizacion->observaciones = $_POST['observaciones'] ?? null;
    $cotizacion->condiciones = $_POST['condiciones'] ?? null;
    $cotizacion->tiempo_entrega = $_POST['tiempo_entrega'] ?? null;
    $cotizacion->forma_pago = $_POST['forma_pago'] ?? null;
    $cotizacion->estado = 'PENDIENTE';
    
    // Generar código
    $ultimoId = $repo->count() + 1;
    $cotizacion->codigo = 'COT' . str_pad((string)$ultimoId, 6, '0', STR_PAD_LEFT);
    
    // Procesar detalles
    $productos = $_POST['producto_id'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $precios = $_POST['precio_unitario'] ?? [];
    
    for ($i = 0; $i < count($productos); $i++) {
        if (!empty($productos[$i]) && $cantidades[$i] > 0) {
            $detalle = new CotizacionDetalle();
            $detalle->producto_id = intval($productos[$i]);
            $detalle->cantidad = intval($cantidades[$i]);
            $detalle->precio_unitario = floatval($precios[$i]);
            $detalle->calcularSubtotal();
            $cotizacion->detalles[] = $detalle;
        }
    }
    
    if ($cotizacion->cliente_id <= 0) {
        $error = 'Debe seleccionar un cliente';
    } elseif (empty($cotizacion->detalles)) {
        $error = 'Debe agregar al menos un producto a la cotización';
    } else {
        $cotizacion->calcularTotales();
        $cotizacion->usuario_id = SessionManager::getUserId();
        
        // Convertir detalles a arrays
        $detallesArray = array_map(fn($d) => [
            'producto_id' => $d->producto_id,
            'cantidad' => $d->cantidad,
            'precio_unitario' => $d->precio_unitario,
            'subtotal' => $d->subtotal
        ], $cotizacion->detalles);
        
        if ($repo->save($cotizacion, $detallesArray)) {
            SessionManager::setFlash('success', 'Cotización creada correctamente: ' . $cotizacion->codigo);
            redirect(SITE_URL . '/views/cotizaciones/index.php');
        } else {
            $error = 'Error al guardar la cotización';
        }
    }
}

$clientes = $clienteRepo->findAllActive();
$productosData = $productoRepo->findAllWithCategories(['estado' => 1], 'p.nombre ASC', 1, 1000);
$productos = $productosData['items'] ?? [];

// Generar código sugerido
$ultimoId = $repo->count() + 1;
$codigoSugerido = 'COT' . str_pad((string)$ultimoId, 6, '0', STR_PAD_LEFT);

ob_start();
?>

<!-- Estilos adicionales -->
<style>
    .cotizacion-card:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 15px 35px rgba(249, 115, 22, 0.25) !important;
        border: 2px solid #f97316 !important;
    }
    .stepper-cotizacion .step-counter.active {
        background: linear-gradient(135deg, #f97316, #fbbf24) !important;
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
    }
    .stepper-cotizacion .step-name.active {
        color: #f97316 !important;
    }
    .detalle-row {
        transition: all 0.2s ease;
    }
    .detalle-row:hover {
        background: #fff7ed;
    }
</style>

<div class="container-fluid">
    <!-- Header Moderno Naranja/Dorado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="cotizacion-header" style="background: linear-gradient(135deg, #f97316 0%, #fbbf24 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(249, 115, 22, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-3"></i>Nueva Cotización</h2>
                        <p class="mb-0 opacity-75">Genera presupuestos para tus clientes - <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #f97316;">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stepper Visual -->
    <div class="row mb-4 stepper-cotizacion">
        <div class="col-12">
            <div class="stepper-wrapper" style="display: flex; justify-content: space-between; position: relative; margin-bottom: 20px;">
                <div class="stepper-item" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-counter active" style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #f97316, #fbbf24); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; font-size: 1.2rem;">1</div>
                    <div class="step-name active fw-bold">Productos</div>
                </div>
                <div class="stepper-item" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-counter" style="width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; font-size: 1.2rem;">2</div>
                    <div class="step-name fw-bold text-muted">Cliente</div>
                </div>
                <div class="stepper-item" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-counter" style="width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; font-size: 1.2rem;">3</div>
                    <div class="step-name fw-bold text-muted">Términos</div>
                </div>
                <div class="stepper-item" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-counter" style="width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; font-size: 1.2rem;">4</div>
                    <div class="step-name fw-bold text-muted">Confirmar</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert" style="border: none; background: linear-gradient(135deg, #ff416c, #ff4b2b); color: white;">
        <i class="bi bi-exclamation-circle me-2 fs-5"></i><strong>Error:</strong> <?php echo $error; ?>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" id="formCotizacion">
        <div class="row g-4">
            <!-- Columna Izquierda: Productos -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold text-dark">
                                <i class="bi bi-grid-3x3-gap me-2 text-warning"></i>Catálogo
                            </h5>
                            <div class="input-group" style="width: 60%;">
                                <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-search text-warning"></i></span>
                                <input type="text" id="buscarProducto" class="form-control border-0 shadow-none" placeholder="Buscar producto..." style="border-radius: 0 12px 12px 0;">
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0" style="background: #fff7ed;">
                        <div class="productos-grid" style="max-height: 55vh; overflow-y: auto; padding: 20px;">
                            <div class="row g-3">
                                <?php foreach ($productos as $producto): ?>
                                <div class="col-6 producto-item" data-nombre="<?php echo strtolower($producto->nombre); ?>" data-codigo="<?php echo strtolower($producto->codigo); ?>">
                                    <div class="card h-100 cotizacion-card border-0" 
                                         data-id="<?php echo $producto->id; ?>"
                                         data-nombre="<?php echo htmlspecialchars($producto->nombre); ?>"
                                         data-precio="<?php echo $producto->precio_venta; ?>"
                                         data-stock="<?php echo $producto->stock_actual; ?>"
                                         style="cursor: pointer; border-radius: 16px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; background: white;">
                                        <div class="position-relative">
                                            <?php if ($producto->imagen): ?>
                                            <img src="<?php echo SITE_URL . '/uploads/' . $producto->imagen; ?>" class="w-100" style="height: 80px; object-fit: cover;">
                                            <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center" style="height: 80px; background: linear-gradient(135deg, #fff7ed, #ffedd5);">
                                                <i class="bi bi-box-seam fs-2 text-warning opacity-50"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body p-3 text-center">
                                            <h6 class="card-title mb-2 fw-semibold" style="font-size: 0.85rem; color: #2d3748;"><?php echo $producto->nombre; ?></h6>
                                            <p class="text-warning fw-bold mb-0 fs-5"><?php echo formatCurrency($producto->precio_venta); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Detalle de Cotización -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #f97316 0%, #fbbf24 100%);">
                        <h5 class="card-title mb-0 text-white fw-bold">
                            <i class="bi bi-receipt me-2"></i>Detalle de Cotización
                        </h5>
                    </div>
                    <div class="card-body" style="background: #ffffff;">
                        <!-- Código y Fechas -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Código</label>
                                <input type="text" class="form-control bg-light" value="<?php echo $codigoSugerido; ?>" readonly style="border-radius: 12px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required style="border-radius: 12px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Vencimiento</label>
                                <input type="date" name="fecha_vencimiento" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" style="border-radius: 12px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Tiempo Entrega</label>
                                <input type="text" name="tiempo_entrega" class="form-control" placeholder="Ej: 5 días" style="border-radius: 12px;">
                            </div>
                        </div>

                        <!-- Cliente -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Cliente <span class="text-danger">*</span></label>
                            <select name="cliente_id" class="form-select" required style="border-radius: 12px;">
                                <option value="">Seleccione un cliente...</option>
                                <?php foreach ($clientes as $cli): ?>
                                <option value="<?php echo $cli->id; ?>">
                                    <?php echo $cli->nombre . ' (' . $cli->documento . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tabla de Productos -->
                        <div class="card border-0 mb-3" style="border-radius: 16px; background: #fff7ed;">
                            <div class="card-header border-0 py-2 d-flex justify-content-between align-items-center" style="background: transparent;">
                                <span class="fw-bold text-dark"><i class="bi bi-cart me-2"></i>Productos Seleccionados</span>
                                <button type="button" class="btn btn-warning btn-sm rounded-pill" onclick="agregarFilaVacia()">
                                    <i class="bi bi-plus-lg me-1"></i>Agregar
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="tablaDetalles">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto</th>
                                                <th style="width: 15%">Cantidad</th>
                                                <th style="width: 20%">Precio</th>
                                                <th style="width: 20%">Subtotal</th>
                                                <th style="width: 8%"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="emptyMessage">
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                    Selecciona productos del catálogo
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                                <td class="fw-bold" id="subtotalCotizacion">$ 0</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Totales y Descuento -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">% IVA</label>
                                <select name="impuesto_porcentaje" class="form-select" id="impuestoSelect" style="border-radius: 12px;">
                                    <option value="0">0%</option>
                                    <option value="5">5%</option>
                                    <option value="19" selected>19%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Descuento ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-warning text-dark" style="border-radius: 12px 0 0 12px;">$</span>
                                    <input type="number" name="descuento" class="form-control" id="descuentoInput" value="0" min="0" step="0.01" style="border-radius: 0 12px 12px 0;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0" style="border-radius: 16px; background: linear-gradient(135deg, #f97316, #fbbf24); color: white;">
                                    <div class="card-body py-2 d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">TOTAL:</span>
                                        <span class="fs-3 fw-bold" id="totalCotizacion">$ 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Forma de Pago y Condiciones -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Forma de Pago</label>
                                <select name="forma_pago" class="form-select" style="border-radius: 12px;">
                                    <option value="">Seleccione...</option>
                                    <option value="Contado">Contado</option>
                                    <option value="Crédito 15 días">Crédito 15 días</option>
                                    <option value="Crédito 30 días">Crédito 30 días</option>
                                    <option value="Crédito 60 días">Crédito 60 días</option>
                                    <option value="Anticipo 50%">Anticipo 50%</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Condiciones</label>
                                <input type="text" name="condiciones" class="form-control" placeholder="Ej: Precios sujetos a cambio sin previo aviso" style="border-radius: 12px;">
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..." style="border-radius: 12px;"></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="index.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                                <i class="bi bi-x-circle me-2"></i>Cancelar
                            </a>
                            <div class="d-flex gap-2">
                                <button type="submit" name="imprimir" value="1" class="btn btn-light btn-lg rounded-pill px-4 border">
                                    <i class="bi bi-printer me-2 text-warning"></i>Imprimir
                                </button>
                                <button type="submit" class="btn btn-warning btn-lg rounded-pill px-5 text-white" style="background: linear-gradient(135deg, #f97316, #fbbf24); border: none; box-shadow: 0 5px 20px rgba(249, 115, 22, 0.4);">
                                    <i class="bi bi-check-circle me-2"></i>Guardar Cotización
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template para nueva fila -->
<template id="filaTemplate">
    <tr class="detalle-row">
        <td>
            <select name="producto_id[]" class="form-select select-producto" required style="border-radius: 10px;">
                <option value="">Seleccione...</option>
                <?php foreach ($productos as $prod): ?>
                <option value="<?php echo $prod->id; ?>" data-precio="<?php echo $prod->precio_venta; ?>">
                    <?php echo $prod->nombre; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" name="cantidad[]" class="form-control input-cantidad" value="1" min="1" required style="border-radius: 10px;" onchange="calcularTotalesCotizacion()">
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text bg-light" style="border-radius: 10px 0 0 10px;">$</span>
                <input type="number" name="precio_unitario[]" class="form-control input-precio" value="0" min="0" step="0.01" required style="border-radius: 0 10px 10px 0;" onchange="calcularTotalesCotizacion()">
            </div>
        </td>
        <td>
            <input type="text" class="form-control input-subtotal bg-light" value="$ 0" readonly style="border-radius: 10px;">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm rounded-circle" onclick="eliminarFilaCotizacion(this)" style="width: 32px; height: 32px; padding: 0;">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
// Buscar productos
document.getElementById('buscarProducto').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('.producto-item').forEach(item => {
        const nombre = item.dataset.nombre;
        const codigo = item.dataset.codigo;
        if (nombre.includes(term) || codigo.includes(term)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// Agregar producto al hacer clic
document.querySelectorAll('.cotizacion-card').forEach(card => {
    card.addEventListener('click', function() {
        const id = this.dataset.id;
        const nombre = this.dataset.nombre;
        const precio = parseFloat(this.dataset.precio);
        
        agregarDetalleCotizacion(id, nombre, precio);
    });
    
    // Efecto hover
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

function agregarDetalleCotizacion(productoId, nombre, precio) {
    document.getElementById('emptyMessage').style.display = 'none';
    
    const tbody = document.querySelector('#tablaDetalles tbody');
    
    // Buscar si ya existe
    const existingRows = tbody.querySelectorAll('.detalle-row');
    for (let row of existingRows) {
        const select = row.querySelector('.select-producto');
        if (select && select.value == productoId) {
            const cantidadInput = row.querySelector('.input-cantidad');
            cantidadInput.value = parseInt(cantidadInput.value) + 1;
            calcularTotalesCotizacion();
            
            // Efecto visual
            row.style.backgroundColor = '#fed7aa';
            setTimeout(() => row.style.backgroundColor = '', 300);
            return;
        }
    }
    
    // Crear nueva fila
    const template = document.getElementById('filaTemplate');
    const clone = template.content.cloneNode(true);
    const row = clone.querySelector('tr');
    
    // Setear valores
    const select = row.querySelector('.select-producto');
    select.value = productoId;
    
    const precioInput = row.querySelector('.input-precio');
    precioInput.value = precio;
    
    const subtotalInput = row.querySelector('.input-subtotal');
    subtotalInput.value = '$ ' + precio.toLocaleString('es-CO');
    
    tbody.appendChild(row);
    calcularTotalesCotizacion();
}

function agregarFilaVacia() {
    document.getElementById('emptyMessage').style.display = 'none';
    
    const template = document.getElementById('filaTemplate');
    const tbody = document.querySelector('#tablaDetalles tbody');
    const clone = template.content.cloneNode(true);
    const row = clone.querySelector('tr');
    
    // Evento para actualizar precio cuando cambia el producto
    const select = row.querySelector('.select-producto');
    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const precio = parseFloat(option.dataset.precio) || 0;
        row.querySelector('.input-precio').value = precio;
        calcularTotalesCotizacion();
    });
    
    tbody.appendChild(row);
}

function eliminarFilaCotizacion(btn) {
    const tbody = document.querySelector('#tablaDetalles tbody');
    btn.closest('tr').remove();
    
    if (tbody.querySelectorAll('.detalle-row').length === 0) {
        document.getElementById('emptyMessage').style.display = '';
    }
    
    calcularTotalesCotizacion();
}

function calcularTotalesCotizacion() {
    let subtotal = 0;
    
    document.querySelectorAll('.detalle-row').forEach(row => {
        const cantidad = parseFloat(row.querySelector('.input-cantidad').value) || 0;
        const precio = parseFloat(row.querySelector('.input-precio').value) || 0;
        const subtotalFila = cantidad * precio;
        
        row.querySelector('.input-subtotal').value = '$ ' + subtotalFila.toLocaleString('es-CO');
        subtotal += subtotalFila;
    });
    
    const impuestoPorcentaje = parseFloat(document.getElementById('impuestoSelect').value) || 0;
    const descuento = parseFloat(document.getElementById('descuentoInput').value) || 0;
    
    const impuesto = subtotal * (impuestoPorcentaje / 100);
    const total = subtotal + impuesto - descuento;
    
    document.getElementById('subtotalCotizacion').textContent = '$ ' + subtotal.toLocaleString('es-CO');
    document.getElementById('totalCotizacion').textContent = '$ ' + total.toLocaleString('es-CO');
}

// Eventos para recalcular
document.getElementById('impuestoSelect').addEventListener('change', calcularTotalesCotizacion);
document.getElementById('descuentoInput').addEventListener('input', calcularTotalesCotizacion);

// Agregar primera fila vacía al cargar
document.addEventListener('DOMContentLoaded', function() {
    agregarFilaVacia();
});
</script>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Nueva Cotización', $content);
