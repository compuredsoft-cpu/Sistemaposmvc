<?php
    /**
 * Gestión de Roles
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('roles');

    $repo  = new RolRepository();
    $roles = $repo->findAllWithUserCount();

    ob_start();
?>

<div class="container-fluid">
    <div class="mb-4">
        <div
            style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; margin-bottom: 15px;">
            <h1 style="font-size: 1.2rem; margin: 0; font-weight: bold;">
                <i class="bi bi-shield-lock me-2"></i>Roles y Permisos
            </h1>
            <a href="nuevo.php" class="btn btn-success" style="font-size: 0.85rem; padding: 6px 12px;">
                <i class="bi bi-plus-circle me-1"></i>Nuevo Rol
            </a>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Rol</th>
                            <th>Descripción</th>
                            <th>Usuarios</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $rol): ?>
                        <tr>
                            <td>
                                <strong><?php echo $rol->nombre; ?></strong>
                            </td>
                            <td><?php echo $rol->descripcion; ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $rol->total_usuarios ?? 0; ?> usuarios</span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $rol->estado ? 'success' : 'danger'; ?>">
                                    <?php echo $rol->estado ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="permisos.php?id=<?php echo $rol->id; ?>" class="btn btn-warning"
                                        title="Permisos">
                                        <i class="bi bi-shield"></i>
                                    </a>
                                    <a href="editar.php?id=<?php echo $rol->id; ?>" class="btn btn-success"
                                        title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if (! in_array($rol->nombre, ['Administrador', 'Admin'])): ?>
                                    <a href="eliminar.php?id=<?php echo $rol->id; ?>"
                                        class="btn btn-danger btn-eliminar" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($roles)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No se encontraron roles
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Lista de Permisos Disponibles -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-key me-2"></i>Permisos Disponibles en el Sistema
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php
                    $permisos = [
                        '*'              => 'Acceso Total (Super Admin)',
                        'dashboard'      => 'Dashboard',
                        'ventas'         => 'Ventas',
                        'almacen'        => 'Almacén/Productos',
                        'compras'        => 'Compras',
                        'proveedores'    => 'Proveedores',
                        'clientes'       => 'Clientes',
                        'caja'           => 'Caja',
                        'cuentas_cobrar' => 'Cuentas por Cobrar',
                        'gastos'         => 'Gastos',
                        'kardex'         => 'Kardex',
                        'cotizaciones'   => 'Cotizaciones',
                        'usuarios'       => 'Usuarios',
                        'roles'          => 'Roles',
                        'configuracion'  => 'Configuración',
                    ];
                    foreach ($permisos as $key => $desc):
                ?>
                <div class="col-md-3 mb-2">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary me-2"><?php echo $key; ?></span>
                        <small><?php echo $desc; ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Responsive styles */
@media (max-width: 768px) {
    .container-fluid h1.h3 {
        font-size: 1.3rem;
    }

    .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start !important;
    }

    .d-flex.justify-content-between .btn {
        width: 100%;
    }

    .card-header h5 {
        font-size: 1rem;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    table {
        font-size: 0.8rem;
        min-width: 600px;
    }

    table th,
    table td {
        padding: 6px 4px;
        white-space: nowrap;
    }

    .btn-group-sm .btn {
        padding: 4px 8px;
        font-size: 0.75rem;
    }

    .row>.col-md-3 {
        width: 50% !important;
    }
}

@media (max-width: 576px) {
    .container-fluid h1.h3 {
        font-size: 1.1rem;
    }
}
</style>

<?php
    $content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Roles', $content);