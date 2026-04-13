<?php
    /**
 * Listado de Clientes
 */
    require_once dirname(__DIR__, 2) . '/config/config.php';
    SessionManager::requirePermission('clientes');

    $repo = new ClienteRepository();

    $page     = intval($_GET['page'] ?? 1);
    $filters  = ['estado' => 1];
    $clientes = $repo->paginate($page, ITEMS_PER_PAGE, $filters, 'nombre ASC');

    ob_start();
?>

<!-- Header Moderno Azul/Cian -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="clientes-header" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); border-radius: 12px; padding: 12px; color: white; box-shadow: 0 10px 40px rgba(14, 165, 233, 0.3);">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.1rem; margin-bottom: 5px; font-weight: bold;"><i class="bi bi-people-fill me-2"></i>Clientes</h2>
                        <p style="font-size: 0.75rem; margin: 0; opacity: 0.9;">Administra clientes</p>
                    </div>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; justify-content: center;">
                        <a href="nuevo.php" class="btn btn-light rounded-pill" style="color: #0ea5e9; font-size: 0.75rem; padding: 5px 10px;">
                            <i class="bi bi-plus-circle me-1"></i>Nuevo
                        </a>
                        <button onclick="exportarExcel('tablaClientes', 'clientes')" class="btn btn-light rounded-pill" style="color: #0ea5e9; font-size: 0.75rem; padding: 5px 10px;">
                            <i class="bi bi-file-excel me-1"></i>Excel
                        </button>
                        <button onclick="window.print()" class="btn btn-light rounded-pill" style="color: #0ea5e9; font-size: 0.75rem; padding: 5px 10px;">
                            <i class="bi bi-printer me-1"></i>PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #f0f9ff, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #0ea5e9, #06b6d4);">
                        <i class="bi bi-people text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Total</div>
                        <div class="fw-bold" style="font-size: 1rem; line-height: 1.2;"><?php echo $clientes['total']; ?></div>
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
                            $activos      = array_filter($clientes['items'], fn($c) => $c->estado);
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
                        <i class="bi bi-credit-card text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Con Crédito</div>
                        <?php
                            $conCredito      = array_filter($clientes['items'], fn($c) => $c->limite_credito > 0);
                            $totalConCredito = count($conCredito);
                        ?>
                        <div class="fw-bold" style="font-size: 1rem; color: #f59e0b; line-height: 1.2;"><?php echo $totalConCredito; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: linear-gradient(135deg, #fee2e2, #ffffff);">
                <div class="card-body d-flex align-items-center p-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #ef4444, #f87171);">
                        <i class="bi bi-wallet2 text-white" style="font-size: 0.9rem;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted" style="font-size: 0.65rem;">Con Saldo</div>
                        <?php
                            $conSaldo      = array_filter($clientes['items'], fn($c) => $c->saldo_pendiente > 0);
                            $totalConSaldo = count($conSaldo);
                            $saldoTotal    = array_sum(array_column($conSaldo, 'saldo_pendiente'));
                        ?>
                        <div class="fw-bold text-danger" style="font-size: 1rem; line-height: 1.2;"><?php echo $totalConSaldo; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Moderna -->
    <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
            <h5 class="card-title mb-0 text-white fw-bold"><i class="bi bi-list-check me-2"></i>Listado de Clientes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaClientes" style="background: #ffffff;">
                    <thead style="background: linear-gradient(135deg, #f0f9ff, #cffafe);">
                        <tr>
                            <th class="fw-bold text-dark" style="border: none;">Documento</th>
                            <th class="fw-bold text-dark" style="border: none;">Cliente</th>
                            <th class="fw-bold text-dark" style="border: none;">Contacto</th>
                            <th class="fw-bold text-dark text-end" style="border: none;">Límite Crédito</th>
                            <th class="fw-bold text-dark text-end" style="border: none;">Saldo</th>
                            <th class="fw-bold text-dark text-center" style="border: none;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes['items'] as $cliente): ?>
                        <?php $tieneSaldo = $cliente->saldo_pendiente > 0; ?>
                        <tr style="transition: all 0.2s;" class="<?php echo $tieneSaldo ? 'con-saldo' : ''; ?>">
                            <td class="align-middle">
                                <span class="badge bg-light text-dark border" style="font-family: monospace; font-size: 0.9rem;">
                                    <i class="bi bi-card-text me-1 text-primary"></i><?php echo $cliente->documento; ?>
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; background: linear-gradient(135deg, #0ea5e9, #06b6d4);">
                                        <i class="bi bi-person text-white" style="font-size: 1.2rem;"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark fs-5"><?php echo $cliente->getNombreCompleto(); ?></span>
                                        <?php if ($cliente->razon_social): ?>
                                        <br><small class="text-muted"><i class="bi bi-building me-1"></i><?php echo $cliente->razon_social; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex flex-column">
                                    <?php if ($cliente->telefono): ?>
                                    <span class="badge bg-light text-dark rounded-pill border mb-1" style="width: fit-content;">
                                        <i class="bi bi-telephone text-success me-1"></i><?php echo $cliente->telefono; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($cliente->email): ?>
                                    <small class="text-muted"><i class="bi bi-envelope text-secondary me-1"></i><?php echo $cliente->email; ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="align-middle text-end">
                                <?php if ($cliente->limite_credito > 0): ?>
                                <span class="fw-bold text-info"><?php echo formatCurrency($cliente->limite_credito); ?></span>
                                <?php else: ?>
                                <span class="text-muted"><em>Contado</em></span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-end">
                                <?php
                                    $saldoStyles = $tieneSaldo
                                        ? ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-exclamation-circle']
                                        : ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-check-circle'];
                                ?>
                                <span class="badge" style="background: <?php echo $saldoStyles['bg']; ?>; color: <?php echo $saldoStyles['color']; ?>; padding: 8px 12px; border-radius: 20px;">
                                    <i class="bi <?php echo $saldoStyles['icon']; ?> me-1"></i><?php echo formatCurrency($cliente->saldo_pendiente); ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-primary btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Editar cliente" onclick="editarCliente(<?php echo $cliente->id; ?>)" data-cliente='<?php echo json_encode($cliente); ?>'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm rounded-circle" style="width: 35px; height: 35px; padding: 0;" title="Ver historial" onclick="verHistorial(<?php echo $cliente->id; ?>)" data-cliente='<?php echo json_encode($cliente); ?>'>
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                    <a href="eliminar.php?id=<?php echo $cliente->id; ?>" class="btn btn-danger btn-sm rounded-circle btn-eliminar" style="width: 35px; height: 35px; padding: 0;" title="Eliminar cliente">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clientes['items'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #f0f9ff, #cffafe);">
                                        <i class="bi bi-inbox fs-1 text-primary"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No se encontraron clientes</h5>
                                    <p class="text-muted mb-3">Registra tu primer cliente para empezar</p>
                                    <a href="nuevo.php" class="btn btn-primary rounded-pill px-4 text-white" style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); border: none;">
                                        <i class="bi bi-plus-circle me-2"></i>Crear Cliente
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
        <?php if ($clientes['total_pages'] > 1): ?>
        <div class="card-footer border-0" style="background: #f8f9fa;">
            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $clientes['page'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $clientes['page'] - 1; ?>" style="border: none;">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $clientes['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $clientes['page'] ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill me-1" href="?page=<?php echo $i; ?>"
                           style="border: none; <?php echo $i === $clientes['page'] ? 'background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $clientes['page'] >= $clientes['total_pages'] ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill" href="?page=<?php echo $clientes['page'] + 1; ?>" style="border: none;">
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
    #tablaClientes tbody tr:hover {
        background: linear-gradient(135deg, #f0f9ff, #ffffff);
        transform: scale(1.002);
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.1);
    }
    #tablaClientes tbody tr.con-saldo {
        background: linear-gradient(135deg, #fef2f2, #ffffff);
    }
    #tablaClientes tbody tr.con-saldo:hover {
        background: linear-gradient(135deg, #fee2e2, #ffffff);
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .clientes-header {
            padding: 15px !important;
        }
        .clientes-header h2 {
            font-size: 1.2rem;
        }
        .clientes-header p {
            font-size: 0.85rem;
        }
        .clientes-header .btn-lg {
            font-size: 0.85rem;
            padding: 8px 15px !important;
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
        #tablaClientes {
            font-size: 0.8rem;
            min-width: 700px;
        }
        #tablaClientes th,
        #tablaClientes td {
            padding: 6px 4px;
            white-space: nowrap;
        }
        .pagination .page-link {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 576px) {
        .clientes-header .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        .clientes-header .btn-lg {
            width: 100%;
            justify-content: center;
        }
        #tablaClientes td .badge {
            font-size: 0.65rem;
            padding: 3px 6px;
        }
        .btn-group .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
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
    background: linear-gradient(135deg, #0ea5e9, #06b6d4);
    color: white;
    border-color: #0ea5e9;
    box-shadow: 0 4px 15px rgba(14, 165, 233, 0.4);
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
    color: #0ea5e9;
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
.wizard-cliente-card {
    background: linear-gradient(135deg, #f0f9ff, #ffffff);
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #cffafe;
}
.wizard-cliente-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0ea5e9, #06b6d4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}
.historial-timeline {
    position: relative;
    padding-left: 30px;
}
.historial-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}
.historial-item {
    position: relative;
    margin-bottom: 20px;
}
.historial-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #0ea5e9;
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

<!-- Modal EDITAR Cliente - Wizard -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Editar Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="editar_guardar.php" method="POST">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body p-4">
                    <!-- Stepper -->
                    <div class="wizard-stepper">
                        <div class="wizard-step active" data-step="1" onclick="goToStepEdit(1)">
                            <div class="step-circle"><i class="bi bi-person"></i></div>
                            <small>Datos</small>
                        </div>
                        <div class="wizard-step" data-step="2" onclick="goToStepEdit(2)">
                            <div class="step-circle"><i class="bi bi-telephone"></i></div>
                            <small>Contacto</small>
                        </div>
                        <div class="wizard-step" data-step="3" onclick="goToStepEdit(3)">
                            <div class="step-circle"><i class="bi bi-credit-card"></i></div>
                            <small>Crédito</small>
                        </div>
                        <div class="wizard-step" data-step="4" onclick="goToStepEdit(4)">
                            <div class="step-circle"><i class="bi bi-check-lg"></i></div>
                            <small>Confirmar</small>
                        </div>
                    </div>

                    <!-- Step 1: Datos Personales -->
                    <div class="wizard-content active" id="edit-step-1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo Documento <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo_documento" id="edit-tipo-documento" required>
                                    <option value="CC">Cédula</option>
                                    <option value="NIT">NIT</option>
                                    <option value="CE">Cédula Extranjería</option>
                                    <option value="PP">Pasaporte</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Documento <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="documento" id="edit-documento" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" id="edit-nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido</label>
                                <input type="text" class="form-control" name="apellido" id="edit-apellido">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Razón Social</label>
                                <input type="text" class="form-control" name="razon_social" id="edit-razon-social" placeholder="Para empresas">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Contacto -->
                    <div class="wizard-content" id="edit-step-2">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="edit-telefono">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit-email">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <textarea class="form-control" name="direccion" id="edit-direccion" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control" name="ciudad" id="edit-ciudad">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha Nacimiento</label>
                                <input type="date" class="form-control" name="fecha_nacimiento" id="edit-fecha-nacimiento">
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Crédito -->
                    <div class="wizard-content" id="edit-step-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Límite de Crédito</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="limite_credito" id="edit-limite-credito" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Saldo Pendiente</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="edit-saldo-pendiente" readonly style="background: #f8f9fa;" value="0">
                                </div>
                                <small class="text-muted">Se actualiza automáticamente</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" id="edit-observaciones" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="estado" id="edit-estado" value="1">
                                    <label class="form-check-label" for="edit-estado">Cliente Activo</label>
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
                            <p class="text-muted">Revisa que la información del cliente sea correcta.</p>
                        </div>
                        <div class="wizard-cliente-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="wizard-cliente-avatar"><i class="bi bi-person"></i></div>
                                <div>
                                    <h6 class="mb-1 fw-bold" id="resumen-nombre">-</h6>
                                    <span class="badge bg-light text-dark" id="resumen-documento">-</span>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-sm-4"><small class="text-muted">Teléfono:</small> <span id="resumen-telefono" class="fw-bold">-</span></div>
                                <div class="col-sm-4"><small class="text-muted">Email:</small> <span id="resumen-email" class="fw-bold">-</span></div>
                                <div class="col-sm-4"><small class="text-muted">Límite Crédito:</small> <span id="resumen-credito" class="fw-bold">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="btn btn-light" id="edit-btn-anterior" onclick="navEdit(-1)" style="border-radius: 20px;">
                            <i class="bi bi-arrow-left me-2"></i>Anterior
                        </button>
                        <button type="button" class="btn btn-primary" id="edit-btn-siguiente" onclick="navEdit(1)" style="border-radius: 20px; background: linear-gradient(135deg, #0ea5e9, #06b6d4); border: none;">
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

<!-- Modal HISTORIAL Cliente - Wizard -->
<div class="modal fade" id="modalHistorialCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: white; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Historial del Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Stepper -->
                <div class="wizard-stepper">
                    <div class="wizard-step active" data-step="1" onclick="goToStepHist(1)">
                        <div class="step-circle"><i class="bi bi-person-vcard"></i></div>
                        <small>Resumen</small>
                    </div>
                    <div class="wizard-step" data-step="2" onclick="goToStepHist(2)">
                        <div class="step-circle"><i class="bi bi-cart"></i></div>
                        <small>Compras</small>
                    </div>
                    <div class="wizard-step" data-step="3" onclick="goToStepHist(3)">
                        <div class="step-circle"><i class="bi bi-cash-stack"></i></div>
                        <small>Pagos</small>
                    </div>
                </div>

                <!-- Step 1: Resumen -->
                <div class="wizard-content active" id="hist-step-1">
                    <div class="wizard-cliente-card mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="wizard-cliente-avatar" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);"><i class="bi bi-person"></i></div>
                            <div>
                                <h5 class="mb-1 fw-bold" id="hist-nombre">-</h5>
                                <span class="badge bg-light text-dark" id="hist-documento">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border-0" style="background: linear-gradient(135deg, #f0f9ff, #ffffff); border-radius: 12px;">
                                <div class="card-body text-center">
                                    <i class="bi bi-cart-check text-primary fs-2 mb-2"></i>
                                    <h6 class="text-muted">Total Compras</h6>
                                    <h4 class="fw-bold text-primary mb-0" id="hist-total-compras">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0" style="background: linear-gradient(135deg, #fef3c7, #ffffff); border-radius: 12px;">
                                <div class="card-body text-center">
                                    <i class="bi bi-wallet2 text-warning fs-2 mb-2"></i>
                                    <h6 class="text-muted">Saldo Pendiente</h6>
                                    <h4 class="fw-bold text-warning mb-0" id="hist-saldo">$0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0" style="background: linear-gradient(135deg, #dcfce7, #ffffff); border-radius: 12px;">
                                <div class="card-body text-center">
                                    <i class="bi bi-credit-card text-success fs-2 mb-2"></i>
                                    <h6 class="text-muted">Crédito Disponible</h6>
                                    <h4 class="fw-bold text-success mb-0" id="hist-credito-disp">$0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Información de Contacto</h6>
                        <div class="row g-2">
                            <div class="col-sm-6"><small class="text-muted">Teléfono:</small> <span id="hist-telefono" class="fw-bold">-</span></div>
                            <div class="col-sm-6"><small class="text-muted">Email:</small> <span id="hist-email" class="fw-bold">-</span></div>
                            <div class="col-sm-6"><small class="text-muted">Dirección:</small> <span id="hist-direccion" class="fw-bold">-</span></div>
                            <div class="col-sm-6"><small class="text-muted">Ciudad:</small> <span id="hist-ciudad" class="fw-bold">-</span></div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Compras -->
                <div class="wizard-content" id="hist-step-2">
                    <div class="text-center py-5">
                        <i class="bi bi-cart text-muted fs-1 mb-3"></i>
                        <p class="text-muted">Historial de compras disponible en el módulo de Ventas</p>
                        <a href="<?php echo SITE_URL; ?>/views/ventas/index.php" class="btn btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa); border: none;">
                            <i class="bi bi-cart me-2"></i>Ver Ventas
                        </a>
                    </div>
                </div>

                <!-- Step 3: Pagos -->
                <div class="wizard-content" id="hist-step-3">
                    <div class="text-center py-5">
                        <i class="bi bi-cash-stack text-muted fs-1 mb-3"></i>
                        <p class="text-muted">Historial de pagos disponible en el módulo de Cuentas por Cobrar</p>
                        <a href="<?php echo SITE_URL; ?>/views/cuentasxcobrar/index.php" class="btn btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa); border: none;">
                            <i class="bi bi-cash-stack me-2"></i>Ver Cuentas por Cobrar
                        </a>
                    </div>
                </div>

                <div class="wizard-nav">
                    <button type="button" class="btn btn-light" id="hist-btn-anterior" onclick="navHist(-1)" style="border-radius: 20px;">
                        <i class="bi bi-arrow-left me-2"></i>Anterior
                    </button>
                    <button type="button" class="btn btn-primary" id="hist-btn-siguiente" onclick="navHist(1)" style="border-radius: 20px; background: linear-gradient(135deg, #8b5cf6, #a78bfa); border: none;">
                        Siguiente<i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentStepEdit = 1;
let currentStepHist = 1;
const totalStepsEdit = 4;
const totalStepsHist = 3;

// Función EDITAR Cliente
function editarCliente(id) {
    const btn = document.querySelector(`button[onclick="editarCliente(${id})"]`);
    const clienteData = btn.getAttribute('data-cliente');
    const c = JSON.parse(clienteData);

    // Llenar formulario
    document.getElementById('edit-id').value = c.id;
    document.getElementById('edit-tipo-documento').value = c.tipo_documento || 'CC';
    document.getElementById('edit-documento').value = c.documento || '';
    document.getElementById('edit-nombre').value = c.nombre || '';
    document.getElementById('edit-apellido').value = c.apellido || '';
    document.getElementById('edit-razon-social').value = c.razon_social || '';
    document.getElementById('edit-telefono').value = c.telefono || '';
    document.getElementById('edit-email').value = c.email || '';
    document.getElementById('edit-direccion').value = c.direccion || '';
    document.getElementById('edit-ciudad').value = c.ciudad || '';
    document.getElementById('edit-fecha-nacimiento').value = c.fecha_nacimiento || '';
    document.getElementById('edit-limite-credito').value = c.limite_credito || 0;
    document.getElementById('edit-saldo-pendiente').value = c.saldo_pendiente || 0;
    document.getElementById('edit-observaciones').value = c.observaciones || '';
    document.getElementById('edit-estado').checked = c.estado == 1;

    // Reset stepper
    currentStepEdit = 1;
    updateStepperEdit();

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditarCliente'));
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
    document.querySelectorAll('#modalEditarCliente .wizard-step').forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');
        if (stepNum === currentStepEdit) {
            step.classList.add('active');
        } else if (stepNum < currentStepEdit) {
            step.classList.add('completed');
        }
    });

    document.querySelectorAll('#modalEditarCliente .wizard-content').forEach((content, index) => {
        content.classList.remove('active');
        if (index + 1 === currentStepEdit) {
            content.classList.add('active');
        }
    });

    document.getElementById('edit-btn-anterior').disabled = currentStepEdit === 1;

    // Actualizar resumen en paso 4
    if (currentStepEdit === 4) {
        document.getElementById('resumen-nombre').textContent = document.getElementById('edit-nombre').value + ' ' + document.getElementById('edit-apellido').value;
        document.getElementById('resumen-documento').textContent = document.getElementById('edit-documento').value;
        document.getElementById('resumen-telefono').textContent = document.getElementById('edit-telefono').value || 'N/A';
        document.getElementById('resumen-email').textContent = document.getElementById('edit-email').value || 'N/A';
        document.getElementById('resumen-credito').textContent = '$' + parseFloat(document.getElementById('edit-limite-credito').value).toLocaleString('es-CO');

        document.getElementById('edit-btn-siguiente').classList.add('d-none');
        document.getElementById('edit-btn-guardar').classList.remove('d-none');
    } else {
        document.getElementById('edit-btn-siguiente').classList.remove('d-none');
        document.getElementById('edit-btn-guardar').classList.add('d-none');
    }
}

// Función VER HISTORIAL
function verHistorial(id) {
    const btn = document.querySelector(`button[onclick="verHistorial(${id})"]`);
    const clienteData = btn.getAttribute('data-cliente');
    const c = JSON.parse(clienteData);

    // Llenar datos
    document.getElementById('hist-nombre').textContent = c.nombre + ' ' + (c.apellido || '');
    document.getElementById('hist-documento').textContent = c.documento;
    document.getElementById('hist-telefono').textContent = c.telefono || 'N/A';
    document.getElementById('hist-email').textContent = c.email || 'N/A';
    document.getElementById('hist-direccion').textContent = c.direccion || 'N/A';
    document.getElementById('hist-ciudad').textContent = c.ciudad || 'N/A';
    document.getElementById('hist-saldo').textContent = '$' + parseFloat(c.saldo_pendiente || 0).toLocaleString('es-CO');
    document.getElementById('hist-credito-disp').textContent = '$' + parseFloat(c.limite_credito - c.saldo_pendiente || 0).toLocaleString('es-CO');

    // Reset stepper
    currentStepHist = 1;
    updateStepperHist();

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalHistorialCliente'));
    modal.show();
}

function goToStepHist(step) {
    currentStepHist = step;
    updateStepperHist();
}

function navHist(direction) {
    const newStep = currentStepHist + direction;
    if (newStep >= 1 && newStep <= totalStepsHist) {
        currentStepHist = newStep;
        updateStepperHist();
    }
}

function updateStepperHist() {
    document.querySelectorAll('#modalHistorialCliente .wizard-step').forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');
        if (stepNum === currentStepHist) {
            step.classList.add('active');
        } else if (stepNum < currentStepHist) {
            step.classList.add('completed');
        }
    });

    document.querySelectorAll('#modalHistorialCliente .wizard-content').forEach((content, index) => {
        content.classList.remove('active');
        if (index + 1 === currentStepHist) {
            content.classList.add('active');
        }
    });

    document.getElementById('hist-btn-anterior').disabled = currentStepHist === 1;
    document.getElementById('hist-btn-siguiente').innerHTML = currentStepHist === totalStepsHist
        ? 'Cerrar<i class="bi bi-x-lg ms-2"></i>'
        : 'Siguiente<i class="bi bi-arrow-right ms-2"></i>';
    if (currentStepHist === totalStepsHist) {
        document.getElementById('hist-btn-siguiente').setAttribute('onclick', 'bootstrap.Modal.getInstance(document.getElementById(\'modalHistorialCliente\')).hide()');
    } else {
        document.getElementById('hist-btn-siguiente').setAttribute('onclick', 'navHist(1)');
    }
}
</script>

<?php
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Clientes', $content);
