<?php
/**
 * Ver Permisos del Usuario
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('usuarios');

$usuarioRepo = new UsuarioRepository();
$rolRepo = new RolRepository();

// Obtener usuario
$id = intval($_GET['id'] ?? 0);
$usuario = $usuarioRepo->findById($id);

if (!$usuario) {
    header('Location: index.php?error=Usuario no encontrado');
    exit;
}

// Obtener rol y permisos
$rol = $rolRepo->findById($usuario->rol_id);
$permisosRol = $rol ? json_decode($rol->permisos ?? '[]', true) : [];

// Lista completa de permisos organizados por categoría
$permisosPorCategoria = [
    'Ventas y Comercio' => [
        'ventas' => ['icon' => 'bi-cart', 'label' => 'Ventas', 'desc' => 'Crear y gestionar ventas, POS'],
        'cotizaciones' => ['icon' => 'bi-file-text', 'label' => 'Cotizaciones', 'desc' => 'Crear y enviar cotizaciones'],
        'cuentasxcobrar' => ['icon' => 'bi-cash-stack', 'label' => 'Cuentas por Cobrar', 'desc' => 'Gestionar créditos y pagos'],
        'clientes' => ['icon' => 'bi-people', 'label' => 'Clientes', 'desc' => 'Gestionar clientes'],
    ],
    'Inventario y Compras' => [
        'almacen' => ['icon' => 'bi-box-seam', 'label' => 'Almacén', 'desc' => 'Gestionar productos y stock'],
        'compras' => ['icon' => 'bi-bag', 'label' => 'Compras', 'desc' => 'Crear y gestionar compras'],
        'proveedores' => ['icon' => 'bi-truck', 'label' => 'Proveedores', 'desc' => 'Gestionar proveedores'],
        'categorias' => ['icon' => 'bi-tags', 'label' => 'Categorías', 'desc' => 'Gestionar categorías de productos'],
    ],
    'Administración' => [
        'usuarios' => ['icon' => 'bi-person-gear', 'label' => 'Usuarios', 'desc' => 'Gestionar usuarios'],
        'configuracion' => ['icon' => 'bi-gear', 'label' => 'Configuración', 'desc' => 'Configurar sistema'],
        'roles' => ['icon' => 'bi-shield', 'label' => 'Roles', 'desc' => 'Gestionar roles y permisos'],
        'reportes' => ['icon' => 'bi-graph-up', 'label' => 'Reportes', 'desc' => 'Ver reportes y estadísticas'],
    ],
    'General' => [
        'dashboard' => ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'desc' => 'Ver panel principal'],
        '*' => ['icon' => 'bi-key-fill', 'label' => 'Acceso Total', 'desc' => 'Permisos de Super Admin'],
    ],
];

// Calcular estadísticas
$totalPermisos = array_sum(array_map('count', $permisosPorCategoria));
$permisosActivos = count($permisosRol);
$tieneAccesoTotal = in_array('*', $permisosRol);

ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="usuarios-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(30, 58, 138, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); border: 3px solid rgba(255,255,255,0.3);">
                            <?php if ($usuario->avatar): ?>
                            <img src="<?php echo SITE_URL . '/uploads/' . $usuario->avatar; ?>" class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover;">
                            <?php else: ?>
                            <i class="bi bi-person-circle fs-1"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="mb-0 fw-bold">Permisos del Usuario</h2>
                            <p class="mb-0 opacity-75 fs-5"><?php echo htmlspecialchars($usuario->getNombreCompleto()); ?></p>
                            <p class="mb-0 opacity-75">
                                <span class="badge bg-light text-dark rounded-pill px-3 py-2 mt-2">
                                    <i class="bi bi-shield me-1"></i><?php echo htmlspecialchars($rol->nombre ?? 'Sin rol'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="editar.php?id=<?php echo $usuario->id; ?>" class="btn btn-outline-light btn-lg rounded-pill">
                            <i class="bi bi-pencil me-2"></i>Editar Usuario
                        </a>
                        <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #1e3a8a;">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-1 text-primary"><?php echo $permisosActivos; ?></h3>
                    <small class="text-muted">Permisos Activos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-1 text-success"><?php echo $totalPermisos; ?></h3>
                    <small class="text-muted">Total Disponibles</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #faf5ff, #f3e8ff);">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-1" style="color: #7c3aed;">
                        <?php echo $tieneAccesoTotal ? 'SÍ' : round(($permisosActivos / $totalPermisos) * 100) . '%'; ?>
                    </h3>
                    <small class="text-muted">Cobertura</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: <?php echo $tieneAccesoTotal ? 'linear-gradient(135deg, #fef3c7, #fde68a)' : 'linear-gradient(135deg, #fef2f2, #fee2e2)'; ?>;">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-1 <?php echo $tieneAccesoTotal ? 'text-warning' : 'text-danger'; ?>">
                        <i class="bi <?php echo $tieneAccesoTotal ? 'bi-key-fill' : 'bi-lock-fill'; ?>"></i>
                    </h3>
                    <small class="text-muted"><?php echo $tieneAccesoTotal ? 'Acceso Total' : 'Acceso Limitado'; ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta Super Admin -->
    <?php if ($tieneAccesoTotal): ?>
    <div class="alert alert-warning border-0 rounded-3 mb-4" style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-3"></i>
            <div>
                <h6 class="fw-bold mb-1 text-dark">Acceso Total (Super Admin)</h6>
                <p class="mb-0 text-dark">Este usuario tiene permisos de acceso total al sistema. Puede realizar cualquier acción en todos los módulos.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Permisos por Categoría -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="bi bi-shield-check me-2"></i>Permisos del Rol: <?php echo htmlspecialchars($rol->nombre ?? 'Sin Rol'); ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <?php foreach ($permisosPorCategoria as $categoria => $permisos): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #fafafa;">
                        <div class="card-header bg-white border-bottom-0 py-3" style="border-radius: 16px 16px 0 0;">
                            <h6 class="fw-bold mb-0 text-primary">
                                <i class="bi bi-folder me-2"></i><?php echo $categoria; ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($permisos as $key => $permiso): 
                                    $tienePermiso = $tieneAccesoTotal || in_array($key, $permisosRol);
                                ?>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card border-0 h-100" style="border-radius: 12px; background: <?php echo $tienePermiso ? 'linear-gradient(135deg, #ecfdf5, #d1fae5)' : 'linear-gradient(135deg, #f3f4f6, #e5e7eb)'; ?>;">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" 
                                                     style="width: 45px; height: 45px; background: <?php echo $tienePermiso ? 'linear-gradient(135deg, #22c55e, #4ade80)' : 'linear-gradient(135deg, #9ca3af, #d1d5db)'; ?>;">
                                                    <i class="bi <?php echo $permiso['icon']; ?> text-white fs-5"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <h6 class="fw-bold mb-0 <?php echo $tienePermiso ? 'text-success' : 'text-muted'; ?>"><?php echo $permiso['label']; ?></h6>
                                                        <?php if ($tienePermiso): ?>
                                                        <i class="bi bi-check-circle-fill text-success ms-2"></i>
                                                        <?php else: ?>
                                                        <i class="bi bi-x-circle-fill text-muted ms-2"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted" style="font-size: 0.8rem;"><?php echo $permiso['desc']; ?></small>
                                                    <div class="mt-2">
                                                        <span class="badge <?php echo $tienePermiso ? 'bg-success' : 'bg-secondary'; ?> rounded-pill" style="font-size: 0.7rem;">
                                                            <?php echo $tienePermiso ? 'ACTIVO' : 'INACTIVO'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer border-top-0 py-4" style="background: #f8f9fa;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Los permisos se asignan mediante roles. Para modificar los permisos de este usuario, debe editar el rol <strong><?php echo htmlspecialchars($rol->nombre ?? ''); ?></strong>.
                </div>
                <a href="../roles/permisos.php?id=<?php echo $rol->id; ?>" class="btn btn-primary rounded-pill">
                    <i class="bi bi-shield-lock me-2"></i>Editar Permisos del Rol
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
    }
</style>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Permisos: ' . $usuario->getNombreCompleto(), $content);
