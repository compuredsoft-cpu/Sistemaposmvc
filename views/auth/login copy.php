<?php
/**
 * Vista de Login
 */
require_once dirname(__DIR__, 2) . '/config/config.php';

if (SessionManager::isLoggedIn()) {
    redirect(SITE_URL . '/views/dashboard/index.php');
}

// Verificar cookie remember_token para auto-login
if (!SessionManager::isLoggedIn() && SessionManager::checkRememberToken()) {
    redirect(SITE_URL . '/views/dashboard/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $repo = new UsuarioRepository();
    $user = $repo->authenticate($username, $password);
    
    if ($user) {
        $remember = isset($_POST['remember']) && $_POST['remember'] == '1';
        SessionManager::login($user, $remember);
        redirect(SITE_URL . '/views/dashboard/index.php');
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}

$timeout = isset($_GET['timeout']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="bi bi-shop fs-1"></i>
                </div>
                <h3 class="mb-0"><?php echo SITE_NAME; ?></h3>
                <p class="text-muted">Inicie sesión para continuar</p>
            </div>
            
            <?php if ($timeout): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-clock-history me-2"></i>Su sesión ha expirado por inactividad
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="mb-3">
                    <label class="form-label">Usuario o Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Ingrese usuario o email" required autofocus>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" id="password" placeholder="Ingrese contraseña" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                    <label class="form-check-label" for="remember">Recordar sesión</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                </button>
                
                <div class="text-center">
                    <a href="recuperar-password.php" class="text-decoration-none">¿Olvidó su contraseña?</a>
                </div>
            </form>
            
            <div class="auth-footer">
                <p class="mb-0">Versión <?php echo APP_VERSION; ?></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Manejar "Recordar sesión" - guardar username y estado del checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.querySelector('input[name="username"]');
            const rememberCheckbox = document.getElementById('remember');
            const loginForm = document.querySelector('form.auth-form');
            
            // Recuperar datos guardados
            const savedUsername = localStorage.getItem('remember_username');
            const savedRemember = localStorage.getItem('remember_checked');
            
            if (savedUsername && savedRemember === 'true') {
                usernameInput.value = savedUsername;
                rememberCheckbox.checked = true;
            }
            
            // Guardar al hacer login
            loginForm.addEventListener('submit', function() {
                if (rememberCheckbox.checked) {
                    localStorage.setItem('remember_username', usernameInput.value);
                    localStorage.setItem('remember_checked', 'true');
                } else {
                    localStorage.removeItem('remember_username');
                    localStorage.removeItem('remember_checked');
                }
            });
        });
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
