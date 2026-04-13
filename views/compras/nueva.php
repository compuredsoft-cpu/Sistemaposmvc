<?php
/**
 * Nueva Compra
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('compras');

$repo = new CompraRepository();
$proveedorRepo = new ProveedorRepository();
$productoRepo = new ProductoRepository();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $compra = new Compra();
    $compra->proveedor_id = intval($_POST['proveedor_id'] ?? 0);
    $compra->fecha = $_POST['fecha'] ?? date('Y-m-d');
    $compra->metodo_pago = $_POST['metodo_pago'] ?? 'EFECTIVO';
    $compra->observaciones = $_POST['observaciones'] ?? null;
    $compra->estado = 'RECIBIDA';
    
    // Procesar detalles
    $productos = $_POST['producto_id'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $precios = $_POST['precio_unitario'] ?? [];
    
    for ($i = 0; $i < count($productos); $i++) {
        if (!empty($productos[$i]) && $cantidades[$i] > 0) {
            $detalle = new CompraDetalle();
            $detalle->producto_id = intval($productos[$i]);
            $detalle->cantidad = intval($cantidades[$i]);
            $detalle->precio_unitario = floatval($precios[$i]);
            $detalle->calcularSubtotal();
            $compra->detalles[] = $detalle;
        }
    }
    
    if ($compra->proveedor_id <= 0) {
        $error = 'Debe seleccionar un proveedor';
    } elseif (empty($compra->detalles)) {
        $error = 'Debe agregar al menos un producto a la compra';
    } else {
        $compra->calcularTotales();
        $compra->usuario_creador = SessionManager::getUserId();
        
        // Convertir detalles a arrays
        $detallesArray = array_map(fn($d) => [
            'producto_id' => $d->producto_id,
            'cantidad' => $d->cantidad,
            'precio_unitario' => $d->precio_unitario,
            'subtotal' => $d->subtotal
        ], $compra->detalles);
        
        if ($repo->save($compra, $detallesArray)) {
            SessionManager::setFlash('success', 'Compra registrada correctamente: ' . $compra->codigo);
            redirect(SITE_URL . '/views/compras/index.php');
        } else {
            $error = 'Error al registrar la compra';
        }
    }
}

$proveedores = $proveedorRepo->findAllActive();
$productosData = $productoRepo->findAllWithCategories([], 'p.nombre ASC', 1, 1000);
$productos = $productosData['items'] ?? [];

// Generar código sugerido
$ultimoId = $repo->count() + 1;
$codigoSugerido = 'COM' . str_pad((string)$ultimoId, 6, '0', STR_PAD_LEFT);

ob_start();
?>

<!-- Header Moderno -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="compra-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(17, 153, 142, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-bag-plus me-3"></i>Nueva Compra</h2>
                        <p class="mb-0 opacity-75">Registra ingreso de mercancía - <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #11998e;">
                        <i class="bi bi-arrow-left me-2"></i>Volver
                    </a>
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
    
    <form method="POST" action="" id="formCompra">
        <!-- Datos de la compra -->
        <div class="card border-0 shadow-lg mb-4" style="border-radius: 20px; overflow: hidden;">
            <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-file-text me-2 text-success"></i>Datos de la Compra</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" value="<?php echo $codigoSugerido; ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Proveedor <span class="text-danger">*</span></label>
                        <select name="proveedor_id" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($proveedores as $prov): ?>
                            <option value="<?php echo $prov->id; ?>"><?php echo $prov->nombre; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Método de Pago</label>
                        <select name="metodo_pago" class="form-select">
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TRANSFERENCIA">Transferencia</option>
                            <option value="CHEQUE">Cheque</option>
                            <option value="TARJETA">Tarjeta</option>
                            <option value="CREDITO">Crédito</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Productos -->
        <div class="card border-0 shadow-lg mb-4" style="border-radius: 20px; overflow: hidden;">
            <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #11998e20, #38ef7d20);">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-box-seam me-2 text-success"></i>Productos</h5>
                <button type="button" class="btn btn-success btn-sm" onclick="agregarProducto()">
                    <i class="bi bi-plus-circle me-2"></i>Agregar Producto
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tablaProductos">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 40%">Producto</th>
                                <th style="width: 15%">Cantidad</th>
                                <th style="width: 20%">Precio Unitario</th>
                                <th style="width: 20%">Subtotal</th>
                                <th style="width: 5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filas dinámicas -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                <td class="fw-bold fs-5 text-primary" id="totalCompra">$ 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center py-3">
            <a href="index.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                <i class="bi bi-x-circle me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-success btn-lg rounded-pill px-5" style="background: linear-gradient(135deg, #11998e, #38ef7d); border: none; box-shadow: 0 5px 20px rgba(17, 153, 142, 0.4);">
                <i class="bi bi-check-circle me-2"></i>Guardar Compra
            </button>
        </div>
    </form>
</div>

<!-- Template para nueva fila -->
<template id="filaTemplate">
    <tr class="fila-producto">
        <td>
            <select name="producto_id[]" class="form-select select-producto" required onchange="actualizarPrecio(this)">
                <option value="">Seleccione...</option>
                <?php foreach ($productos as $prod): ?>
                <option value="<?php echo $prod->id; ?>" data-precio="<?php echo $prod->precio_costo; ?>">
                    <?php echo $prod->codigo . ' - ' . $prod->nombre; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" name="cantidad[]" class="form-control input-cantidad" value="1" min="1" required onchange="calcularSubtotal(this)">
        </td>
        <td>
            <input type="number" name="precio_unitario[]" class="form-control input-precio" value="0" min="0" step="0.01" required onchange="calcularSubtotal(this)">
        </td>
        <td>
            <input type="text" class="form-control input-subtotal" value="$ 0" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm rounded-circle" onclick="eliminarFila(this)" style="width: 35px; height: 35px; padding: 0;">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
function agregarProducto() {
    const template = document.getElementById('filaTemplate');
    const tbody = document.querySelector('#tablaProductos tbody');
    const clone = template.content.cloneNode(true);
    tbody.appendChild(clone);
    calcularTotal();
}

function eliminarFila(btn) {
    const filas = document.querySelectorAll('.fila-producto');
    if (filas.length > 1) {
        btn.closest('tr').remove();
        calcularTotal();
    } else {
        alert('Debe tener al menos un producto');
    }
}

function actualizarPrecio(select) {
    const precio = select.options[select.selectedIndex].dataset.precio || 0;
    const fila = select.closest('tr');
    fila.querySelector('.input-precio').value = precio;
    calcularSubtotal(select);
}

function calcularSubtotal(element) {
    const fila = element.closest('tr');
    const cantidad = parseInt(fila.querySelector('.input-cantidad').value) || 0;
    const precio = parseFloat(fila.querySelector('.input-precio').value) || 0;
    const subtotal = cantidad * precio;
    fila.querySelector('.input-subtotal').value = '$ ' + subtotal.toLocaleString('es-CO');
    calcularTotal();
}

function calcularTotal() {
    let total = 0;
    document.querySelectorAll('.fila-producto').forEach(fila => {
        const cantidad = parseInt(fila.querySelector('.input-cantidad').value) || 0;
        const precio = parseFloat(fila.querySelector('.input-precio').value) || 0;
        total += cantidad * precio;
    });
    document.getElementById('totalCompra').textContent = '$ ' + total.toLocaleString('es-CO');
}

// Agregar primera fila al cargar
document.addEventListener('DOMContentLoaded', function() {
    agregarProducto();
});
</script>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Nueva Compra', $content);
