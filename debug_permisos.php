<?php
/**
 * Debug de permisos - Temporal
 */
require_once __DIR__ . '/config/config.php';

// Verificar que esté logueado
if (!SessionManager::isLoggedIn()) {
    die('No estás logueado. Inicia sesión primero.');
}

echo "<h1>Debug de Permisos</h1>";
echo "<hr>";

// Info del usuario
$userData = SessionManager::getUserData();
echo "<h2>Datos del Usuario en Sesión:</h2>";
echo "<pre>";
print_r($userData);
echo "</pre>";

echo "<hr>";

// Permisos en sesión
$permissions = $_SESSION['user_permissions'] ?? [];
echo "<h2>Permisos en Sesión (" . count($permissions) . "):</h2>";
echo "<pre>";
print_r($permissions);
echo "</pre>";

echo "<hr>";

// Verificar permisos específicos
$permisosAVerificar = ['ventas', 'cotizaciones', 'cuentas_cobrar', 'clientes', 'almacen', 'compras', 'proveedores', 'caja', 'gastos', 'usuarios', 'roles', 'configuracion', 'dashboard'];

echo "<h2>Verificación de Permisos:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Permiso</th><th>¿Tiene permiso?</th></tr>";
foreach ($permisosAVerificar as $perm) {
    $tiene = SessionManager::hasPermission($perm) ? '✅ SÍ' : '❌ NO';
    echo "<tr><td>$perm</td><td>$tiene</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>Verificar JSON de permisos:</h2>";
echo "<p>Si los permisos vienen como string JSON, debe decodificarse correctamente.</p>";
echo "<pre>";
$permisosRaw = $userData['permisos'] ?? 'No existe';
echo "Raw permisos del usuario: " . $permisosRaw . "\n\n";

if (is_string($permisosRaw) && !empty($permisosRaw)) {
    $decoded = json_decode($permisosRaw, true);
    if ($decoded !== null) {
        echo "Decodificado correctamente:\n";
        print_r($decoded);
    } else {
        echo "ERROR: No se pudo decodificar JSON. Error: " . json_last_error_msg();
    }
}
echo "</pre>";

echo "<hr>";
echo "<p><a href='/Sistemaposmvc/views/dashboard/index.php'>Volver al Dashboard</a></p>";