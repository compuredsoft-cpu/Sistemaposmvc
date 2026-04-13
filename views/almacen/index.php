<?php
    /**
 * Listado de Productos (Almacén)
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('almacen');

    $repo          = new ProductoRepository();
    $categoriaRepo = new CategoriaRepository();

    $page    = intval($_GET['page'] ?? 1);
    $filters = [
    'categoria_id' => $_GET['categoria_id'] ?? null,
    'busqueda'     => $_GET['busqueda'] ?? null,
    'stock_alerta' => isset($_GET['stock_alerta']) ? 1 : null,
    ];

    $filters    = array_filter($filters);
    $productos  = $repo->findAllWithCategories($filters, 'p.nombre ASC', $page);
    $categorias = $categoriaRepo->findAllActive();

    ob_start();
?>

<!-- Header Moderno Azul/Índigo -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="almacen-header" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(79, 70, 229, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i class="bi bi-box-seam-fill me-2"></i>Gestión de Almacén</h2>
                        <p style="font-size: 0.8rem; margin: 0; opacity: 0.9;">Controla tu inventario</p>
                    </div>
                    <a href="nuevo.php" class="btn btn-light rounded-pill" style="color: #4f46e5; font-size: 0.85rem; padding: 6px 12px;">
                        <i class="bi bi-plus-circle me-1"></i>Nuevo Producto
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #eef2ff, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                        <i class="bi bi-box-seam text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total Productos</div>
                        <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?php echo $productos['total']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #ecfdf5, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #22c55e, #4ade80);">
                        <i class="bi bi-check-circle text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Stock OK</div>
                        <?php
                            $stockOk      = array_filter($productos['items'], fn($p) => ! $p->estaEnStockMinimo() && $p->estado);
                            $totalStockOk = count($stockOk);
                        ?>
                        <div class="fw-bold text-success" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalStockOk; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fee2e2, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #ef4444, #f87171);">
                        <i class="bi bi-exclamation-triangle text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Stock Bajo</div>
                        <?php
                            $stockBajo      = array_filter($productos['items'], fn($p) => $p->estaEnStockMinimo());
                            $totalStockBajo = count($stockBajo);
                        ?>
                        <div class="fw-bold text-danger" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalStockBajo; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fef3c7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="bi bi-pause-circle text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Inactivos</div>
                        <?php
                            $inactivos      = array_filter($productos['items'], fn($p) => ! $p->estado);
                            $totalInactivos = count($inactivos);
                        ?>
                        <div class="fw-bold" style="font-size: 1rem; color: #f59e0b; line-height: 1.2;"><?php echo $totalInactivos; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros Modernos -->
    <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header border-0 py-2 py-md-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);">
            <h5 class="card-title mb-0 fw-bold text-dark fs-6 fs-md-5"><i class="bi bi-funnel me-2 text-primary"></i>Filtros</h5>
            <?php if (isset($_GET['stock_alerta'])): ?>
            <span class="badge bg-danger rounded-pill fs-7"><i class="bi bi-exclamation-triangle me-1"></i>Alertas</span>
            <?php endif; ?>
        </div>
        <div class="card-body p-2 p-md-3">
            <form method="GET" class="row g-2 g-md-3">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-bold small">Categoría</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-folder text-primary"></i></span>
                        <select name="categoria_id" class="form-select border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat->id; ?>" <?php echo($_GET['categoria_id'] ?? '') == $cat->id ? 'selected' : ''; ?>>
                                <?php echo $cat->nombre; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold small">Búsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0" style="border-radius: 12px 0 0 12px;"><i class="bi bi-search text-primary"></i></span>
                        <input type="text" name="busqueda" class="form-control border-0 shadow-none" style="border-radius: 0 12px 12px 0; background: #f8f9fa;" placeholder="Código, nombre, código de barras..." value="<?php echo $_GET['busqueda'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-bold small">Opciones</label>
                    <div class="form-check d-flex align-items-center" style="height: 38px;">
                        <input class="form-check-input border-primary" type="checkbox" name="stock_alerta" id="stockAlerta" value="1" <?php echo isset($_GET['stock_alerta']) ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
                        <label class="form-check-label ms-2 fw-medium text-danger" for="stockAlerta">
                            <i class="bi bi-exclamation-triangle me-1"></i>Solo stock bajo
                        </label>
                    </div>
                </div>
                <div class="col-6 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill text-white btn-sm" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none;">
                        <i class="bi bi-search me-1 me-md-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Moderna -->
    <div class="card border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header border-0 py-2 py-md-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
            <h5 class="card-title mb-0 text-white fw-bold fs-6 fs-md-5"><i class="bi bi-grid-3x3-gap me-2"></i>Productos en Almacén</h5>
            <div class="d-flex gap-2">
                <button onclick="exportarExcel('tablaProductos', 'productos')" class="btn btn-light btn-sm rounded-pill" style="color: #4f46e5; padding: 4px 10px; font-size: 0.8rem;">
                    <i class="bi bi-file-excel"></i><span class="d-none d-md-inline ms-1">Excel</span>
                </button>
                <button onclick="window.print()" class="btn btn-light btn-sm rounded-pill" style="color: #4f46e5;">
                    <i class="bi bi-printer me-1"></i>PDF
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaProductos" style="background: #ffffff;">
                    <thead style="background: linear-gradient(135deg, #eef2ff, #e0e7ff);">
                        <tr>
                            <th class="fw-bold text-dark" style="border: none;">Código</th>
                            <th class="fw-bold text-dark" style="border: none;">Producto</th>
                            <th class="fw-bold text-dark" style="border: none;">Categoría</th>
                            <th class="fw-bold text-dark" style="border: none;">Precio</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Stock</th>
                            <th class="fw-bold text-dark" style="border: none;">Estado</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos['items'] as $producto): ?>
                        <?php
                            $stockClass = $producto->estaEnStockMinimo() ? 'stock-bajo' : ($producto->stock_actual > $producto->stock_maximo ? 'stock-alto' : 'stock-ok');
                        ?>
                        <tr style="transition: all 0.2s;" class="<?php echo $stockClass; ?>">
                            <td class="align-middle">
                                <span class="badge bg-light text-dark border" style="font-family: monospace; font-size: 0.9rem;">
                                    <i class="bi bi-upc-scan me-1 text-primary"></i><?php echo $producto->codigo; ?>
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <?php if ($producto->imagen): ?>
                                    <img src="<?php echo SITE_URL . '/uploads/' . $producto->imagen; ?>" class="me-3 rounded-circle" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #e0e7ff;">
                                    <?php else: ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                                        <i class="bi bi-box text-white"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <span class="fw-bold text-dark"><?php echo $producto->nombre; ?></span>
                                        <?php if ($producto->codigo_barras): ?>
                                        <br><small class="text-muted"><i class="bi bi-barcode me-1"></i><?php echo $producto->codigo_barras; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-light text-dark rounded-pill border px-3">
                                    <i class="bi bi-folder text-primary me-1"></i><?php echo $producto->categoria_nombre ?? 'Sin categoría'; ?>
                                </span>
                            </td>
                            <td class="align-middle text-end">
                                <span class="fw-bold text-primary fs-5"><?php echo formatCurrency($producto->precio_venta); ?></span>
                            </td>
                            <td class="align-middle text-center">
                                <?php
                                    $stockStyles = match (true) {
                                        $producto->estaEnStockMinimo()                    => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-exclamation-triangle-fill', 'label' => 'CRÍTICO'],
                                        $producto->stock_actual > $producto->stock_maximo => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-arrow-up-circle', 'label' => 'EXCESO'],
                                        default                                           => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-check-circle-fill', 'label' => 'OK']
                                    };
                                ?>
                                <div class="d-flex flex-column align-items-center">
                                    <span class="badge" style="background: <?php echo $stockStyles['bg']; ?>; color: <?php echo $stockStyles['color']; ?>; padding: 8px 12px; border-radius: 20px; font-size: 0.8rem;">
                                        <i class="bi <?php echo $stockStyles['icon']; ?> me-1"></i><?php echo $stockStyles['label']; ?>
                                    </span>
                                    <small class="text-muted mt-1 fw-bold"><?php echo $producto->stock_actual; ?> / <?php echo $producto->stock_minimo; ?> min</small>
                                </div>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $estadoStyles = $producto->estado
                                        ? ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-check-circle', 'label' => 'Activo']
                                        : ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-x-circle', 'label' => 'Inactivo'];
                                ?>
                                <span class="badge" style="background: <?php echo $estadoStyles['bg']; ?>; color: <?php echo $estadoStyles['color']; ?>; padding: 8px 12px; border-radius: 20px;">
                                    <i class="bi <?php echo $estadoStyles['icon']; ?> me-1"></i><?php echo $estadoStyles['label']; ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-info btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Ver detalles" onclick="verProducto(<?php echo $producto->id; ?>)" data-producto='<?php echo json_encode($producto); ?>'>
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Editar producto" onclick="editarProducto(<?php echo $producto->id; ?>)" data-producto='<?php echo json_encode($producto); ?>'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="eliminar.php?id=<?php echo $producto->id; ?>" class="btn btn-danger btn-sm rounded-circle btn-eliminar" style="width: 35px; height: 35px; padding: 0;" title="Eliminar producto">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($productos['items'])): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #eef2ff, #e0e7ff);">
                                        <i class="bi bi-inbox fs-1 text-primary"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No se encontraron productos</h5>
                                    <p class="text-muted mb-3">Agrega tu primer producto al almacén</p>
                                    <a href="nuevo.php" class="btn btn-primary rounded-pill px-4 text-white" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none;">
                                        <i class="bi bi-plus-circle me-2"></i>Crear Producto
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación Moderna -->
        <?php if ($productos['total_pages'] > 1): ?>
        <div class="card-footer border-0" style="background: #f8f9fa;">
            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $productos['page'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $productos['page'] - 1; ?>&<?php echo http_build_query($filters); ?>" style="border: none;">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $productos['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $productos['page'] ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"
                           style="border: none; <?php echo $i === $productos['page'] ? 'background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $productos['page'] >= $productos['total_pages'] ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill" href="?page=<?php echo $productos['page'] + 1; ?>&<?php echo http_build_query($filters); ?>" style="border: none;">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    #tablaProductos tbody tr:hover {
        background: linear-gradient(135deg, #eef2ff, #ffffff);
        transform: scale(1.002);
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
    }
    #tablaProductos tbody tr.stock-bajo {
        background: linear-gradient(135deg, #fef2f2, #ffffff);
    }
    #tablaProductos tbody tr.stock-bajo:hover {
        background: linear-gradient(135deg, #fee2e2, #ffffff);
    }
    #tablaProductos tbody tr.stock-alto {
        background: linear-gradient(135deg, #fffbeb, #ffffff);
    }
    #tablaProductos tbody tr.stock-alto:hover {
        background: linear-gradient(135deg, #fef3c7, #ffffff);
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .almacen-header {
            padding: 15px !important;
            margin-bottom: 15px !important;
        }
        .almacen-header h2 {
            font-size: 1.2rem;
        }
        .almacen-header p {
            font-size: 0.85rem;
        }
        .almacen-header .btn-lg {
            font-size: 0.85rem;
            padding: 8px 12px !important;
        }
        .row.g-3.mb-4 > .col-md-3 {
            width: 50% !important;
            padding: 5px !important;
        }
        .row.g-3.mb-4 .card-body {
            padding: 0.75rem !important;
        }
        .row.g-3.mb-4 .rounded-circle {
            width: 45px !important;
            height: 45px !important;
        }
        .row.g-3.mb-4 h6 {
            font-size: 0.7rem;
        }
        .row.g-3.mb-4 h3 {
            font-size: 1.1rem;
        }
        .card-body.p-4 {
            padding: 0.75rem !important;
        }
        .card-header.py-3 {
            padding: 12px 15px !important;
        }
        .card-header h5 {
            font-size: 0.95rem;
        }
        .card-header .btn-sm {
            font-size: 0.75rem;
            padding: 4px 10px !important;
        }
        .table-responsive {
            border: none;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #tablaProductos {
            font-size: 0.8rem;
            min-width: 700px;
        }
        #tablaProductos th,
        #tablaProductos td {
            padding: 6px 4px;
            white-space: nowrap;
        }
        #tablaProductos .btn-sm {
            width: 28px !important;
            height: 28px !important;
            font-size: 0.7rem;
        }
        #tablaProductos img.rounded-circle,
        #tablaProductos .rounded-circle.d-flex {
            width: 32px !important;
            height: 32px !important;
        }
        #tablaProductos .d-flex.flex-column.align-items-center small {
            font-size: 0.65rem;
        }
        form .col-md-4,
        form .col-md-3,
        form .col-md-2 {
            width: 100% !important;
            margin-bottom: 10px;
        }
        form .d-flex.align-items-end {
            align-items: stretch !important;
        }
        .pagination .page-link {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 576px) {
        .almacen-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        .almacen-header .btn-lg {
            width: 100%;
            justify-content: center;
        }
        #tablaProductos td .badge {
            font-size: 0.65rem;
            padding: 3px 6px;
        }
        .card-header .d-flex.gap-2 {
            gap: 5px !important;
        }
    }
/* Wizard Modals Styles */
.wizard-stepper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding: 15px 0;
    position: relative;
}
.wizard-stepper::before {
    content: '';
    position: absolute;
    top: 28px;
    left: 50px;
    right: 50px;
    height: 3px;
    background: #e5e7eb;
    z-index: 0;
}
.wizard-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
    cursor: pointer;
}
.wizard-step .step-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #6b7280;
    transition: all 0.3s ease;
    border: 3px solid #e5e7eb;
}
.wizard-step.active .step-circle {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border-color: #6366f1;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
}
.wizard-step.completed .step-circle {
    background: #22c55e;
    color: white;
    border-color: #22c55e;
}
.wizard-step small {
    margin-top: 8px;
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
}
.wizard-step.active small {
    color: #6366f1;
    font-weight: 600;
}
.wizard-content {
    display: none;
    animation: fadeIn 0.3s ease;
}
.wizard-content.active {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.wizard-nav {
    display: flex;
    justify-content: space-between;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}
.wizard-producto-card {
    background: linear-gradient(135deg, #eef2ff, #ffffff);
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #e0e7ff;
}
.wizard-producto-img {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
    border: 3px solid #6366f1;
}
.wizard-producto-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .wizard-stepper::before {
        left: 30px;
        right: 30px;
    }
    .wizard-step .step-circle {
        width: 35px;
        height: 35px;
        font-size: 0.8rem;
    }
    .wizard-step small {
        font-size: 0.6rem;
    }
    .modal-lg {
        max-width: 100% !important;
        margin: 10px !important;
    }
}
</style>

<!-- Modal VER Producto - Wizard -->
<div class="modal fade" id="modalVerProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title"><i class="bi bi-eye-fill me-2"></i>Detalles del Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Stepper -->
                <div class="wizard-stepper">
                    <div class="wizard-step active" data-step="1" onclick="goToStepVer(1)">
                        <div class="step-circle"><i class="bi bi-box"></i></div>
                        <small>General</small>
                    </div>
                    <div class="wizard-step" data-step="2" onclick="goToStepVer(2)">
                        <div class="step-circle"><i class="bi bi-currency-dollar"></i></div>
                        <small>Precios</small>
                    </div>
                    <div class="wizard-step" data-step="3" onclick="goToStepVer(3)">
                        <div class="step-circle"><i class="bi bi-clipboard-data"></i></div>
                        <small>Stock</small>
                    </div>
                    <div class="wizard-step" data-step="4" onclick="goToStepVer(4)">
                        <div class="step-circle"><i class="bi bi-clock-history"></i></div>
                        <small>Historial</small>
                    </div>
                </div>

                <!-- Step 1: Info General -->
                <div class="wizard-content active" id="ver-step-1">
                    <div class="wizard-producto-card">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div id="ver-producto-img-container">
                                <div class="wizard-producto-placeholder"><i class="bi bi-box text-white fs-2"></i></div>
                            </div>
                            <div>
                                <h5 class="mb-1 fw-bold" id="ver-nombre">-</h5>
                                <span class="badge bg-light text-dark" id="ver-codigo">-</span>
                                <div class="mt-1" id="ver-categoria-badge"></div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="text-muted small">Código de Barras</label><p class="fw-bold mb-0" id="ver-codigo-barras">-</p></div>
                            <div class="col-md-6"><label class="text-muted small">Unidad de Medida</label><p class="fw-bold mb-0" id="ver-unidad">-</p></div>
                            <div class="col-md-6"><label class="text-muted small">Categoría</label><p class="fw-bold mb-0" id="ver-categoria">-</p></div>
                            <div class="col-md-6"><label class="text-muted small">Proveedor</label><p class="fw-bold mb-0" id="ver-proveedor">-</p></div>
                            <div class="col-12"><label class="text-muted small">Descripción</label><p class="mb-0" id="ver-descripcion">-</p></div>
                            <div class="col-12"><label class="text-muted small">Ubicación</label><p class="mb-0" id="ver-ubicacion">-</p></div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Precios -->
                <div class="wizard-content" id="ver-step-2">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border-0" style="background: linear-gradient(135deg, #fee2e2, #ffffff); border-radius: 12px;">
                                <div class="card-body text-center">
                                    <i class="bi bi-cart-fill text-danger fs-2 mb-2"></i>
                                    <h6 class="text-muted">Costo</h6>
                                    <h4 class="fw-bold text-danger mb-0" id="ver-precio-costo">-</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0" style="background: linear-gradient(135deg, #dcfce7, #ffffff); border-radius: 12px;">
                                <div class="card-body text-center">
                                    <i class="bi bi-tag-fill text-success fs-2 mb-2"></i>
                                    <h6 class="text-muted">Venta</h6>
                                    <h4 class="fw-bold text-success mb-0" id="ver-precio-venta">-</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0" style="background: linear-gradient(135deg, #fef3c7, #ffffff); border-radius: 12px;">
                                <div class="card-body text-center">
                                    <i class="bi bi-shop-fill text-warning fs-2 mb-2"></i>
                                    <h6 class="text-muted">Mayorista</h6>
                                    <h4 class="fw-bold text-warning mb-0" id="ver-precio-mayorista">-</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Margen de venta:</strong> <span id="ver-margen">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Stock -->
                <div class="wizard-content" id="ver-step-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-0" style="background: linear-gradient(135deg, #eef2ff, #ffffff); border-radius: 12px;">
                                <div class="card-body">
                                    <h6 class="text-muted">Stock Actual</h6>
                                    <h2 class="fw-bold text-primary mb-0" id="ver-stock-actual">-</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0" style="background: linear-gradient(135deg, #f3f4f6, #ffffff); border-radius: 12px;">
                                <div class="card-body">
                                    <h6 class="text-muted">Estado</h6>
                                    <div id="ver-stock-estado"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4"><label class="text-muted">Stock Mínimo</label><p class="fw-bold" id="ver-stock-minimo">-</p></div>
                        <div class="col-md-4"><label class="text-muted">Stock Máximo</label><p class="fw-bold" id="ver-stock-maximo">-</p></div>
                        <div class="col-md-4"><label class="text-muted">Disponible</label><p class="fw-bold" id="ver-stock-disponible">-</p></div>
                    </div>
                </div>

                <!-- Step 4: Historial -->
                <div class="wizard-content" id="ver-step-4">
                    <div class="text-center py-5">
                        <i class="bi bi-clock-history text-muted fs-1 mb-3"></i>
                        <p class="text-muted">Ver historial de movimientos en el módulo Kardex</p>
                        <a href="<?php echo SITE_URL; ?>/views/kardex/index.php" class="btn btn-primary">
                            <i class="bi bi-box-seam me-2"></i>Ir a Kardex
                        </a>
                    </div>
                </div>

                <div class="wizard-nav">
                    <button type="button" class="btn btn-light" id="ver-btn-anterior" onclick="navVer(-1)" style="border-radius: 20px;">
                        <i class="bi bi-arrow-left me-2"></i>Anterior
                    </button>
                    <button type="button" class="btn btn-primary" id="ver-btn-siguiente" onclick="navVer(1)" style="border-radius: 20px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none;">
                        Siguiente<i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal EDITAR Producto - Wizard -->
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Editar Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="editar_guardar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body p-4">
                    <!-- Stepper -->
                    <div class="wizard-stepper">
                        <div class="wizard-step active" data-step="1" onclick="goToStepEdit(1)">
                            <div class="step-circle"><i class="bi bi-box"></i></div>
                            <small>General</small>
                        </div>
                        <div class="wizard-step" data-step="2" onclick="goToStepEdit(2)">
                            <div class="step-circle"><i class="bi bi-currency-dollar"></i></div>
                            <small>Precios</small>
                        </div>
                        <div class="wizard-step" data-step="3" onclick="goToStepEdit(3)">
                            <div class="step-circle"><i class="bi bi-clipboard-data"></i></div>
                            <small>Stock</small>
                        </div>
                        <div class="wizard-step" data-step="4" onclick="goToStepEdit(4)">
                            <div class="step-circle"><i class="bi bi-check-lg"></i></div>
                            <small>Confirmar</small>
                        </div>
                    </div>

                    <!-- Step 1: Info General -->
                    <div class="wizard-content active" id="edit-step-1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Código <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="codigo" id="edit-codigo" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Código de Barras</label>
                                <input type="text" class="form-control" name="codigo_barras" id="edit-codigo-barras">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" id="edit-nombre" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" id="edit-descripcion" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" name="categoria_id" id="edit-categoria">
                                    <option value="">Sin categoría</option>
                                    <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat->id; ?>"><?php echo $cat->nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unidad de Medida</label>
                                <input type="text" class="form-control" name="unidad_medida" id="edit-unidad" placeholder="LT, KG, UN, etc.">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Precios -->
                    <div class="wizard-content" id="edit-step-2">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Precio Costo <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="precio_costo" id="edit-precio-costo" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio Venta <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="precio_venta" id="edit-precio-venta" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio Mayorista</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="precio_mayorista" id="edit-precio-mayorista" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Stock -->
                    <div class="wizard-content" id="edit-step-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Stock Actual</label>
                                <input type="number" class="form-control" name="stock_actual" id="edit-stock-actual" readonly style="background: #f8f9fa;">
                                <small class="text-muted">Gestionado por Kardex</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" name="stock_minimo" id="edit-stock-minimo">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Máximo</label>
                                <input type="number" class="form-control" name="stock_maximo" id="edit-stock-maximo">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ubicación</label>
                                <input type="text" class="form-control" name="ubicacion" id="edit-ubicacion" placeholder="Estante A-1, Bodega, etc.">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="estado" id="edit-estado" value="1">
                                    <label class="form-check-label" for="edit-estado">Producto Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Confirmar -->
                    <div class="wizard-content" id="edit-step-4">
                        <div class="text-center mb-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #dcfce7, #bbf7d0);">
                                <i class="bi bi-check-lg fs-1 text-success"></i>
                            </div>
                            <h5>¿Guardar cambios?</h5>
                            <p class="text-muted">Revisa que la información sea correcta antes de continuar.</p>
                        </div>
                        <div class="wizard-producto-card">
                            <h6 class="mb-3">Resumen de cambios:</h6>
                            <div class="row g-2">
                                <div class="col-sm-6"><small class="text-muted">Nombre:</small> <span id="resumen-nombre" class="fw-bold"></span></div>
                                <div class="col-sm-6"><small class="text-muted">Código:</small> <span id="resumen-codigo" class="fw-bold"></span></div>
                                <div class="col-sm-6"><small class="text-muted">Precio Venta:</small> <span id="resumen-venta" class="fw-bold"></span></div>
                                <div class="col-sm-6"><small class="text-muted">Stock Mín:</small> <span id="resumen-min" class="fw-bold"></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="btn btn-light" id="edit-btn-anterior" onclick="navEdit(-1)" style="border-radius: 20px;">
                            <i class="bi bi-arrow-left me-2"></i>Anterior
                        </button>
                        <button type="button" class="btn btn-primary" id="edit-btn-siguiente" onclick="navEdit(1)" style="border-radius: 20px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none;">
                            <span id="edit-btn-text">Siguiente<i class="bi bi-arrow-right ms-2"></i></span>
                        </button>
                        <button type="submit" class="btn btn-success d-none" id="edit-btn-guardar" style="border-radius: 20px;">
                            <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentStepVer = 1;
let currentStepEdit = 1;
const totalStepsVer = 4;
const totalStepsEdit = 4;

// Función VER Producto
function verProducto(id) {
    const btn = document.querySelector(`button[onclick="verProducto(${id})"]`);
    const productoData = btn.getAttribute('data-producto');
    const p = JSON.parse(productoData);

    // Llenar datos
    document.getElementById('ver-nombre').textContent = p.nombre;
    document.getElementById('ver-codigo').textContent = p.codigo;
    document.getElementById('ver-codigo-barras').textContent = p.codigo_barras || 'N/A';
    document.getElementById('ver-unidad').textContent = p.unidad_medida || 'N/A';
    document.getElementById('ver-categoria').textContent = p.categoria_nombre || 'Sin categoría';
    document.getElementById('ver-proveedor').textContent = p.proveedor_nombre || 'N/A';
    document.getElementById('ver-descripcion').textContent = p.descripcion || 'Sin descripción';
    document.getElementById('ver-ubicacion').textContent = p.ubicacion || 'No especificada';
    document.getElementById('ver-categoria-badge').innerHTML = p.categoria_nombre ? `<span class="badge bg-primary">${p.categoria_nombre}</span>` : '';

    // Imagen
    const imgContainer = document.getElementById('ver-producto-img-container');
    if (p.imagen) {
        imgContainer.innerHTML = `<img src="<?php echo SITE_URL; ?>/uploads/${p.imagen}" class="wizard-producto-img">`;
    } else {
        imgContainer.innerHTML = `<div class="wizard-producto-placeholder"><i class="bi bi-box text-white fs-2"></i></div>`;
    }

    // Precios
    document.getElementById('ver-precio-costo').textContent = '$' + parseFloat(p.precio_costo).toLocaleString('es-CO');
    document.getElementById('ver-precio-venta').textContent = '$' + parseFloat(p.precio_venta).toLocaleString('es-CO');
    document.getElementById('ver-precio-mayorista').textContent = p.precio_mayorista ? '$' + parseFloat(p.precio_mayorista).toLocaleString('es-CO') : 'N/A';

    const margen = p.precio_costo > 0 ? ((p.precio_venta - p.precio_costo) / p.precio_costo * 100).toFixed(1) : 0;
    document.getElementById('ver-margen').textContent = margen + '%';

    // Stock
    document.getElementById('ver-stock-actual').textContent = p.stock_actual;
    document.getElementById('ver-stock-minimo').textContent = p.stock_minimo;
    document.getElementById('ver-stock-maximo').textContent = p.stock_maximo;
    document.getElementById('ver-stock-disponible').textContent = p.stock_actual;

    let estadoHtml = '';
    if (p.stock_actual <= p.stock_minimo) {
        estadoHtml = '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>CRÍTICO</span>';
    } else if (p.stock_actual > p.stock_maximo) {
        estadoHtml = '<span class="badge bg-warning text-dark"><i class="bi bi-arrow-up-circle me-1"></i>EXCESO</span>';
    } else {
        estadoHtml = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>OK</span>';
    }
    document.getElementById('ver-stock-estado').innerHTML = estadoHtml;

    // Reset stepper
    currentStepVer = 1;
    updateStepperVer();

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalVerProducto'));
    modal.show();
}

function goToStepVer(step) {
    currentStepVer = step;
    updateStepperVer();
}

function navVer(direction) {
    const newStep = currentStepVer + direction;
    if (newStep >= 1 && newStep <= totalStepsVer) {
        currentStepVer = newStep;
        updateStepperVer();
    }
}

function updateStepperVer() {
    document.querySelectorAll('#modalVerProducto .wizard-step').forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');
        if (stepNum === currentStepVer) {
            step.classList.add('active');
        } else if (stepNum < currentStepVer) {
            step.classList.add('completed');
        }
    });

    document.querySelectorAll('#modalVerProducto .wizard-content').forEach((content, index) => {
        content.classList.remove('active');
        if (index + 1 === currentStepVer) {
            content.classList.add('active');
        }
    });

    document.getElementById('ver-btn-anterior').disabled = currentStepVer === 1;
    document.getElementById('ver-btn-siguiente').innerHTML = currentStepVer === totalStepsVer
        ? 'Cerrar<i class="bi bi-x-lg ms-2"></i>'
        : 'Siguiente<i class="bi bi-arrow-right ms-2"></i>';
    if (currentStepVer === totalStepsVer) {
        document.getElementById('ver-btn-siguiente').setAttribute('onclick', 'bootstrap.Modal.getInstance(document.getElementById(\'modalVerProducto\')).hide()');
    } else {
        document.getElementById('ver-btn-siguiente').setAttribute('onclick', 'navVer(1)');
    }
}

// Función EDITAR Producto
function editarProducto(id) {
    const btn = document.querySelector(`button[onclick="editarProducto(${id})"]`);
    const productoData = btn.getAttribute('data-producto');
    const p = JSON.parse(productoData);

    // Llenar formulario
    document.getElementById('edit-id').value = p.id;
    document.getElementById('edit-codigo').value = p.codigo;
    document.getElementById('edit-codigo-barras').value = p.codigo_barras || '';
    document.getElementById('edit-nombre').value = p.nombre;
    document.getElementById('edit-descripcion').value = p.descripcion || '';
    document.getElementById('edit-categoria').value = p.categoria_id || '';
    document.getElementById('edit-unidad').value = p.unidad_medida || '';
    document.getElementById('edit-precio-costo').value = p.precio_costo;
    document.getElementById('edit-precio-venta').value = p.precio_venta;
    document.getElementById('edit-precio-mayorista').value = p.precio_mayorista || '';
    document.getElementById('edit-stock-actual').value = p.stock_actual;
    document.getElementById('edit-stock-minimo').value = p.stock_minimo;
    document.getElementById('edit-stock-maximo').value = p.stock_maximo;
    document.getElementById('edit-ubicacion').value = p.ubicacion || '';
    document.getElementById('edit-estado').checked = p.estado == 1;

    // Reset stepper
    currentStepEdit = 1;
    updateStepperEdit();

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditarProducto'));
    modal.show();
}

function goToStepEdit(step) {
    currentStepEdit = step;
    updateStepperEdit();
}

function navEdit(direction) {
    const newStep = currentStepEdit + direction;
    if (newStep >= 1 && newStep <= totalStepsEdit) {
        currentStepEdit = newStep;
        updateStepperEdit();
    }
}

function updateStepperEdit() {
    document.querySelectorAll('#modalEditarProducto .wizard-step').forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');
        if (stepNum === currentStepEdit) {
            step.classList.add('active');
        } else if (stepNum < currentStepEdit) {
            step.classList.add('completed');
        }
    });

    document.querySelectorAll('#modalEditarProducto .wizard-content').forEach((content, index) => {
        content.classList.remove('active');
        if (index + 1 === currentStepEdit) {
            content.classList.add('active');
        }
    });

    document.getElementById('edit-btn-anterior').disabled = currentStepEdit === 1;

    // Actualizar resumen en paso 4
    if (currentStepEdit === 4) {
        document.getElementById('resumen-nombre').textContent = document.getElementById('edit-nombre').value;
        document.getElementById('resumen-codigo').textContent = document.getElementById('edit-codigo').value;
        document.getElementById('resumen-venta').textContent = '$' + parseFloat(document.getElementById('edit-precio-venta').value).toLocaleString('es-CO');
        document.getElementById('resumen-min').textContent = document.getElementById('edit-stock-minimo').value;

        document.getElementById('edit-btn-siguiente').classList.add('d-none');
        document.getElementById('edit-btn-guardar').classList.remove('d-none');
    } else {
        document.getElementById('edit-btn-siguiente').classList.remove('d-none');
        document.getElementById('edit-btn-guardar').classList.add('d-none');
    }
}

// Actualizar resumen al cambiar datos
document.querySelectorAll('#modalEditarProducto input').forEach(input => {
    input.addEventListener('change', () => {
        if (currentStepEdit === 4) {
            document.getElementById('resumen-nombre').textContent = document.getElementById('edit-nombre').value;
            document.getElementById('resumen-codigo').textContent = document.getElementById('edit-codigo').value;
        }
    });
});
</script>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Almacén', $content);
