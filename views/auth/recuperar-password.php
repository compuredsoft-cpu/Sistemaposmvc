<?php
/**
 * Vista de Recuperar Contraseña
 */
require_once dirname(__DIR__, 2) . '/config/config.php';

if (SessionManager::isLoggedIn()) {
    redirect(SITE_URL . '/views/dashboard/index.php');
}

$step = $_GET['step'] ?? 'request';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repo = new UsuarioRepository();
    
    if ($step === 'request') {
        $email = $_POST['email'] ?? '';
        $user = $repo->findByEmail($email);
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $repo->setTokenRecuperacion($user->id, $token, $expira);
            
            // Aquí se enviaría el email con el enlace
            // mail($email, 'Recuperación de contraseña', "Enlace: .../reset-password.php?token=$token");
            
            $message = 'Se ha enviado un enlace de recuperación a su email. Token: ' . $token;
        } else {
            $error = 'No se encontró un usuario con ese email';
        }
    } elseif ($step === 'reset' && !empty($_POST['token'])) {
        $token = $_POST['token'];
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if ($password !== $confirm) {
            $error = 'Las contraseñas no coinciden';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres';
        } else {
            $user = $repo->findByToken($token);
            if ($user) {
                $repo->updatePassword($user->id, $password);
                $message = 'Contraseña actualizada correctamente. Redirigiendo...';
                header("Refresh: 2; URL=login.php");
            } else {
                $error = 'Token inválido o expirado';
            }
        }
    }
}

// Verificar token si viene por GET
if ($step === 'reset' && !empty($_GET['token'])) {
    $token = $_GET['token'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="bi bi-key fs-1"></i>
                </div>
                <h3 class="mb-0">Recuperar Contraseña</h3>
                <p class="text-muted">
                    <?php echo $step === 'request' ? 'Ingrese su email para recibir instrucciones' : 'Ingrese su nueva contraseña'; ?>
                </p>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($step === 'request'): ?>
            <form method="POST" action="" class="auth-form">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Ingrese su email" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="bi bi-send me-2"></i>Enviar Instrucciones
                </button>
                
                <div class="text-center">
                    <a href="login.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>Volver al login
                    </a>
                </div>
            </form>
            
            <?php else: ?>
            <form method="POST" action="?step=reset" class="auth-form">
                <input type="hidden" name="token" value="<?php echo $token ?? ''; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Nueva Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirmar Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repita la contraseña" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="bi bi-check-lg me-2"></i>Cambiar Contraseña
                </button>
                
                <div class="text-center">
                    <a href="login.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>Volver al login
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
