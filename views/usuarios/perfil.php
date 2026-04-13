<?php
/**
 * Perfil de Usuario
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requireAuth();

$usuarioRepo = new UsuarioRepository();
$rolRepo = new RolRepository();

$usuarioId = SessionManager::getUserId();
$usuario = $usuarioRepo->findById($usuarioId);

if (!$usuario) {
    header('Location: ../auth/login.php?error=Usuario no encontrado');
    exit;
}

$rol = $rolRepo->findById($usuario->rol_id);
$permisos = $rol ? json_decode($rol->permisos ?? '[]', true) : [];

$error = '';
$success = '';

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'perfil') {
    try {
        $usuario->nombre = $_POST['nombre'] ?? '';
        $usuario->apellido = $_POST['apellido'] ?? '';
        $usuario->email = $_POST['email'] ?? '';
        $usuario->telefono = $_POST['telefono'] ?? null;
        $usuario->direccion = $_POST['direccion'] ?? null;
        
        if ($usuarioRepo->save($usuario)) {
            // Actualizar datos en sesión
            $userData = $usuario->toArray();
            $userData['rol_nombre'] = $rol->nombre ?? '';
            $userData['permisos'] = $rol->permisos ?? '[]';
            SessionManager::login($userData);
            
            $success = 'Perfil actualizado correctamente';
            $usuario = $usuarioRepo->findById($usuarioId); // Recargar
        } else {
            $error = 'Error al actualizar el perfil';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'password') {
    try {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Verificar contraseña actual
        $usuarioCheck = $usuarioRepo->authenticate($usuario->email, $currentPassword);
        if (!$usuarioCheck) {
            throw new Exception('La contraseña actual es incorrecta');
        }
        
        if ($newPassword !== $confirmPassword) {
            throw new Exception('Las contraseñas nuevas no coinciden');
        }
        
        if (strlen($newPassword) < 6) {
            throw new Exception('La nueva contraseña debe tener al menos 6 caracteres');
        }
        
        $usuario->password = $newPassword;
        
        if ($usuarioRepo->save($usuario)) {
            $success = 'Contraseña actualizada correctamente';
        } else {
            $error = 'Error al cambiar la contraseña';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Procesar cambio de avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'avatar') {
    try {
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $avatarName = 'avatar_' . $usuarioId . '_' . time() . '.png';
            $uploadPath = rtrim(UPLOADS_PATH, '/') . '/' . $avatarName;
            
            // Crear carpeta uploads si no existe
            if (!is_dir(UPLOADS_PATH)) {
                mkdir(UPLOADS_PATH, 0755, true);
            }
            
            move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath);
            
            // Eliminar avatar anterior si existe
            if ($usuario->avatar && file_exists(UPLOADS_PATH . '/' . $usuario->avatar)) {
                unlink(UPLOADS_PATH . '/' . $usuario->avatar);
            }
            
            $usuario->avatar = $avatarName;
            
            if ($usuarioRepo->save($usuario)) {
                $success = 'Avatar actualizado correctamente';
                $usuario = $usuarioRepo->findById($usuarioId); // Recargar
            }
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
            <div class="perfil-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 20px; padding: 30px; color: white; box-shadow: 0 10px 40px rgba(30, 58, 138, 0.3);">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0 fw-bold"><i class="bi bi-person-circle me-3"></i>Mi Perfil</h2>
                        <p class="mb-0 opacity-75 mt-2">Gestiona tu información personal y seguridad</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-light text-dark rounded-pill px-4 py-2 fs-6">
                            <i class="bi bi-shield me-2"></i><?php echo htmlspecialchars($rol->nombre ?? 'Sin Rol'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-pill" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-pill" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Avatar y Rol -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg mb-4" style="border-radius: 20px; overflow: hidden;">
                <div class="card-body text-center p-4" style="background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                    <div class="position-relative d-inline-block mb-3">
                        <div class="rounded-circle overflow-hidden" style="width: 150px; height: 150px; border: 5px solid white; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                            <?php if ($usuario->avatar): ?>
                            <img src="<?php echo SITE_URL . '/uploads/' . $usuario->avatar; ?>" class="w-100 h-100" style="object-fit: cover;">
                            <?php else: ?>
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                <i class="bi bi-person-circle text-secondary" style="font-size: 80px;"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm rounded-circle position-absolute" style="bottom: 5px; right: 5px; width: 40px; height: 40px;" data-bs-toggle="modal" data-bs-target="#modalAvatar">
                            <i class="bi bi-camera"></i>
                        </button>
                    </div>
                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($usuario->getNombreCompleto()); ?></h4>
                    <p class="text-muted mb-2">@<?php echo htmlspecialchars($usuario->username); ?></p>
                    <span class="badge bg-primary rounded-pill px-3">
                        <i class="bi bi-shield me-1"></i><?php echo htmlspecialchars($rol->nombre ?? 'Sin Rol'); ?>
                    </span>
                </div>
            </div>

            <!-- Información de Sesión -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle me-2"></i>Información de Cuenta</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">ID de Usuario</small>
                        <strong>#<?php echo $usuario->id; ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Miembro desde</small>
                        <strong><?php echo $usuario->fecha_creacion ? date('d/m/Y', strtotime($usuario->fecha_creacion)) : 'N/A'; ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Último acceso</small>
                        <strong><?php echo $usuario->ultimo_acceso ? date('d/m/Y H:i', strtotime($usuario->ultimo_acceso)) : 'N/A'; ?></strong>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Estado</small>
                        <span class="badge bg-<?php echo $usuario->estado ? 'success' : 'danger'; ?> rounded-pill">
                            <?php echo $usuario->estado ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formularios -->
        <div class="col-lg-8">
            <!-- Datos Personales -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-person me-2"></i>Datos Personales</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="accion" value="perfil">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-person text-primary"></i></span>
                                    <input type="text" name="nombre" class="form-control border-0 shadow-none" 
                                           style="background: #f8f9fa;" value="<?php echo htmlspecialchars($usuario->nombre); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Apellido</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-person text-primary"></i></span>
                                    <input type="text" name="apellido" class="form-control border-0 shadow-none" 
                                           style="background: #f8f9fa;" value="<?php echo htmlspecialchars($usuario->apellido); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-envelope text-primary"></i></span>
                                    <input type="email" name="email" class="form-control border-0 shadow-none" 
                                           style="background: #f8f9fa;" value="<?php echo htmlspecialchars($usuario->email); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre de Usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-at text-primary"></i></span>
                                    <input type="text" class="form-control border-0 shadow-none bg-light" 
                                           value="<?php echo htmlspecialchars($usuario->username); ?>" readonly>
                                </div>
                                <small class="text-muted">El nombre de usuario no se puede cambiar</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Teléfono</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-telephone text-primary"></i></span>
                                    <input type="text" name="telefono" class="form-control border-0 shadow-none" 
                                           style="background: #f8f9fa;" value="<?php echo htmlspecialchars($usuario->telefono ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dirección</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-geo-alt text-primary"></i></span>
                                    <input type="text" name="direccion" class="form-control border-0 shadow-none" 
                                           style="background: #f8f9fa;" value="<?php echo htmlspecialchars($usuario->direccion ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-save me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cambiar Contraseña -->
            <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #fef2f2, #fee2e2);">
                    <h5 class="mb-0 fw-bold text-danger"><i class="bi bi-shield-lock me-2"></i>Cambiar Contraseña</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="accion" value="password">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Contraseña Actual</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-lock text-danger"></i></span>
                                    <input type="password" name="current_password" class="form-control border-0 shadow-none" 
                                           style="background: #f8f9fa;" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-lock-fill text-danger"></i></span>
                                    <input type="password" name="new_password" class="form-control border-0 shadow-none" 
                                           style="background: #f8f9fa;" required minlength="6">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Confirmar Nueva</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-0"><i class="bi bi-lock-fill text-danger"></i></span>
                                    <input type="password" name="confirm_password" class="form-control border-0 shadow-none" 
                                           style="background: #f8f9fa;" required minlength="6">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-danger rounded-pill px-4">
                                <i class="bi bi-key me-2"></i>Cambiar Contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cambiar Avatar -->
<div class="modal fade" id="modalAvatar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                <h5 class="modal-title fw-bold text-primary"><i class="bi bi-camera me-2"></i>Cambiar Avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="avatar">
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="rounded-circle overflow-hidden mx-auto mb-3" style="width: 120px; height: 120px; border: 3px dashed #3b82f6;">
                            <div id="avatarPreview" class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                <i class="bi bi-image text-secondary fs-1"></i>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Selecciona una imagen cuadrada para mejor resultado</p>
                    </div>
                    <div class="input-group">
                        <input type="file" name="avatar" class="form-control" accept="image/*" required id="avatarInput">
                    </div>
                    <small class="text-muted">Formatos: JPG, PNG. Tamaño máximo: 2MB</small>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill">
                        <i class="bi bi-check-circle me-2"></i>Guardar Avatar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .perfil-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    }
    .input-group:focus-within .input-group-text {
        background: #dbeafe !important;
    }
    .input-group:focus-within .form-control {
        background: #dbeafe !important;
        box-shadow: 0 0 0 0.25rem rgba(30, 58, 138, 0.25) !important;
    }
</style>

<script>
    // Vista previa del avatar
    document.getElementById('avatarInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').innerHTML = '<img src="' + e.target.result + '" class="w-100 h-100" style="object-fit: cover;">';
            }
            reader.readAsDataURL(file);
        }
    });
</script>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/layouts/main.php';
renderLayout('Mi Perfil', $content);
