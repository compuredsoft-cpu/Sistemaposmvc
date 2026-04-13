<?php
/**
 * Configuración General del Sistema POS
 */

// Evitar acceso directo
if (! defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Zona horaria (Colombia)
date_default_timezone_set('America/Bogota');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Constantes del sistema
define('SITE_NAME', 'Sistema POS');
define('SITE_URL', 'http://localhost/Sistemaposmvc');
define('APP_VERSION', '1.0.0');

// Rutas del sistema
define('CONTROLLERS_PATH', APP_ROOT . '/controllers/');
define('MODELS_PATH', APP_ROOT . '/models/');
define('VIEWS_PATH', APP_ROOT . '/views/');
define('ASSETS_PATH', APP_ROOT . '/assets/');
define('UPLOADS_PATH', APP_ROOT . '/uploads/');
define('REPORTS_PATH', APP_ROOT . '/reports/');
define('SERVICES_PATH', APP_ROOT . '/services/');

// Configuración de paginación
define('ITEMS_PER_PAGE', 10);

// Configuración de IVA Colombia
define('IVA_PORCENTAJE', 19);
define('IVA_OPCIONES', [0, 5, 19]);

// Monedas soportadas
define('MONEDAS', [
    'COP' => ['nombre' => 'Peso Colombiano', 'simbolo' => '$', 'decimales' => 0],
    'USD' => ['nombre' => 'Dólar Americano', 'simbolo' => 'US$', 'decimales' => 2],
    'EUR' => ['nombre' => 'Euro', 'simbolo' => '€', 'decimales' => 2],
]);

// Estados
define('ESTADOS', [
    'ACTIVO'   => 1,
    'INACTIVO' => 0,
]);

// Incluir archivos de config manualmente (SessionManager, Database)
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';

// Autoload de clases
spl_autoload_register(function ($class) {
    $paths = [
        MODELS_PATH . 'entities/',
        MODELS_PATH . 'repositories/',
        CONTROLLERS_PATH,
        SERVICES_PATH,
        __DIR__ . '/', // config/
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Funciones helper
function formatCurrency(float $amount, string $moneda = 'COP'): string
{
    $config = MONEDAS[$moneda] ?? MONEDAS['COP'];
    return $config['simbolo'] . ' ' . number_format($amount, $config['decimales'], ',', '.');
}

function formatDate(string $date, string $format = 'd/m/Y'): string
{
    return date($format, strtotime($date));
}

function formatDateTime(string $datetime): string
{
    return date('d/m/Y H:i:s', strtotime($datetime));
}

function sanitizeInput(string $input): string
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateCode(string $prefix, int $number): string
{
    return $prefix . str_pad((string) $number, 6, '0', STR_PAD_LEFT);
}

function generateUUID(): string
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function isAjaxRequest(): bool
{
    return ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function getUserIP(): string
{
    $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (! empty($_SERVER[$header])) {
            return $_SERVER[$header];
        }
    }
    return '0.0.0.0';
}
