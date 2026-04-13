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
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        min-height: 100vh;
        display: flex;
        background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 50%, #581c87 100%);
        position: relative;
        overflow-x: hidden;
    }

    /* Patrón de fondo */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image:
            radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
            radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 40%);
        z-index: 0;
    }

    /* Container dividido */
    .login-wrapper {
        display: flex;
        width: 100%;
        min-height: 100vh;
        position: relative;
        z-index: 1;
        justify-content: center;
        align-items: center;
    }

    /* Lado izquierdo - Visual */
    .login-visual {
        flex: 0 0 45%;
        max-width: 500px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 40px;
        position: relative;
    }

    .visual-content {
        text-align: center;
        color: white;
        max-width: 500px;
    }

    .visual-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border-radius: 50px;
        font-size: 0.85rem;
        margin-bottom: 30px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .visual-badge i {
        color: #fbbf24;
    }

    .visual-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 16px;
        line-height: 1.2;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .visual-title span {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .visual-subtitle {
        font-size: 1.1rem;
        opacity: 0.85;
        margin-bottom: 40px;
        line-height: 1.6;
    }

    /* Stats */
    .visual-stats {
        display: flex;
        gap: 40px;
        justify-content: center;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #fbbf24;
    }

    .stat-label {
        font-size: 0.85rem;
        opacity: 0.7;
    }

    /* Lado derecho - Formulario */
    .login-form-side {
        flex: 0 0 420px;
        background: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 35px 40px;
        position: relative;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        margin: 20px;
    }

    .form-container {
        width: 100%;
        max-width: 380px;
        margin: 0 auto;
    }

    /* Header del form */
    .form-header {
        margin-bottom: 30px;
    }

    .form-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .form-header p {
        color: #6b7280;
        font-size: 0.95rem;
    }

    /* Inputs modernos */
    .input-wrap {
        margin-bottom: 18px;
    }

    .input-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .input-field {
        position: relative;
    }

    .input-field input {
        width: 100%;
        padding: 14px 16px 14px 48px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f9fafb;
    }

    .input-field input:focus {
        outline: none;
        border-color: #4f46e5;
        background: white;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    .input-field .field-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 1.1rem;
    }

    .input-field input:focus+.field-icon,
    .input-field input:focus~.field-icon {
        color: #4f46e5;
    }

    /* Toggle password */
    .password-toggle-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .password-toggle-btn:hover {
        background: #f3f4f6;
        color: #4f46e5;
    }

    /* Opciones */
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .remember-option {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }

    .remember-option input {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 2px solid #d1d5db;
        appearance: none;
        cursor: pointer;
        position: relative;
        transition: all 0.2s ease;
    }

    .remember-option input:checked {
        background: #4f46e5;
        border-color: #4f46e5;
    }

    .remember-option input:checked::after {
        content: '✓';
        position: absolute;
        color: white;
        font-size: 12px;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: bold;
    }

    .remember-option span {
        font-size: 0.9rem;
        color: #4b5563;
    }

    .forgot-link {
        font-size: 0.9rem;
        color: #4f46e5;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s ease;
    }

    .forgot-link:hover {
        color: #3730a3;
        text-decoration: underline;
    }

    /* Botón principal */
    .btn-login {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(79, 70, 229, 0.5);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    /* Alertas */
    .alert-box {
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.9rem;
    }

    .alert-box.error {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .alert-box.warning {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fcd34d;
    }

    /* Footer */
    .form-footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #e5e7eb;
    }

    .version-text {
        font-size: 0.8rem;
        color: #9ca3af;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .login-visual {
            display: none;
        }

        .login-form-side {
            flex: 1;
            max-width: 100%;
            padding: 30px;
        }
    }

    @media (max-width: 480px) {
        .login-form-side {
            padding: 24px;
        }

        .form-header h2 {
            font-size: 1.5rem;
        }

        .visual-title {
            font-size: 2rem;
        }
    }

    @media (min-height: 800px) {
        .login-wrapper {
            align-items: center;
        }

        .login-form-side {
            min-height: auto;
            padding: 50px;
        }
    }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <!-- Lado Visual -->
        <div class="login-visual">
            <div class="visual-content">
                <div class="visual-badge">
                    <i class="bi bi-shield-check"></i>
                    <span>Sistema POS Seguro y Autoadministrable</span>
                </div>
                <h1 class="visual-title">
                    Gestiona tu<br><span>Negocio</span> con<br>Facilidad
                </h1>
                <p class="visual-subtitle">
                    Sistema integral de gestión de inventario, ventas y clientes diseñado para impulsar tu
                    productividad.
                </p>
                <div class="visual-stats">
                    <div class="stat-item">
                        <div class="stat-number">10K+</div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">99.9%</div>
                        <div class="stat-label">Uptime</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Soporte</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lado Formulario -->
        <div class="login-form-side">
            <div class="form-container">
                <div class="form-header">
                    <h2>Iniciar Sesión</h2>
                    <p>Ingresa tus credenciales para acceder al sistema</p>
                </div>

                <?php if ($timeout): ?>
                <div class="alert-box warning">
                    <i class="bi bi-clock-history"></i>
                    <span>Su sesión ha expirado por inactividad</span>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert-box error">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <div class="input-wrap">
                        <label class="input-label">Usuario o Email</label>
                        <div class="input-field">
                            <i class="bi bi-person field-icon"></i>
                            <input type="text" name="username" id="username" placeholder="Ingresa tu usuario" required
                                autofocus>
                        </div>
                    </div>

                    <div class="input-wrap">
                        <label class="input-label">Contraseña</label>
                        <div class="input-field">
                            <i class="bi bi-lock field-icon"></i>
                            <input type="password" name="password" id="password" placeholder="Ingresa tu contraseña"
                                required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-option">
                            <input type="checkbox" id="remember" name="remember" value="1">
                            <span>Recordarme</span>
                        </label>
                        <a href="recuperar-password.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Ingresar al Sistema
                    </button>
                </form>

                <div class="form-footer">
                    <span class="version-text">Versión <?php echo APP_VERSION; ?> | Sistema POS</span>
                </div>
            </div>
        </div>
    </div>

    <script>
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

    // Recordar usuario
    document.addEventListener('DOMContentLoaded', function() {
        const usernameInput = document.getElementById('username');
        const rememberCheckbox = document.getElementById('remember');
        const loginForm = document.getElementById('loginForm');

        const savedUsername = localStorage.getItem('remember_username');
        const savedRemember = localStorage.getItem('remember_checked');

        if (savedUsername && savedRemember === 'true') {
            usernameInput.value = savedUsername;
            rememberCheckbox.checked = true;
        }

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
    </script>
</body>

</html>