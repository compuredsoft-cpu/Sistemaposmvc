<?php
/**
 * API Router
 * Punto de entrada para todas las peticiones API
 */

// Desactivar display de errores para evitar HTML en respuestas JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Capturar errores no manejados
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error [$severity]: $message in $file:$line");
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/config/config.php';

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fatal: ' . $error['message'] . ' en ' . $error['file'] . ':' . $error['line']]);
    }
});

require_once __DIR__ . '/config/config.php';

// Headers para API REST
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función helper para respuestas JSON
if (!function_exists('jsonResponse')) {
    function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Obtener la ruta de la URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Debug info
$debugInfo = [
    'request_uri' => $requestUri,
    'script_name' => $scriptName,
    'base_path' => dirname($scriptName)
];

// Extraer el path después de api.php
$basePath = dirname($scriptName);
$path = str_replace($basePath, '', $requestUri);

// Manejar query strings
$pathParts = explode('?', $path);
$path = $pathParts[0];

$path = preg_replace('/^\/api\.php/', '', $path);
$path = preg_replace('/^\//', '', $path);
$path = trim($path, '/');

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Parsear el path
$segments = explode('/', $path);
$resource = $segments[0] ?? '';
$action = $segments[1] ?? '';
$id = $segments[2] ?? null;

// Log para debugging
error_log("API Request: $method /$path - Resource: $resource, Action: $action, Debug: " . json_encode($debugInfo));

// Verificar que el autoload funcione
try {
    // Verificar que existan los archivos necesarios
    $requiredFiles = [
        __DIR__ . '/controllers/PaymentController.php',
        __DIR__ . '/models/repositories/PaymentRepository.php',
        __DIR__ . '/models/entities/MetodoPago.php',
        __DIR__ . '/models/entities/VentaPago.php',
        __DIR__ . '/services/PaymentGatewayService.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Archivo no encontrado: " . basename($file));
        }
    }
    
    // Instanciar controlador de pagos
    $paymentController = new PaymentController();
} catch (Exception $e) {
    error_log("Error instanciando PaymentController: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
    jsonResponse(['success' => false, 'message' => 'Error inicializando controlador: ' . $e->getMessage(), 'debug' => $debugInfo], 500);
}

// Routing
switch ($resource) {
    case '':
        // Endpoint de prueba
        jsonResponse(['success' => true, 'message' => 'API funcionando', 'version' => '1.0']);
        break;
        
    case 'test':
        // Test de conexión a base de datos
        try {
            $db = Database::getConnection();
            jsonResponse(['success' => true, 'message' => 'Conexión a BD OK']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'pagos':
        handlePagosRoutes($method, $action, $id, $paymentController);
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ruta no encontrada: ' . $resource], 404);
}

/**
 * Manejar rutas de pagos
 */
function handlePagosRoutes(string $method, string $action, ?string $id, PaymentController $controller): void {
    // Si no hay acción, asumir que es la ruta base
    if (empty($action)) {
        jsonResponse(['success' => false, 'message' => 'Acción no especificada'], 400);
        return;
    }
    
    switch ($action) {
        case 'metodos':
            if ($method === 'GET') {
                try {
                    $controller->getMetodosPago();
                } catch (Exception $e) {
                    error_log("Error en getMetodosPago: " . $e->getMessage());
                    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                }
            } else {
                jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            }
            break;
            
        case 'procesar':
            if ($method === 'POST') {
                $controller->procesarPagos();
            }
            break;
            
        case 'venta':
            if ($method === 'GET' && $id) {
                $controller->getPagosByVenta((int) $id);
            }
            break;
            
        case 'wompi':
            if ($method === 'POST') {
                if ($id === 'webhook') {
                    $controller->webhookWompi();
                } else {
                    $controller->crearTransaccionWompi();
                }
            }
            break;
            
        case 'placetopay':
            if ($method === 'POST') {
                if ($id === 'webhook') {
                    $controller->webhookPlaceToPay();
                } else {
                    $controller->crearSesionPlaceToPay();
                }
            }
            break;
            
        case 'qr':
            if ($method === 'POST' && $id === 'generar') {
                $controller->generarQR();
            } elseif ($method === 'GET' && $id) {
                $controller->verificarEstadoQR((int) $id);
            }
            break;
            
        case 'devolucion':
            if ($method === 'POST') {
                $controller->procesarDevolucion();
            }
            break;
            
        case 'cuentas-bancarias':
            if ($method === 'GET') {
                $controller->getCuentasBancarias();
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no encontrada: ' . $action], 404);
    }
}
