<?php
    /**
 * Gestión de Usuarios
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('usuarios');

    $repo    = new UsuarioRepository();
    $rolRepo = new RolRepository();

    $page     = intval($_GET['page'] ?? 1);
    $filters  = ['estado' => 1];
    $usuarios = $repo->paginateWithRoles($page, ITEMS_PER_PAGE, $filters, 'u.nombre ASC');
    $roles    = $rolRepo->findAllActive();

    ob_start();
?>

<!-- Header Moderno Azul Navy -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="usuarios-header" style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(30, 58, 95, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i class="bi bi-person-gear-fill me-2"></i>Usuarios</h2>
                        <p style="font-size: 0.75rem; margin: 0; opacity: 0.9;">Administra usuarios</p>
                    </div>
                    <a href="nuevo.php" class="btn btn-light rounded-pill" style="color: #1e3a5f; font-size: 0.85rem; padding: 6px 12px;">
                        <i class="bi bi-plus-circle me-1"></i>Nuevo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #eff6ff, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #1e3a5f, #2563eb);">
                        <i class="bi bi-people text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total</div>
                        <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?php echo $usuarios['total']; ?></div>
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
                        <div class="text-muted" style="font-size: 0.65rem;">Activos</div>
                        <?php
                            $activos      = array_filter($usuarios['items'], fn($u) => $u->estado);
                            $totalActivos = count($activos);
                        ?>
                        <div class="fw-bold text-success" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalActivos; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fef3c7, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="bi bi-shield text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Roles</div>
                        <div class="fw-bold" style="font-size: 1rem; color: #f59e0b; line-height: 1.2;"><?php echo count($roles); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #f0f9ff, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #0ea5e9, #38bdf8);">
                        <i class="bi bi-clock-history text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">En línea</div>
                        <?php
                            $recientes      = array_filter($usuarios['items'], fn($u) => $u->ultimo_acceso && strtotime($u->ultimo_acceso) > strtotime('-24 hours'));
                            $totalRecientes = count($recientes);
                        ?>
                        <div class="fw-bold" style="font-size: 1rem; color: #0ea5e9; line-height: 1.2;"><?php echo $totalRecientes; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Moderna -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);">
            <h5 class="card-title mb-0 text-white fw-bold"><i class="bi bi-list-check me-2"></i>Listado de Usuarios</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaUsuarios" style="background: #ffffff;">
                    <thead style="background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                        <tr>
                            <th class="fw-bold text-dark" style="border: none;">Usuario</th>
                            <th class="fw-bold text-dark" style="border: none;">Email</th>
                            <th class="fw-bold text-dark" style="border: none;">Rol</th>
                            <th class="fw-bold text-dark" style="border: none;">Último Acceso</th>
                            <th class="fw-bold text-dark" style="border: none;">Estado</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios['items'] as $usuario): ?>
                        <?php
                            $enLinea = $usuario->ultimo_acceso && strtotime($usuario->ultimo_acceso) > strtotime('-24 hours');
                        ?>
                        <tr style="transition: all 0.2s;" class="<?php echo $enLinea ? 'en-linea' : ''; ?>">
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <?php if ($usuario->avatar): ?>
                                    <img src="<?php echo SITE_URL . '/uploads/' . $usuario->avatar; ?>" class="rounded-circle me-3" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #dbeafe;">
                                    <?php else: ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; background: linear-gradient(135deg, #1e3a5f, #2563eb);">
                                        <span class="text-white fw-bold" style="font-size: 1.2rem;"><?php echo strtoupper(substr($usuario->nombre, 0, 1)); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <span class="fw-bold text-dark fs-5"><?php echo $usuario->getNombreCompleto(); ?></span>
                                        <br><small class="text-muted"><i class="bi bi-at me-1"></i><?php echo $usuario->username; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <span class="text-muted"><i class="bi bi-envelope text-secondary me-1"></i><?php echo $usuario->email; ?></span>
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-light text-dark rounded-pill border px-3">
                                    <i class="bi bi-shield text-primary me-1"></i><?php echo $usuario->rol_nombre; ?>
                                </span>
                            </td>
                            <td class="align-middle">
                                <?php if ($usuario->ultimo_acceso): ?>
                                <div class="d-flex flex-column">
                                    <span class="badge bg-light text-dark rounded-pill border mb-1" style="width: fit-content;">
                                        <i class="bi bi-calendar text-primary me-1"></i><?php echo date('d/m/Y', strtotime($usuario->ultimo_acceso)); ?>
                                    </span>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($usuario->ultimo_acceso)); ?></small>
                                </div>
                                <?php else: ?>
                                <span class="badge bg-light text-muted rounded-pill border">
                                    <i class="bi bi-dash-circle me-1"></i>Nunca
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle">
                                <?php
                                    $estadoStyles = $usuario->estado
                                        ? ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-check-circle', 'label' => 'Activo']
                                        : ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-x-circle', 'label' => 'Inactivo'];
                                ?>
                                <span class="badge" style="background: <?php echo $estadoStyles['bg']; ?>; color: <?php echo $estadoStyles['color']; ?>; padding: 8px 12px; border-radius: 20px;">
                                    <i class="bi <?php echo $estadoStyles['icon']; ?> me-1"></i><?php echo $estadoStyles['label']; ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="editar.php?id=<?php echo $usuario->id; ?>" class="btn btn-primary btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Editar usuario">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="permisos.php?id=<?php echo $usuario->id; ?>" class="btn btn-warning btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Gestionar permisos">
                                        <i class="bi bi-shield"></i>
                                    </a>
                                    <?php if ($usuario->id !== SessionManager::getUserId()): ?>
                                    <a href="eliminar.php?id=<?php echo $usuario->id; ?>" class="btn btn-danger btn-sm rounded-circle btn-eliminar" style="width: 35px; height: 35px; padding: 0;" title="Eliminar usuario">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usuarios['items'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                                        <i class="bi bi-inbox fs-1 text-primary"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No se encontraron usuarios</h5>
                                    <p class="text-muted mb-3">Crea tu primer usuario para el sistema</p>
                                    <a href="nuevo.php" class="btn btn-primary rounded-pill px-4 text-white" style="background: linear-gradient(135deg, #1e3a5f, #2563eb); border: none;">
                                        <i class="bi bi-plus-circle me-2"></i>Crear Usuario
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
        <?php if ($usuarios['total_pages'] > 1): ?>
        <div class="card-footer border-0" style="background: #f8f9fa;">
            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $usuarios['page'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $usuarios['page'] - 1; ?>" style="border: none;">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $usuarios['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $usuarios['page'] ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $i; ?>"
                           style="border: none; <?php echo $i === $usuarios['page'] ? 'background: linear-gradient(135deg, #1e3a5f, #2563eb); color: white;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $usuarios['page'] >= $usuarios['total_pages'] ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill" href="?page=<?php echo $usuarios['page'] + 1; ?>" style="border: none;">
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
    #tablaUsuarios tbody tr:hover {
        background: linear-gradient(135deg, #eff6ff, #ffffff);
        transform: scale(1.002);
        box-shadow: 0 2px 8px rgba(30, 58, 95, 0.1);
    }
    #tablaUsuarios tbody tr.en-linea {
        background: linear-gradient(135deg, #f0f9ff, #ffffff);
    }
    #tablaUsuarios tbody tr.en-linea:hover {
        background: linear-gradient(135deg, #e0f2fe, #ffffff);
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .usuarios-header {
            padding: 15px !important;
        }
        .usuarios-header h2 {
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
        #tablaUsuarios {
            font-size: 0.8rem;
            min-width: 700px;
        }
        #tablaUsuarios th,
        #tablaUsuarios td {
            padding: 6px 4px;
            white-space: nowrap;
        }
        .pagination .page-link {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 576px) {
        .usuarios-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        #tablaUsuarios td .badge {
            font-size: 0.65rem;
            padding: 3px 6px;
        }
    }
</style>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Usuarios', $content);
