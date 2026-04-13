<?php
    /**
 * Gestión de Permisos del Rol
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('usuarios');

    $repo = new RolRepository();

    // Lista de permisos disponibles organizados por categoría
    $permisosPorCategoria = [
    'Ventas y Comercio'    => [
        'ventas'         => ['icon' => 'bi-cart', 'label' => 'Ventas', 'desc' => 'Crear y gestionar ventas, POS'],
        'cotizaciones'   => ['icon' => 'bi-file-text', 'label' => 'Cotizaciones', 'desc' => 'Crear y enviar cotizaciones'],
        'cuentas_cobrar' => ['icon' => 'bi-cash-stack', 'label' => 'Cuentas por Cobrar', 'desc' => 'Gestionar créditos y pagos'],
        'clientes'       => ['icon' => 'bi-people', 'label' => 'Clientes', 'desc' => 'Gestionar clientes'],
    ],
    'Inventario y Compras' => [
        'almacen'     => ['icon' => 'bi-box-seam', 'label' => 'Almacén', 'desc' => 'Gestionar productos y stock'],
        'compras'     => ['icon' => 'bi-bag', 'label' => 'Compras', 'desc' => 'Crear y gestionar compras'],
        'proveedores' => ['icon' => 'bi-truck', 'label' => 'Proveedores', 'desc' => 'Gestionar proveedores'],
        'categorias'  => ['icon' => 'bi-tags', 'label' => 'Categorías', 'desc' => 'Gestionar categorías de productos'],
    ],
    'Gestión'              => [
        'caja'   => ['icon' => 'bi-cash-coin', 'label' => 'Caja', 'desc' => 'Apertura y cierre de caja'],
        'gastos' => ['icon' => 'bi-graph-down-arrow', 'label' => 'Gastos', 'desc' => 'Gestionar gastos y ganancias'],
    ],
    'Administración'       => [
        'usuarios'      => ['icon' => 'bi-person-gear', 'label' => 'Usuarios', 'desc' => 'Gestionar usuarios'],
        'configuracion' => ['icon' => 'bi-gear', 'label' => 'Configuración', 'desc' => 'Configurar sistema'],
        'roles'         => ['icon' => 'bi-shield', 'label' => 'Roles', 'desc' => 'Gestionar roles y permisos'],
        'reportes'      => ['icon' => 'bi-graph-up', 'label' => 'Reportes', 'desc' => 'Ver reportes y estadísticas'],
    ],
    'General'              => [
        'dashboard' => ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'desc' => 'Ver panel principal'],
        '*'         => ['icon' => 'bi-key-fill', 'label' => 'Acceso Total', 'desc' => 'Permisos de Super Admin'],
    ],
    ];

    // Obtener rol
    $id  = intval($_GET['id'] ?? 0);
    $rol = $repo->findById($id);

    if (! $rol) {
    header('Location: index.php?error=Rol no encontrado');
    exit;
    }

    $permisosActuales = $rol->getPermisosArray();

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rol->permisos = json_encode($_POST['permisos'] ?? []);

        if ($repo->save($rol)) {
            $success          = 'Permisos actualizados correctamente';
            $permisosActuales = $rol->getPermisosArray(); // Recargar
        } else {
            $error = 'Error al actualizar los permisos.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    }

    ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="roles-header" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 40px rgba(124, 58, 237, 0.3);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-3"></i>Gestionar Permisos</h2>
                        <p class="mb-0 opacity-75">Rol: <strong><?php echo htmlspecialchars($rol->nombre); ?></strong></p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="editar.php?id=<?php echo $rol->id; ?>" class="btn btn-outline-light btn-lg rounded-pill">
                            <i class="bi bi-pencil me-2"></i>Editar Rol
                        </a>
                        <a href="index.php" class="btn btn-light btn-lg rounded-pill" style="color: #7c3aed;">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-pill" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-pill" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Resumen de Permisos -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #faf5ff, #f3e8ff);">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-1" style="color: #7c3aed;" id="totalPermisos"><?php echo count($permisosActuales); ?></h3>
                    <small class="text-muted">Permisos Activos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-1 text-success" id="totalDisponibles"><?php echo array_sum(array_map('count', $permisosPorCategoria)); ?></h3>
                    <small class="text-muted">Permisos Disponibles</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                <div class="card-body text-center">
                    <?php
                        $totalPermisosDisponibles = array_sum(array_map('count', $permisosPorCategoria));
                        $porcentaje               = $totalPermisosDisponibles > 0 ? round((count($permisosActuales) / $totalPermisosDisponibles) * 100) : 0;
                    ?>
                    <h3 class="fw-bold mb-1 text-primary" id="porcentaje"><?php echo $porcentaje; ?>%</h3>
                    <small class="text-muted">Cobertura</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: linear-gradient(135deg, #fefce8, #fef9c3);">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-1 text-warning"><?php echo count($permisosPorCategoria); ?></h3>
                    <small class="text-muted">Categorías</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de Permisos -->
    <form method="POST" id="formPermisos">
        <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-check2-square me-2"></i>Seleccionar Permisos</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="seleccionarTodos(true)">
                            <i class="bi bi-check-all me-1"></i>Todos
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="seleccionarTodos(false)">
                            <i class="bi bi-x-lg me-1"></i>Ninguno
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-4">
                    <?php foreach ($permisosPorCategoria as $categoria => $permisos): ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #fafafa;">
                            <div class="card-header bg-white border-bottom-0 py-3" style="border-radius: 16px 16px 0 0;">
                                <h6 class="fw-bold mb-0" style="color: #7c3aed;">
                                    <i class="bi bi-folder me-2"></i><?php echo $categoria; ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <?php foreach ($permisos as $key => $permiso):
                                            $isChecked = in_array($key, $permisosActuales);
                                    ?>
                                    <div class="col-md-6 col-lg-3">
                                        <div class="permiso-card card border-0 shadow-sm h-100 <?php echo $isChecked ? 'selected' : ''; ?>"
                                             style="border-radius: 12px; cursor: pointer; transition: all 0.2s;"
                                             onclick="togglePermiso('<?php echo $key; ?>')"
                                             data-categoria="<?php echo $categoria; ?>">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-start">
                                                    <div class="form-check me-3">
                                                        <input class="form-check-input permiso-check" type="checkbox"
                                                               name="permisos[]" value="<?php echo $key; ?>"
                                                               id="perm_<?php echo $key; ?>"
                                                               style="width: 20px; height: 20px;"
                                                               <?php echo $isChecked ? 'checked' : ''; ?>>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i class="bi <?php echo $permiso['icon']; ?> me-2" style="color: #7c3aed;"></i>
                                                            <h6 class="fw-bold mb-0" style="color: #7c3aed;"><?php echo $permiso['label']; ?></h6>
                                                        </div>
                                                        <small class="text-muted" style="font-size: 0.8rem;"><?php echo $permiso['desc']; ?></small>
                                                        <div class="mt-2">
                                                            <span class="badge bg-light text-dark border" style="font-size: 0.7rem;"><?php echo $key; ?></span>
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
                    <div>
                        <span class="fw-bold text-primary"><i class="bi bi-check2-square me-2"></i>Seleccionados:</span>
                        <span class="badge bg-primary rounded-pill fs-6 ms-2" id="contadorPermisos"><?php echo count($permisosActuales); ?></span>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-5">
                        <i class="bi bi-save me-2"></i>Guardar Permisos
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .permiso-card {
        border: 2px solid transparent !important;
    }
    .permiso-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(124, 58, 237, 0.12) !important;
    }
    .permiso-card.selected {
        border-color: #7c3aed !important;
        background: linear-gradient(135deg, #faf5ff, #f3e8ff) !important;
    }
    .permiso-card.selected .permiso-check {
        background-color: #7c3aed;
        border-color: #7c3aed;
    }
    .permiso-check:checked {
        background-color: #7c3aed;
        border-color: #7c3aed;
    }
    .card-header {
        background: transparent;
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .roles-header {
            padding: 15px !important;
        }
        .roles-header h2 {
            font-size: 1.3rem;
        }
        .card-body.p-4 {
            padding: 1rem !important;
        }
        .permiso-card {
            padding: 0.75rem !important;
        }
        .card-footer .d-flex {
            flex-direction: column;
            gap: 10px;
        }
        .card-footer button {
            width: 100%;
        }
    }

    @media (max-width: 576px) {
        .roles-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 15px;
        }
        .permiso-card h6 {
            font-size: 0.9rem;
        }
    }
</style>

<script>
    const permisosPorCategoria = <?php echo json_encode($permisosPorCategoria); ?>;

    function togglePermiso(key) {
        const checkbox = document.getElementById('perm_' + key);
        const card = checkbox.closest('.permiso-card');

        checkbox.checked = !checkbox.checked;

        if (checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }

        actualizarContadores();
    }

    function seleccionarTodos(seleccionar) {
        document.querySelectorAll('.permiso-check').forEach(checkbox => {
            checkbox.checked = seleccionar;
            const card = checkbox.closest('.permiso-card');
            if (seleccionar) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
        actualizarContadores();
    }

    function actualizarContadores() {
        const checked = document.querySelectorAll('.permiso-check:checked');
        const total = document.querySelectorAll('.permiso-check').length;

        document.getElementById('contadorPermisos').textContent = checked.length;
        document.getElementById('totalPermisos').textContent = checked.length;
        document.getElementById('porcentaje').textContent = Math.round((checked.length / total) * 100) + '%';
    }

    // Event listeners
    document.querySelectorAll('.permiso-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.permiso-card');
            if (this.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            actualizarContadores();
        });
    });

    // Confirmación al guardar
    document.getElementById('formPermisos').addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.permiso-check:checked');
        if (checked.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Sin permisos seleccionados',
                text: '¿Desea guardar el rol sin ningún permiso?',
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        }
    });
</script>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Permisos: ' . $rol->nombre, $content);
