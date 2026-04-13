<?php
    /**
 * Listado de Proveedores
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('proveedores');

    $repo = new ProveedorRepository();

    $page        = intval($_GET['page'] ?? 1);
    $filters     = ['estado' => 1];
    $proveedores = $repo->paginate($page, ITEMS_PER_PAGE, $filters, 'nombre ASC');

    ob_start();
?>

<!-- Header Moderno Teal/Cian -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="proveedores-header" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(13, 148, 136, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i class="bi bi-truck-fill me-2"></i>Proveedores</h2>
                        <p style="font-size: 0.75rem; margin: 0; opacity: 0.9;">Administra proveedores</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="nuevo.php" class="btn btn-light rounded-pill" style="color: #0d9488; font-size: 0.8rem; padding: 5px 10px;">
                            <i class="bi bi-plus-circle me-1"></i>Nuevo
                        </a>
                        <button onclick="exportarExcel('tablaProveedores', 'proveedores')" class="btn btn-light rounded-pill" style="color: #0d9488; font-size: 0.8rem; padding: 5px 10px;">
                            <i class="bi bi-file-excel me-1"></i>Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #f0fdfa, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #0d9488, #14b8a6);">
                        <i class="bi bi-truck text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total</div>
                        <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?php echo $proveedores['total']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #ecfdf5, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #22c55e, #4ade80);">
                        <i class="bi bi-check-circle text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Activos</div>
                        <?php
                            $activos      = array_filter($proveedores['items'], fn($p) => $p->estado);
                            $totalActivos = count($activos);
                        ?>
                        <div class="fw-bold text-success" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalActivos; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fef3c7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="bi bi-people text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Con Contacto</div>
                        <?php
                            $conContacto      = array_filter($proveedores['items'], fn($p) => ! empty($p->contacto));
                            $totalConContacto = count($conContacto);
                        ?>
                        <div class="fw-bold" style="font-size: 1rem; color: #f59e0b; line-height: 1.2;"><?php echo $totalConContacto; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Moderna -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);">
            <h5 class="card-title mb-0 text-white fw-bold"><i class="bi bi-list-check me-2"></i>Listado de Proveedores</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaProveedores" style="background: #ffffff;">
                    <thead style="background: linear-gradient(135deg, #f0fdfa, #ccfbf1);">
                        <tr>
                            <th class="fw-bold text-dark" style="border: none;">Proveedor</th>
                            <th class="fw-bold text-dark" style="border: none;">Contacto</th>
                            <th class="fw-bold text-dark" style="border: none;">Teléfono</th>
                            <th class="fw-bold text-dark" style="border: none;">Ubicación</th>
                            <th class="fw-bold text-dark" style="border: none;">Estado</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores['items'] as $prov): ?>
                        <tr style="transition: all 0.2s;">
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; background: linear-gradient(135deg, #0d9488, #14b8a6);">
                                        <i class="bi bi-building text-white" style="font-size: 1.2rem;"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark fs-5"><?php echo $prov->nombre; ?></span>
                                        <?php if ($prov->documento): ?>
                                        <br><small class="text-muted"><i class="bi bi-card-text me-1"></i><?php echo $prov->tipo_documento; ?>: <?php echo $prov->documento; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex flex-column">
                                    <?php if ($prov->contacto): ?>
                                    <span class="badge bg-light text-dark rounded-pill border mb-1" style="width: fit-content;">
                                        <i class="bi bi-person text-primary me-1"></i><?php echo $prov->contacto; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($prov->email): ?>
                                    <small class="text-muted"><i class="bi bi-envelope text-secondary me-1"></i><?php echo $prov->email; ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="align-middle">
                                <?php if ($prov->telefono): ?>
                                <span class="badge bg-light text-dark rounded-pill border px-3">
                                    <i class="bi bi-telephone text-success me-1"></i><?php echo $prov->telefono; ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted"><em>Sin teléfono</em></span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle">
                                <?php if ($prov->direccion): ?>
                                <span class="text-muted"><i class="bi bi-geo-alt text-danger me-1"></i><?php echo $prov->direccion; ?></span>
                                <?php else: ?>
                                <span class="text-muted"><em>Sin dirección</em></span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $estadoStyles = $prov->estado
                                        ? ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-check-circle', 'label' => 'Activo']
                                        : ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-x-circle', 'label' => 'Inactivo'];
                                ?>
                                <span class="badge" style="background: <?php echo $estadoStyles['bg']; ?>; color: <?php echo $estadoStyles['color']; ?>; padding: 8px 12px; border-radius: 20px;">
                                    <i class="bi <?php echo $estadoStyles['icon']; ?> me-1"></i><?php echo $estadoStyles['label']; ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="editar.php?id=<?php echo $prov->id; ?>" class="btn btn-primary btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Editar proveedor">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="historial.php?id=<?php echo $prov->id; ?>" class="btn btn-info btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Ver historial">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <a href="eliminar.php?id=<?php echo $prov->id; ?>" class="btn btn-danger btn-sm rounded-circle btn-eliminar" style="width: 35px; height: 35px; padding: 0;" title="Eliminar proveedor">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($proveedores['items'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #f0fdfa, #ccfbf1);">
                                        <i class="bi bi-inbox fs-1 text-primary"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No se encontraron proveedores</h5>
                                    <p class="text-muted mb-3">Registra tu primer proveedor para gestionar compras</p>
                                    <a href="nuevo.php" class="btn btn-primary rounded-pill px-4 text-white" style="background: linear-gradient(135deg, #0d9488, #14b8a6); border: none;">
                                        <i class="bi bi-plus-circle me-2"></i>Crear Proveedor
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
        <?php if ($proveedores['total_pages'] > 1): ?>
        <div class="card-footer border-0" style="background: #f8f9fa;">
            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $proveedores['page'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $proveedores['page'] - 1; ?>" style="border: none;">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $proveedores['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $proveedores['page'] ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $i; ?>"
                           style="border: none; <?php echo $i === $proveedores['page'] ? 'background: linear-gradient(135deg, #0d9488, #14b8a6); color: white;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $proveedores['page'] >= $proveedores['total_pages'] ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill" href="?page=<?php echo $proveedores['page'] + 1; ?>" style="border: none;">
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
    #tablaProveedores tbody tr:hover {
        background: linear-gradient(135deg, #f0fdfa, #ffffff);
        transform: scale(1.002);
        box-shadow: 0 2px 8px rgba(13, 148, 136, 0.1);
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .proveedores-header {
            padding: 15px !important;
        }
        .proveedores-header h2 {
            font-size: 1.2rem;
        }
        .row.g-3.mb-4 > .col-md-3 {
            width: 50% !important;
            padding: 5px !important;
        }
        .card-body.p-4 {
            padding: 0.75rem !important;
        }
        form .col-md-3,
        form .col-md-2 {
            width: 100% !important;
            margin-bottom: 8px;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #tablaProveedores {
            font-size: 0.8rem;
            min-width: 700px;
        }
        #tablaProveedores th,
        #tablaProveedores td {
            padding: 6px 4px;
            white-space: nowrap;
        }
        .pagination .page-link {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 576px) {
        .proveedores-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        .proveedores-header .btn-lg {
            width: 100%;
            justify-content: center;
        }
        #tablaProveedores td .badge {
            font-size: 0.65rem;
            padding: 3px 6px;
        }
    }
</style>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Proveedores', $content);
