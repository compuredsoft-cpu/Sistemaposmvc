<?php
/**
 * Guardar Edición de Cliente (desde modal wizard)
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('clientes');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    $repo = new ClienteRepository();
    $userId = SessionManager::getUserId();
    
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID de cliente no válido');
    }
    
    // Buscar cliente existente
    $cliente = $repo->findById($id);
    if (!$cliente) {
        throw new Exception('Cliente no encontrado');
    }
    
    // Actualizar datos
    $cliente->tipo_documento = $_POST['tipo_documento'] ?? $cliente->tipo_documento;
    $cliente->documento = $_POST['documento'] ?? $cliente->documento;
    $cliente->nombre = $_POST['nombre'] ?? $cliente->nombre;
    $cliente->apellido = $_POST['apellido'] ?? null;
    $cliente->razon_social = $_POST['razon_social'] ?? null;
    $cliente->telefono = $_POST['telefono'] ?? null;
    $cliente->email = $_POST['email'] ?? null;
    $cliente->direccion = $_POST['direccion'] ?? null;
    $cliente->ciudad = $_POST['ciudad'] ?? null;
    $cliente->fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $cliente->limite_credito = floatval($_POST['limite_credito'] ?? $cliente->limite_credito);
    $cliente->observaciones = $_POST['observaciones'] ?? null;
    $cliente->estado = isset($_POST['estado']) ? 1 : 0;
    
    $result = $repo->save($cliente, $userId);
    
    if ($result) {
        SessionManager::setFlash('success', 'Cliente actualizado correctamente');
    } else {
        throw new Exception('Error al actualizar el cliente');
    }
    
} catch (Exception $e) {
    SessionManager::setFlash('error', $e->getMessage());
}

header('Location: index.php');
exit;
?>
