<?php
/**
 * Gestión de Sesiones y Autenticación
 */

class SessionManager {
    
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUserData(): ?array {
        return $_SESSION['user_data'] ?? null;
    }
    
    public static function getUserRole(): ?string {
        return $_SESSION['user_role'] ?? null;
    }
    
    public static function hasPermission(string $permission): bool {
        $permissions = $_SESSION['user_permissions'] ?? [];
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
    
    public static function login(array $userData, bool $remember = false): void {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_data'] = $userData;
        $_SESSION['user_role'] = $userData['rol_nombre'] ?? '';
        $_SESSION['user_permissions'] = json_decode($userData['permisos'] ?? '[]', true);
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Crear cookie persistente si se solicitó "Recordar sesión"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Guardar token en BD
            $repo = new UsuarioRepository();
            $repo->setRememberToken($userData['id'], $token, $expira);
            
            // Crear cookie (30 días)
            setcookie('remember_token', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
    }
    
    public static function checkRememberToken(): bool {
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $repo = new UsuarioRepository();
            $user = $repo->findByRememberToken($token);
            
            if ($user) {
                $userData = [
                    'id' => $user->id,
                    'rol_nombre' => $user->rol_nombre ?? '',
                    'permisos' => $user->permisos ?? '[]'
                ];
                self::login($userData, true);
                return true;
            }
        }
        return false;
    }
    
    public static function logout(): void {
        // Eliminar cookie remember_token
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        session_destroy();
    }
    
    public static function checkActivity(int $timeout = 1800): bool {
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > $timeout) {
                self::logout();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public static function regenerate(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    public static function requireAuth(): void {
        if (!self::isLoggedIn()) {
            if (isAjaxRequest()) {
                jsonResponse(['success' => false, 'message' => 'Sesión expirada'], 401);
            } else {
                redirect(SITE_URL . '/views/auth/login.php');
            }
        }
        
        if (!self::checkActivity()) {
            if (isAjaxRequest()) {
                jsonResponse(['success' => false, 'message' => 'Sesión expirada por inactividad'], 401);
            } else {
                redirect(SITE_URL . '/views/auth/login.php?timeout=1');
            }
        }
    }
    
    public static function requirePermission(string $permission): void {
        self::requireAuth();
        
        if (!self::hasPermission($permission)) {
            if (isAjaxRequest()) {
                jsonResponse(['success' => false, 'message' => 'No tiene permisos para esta acción'], 403);
            } else {
                redirect(SITE_URL . '/views/errors/403.php');
            }
        }
    }
    
    public static function setFlash(string $key, string $message): void {
        $_SESSION['flash'][$key] = $message;
    }
    
    public static function getFlash(string $key): ?string {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    
    public static function hasFlash(string $key): bool {
        return isset($_SESSION['flash'][$key]);
    }
}
