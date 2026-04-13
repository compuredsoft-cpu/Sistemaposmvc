<?php
    /**
 * Kardex - Control de Inventario con Wizard
 */
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('almacen');

    ob_start();

    $repo         = new KardexRepository();
    $productoRepo = new ProductoRepository();

    $page    = intval($_GET['page'] ?? 1);
    $filters = [
    'producto_id'     => $_GET['producto_id'] ?? '',
    'tipo_movimiento' => $_GET['tipo_movimiento'] ?? '',
    'fecha_desde'     => $_GET['fecha_desde'] ?? '',
    'fecha_hasta'     => $_GET['fecha_hasta'] ?? '',
    ];
    $movimientos = $repo->findAllWithFilters($filters, $page);
    $productos   = $productoRepo->findAllActive();
?>

<div class="container-fluid">
    <!-- Header Moderno -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="kardex-header" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i class="bi bi-box-seam-fill me-2"></i>Kardex de Inventario</h2>
                        <p style="font-size: 0.75rem; margin: 0; opacity: 0.9;">Control de movimientos</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-toggle="modal" data-bs-target="#modalMovimiento" style="color: #6366f1; font-size: 0.8rem; padding: 5px 12px;">
                            <i class="bi bi-plus-circle me-1"></i>Nuevo Mov.
                        </button>
                        <button onclick="exportarExcel('tablaKardex', 'kardex')" class="btn btn-light rounded-pill" style="color: #6366f1; font-size: 0.8rem; padding: 5px 12px;">
                            <i class="bi bi-file-excel me-1"></i>Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #eef2ff, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                        <i class="bi bi-arrow-left-right text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total Movs.</div>
                        <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?php echo $movimientos['total']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #ecfdf5, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #22c55e, #4ade80);">
                        <i class="bi bi-arrow-down-left text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Entradas</div>
                        <?php
                            $entradas      = array_filter($movimientos['items'], fn($m) => $m->tipo_movimiento === 'ENTRADA');
                            $totalEntradas = array_sum(array_column($entradas, 'cantidad'));
                        ?>
                        <div class="fw-bold text-success" style="font-size: 1rem; line-height: 1.2;">+<?php echo $totalEntradas; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fef3c7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="bi bi-arrow-up-right text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Salidas</div>
                        <?php
                            $salidas      = array_filter($movimientos['items'], fn($m) => $m->tipo_movimiento === 'SALIDA');
                            $totalSalidas = array_sum(array_column($salidas, 'cantidad'));
                        ?>
                        <div class="fw-bold" style="font-size: 1rem; color: #f59e0b; line-height: 1.2;">-<?php echo $totalSalidas; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fee2e2, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #ef4444, #f87171);">
                        <i class="bi bi-box text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Productos</div>
                        <div class="fw-bold text-danger" style="font-size: 1rem; line-height: 1.2;"><?php echo count($productos); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header border-0 py-2" style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);">
            <h6 class="card-title mb-0 fw-bold text-dark" style="font-size: 0.9rem;"><i class="bi bi-funnel me-2 text-primary"></i>Filtros de Búsqueda</h6>
        </div>
        <div class="card-body" style="padding: 12px;">
            <form method="GET" class="row g-2">
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size: 0.75rem;">Producto</label>
                    <select name="producto_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($productos as $p): ?>
                        <option value="<?php echo $p->id; ?>" <?php echo($filters['producto_id'] == $p->id) ? 'selected' : ''; ?>>
                            <?php echo $p->codigo; ?> - <?php echo $p->nombre; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size: 0.75rem;">Tipo Mov.</label>
                    <select name="tipo_movimiento" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="ENTRADA" <?php echo($filters['tipo_movimiento'] == 'ENTRADA') ? 'selected' : ''; ?>>Entrada</option>
                        <option value="SALIDA" <?php echo($filters['tipo_movimiento'] == 'SALIDA') ? 'selected' : ''; ?>>Salida</option>
                        <option value="AJUSTE" <?php echo($filters['tipo_movimiento'] == 'AJUSTE') ? 'selected' : ''; ?>>Ajuste</option>
                        <option value="DEVOLUCION" <?php echo($filters['tipo_movimiento'] == 'DEVOLUCION') ? 'selected' : ''; ?>>Devolución</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size: 0.75rem;">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?php echo $filters['fecha_desde']; ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size: 0.75rem;">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?php echo $filters['fecha_hasta']; ?>">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Movimientos -->
    <div class="card border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header border-0 py-2 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
            <h6 class="card-title mb-0 text-white fw-bold" style="font-size: 0.9rem;"><i class="bi bi-list-check me-2"></i>Movimientos de Inventario</h6>
            <span class="badge bg-light text-dark" style="font-size: 0.7rem;"><?php echo $movimientos['total']; ?> registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="tablaKardex">
                    <thead style="background: linear-gradient(135deg, #eef2ff, #e0e7ff);">
                        <tr>
                            <th class="fw-bold" style="font-size: 0.75rem; border: none; padding: 8px;">Fecha</th>
                            <th class="fw-bold" style="font-size: 0.75rem; border: none; padding: 8px;">Producto</th>
                            <th class="fw-bold" style="font-size: 0.75rem; border: none; padding: 8px;">Tipo</th>
                            <th class="fw-bold text-center" style="font-size: 0.75rem; border: none; padding: 8px;">Cant.</th>
                            <th class="fw-bold text-center" style="font-size: 0.75rem; border: none; padding: 8px;">Stock Ant.</th>
                            <th class="fw-bold text-center" style="font-size: 0.75rem; border: none; padding: 8px;">Stock Nuevo</th>
                            <th class="fw-bold" style="font-size: 0.75rem; border: none; padding: 8px;">Doc.</th>
                            <th class="fw-bold" style="font-size: 0.75rem; border: none; padding: 8px;">Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos['items'] as $mov): ?>
                        <tr>
                            <td style="font-size: 0.75rem; padding: 8px;">
                                <?php echo date('d/m/Y H:i', strtotime($mov->fecha_movimiento)); ?>
                            </td>
                            <td style="font-size: 0.75rem; padding: 8px;">
                                <strong><?php echo $mov->producto_codigo; ?></strong><br>
                                <small class="text-muted"><?php echo substr($mov->producto_nombre, 0, 25); ?></small>
                            </td>
                            <td style="font-size: 0.75rem; padding: 8px;">
                                <span class="badge bg-<?php
                                                          echo match ($mov->tipo_movimiento) {
                                                              'ENTRADA'    => 'success',
                                                              'SALIDA'     => 'warning',
                                                              'AJUSTE'     => 'danger',
                                                              'DEVOLUCION' => 'info',
                                                              default      => 'secondary'
                                                      };
                                                      ?>" style="font-size: 0.65rem;">
                                    <?php echo $mov->tipo_movimiento; ?>
                                </span>
                            </td>
                            <td class="text-center fw-bold" style="font-size: 0.75rem; padding: 8px;">
                                <span class="text-<?php echo in_array($mov->tipo_movimiento, ['ENTRADA', 'DEVOLUCION']) ? 'success' : 'danger'; ?>">
                                    <?php echo(in_array($mov->tipo_movimiento, ['ENTRADA', 'DEVOLUCION']) ? '+' : '-') . $mov->cantidad; ?>
                                </span>
                            </td>
                            <td class="text-center" style="font-size: 0.75rem; padding: 8px;">
                                <?php echo $mov->stock_anterior; ?>
                            </td>
                            <td class="text-center fw-bold" style="font-size: 0.75rem; padding: 8px;">
                                <?php echo $mov->stock_nuevo; ?>
                            </td>
                            <td style="font-size: 0.75rem; padding: 8px;">
                                <span class="badge bg-secondary" style="font-size: 0.6rem;"><?php echo $mov->documento_tipo; ?></span><br>
                                <small><?php echo $mov->documento_codigo ?? '-'; ?></small>
                            </td>
                            <td style="font-size: 0.75rem; padding: 8px;">
                                <small><?php echo $mov->usuario_nombre ?? 'Sistema'; ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($movimientos['items'])): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No se encontraron movimientos
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($movimientos['total_pages'] > 1): ?>
        <div class="card-footer" style="padding: 10px;">
            <nav aria-label="Paginación">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $movimientos['total_pages']; $i++): ?>
                    <li class="page-item <?php echo($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Wizard para Nuevo Movimiento -->
<div class="modal fade" id="modalMovimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 15px 15px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-magic me-2"></i>Nuevo Movimiento - Wizard</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Stepper -->
                <div class="wizard-stepper d-flex justify-content-center py-3" style="background: #f8fafc;">
                    <div class="step-item text-center px-2 active" data-step="1" style="flex: 1; max-width: 120px;">
                        <div class="step-circle mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; border-radius: 50%; background: #6366f1; color: white; font-size: 0.9rem; font-weight: bold;">1</div>
                        <small style="font-size: 0.7rem; font-weight: 600; color: #6366f1;">Producto</small>
                    </div>
                    <div class="step-line" style="width: 30px; height: 2px; background: #e2e8f0; margin-top: 17px;"></div>
                    <div class="step-item text-center px-2" data-step="2" style="flex: 1; max-width: 120px;">
                        <div class="step-circle mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; border-radius: 50%; background: #e2e8f0; color: #64748b; font-size: 0.9rem; font-weight: bold;">2</div>
                        <small style="font-size: 0.7rem; font-weight: 600; color: #64748b;">Tipo</small>
                    </div>
                    <div class="step-line" style="width: 30px; height: 2px; background: #e2e8f0; margin-top: 17px;"></div>
                    <div class="step-item text-center px-2" data-step="3" style="flex: 1; max-width: 120px;">
                        <div class="step-circle mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; border-radius: 50%; background: #e2e8f0; color: #64748b; font-size: 0.9rem; font-weight: bold;">3</div>
                        <small style="font-size: 0.7rem; font-weight: 600; color: #64748b;">Detalles</small>
                    </div>
                    <div class="step-line" style="width: 30px; height: 2px; background: #e2e8f0; margin-top: 17px;"></div>
                    <div class="step-item text-center px-2" data-step="4" style="flex: 1; max-width: 120px;">
                        <div class="step-circle mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; border-radius: 50%; background: #e2e8f0; color: #64748b; font-size: 0.9rem; font-weight: bold;">4</div>
                        <small style="font-size: 0.7rem; font-weight: 600; color: #64748b;">Confirmar</small>
                    </div>
                </div>

                <form id="wizardForm" action="guardar.php" method="POST" style="padding: 15px;">
                    <!-- Paso 1: Producto -->
                    <div class="wizard-step" data-step="1">
                        <h6 class="fw-bold mb-3" style="color: #6366f1;"><i class="bi bi-box me-2"></i>Seleccionar Producto</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.8rem;">Buscar Producto</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <select name="producto_id" class="form-select wizard-producto" required>
                                    <option value="">Seleccione un producto...</option>
                                    <?php foreach ($productos as $p): ?>
                                    <option value="<?php echo $p->id; ?>" data-stock="<?php echo $p->stock_actual; ?>" data-codigo="<?php echo $p->codigo; ?>">
                                        <?php echo $p->codigo; ?> - <?php echo $p->nombre; ?> (Stock: <?php echo $p->stock_actual; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div id="productoInfo" class="alert alert-info d-none" style="border-radius: 10px; font-size: 0.85rem;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                                <div>
                                    <strong>Stock Actual:</strong> <span id="stockActual" class="fs-5">0</span> unidades
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Tipo de Movimiento -->
                    <div class="wizard-step d-none" data-step="2">
                        <h6 class="fw-bold mb-3" style="color: #6366f1;"><i class="bi bi-arrow-left-right me-2"></i>Tipo de Movimiento</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="tipo-mov-option card text-center p-2 cursor-pointer" data-tipo="ENTRADA" style="border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer;">
                                    <i class="bi bi-arrow-down-left text-success fs-2 mb-1"></i>
                                    <div class="fw-bold" style="font-size: 0.8rem;">Entrada</div>
                                    <small class="text-muted" style="font-size: 0.65rem;">Aumenta stock</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="tipo-mov-option card text-center p-2 cursor-pointer" data-tipo="SALIDA" style="border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer;">
                                    <i class="bi bi-arrow-up-right text-warning fs-2 mb-1"></i>
                                    <div class="fw-bold" style="font-size: 0.8rem;">Salida</div>
                                    <small class="text-muted" style="font-size: 0.65rem;">Reduce stock</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="tipo-mov-option card text-center p-2 cursor-pointer" data-tipo="AJUSTE" style="border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer;">
                                    <i class="bi bi-tools text-danger fs-2 mb-1"></i>
                                    <div class="fw-bold" style="font-size: 0.8rem;">Ajuste</div>
                                    <small class="text-muted" style="font-size: 0.65rem;">Corrección</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="tipo-mov-option card text-center p-2 cursor-pointer" data-tipo="DEVOLUCION" style="border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer;">
                                    <i class="bi bi-arrow-counterclockwise text-info fs-2 mb-1"></i>
                                    <div class="fw-bold" style="font-size: 0.8rem;">Devolución</div>
                                    <small class="text-muted" style="font-size: 0.65rem;">Retorno</small>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="tipo_movimiento" id="tipoMovimiento" required>
                    </div>

                    <!-- Paso 3: Detalles -->
                    <div class="wizard-step d-none" data-step="3">
                        <h6 class="fw-bold mb-3" style="color: #6366f1;"><i class="bi bi-pencil-square me-2"></i>Detalles del Movimiento</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size: 0.8rem;">Cantidad</label>
                                <input type="number" name="cantidad" class="form-control" min="1" required placeholder="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size: 0.8rem;">Costo Unitario (opcional)</label>
                                <input type="number" name="costo_unitario" class="form-control" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size: 0.8rem;">Tipo Documento</label>
                                <select name="documento_tipo" class="form-select">
                                    <option value="MANUAL">Manual</option>
                                    <option value="COMPRA">Compra</option>
                                    <option value="VENTA">Venta</option>
                                    <option value="AJUSTE">Ajuste</option>
                                    <option value="TRASPASO">Traspaso</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size: 0.8rem;">Código Documento</label>
                                <input type="text" name="documento_codigo" class="form-control" placeholder="Ej: FAC-001">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold" style="font-size: 0.8rem;">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 4: Confirmación -->
                    <div class="wizard-step d-none" data-step="4">
                        <h6 class="fw-bold mb-3" style="color: #6366f1;"><i class="bi bi-check-circle me-2"></i>Confirmar Movimiento</h6>
                        <div class="card border-0" style="background: #f8fafc; border-radius: 10px;">
                            <div class="card-body" style="font-size: 0.85rem;">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted">Producto:</small>
                                        <div class="fw-bold" id="resProducto">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Tipo:</small>
                                        <div class="fw-bold" id="resTipo">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Cantidad:</small>
                                        <div class="fw-bold" id="resCantidad">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Stock Resultante:</small>
                                        <div class="fw-bold" id="resStock">-</div>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted">Observaciones:</small>
                                        <div id="resObs">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-3" style="font-size: 0.8rem; border-radius: 10px;">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Verifique los datos antes de guardar. Este movimiento afectará el stock del producto.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between" style="padding: 12px 15px;">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPrev" style="display: none;">
                    <i class="bi bi-arrow-left me-1"></i>Anterior
                </button>
                <div class="flex-grow-1"></div>
                <button type="button" class="btn btn-primary btn-sm" id="btnNext" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none;">
                    Siguiente<i class="bi bi-arrow-right ms-1"></i>
                </button>
                <button type="button" class="btn btn-success btn-sm d-none" id="btnGuardar" onclick="document.getElementById('wizardForm').submit();">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    const totalSteps = 4;
    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');
    const btnGuardar = document.getElementById('btnGuardar');

    // Cambio de producto - mostrar stock
    document.querySelector('.wizard-producto').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const stock = selected.dataset.stock;
        const codigo = selected.dataset.codigo;
        if (stock) {
            document.getElementById('stockActual').textContent = stock;
            document.getElementById('productoInfo').classList.remove('d-none');
        }
    });

    // Selección tipo movimiento
    document.querySelectorAll('.tipo-mov-option').forEach(el => {
        el.addEventListener('click', function() {
            document.querySelectorAll('.tipo-mov-option').forEach(o => {
                o.style.borderColor = '#e2e8f0';
                o.style.background = '#fff';
            });
            this.style.borderColor = '#6366f1';
            this.style.background = '#eef2ff';
            document.getElementById('tipoMovimiento').value = this.dataset.tipo;
        });
    });

    function updateStep() {
        // Ocultar todos los pasos
        document.querySelectorAll('.wizard-step').forEach(s => s.classList.add('d-none'));
        document.querySelector('.wizard-step[data-step="' + currentStep + '"]').classList.remove('d-none');

        // Actualizar stepper visual
        document.querySelectorAll('.step-item').forEach((item, idx) => {
            const step = idx + 1;
            const circle = item.querySelector('.step-circle');
            if (step === currentStep) {
                circle.style.background = '#6366f1';
                circle.style.color = '#fff';
                item.querySelector('small').style.color = '#6366f1';
            } else if (step < currentStep) {
                circle.style.background = '#22c55e';
                circle.style.color = '#fff';
                item.querySelector('small').style.color = '#22c55e';
            } else {
                circle.style.background = '#e2e8f0';
                circle.style.color = '#64748b';
                item.querySelector('small').style.color = '#64748b';
            }
        });

        // Actualizar botones
        btnPrev.style.display = currentStep === 1 ? 'none' : 'inline-block';
        if (currentStep === totalSteps) {
            btnNext.classList.add('d-none');
            btnGuardar.classList.remove('d-none');
            updateResumen();
        } else {
            btnNext.classList.remove('d-none');
            btnGuardar.classList.add('d-none');
        }
    }

    function updateResumen() {
        const producto = document.querySelector('.wizard-producto');
        const tipo = document.getElementById('tipoMovimiento').value;
        const cantidad = document.querySelector('input[name="cantidad"]').value;
        const obs = document.querySelector('textarea[name="observaciones"]').value;

        document.getElementById('resProducto').textContent = producto.options[producto.selectedIndex].text;
        document.getElementById('resTipo').textContent = tipo;
        document.getElementById('resTipo').className = 'fw-bold text-' + (tipo === 'ENTRADA' || tipo === 'DEVOLUCION' ? 'success' : 'warning');
        document.getElementById('resCantidad').textContent = cantidad;
        document.getElementById('resObs').textContent = obs || 'Ninguna';

        // Calcular stock resultante
        const stockActual = parseInt(producto.options[producto.selectedIndex].dataset.stock) || 0;
        const cant = parseInt(cantidad) || 0;
        let stockNuevo = stockActual;
        if (tipo === 'ENTRADA' || tipo === 'DEVOLUCION') stockNuevo += cant;
        else if (tipo === 'SALIDA' || tipo === 'AJUSTE') stockNuevo -= cant;
        document.getElementById('resStock').textContent = stockNuevo;
    }

    btnNext.addEventListener('click', function() {
        if (currentStep < totalSteps) {
            currentStep++;
            updateStep();
        }
    });

    btnPrev.addEventListener('click', function() {
        if (currentStep > 1) {
            currentStep--;
            updateStep();
        }
    });
});
</script>

<style>
@media (max-width: 576px) {
    .wizard-stepper .step-line {
        width: 15px !important;
    }
    .wizard-stepper .step-item {
        max-width: 70px !important;
    }
    .wizard-stepper .step-circle {
        width: 28px !important;
        height: 28px !important;
        font-size: 0.75rem !important;
    }
    .wizard-stepper small {
        font-size: 0.6rem !important;
    }
    .modal-lg {
        max-width: 100% !important;
        margin: 10px !important;
    }
    .tipo-mov-option {
        padding: 8px !important;
    }
    .tipo-mov-option i {
        font-size: 1.5rem !important;
    }
}
</style>

<?php
    $content = ob_get_clean();
    require_once __DIR__ . '/../layouts/main.php';
    renderLayout('Kardex de Inventario', $content);
?>
