<?php
    /**
 * Punto de Venta (POS)
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('ventas');

    $configRepo = new ConfiguracionRepository();
    $config     = $configRepo->getConfig();

    $productoRepo = new ProductoRepository();
    $clienteRepo  = new ClienteRepository();
    $cajaRepo     = new CajaRepository();

    // Verificar caja abierta (cualquiera, no solo la del usuario)
    $caja = $cajaRepo->getAnyCajaAbierta();
    if (! $caja) {
    SessionManager::setFlash('warning', 'Debe abrir una caja primero');
    redirect(SITE_URL . '/views/caja/apertura.php');
    }

    $error   = '';
    $success = $_GET['success'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ventaRepo  = new VentaRepository();
        $configRepo = new ConfiguracionRepository();
        $config     = $configRepo->getConfig();

        // Generar código de venta
        $ultimoCodigo = $ventaRepo->getLastCode();
        if ($ultimoCodigo) {
            $numero = intval(substr($ultimoCodigo, strlen($config->prefijo_factura))) + 1;
        } else {
            $numero = $config->numero_factura_inicial;
        }
        $codigo = generateCode($config->prefijo_factura, $numero);

        // Extraer método de pago del JSON de pagos o usar campo simple
        $metodoPago = 'efectivo';
        if (! empty($_POST['pagos_json'])) {
            $pagos = json_decode($_POST['pagos_json'], true);
            if (! empty($pagos[0]['metodo_codigo'])) {
                $metodoPago = $pagos[0]['metodo_codigo'];
            } elseif (! empty($pagos[0]['metodo_nombre'])) {
                $metodoPago = strtolower($pagos[0]['metodo_nombre']);
            }
        } elseif (! empty($_POST['metodo_pago'])) {
            $metodoPago = $_POST['metodo_pago'];
        }

        // Crear venta
        $venta                      = new Venta();
        $venta->codigo              = $codigo;
        $venta->cliente_id          = $_POST['cliente_id'];
        $venta->usuario_id          = SessionManager::getUserId();
        $venta->caja_id             = $caja->id;
        $venta->impuesto_porcentaje = floatval($_POST['impuesto_porcentaje'] ?? $config->impuesto_porcentaje);
        $venta->descuento           = floatval($_POST['descuento'] ?? 0);
        $venta->metodo_pago         = $metodoPago;
        $venta->estado              = 'COMPLETADA';
        $venta->observaciones       = $_POST['observaciones'] ?? null;
        $venta->es_credito          = isset($_POST['es_credito']) ? 1 : 0;

        if ($venta->es_credito) {
            $venta->cuotas = intval($_POST['cuotas'] ?? 1);
        }

        // Detalles
        $detalles   = [];
        $productos  = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios    = $_POST['precio_unitario'] ?? [];

        foreach ($productos as $i => $productoId) {
            if ($productoId) {
                $detalle                  = new VentaDetalle();
                $detalle->producto_id     = $productoId;
                $detalle->cantidad        = intval($cantidades[$i]);
                $detalle->precio_unitario = floatval($precios[$i]);
                $detalle->subtotal        = $detalle->cantidad * $detalle->precio_unitario;
                $detalles[]               = $detalle;
            }
        }

        if (empty($detalles)) {
            throw new Exception('Debe agregar al menos un producto');
        }

        // Calcular totales
        $venta->detalles = $detalles;
        $venta->calcularTotales();

        // Si es crédito, calcular fecha vencimiento
        if ($venta->es_credito) {
            $venta->fecha_vencimiento = date('Y-m-d', strtotime("+{$venta->cuotas} months"));
        }

        // Convertir objetos a arrays para el repositorio
        $detallesArray = array_map(function ($d) {
            return [
                'producto_id'     => $d->producto_id,
                'cantidad'        => $d->cantidad,
                'precio_unitario' => $d->precio_unitario,
                'subtotal'        => $d->subtotal,
            ];
        }, $detalles);

        // Log personalizado
        $logFile = dirname(__DIR__, 2) . '/debug_venta.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - INICIANDO VENTA\n", FILE_APPEND);

        $saveResult = $ventaRepo->save($venta, $detallesArray, SessionManager::getUserId());
        $logMsg     = date('Y-m-d H:i:s') . " - Resultado save(): " . ($saveResult ? 'true' : 'false') . " - Venta ID: " . ($venta->id ?? 'null') . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        if ($saveResult === true && ! empty($venta->id) && $venta->id > 0) {
            $success = 'Venta realizada exitosamente. Código: ' . $codigo;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - POS: Venta exitosa - " . $codigo . "\n", FILE_APPEND);

            // Si se solicitó imprimir
            if (isset($_POST['imprimir'])) {
                redirect(SITE_URL . '/views/ventas/ticket.php?id=' . $venta->id . '&imprimir=1');
            }

            // Redirect a la misma página para evitar que F5 cree duplicados (POST-Redirect-GET)
            redirect(SITE_URL . '/views/ventas/pos.php?success=' . urlencode($success));
        } else {
            $error = 'Error al guardar la venta. Resultado: ' . var_export($saveResult, true) . ', ID: ' . var_export($venta->id ?? null, true);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - POS ERROR: " . $error . "\n", FILE_APPEND);
        }

    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR POS: " . $e->getMessage() . "\n", FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - TRACE: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    }
    } // Cierre del if POST

    // Clientes
    $clientes = $clienteRepo->findAllActive();

    // Productos
    $productos = $productoRepo->findAllWithCategories(['estado' => 1])['items'];

    ob_start();
?>

<!-- Stepper / Wizard -->
<div class="container-fluid">
    <!-- Header Moderno Glassmorphism -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="pos-header"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-cart-plus me-3"></i>Punto de Venta</h2>
                        <p class="mb-0 opacity-75">Crea ventas rápidamente - <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-dark fs-6 px-3 py-2 rounded-pill">
                            <i
                                class="bi bi-person-circle me-2"></i><?php echo SessionManager::getUserData()['nombre'] ?? 'Usuario'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stepper Visual -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="stepper-wrapper"
                style="display: flex; justify-content: space-between; position: relative; margin-bottom: 20px;">
                <div class="stepper-item active" data-step="1" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-counter"
                        style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; font-size: 1.2rem; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                        1</div>
                    <div class="step-name fw-bold text-primary">Productos</div>
                </div>
                <div class="stepper-item" data-step="2" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-counter"
                        style="width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; font-size: 1.2rem;">
                        2</div>
                    <div class="step-name fw-bold text-muted">Cliente & Pago</div>
                </div>
                <div class="stepper-item" data-step="3" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-counter"
                        style="width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; font-size: 1.2rem;">
                        3</div>
                    <div class="step-name fw-bold text-muted">Confirmar</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna Productos -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header border-0 py-3"
                    style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Catálogo
                        </h5>
                        <div class="input-group" style="width: 60%;">
                            <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i
                                    class="bi bi-search text-primary"></i></span>
                            <input type="text" id="buscarProducto" class="form-control border-0 shadow-none"
                                placeholder="Buscar por nombre o código..." style="border-radius: 0 12px 12px 0;">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0" style="background: #f8f9fa;">
                    <div class="productos-grid" style="max-height: 65vh; overflow-y: auto; padding: 20px;">
                        <div class="row g-3">
                            <?php foreach ($productos as $producto): ?>
                            <div class="col-6 producto-item" data-nombre="<?php echo strtolower($producto->nombre); ?>"
                                data-codigo="<?php echo strtolower($producto->codigo); ?>">
                                <div class="card h-100 producto-card border-0" data-id="<?php echo $producto->id; ?>"
                                    data-nombre="<?php echo htmlspecialchars($producto->nombre); ?>"
                                    data-precio="<?php echo $producto->precio_venta; ?>"
                                    data-stock="<?php echo $producto->stock_actual; ?>"
                                    style="cursor: pointer; border-radius: 16px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
                                    <div class="position-relative">
                                        <?php if ($producto->imagen): ?>
                                        <img src="<?php echo SITE_URL . '/uploads/' . $producto->imagen; ?>"
                                            class="w-100" style="height: 100px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-gradient d-flex align-items-center justify-content-center"
                                            style="height: 100px; background: linear-gradient(135deg, #667eea20, #764ba220);">
                                            <i class="bi bi-box-seam fs-1 text-primary opacity-50"></i>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($producto->stock_actual <= $producto->stock_minimo): ?>
                                        <span class="badge bg-warning position-absolute top-0 end-0 m-2 rounded-pill">
                                            <i class="bi bi-exclamation-triangle"></i> Bajo
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-3 text-center">
                                        <h6 class="card-title mb-2 fw-semibold"
                                            style="font-size: 0.9rem; color: #2d3748;"><?php echo $producto->nombre; ?>
                                        </h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-light text-dark rounded-pill">
                                                <i class="bi bi-box me-1"></i><?php echo $producto->stock_actual; ?>
                                            </span>
                                            <p class="text-primary fw-bold mb-0 fs-5">
                                                <?php echo formatCurrency($producto->precio_venta); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Venta -->
        <div class="col-lg-7">
            <form method="POST" id="formVenta">
                <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-header border-0 py-3"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="card-title mb-0 text-white fw-bold">
                            <i class="bi bi-receipt me-2"></i>Detalle de Venta
                        </h5>
                    </div>
                    <div class="card-body" style="background: #ffffff;">
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div id="success-alert" class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <script>
                            setTimeout(function() {
                                var alert = document.getElementById('success-alert');
                                if (alert) {
                                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                                    bsAlert.close();
                                }
                            }, 5000);
                        </script>
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Cliente</label>
                                <select name="cliente_id" class="form-select select2" required>
                                    <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente->id; ?>"
                                        <?php echo $cliente->documento === '22222222' ? 'selected' : ''; ?>>
                                        <?php echo $cliente->getNombreCompleto() . ' (' . $cliente->documento . ')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <input type="checkbox" id="esCredito" name="es_credito" value="1"
                                        class="form-check-input me-2">
                                    Venta a Crédito
                                </label>
                                <input type="number" name="cuotas" id="cuotas" class="form-control mt-1" value="1"
                                    min="1" placeholder="Cuotas" style="display: none;">
                            </div>
                        </div>

                        <!-- Sección de Pagos Modernos -->
                        <div class="card border-0 shadow-sm mb-3"
                            style="border-radius: 15px; background: linear-gradient(135deg, #f8f9fa, #ffffff);">
                            <div class="card-header border-0 py-2" style="background: transparent;">
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="bi bi-credit-card me-2 text-primary"></i>Métodos de Pago
                                    <span class="badge bg-primary rounded-pill ms-2" id="badgeTotalPagos">$ 0</span>
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <!-- Grid de métodos de pago -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Seleccionar método:</label>
                                    <div class="payment-methods-grid" id="paymentMethodsGrid"
                                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px;">
                                        <!-- Los métodos se cargarán dinámicamente -->
                                    </div>
                                </div>

                                <!-- Lista de pagos agregados -->
                                <div id="paymentsList" style="max-height: 200px; overflow-y: auto;">
                                    <div class="alert alert-light text-center py-3" id="emptyPaymentsMessage">
                                        <i class="bi bi-plus-circle me-2"></i>Haga clic en un método de pago para
                                        agregarlo
                                    </div>
                                </div>

                                <!-- Resumen de pagos -->
                                <div class="border-top pt-3 mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Total Venta:</span>
                                        <span class="fw-bold" id="summaryTotalVenta">$ 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Total Pagado:</span>
                                        <span class="fw-bold text-success" id="summaryTotalPagado">$ 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center" id="rowCambio"
                                        style="display: none !important;">
                                        <span class="text-muted">Cambio:</span>
                                        <span class="fw-bold text-warning" id="summaryCambio">$ 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center" id="rowFaltante"
                                        style="display: none !important;">
                                        <span class="text-danger">Faltante:</span>
                                        <span class="fw-bold text-danger" id="summaryFaltante">$ 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">% IVA</label>
                                <select name="impuesto_porcentaje" id="impuesto_porcentaje" class="form-select">
                                    <?php foreach (IVA_OPCIONES as $iva): ?>
                                    <option value="<?php echo $iva; ?>"
                                        <?php echo $iva == $config->impuesto_porcentaje ? 'selected' : ''; ?>>
                                        <?php echo $iva; ?>%
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Descuento</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="descuento" id="descuento" class="form-control" value="0"
                                        min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Detalles -->
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered" id="tablaDetalles">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 40%;">Producto</th>
                                        <th style="width: 15%;">Cantidad</th>
                                        <th style="width: 20%;">Precio</th>
                                        <th style="width: 20%;">Subtotal</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="detallesBody">
                                    <!-- Filas de detalle se agregan dinámicamente -->
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info" id="emptyMessage">
                            <i class="bi bi-info-circle me-2"></i>Haga clic en un producto para agregarlo
                        </div>

                        <!-- Totales -->
                        <div class="row justify-content-end">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end subtotal-display">$ 0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-end"><strong>IVA (<span
                                                    id="ivaLabel"><?php echo $config->impuesto_porcentaje; ?></span>%):</strong>
                                        </td>
                                        <td class="text-end impuesto-display">$ 0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-end"><strong>Descuento:</strong></td>
                                        <td class="text-end descuento-display">$ 0</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td class="text-end">
                                            <h5 class="mb-0">TOTAL:</h5>
                                        </td>
                                        <td class="text-end">
                                            <h4 class="mb-0 total-display">$ 0</h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-primary btn-lg flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Completar Venta
                            </button>
                            <button type="submit" name="imprimir" value="1" class="btn btn-success btn-lg">
                                <i class="bi bi-printer me-2"></i>Imprimir
                            </button>
                            <a href="<?php echo SITE_URL; ?>/views/dashboard/index.php"
                                class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle me-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Definir SITE_URL para uso en JavaScript
const SITE_URL = '<?php echo SITE_URL; ?>';

// Estilos adicionales para efectos hover
document.head.insertAdjacentHTML('beforeend', `
<style>
    .producto-card:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.25) !important;
        border: 2px solid #667eea !important;
    }
    .producto-card:active {
        transform: translateY(-2px) scale(0.98);
    }
    .stepper-item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 25px;
        left: 60%;
        width: 80%;
        height: 3px;
        background: linear-gradient(90deg, #e9ecef 0%, #e9ecef 100%);
        z-index: -1;
    }
    .stepper-item.active:not(:last-child)::after {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }
    .detalle-row {
        transition: all 0.2s ease;
    }
    .detalle-row:hover {
        background: #f8f9fa;
    }
</style>
`);

document.addEventListener('DOMContentLoaded', function() {
    let detalleIndex = 0;
    const productosCache = {};

    // Efectos hover para productos
    document.querySelectorAll('.producto-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Agregar producto al hacer clic
    document.querySelectorAll('.producto-card').forEach(card => {
        card.addEventListener('click', function() {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            const precio = parseFloat(this.dataset.precio);
            const stock = parseInt(this.dataset.stock);

            if (stock <= 0) {
                Swal.fire('Sin stock', 'Este producto no tiene stock disponible', 'warning');
                return;
            }

            agregarDetalle(id, nombre, precio, stock);
        });
    });

    function agregarDetalle(productoId, nombre, precio, stock) {
        document.getElementById('emptyMessage').style.display = 'none';

        const tbody = document.getElementById('detallesBody');

        // Buscar si el producto ya existe en la tabla
        const existingRows = tbody.querySelectorAll('.detalle-row');
        for (let row of existingRows) {
            const inputProductoId = row.querySelector('input[name="producto_id[]"]');
            if (inputProductoId && parseInt(inputProductoId.value) === parseInt(productoId)) {
                // Producto ya existe, incrementar cantidad
                const cantidadInput = row.querySelector('.detalle-cantidad');
                const nuevaCantidad = parseInt(cantidadInput.value) + 1;

                if (nuevaCantidad > stock) {
                    Swal.fire('Stock insuficiente', `Solo hay ${stock} unidades disponibles`, 'warning');
                    return;
                }

                cantidadInput.value = nuevaCantidad;
                calcularFila(row);
                actualizarStepper();

                // Efecto visual de actualización
                row.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    row.style.backgroundColor = '';
                }, 300);

                return;
            }
        }

        // Producto no existe, crear nueva fila
        const row = document.createElement('tr');
        row.className = 'detalle-row';
        row.dataset.index = detalleIndex;

        row.innerHTML = `
            <td>
                <input type="hidden" name="producto_id[]" value="${productoId}">
                ${nombre}
                <br><small class="text-muted">Stock: ${stock}</small>
            </td>
            <td>
                <input type="number" name="cantidad[]" class="form-control form-control-sm detalle-cantidad"
                       value="1" min="1" max="${stock}" required>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" name="precio_unitario[]" class="form-control detalle-precio"
                           value="${precio}" min="0" step="0.01" required>
                </div>
            </td>
            <td class="text-end">
                <input type="hidden" name="subtotal[]" class="detalle-subtotal" value="${precio}">
                <span class="subtotal-cell">$ ${precio.toLocaleString('es-CO')}</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger eliminar-detalle">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
        detalleIndex++;
        calcularTotales();

        actualizarStepper();

        // Eventos para la nueva fila
        row.querySelector('.detalle-cantidad').addEventListener('change', function() {
            if (parseInt(this.value) > stock) {
                Swal.fire('Stock insuficiente', `Solo hay ${stock} unidades disponibles`, 'warning');
                this.value = stock;
            }
            calcularFila(row);
        });

        row.querySelector('.detalle-precio').addEventListener('input', function() {
            calcularFila(row);
        });

        row.querySelector('.eliminar-detalle').addEventListener('click', function() {
            row.remove();
            if (tbody.children.length === 0) {
                document.getElementById('emptyMessage').style.display = 'block';
            }
            calcularTotales();
            actualizarStepper();
        });
    }

    function calcularFila(row) {
        const cantidad = parseFloat(row.querySelector('.detalle-cantidad').value) || 0;
        const precio = parseFloat(row.querySelector('.detalle-precio').value) || 0;
        const subtotal = cantidad * precio;

        row.querySelector('.detalle-subtotal').value = subtotal.toFixed(0);
        row.querySelector('.subtotal-cell').textContent = '$ ' + subtotal.toLocaleString('es-CO');

        calcularTotales();
    }

    function calcularTotales() {
        let subtotal = 0;
        document.querySelectorAll('.detalle-subtotal').forEach(input => {
            subtotal += parseFloat(input.value) || 0;
        });

        const ivaPorcentaje = parseFloat(document.getElementById('impuesto_porcentaje').value) || 0;
        const descuento = parseFloat(document.getElementById('descuento').value) || 0;

        const impuesto = subtotal * (ivaPorcentaje / 100);
        const total = subtotal + impuesto - descuento;

        document.querySelector('.subtotal-display').textContent = '$ ' + subtotal.toLocaleString('es-CO');
        document.querySelector('.impuesto-display').textContent = '$ ' + impuesto.toLocaleString('es-CO');
        document.querySelector('.descuento-display').textContent = '$ ' + descuento.toLocaleString('es-CO');
        document.querySelector('.total-display').textContent = '$ ' + total.toLocaleString('es-CO');
        document.getElementById('ivaLabel').textContent = ivaPorcentaje;
    }

    // Eventos globales
    document.getElementById('impuesto_porcentaje').addEventListener('change', calcularTotales);
    document.getElementById('descuento').addEventListener('input', calcularTotales);

    // Toggle crédito
    document.getElementById('esCredito').addEventListener('change', function() {
        document.getElementById('cuotas').style.display = this.checked ? 'block' : 'none';
        if (this.checked) {
            document.getElementById('metodoPago').value = 'CREDITO';
        }
    });

    // Buscar producto
    document.getElementById('buscarProducto').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.producto-item').forEach(item => {
            const nombre = item.dataset.nombre;
            const codigo = item.dataset.codigo;
            if (nombre.includes(query) || codigo.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Validar formulario
    document.getElementById('formVenta').addEventListener('submit', function(e) {
        if (document.querySelectorAll('.detalle-row').length === 0) {
            e.preventDefault();
            Swal.fire('Error', 'Debe agregar al menos un producto', 'error');
            return;
        }

        // Validar pagos
        const totalVenta = parseFloat(document.querySelector('.total-display').textContent.replace(
            /[^0-9]/g, '')) || 0;
        const totalPagado = calcularTotalPagado();

        if (totalPagado < totalVenta && !document.getElementById('esCredito').checked) {
            e.preventDefault();
            Swal.fire('Error', 'El total pagado es menor al total de la venta', 'error');
            return;
        }

        // Agregar pagos como input hidden
        const pagosInput = document.createElement('input');
        pagosInput.type = 'hidden';
        pagosInput.name = 'pagos_json';
        pagosInput.value = JSON.stringify(payments);
        this.appendChild(pagosInput);
    });

    // ============================================
    // SISTEMA DE PAGOS MODERNOS
    // ============================================

    let metodosPago = [];
    let payments = [];
    let paymentIdCounter = 0;

    // Cargar métodos de pago
    async function cargarMetodosPago() {
        try {
            const response = await fetch(SITE_URL + '/api.php/pagos/metodos', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const result = await response.json();

            if (result.success) {
                metodosPago = result.data;
                console.log('Métodos de pago cargados:', metodosPago);
                renderizarMetodosPago();
            } else {
                console.error('Error en respuesta:', result.message);
            }
        } catch (error) {
            console.error('Error cargando métodos de pago:', error);
        }
    }

    // Renderizar grid de métodos de pago
    function renderizarMetodosPago() {
        const grid = document.getElementById('paymentMethodsGrid');
        grid.innerHTML = '';

        if (!metodosPago || metodosPago.length === 0) {
            grid.innerHTML = `
                <div class="alert alert-warning text-center w-100 py-2 mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    No hay métodos de pago configurados. Ejecute la migración SQL.
                </div>
            `;
            return;
        }

        metodosPago.forEach(metodo => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-primary payment-method-btn';
            btn.style.cssText = `
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 10px 5px;
                border-radius: 10px;
                border: 2px solid ${metodo.color};
                background: white;
                color: ${metodo.color};
                transition: all 0.2s ease;
                font-size: 0.75rem;
            `;
            btn.innerHTML = `
                <i class="bi ${metodo.icon} fs-4 mb-1"></i>
                <span style="font-weight: 600;">${metodo.nombre}</span>
            `;

            btn.addEventListener('click', () => agregarPago(metodo));
            btn.addEventListener('mouseenter', () => {
                btn.style.background = metodo.color;
                btn.style.color = 'white';
            });
            btn.addEventListener('mouseleave', () => {
                btn.style.background = 'white';
                btn.style.color = metodo.color;
            });

            grid.appendChild(btn);
        });
    }

    // Agregar o reemplazar pago (solo un método a la vez)
    function agregarPago(metodo) {
        const totalVenta = obtenerTotalVenta();

        // Si ya hay un pago, reemplazarlo; si no, crear nuevo
        const paymentId = payments.length > 0 ? payments[0].id : ++paymentIdCounter;

        const payment = {
            id: paymentId,
            metodo_pago_id: metodo.id,
            metodo_nombre: metodo.nombre,
            metodo_codigo: metodo.codigo,
            metodo_tipo: metodo.tipo,
            metodo_color: metodo.color,
            metodo_icon: metodo.icon,
            monto: totalVenta,
            monto_recibido: totalVenta,
            cambio: 0,
            referencia: '',
            autorizacion: '',
            ultimos_digitos: ''
        };

        // Reemplazar el array con solo este pago
        payments = [payment];
        renderizarPagos();
        actualizarResumenPagos();
        actualizarStepper();
    }

    // Renderizar lista de pagos
    function renderizarPagos() {
        const container = document.getElementById('paymentsList');

        if (payments.length === 0) {
            container.innerHTML = `
                <div class="alert alert-light text-center py-3" id="emptyPaymentsMessage">
                    <i class="bi bi-plus-circle me-2"></i>Haga clic en un método de pago para agregarlo
                </div>
            `;
            return;
        }

        container.innerHTML = '';

        payments.forEach(payment => {
            const div = document.createElement('div');
            div.className = 'payment-item mb-2';
            div.style.cssText = `
                background: white;
                border: 2px solid ${payment.metodo_color};
                border-radius: 10px;
                padding: 12px;
            `;

            const camposAdicionales = generarCamposAdicionales(payment);

            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                             style="width: 35px; height: 35px; background: ${payment.metodo_color};">
                            <i class="bi ${payment.metodo_icon} text-white"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color: ${payment.metodo_color};">${payment.metodo_nombre}</div>
                            <small class="text-muted">${payment.metodo_codigo}</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarPago(${payment.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small">Monto</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control payment-monto"
                                   value="${payment.monto}" min="0" step="0.01"
                                   data-id="${payment.id}">
                        </div>
                    </div>
                    ${payment.metodo_tipo === 'EFECTIVO' ? `
                    <div class="col-md-4">
                        <label class="form-label small">Recibido</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control payment-recibido"
                                   value="${payment.monto_recibido}" min="0" step="0.01"
                                   data-id="${payment.id}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Cambio</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control payment-cambio"
                                   value="${payment.cambio.toFixed(0)}" readonly>
                        </div>
                    </div>
                    ` : camposAdicionales}
                </div>
            `;

            container.appendChild(div);

            // Eventos
            const montoInput = div.querySelector('.payment-monto');
            montoInput.addEventListener('input', function() {
                actualizarMontoPago(payment.id, parseFloat(this.value) || 0);
            });

            if (payment.metodo_tipo === 'EFECTIVO') {
                const recibidoInput = div.querySelector('.payment-recibido');
                recibidoInput.addEventListener('input', function() {
                    actualizarRecibidoPago(payment.id, parseFloat(this.value) || 0);
                });
            }
        });
    }

    // Generar campos adicionales según tipo de pago
    function generarCamposAdicionales(payment) {
        if (payment.metodo_tipo === 'TARJETA') {
            return `
                <div class="col-md-4">
                    <label class="form-label small">Autorización</label>
                    <input type="text" class="form-control form-control-sm payment-autorizacion"
                           placeholder="N° Autorización" data-id="${payment.id}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Últimos 4 dígitos</label>
                    <input type="text" class="form-control form-control-sm payment-digitos"
                           placeholder="1234" maxlength="4" data-id="${payment.id}">
                </div>
            `;
        }

        if (payment.metodo_tipo === 'TRANSFERENCIA' || payment.metodo_tipo === 'QR') {
            return `
                <div class="col-md-8">
                    <label class="form-label small">Referencia / N° Transacción</label>
                    <input type="text" class="form-control form-control-sm payment-referencia"
                           placeholder="N° de referencia" data-id="${payment.id}">
                </div>
            `;
        }

        return '';
    }

    // Actualizar monto de pago
    function actualizarMontoPago(id, monto) {
        const payment = payments.find(p => p.id === id);
        if (payment) {
            payment.monto = monto;
            if (payment.metodo_tipo === 'EFECTIVO') {
                payment.monto_recibido = Math.max(payment.monto_recibido, monto);
                payment.cambio = payment.monto_recibido - payment.monto;
            }
            renderizarPagos();
            actualizarResumenPagos();
        }
    }

    // Actualizar recibido (efectivo)
    function actualizarRecibidoPago(id, recibido) {
        const payment = payments.find(p => p.id === id);
        if (payment) {
            payment.monto_recibido = recibido;
            payment.cambio = Math.max(0, recibido - payment.monto);
            renderizarPagos();
            actualizarResumenPagos();
        }
    }

    // Eliminar pago
    window.eliminarPago = function(id) {
        payments = payments.filter(p => p.id !== id);
        renderizarPagos();
        actualizarResumenPagos();
        actualizarStepper();
    };

    // Calcular total pagado
    function calcularTotalPagado() {
        return payments.reduce((sum, p) => sum + p.monto, 0);
    }

    // Obtener total de venta
    function obtenerTotalVenta() {
        const totalText = document.querySelector('.total-display').textContent;
        return parseFloat(totalText.replace(/[^0-9]/g, '')) || 0;
    }

    // Actualizar resumen de pagos
    function actualizarResumenPagos() {
        const totalVenta = obtenerTotalVenta();
        const totalPagado = calcularTotalPagado();
        const cambio = totalPagado > totalVenta ? totalPagado - totalVenta : 0;
        const faltante = totalPagado < totalVenta ? totalVenta - totalPagado : 0;

        document.getElementById('summaryTotalVenta').textContent = '$ ' + totalVenta.toLocaleString('es-CO');
        document.getElementById('summaryTotalPagado').textContent = '$ ' + totalPagado.toLocaleString('es-CO');
        document.getElementById('badgeTotalPagos').textContent = '$ ' + totalPagado.toLocaleString('es-CO');

        const rowCambio = document.getElementById('rowCambio');
        const rowFaltante = document.getElementById('rowFaltante');

        if (cambio > 0) {
            rowCambio.style.display = 'flex';
            document.getElementById('summaryCambio').textContent = '$ ' + cambio.toLocaleString('es-CO');
        } else {
            rowCambio.style.display = 'none';
        }

        if (faltante > 0) {
            rowFaltante.style.display = 'flex';
            document.getElementById('summaryFaltante').textContent = '$ ' + faltante.toLocaleString('es-CO');
        } else {
            rowFaltante.style.display = 'none';
        }
    }

    // Función para actualizar el stepper visual según progreso
    function actualizarStepper() {
        const tieneProductos = document.querySelectorAll('.detalle-row').length > 0;
        const tienePago = payments.length > 0;
        const totalVenta = obtenerTotalVenta();
        const totalPagado = calcularTotalPagado();
        const pagoCompleto = totalPagado >= totalVenta && totalVenta > 0;

        // Paso 1: Productos (completado si hay productos)
        const step1 = document.querySelector('.stepper-item[data-step="1"]');
        if (tieneProductos) {
            step1.classList.add('completed');
            step1.querySelector('.step-counter').style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            step1.querySelector('.step-counter').innerHTML = '<i class="bi bi-check-lg"></i>';
        } else {
            step1.classList.remove('completed');
            step1.querySelector('.step-counter').style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
            step1.querySelector('.step-counter').textContent = '1';
        }

        // Paso 2: Cliente & Pago (activo si hay productos, completado si hay pago)
        const step2 = document.querySelector('.stepper-item[data-step="2"]');
        if (tieneProductos) {
            step2.classList.add('active');
            step2.querySelector('.step-name').classList.remove('text-muted');
            step2.querySelector('.step-name').classList.add('text-primary');
            if (tienePago) {
                step2.classList.add('completed');
                step2.querySelector('.step-counter').style.background =
                    'linear-gradient(135deg, #28a745, #20c997)';
                step2.querySelector('.step-counter').innerHTML = '<i class="bi bi-check-lg"></i>';
            } else {
                step2.querySelector('.step-counter').style.background =
                    'linear-gradient(135deg, #667eea, #764ba2)';
                step2.querySelector('.step-counter').textContent = '2';
            }
        } else {
            step2.classList.remove('active', 'completed');
            step2.querySelector('.step-counter').style.background = '#e9ecef';
            step2.querySelector('.step-counter').style.color = '#6c757d';
            step2.querySelector('.step-counter').textContent = '2';
        }

        // Paso 3: Confirmar (activo cuando pago está completo)
        const step3 = document.querySelector('.stepper-item[data-step="3"]');
        if (tieneProductos && tienePago && pagoCompleto) {
            step3.classList.add('active', 'completed');
            step3.querySelector('.step-name').classList.remove('text-muted');
            step3.querySelector('.step-name').classList.add('text-success');
            step3.querySelector('.step-counter').style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            step3.querySelector('.step-counter').innerHTML = '<i class="bi bi-check-lg"></i>';
        } else {
            step3.classList.remove('active', 'completed');
            step3.querySelector('.step-counter').style.background = '#e9ecef';
            step3.querySelector('.step-counter').style.color = '#6c757d';
            step3.querySelector('.step-counter').textContent = '3';
        }
    }

    // Inicializar
    cargarMetodosPago();
    actualizarStepper();

    // Actualizar cuando cambian los totales
    const observer = new MutationObserver(() => {
        actualizarResumenPagos();
        actualizarStepper();
    });

    observer.observe(document.querySelector('.total-display'), {
        childList: true
    });
});
</script>

<?php
    $content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Nueva Venta', $content);