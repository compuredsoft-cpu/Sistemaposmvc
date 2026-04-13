<?php
    /**
 * Gestión de Categorías
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('almacen');

    $repo       = new CategoriaRepository();
    $categorias = $repo->findAllActive();

    ob_start();
?>

<!-- Header Moderno Rosa/Magenta -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="categorias-header" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(236, 72, 153, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i class="bi bi-tags-fill me-2"></i>Categorías</h2>
                        <p style="font-size: 0.75rem; margin: 0; opacity: 0.9;">Organiza tus productos</p>
                    </div>
                    <a href="nuevo.php" class="btn btn-light rounded-pill" style="color: #ec4899; font-size: 0.85rem; padding: 6px 12px;">
                        <i class="bi bi-plus-circle me-1"></i>Nueva
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fdf2f8, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #ec4899, #db2777);">
                        <i class="bi bi-tags text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total</div>
                        <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?php echo count($categorias); ?></div>
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
                        <div class="text-muted" style="font-size: 0.65rem;">Activas</div>
                        <?php
                            $activas      = array_filter($categorias, fn($c) => $c->estado);
                            $totalActivas = count($activas);
                        ?>
                        <div class="fw-bold text-success" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalActivas; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fef3c7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="bi bi-box-seam text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total Productos</div>
                        <?php
                            $totalProductos = array_sum(array_column($categorias, 'total_productos'));
                        ?>
                        <div class="fw-bold" style="font-size: 1rem; color: #f59e0b; line-height: 1.2;"><?php echo $totalProductos; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Moderna -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
            <h5 class="card-title mb-0 text-white fw-bold"><i class="bi bi-grid-3x3-gap me-2"></i>Listado de Categorías</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaCategorias" style="background: #ffffff;">
                    <thead style="background: linear-gradient(135deg, #fdf2f8, #fce7f3);">
                        <tr>
                            <th class="fw-bold text-dark" style="border: none;">Categoría</th>
                            <th class="fw-bold text-dark" style="border: none;">Descripción</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Productos</th>
                            <th class="fw-bold text-dark" style="border: none;">Estado</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                        <tr style="transition: all 0.2s;">
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #ec4899, #db2777);">
                                        <i class="bi bi-tag-fill text-white" style="font-size: 1rem;"></i>
                                    </div>
                                    <span class="fw-bold text-dark fs-5"><?php echo $cat->nombre; ?></span>
                                </div>
                            </td>
                            <td class="align-middle">
                                <span class="text-muted"><?php echo $cat->descripcion ?: '<em class="text-muted">Sin descripción</em>'; ?></span>
                            </td>
                            <td class="align-middle text-center">
                                <span class="badge bg-light text-dark rounded-pill border px-3 py-2" style="font-size: 0.9rem;">
                                    <i class="bi bi-box-seam text-primary me-1"></i>
                                    <strong><?php echo $cat->total_productos ?? 0; ?></strong> productos
                                </span>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $estadoStyles = $cat->estado
                                        ? ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-check-circle', 'label' => 'Activa']
                                        : ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-x-circle', 'label' => 'Inactiva'];
                                ?>
                                <span class="badge" style="background: <?php echo $estadoStyles['bg']; ?>; color: <?php echo $estadoStyles['color']; ?>; padding: 8px 12px; border-radius: 20px;">
                                    <i class="bi <?php echo $estadoStyles['icon']; ?> me-1"></i><?php echo $estadoStyles['label']; ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="editar.php?id=<?php echo $cat->id; ?>" class="btn btn-primary btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Editar categoría">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="eliminar.php?id=<?php echo $cat->id; ?>" class="btn btn-danger btn-sm rounded-circle btn-eliminar" style="width: 35px; height: 35px; padding: 0;" title="Eliminar categoría">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #fdf2f8, #fce7f3);">
                                        <i class="bi bi-inbox fs-1 text-primary"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No se encontraron categorías</h5>
                                    <p class="text-muted mb-3">Crea tu primera categoría para organizar productos</p>
                                    <a href="nuevo.php" class="btn btn-primary rounded-pill px-4 text-white" style="background: linear-gradient(135deg, #ec4899, #db2777); border: none;">
                                        <i class="bi bi-plus-circle me-2"></i>Crear Categoría
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    #tablaCategorias tbody tr:hover {
        background: linear-gradient(135deg, #fdf2f8, #ffffff);
        transform: scale(1.002);
        box-shadow: 0 2px 8px rgba(236, 72, 153, 0.1);
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .categorias-header {
            padding: 15px !important;
        }
        .categorias-header h2 {
            font-size: 1.2rem;
        }
        .row.g-3.mb-4 > .col-md-3 {
            width: 50% !important;
            padding: 5px !important;
        }
        .card-body.p-4 {
            padding: 0.75rem !important;
        }
        form .col-md-4,
        form .col-md-2 {
            width: 100% !important;
            margin-bottom: 8px;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #tablaCategorias {
            font-size: 0.8rem;
            min-width: 600px;
        }
        #tablaCategorias th,
        #tablaCategorias td {
            padding: 6px 4px;
            white-space: nowrap;
        }
    }

    @media (max-width: 576px) {
        .categorias-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
    }
</style>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Categorías', $content);
