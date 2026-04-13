<?php
/**
 * Guardar Movimiento de Kardex
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('almacen');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    $repo = new KardexRepository();
    $userId = SessionManager::getUserId();
    
    $productoId = intval($_POST['producto_id'] ?? 0);
    $tipoMovimiento = $_POST['tipo_movimiento'] ?? '';
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $costoUnitario = !empty($_POST['costo_unitario']) ? floatval($_POST['costo_unitario']) : null;
    $documentoTipo = $_POST['documento_tipo'] ?? 'MANUAL';
    $documentoCodigo = $_POST['documento_codigo'] ?? null;
    $observaciones = $_POST['observaciones'] ?? null;
    
    // Validaciones
    if (!$productoId) {
        throw new Exception('Debe seleccionar un producto');
    }
    if (!in_array($tipoMovimiento, ['ENTRADA', 'SALIDA', 'AJUSTE', 'DEVOLUCION'])) {
        throw new Exception('Tipo de movimiento inválido');
    }
    if ($cantidad <= 0) {
        throw new Exception('La cantidad debe ser mayor a cero');
    }
    
    // Verificar stock suficiente para salidas/ajustes
    if (in_array($tipoMovimiento, ['SALIDA', 'AJUSTE'])) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT stock_actual FROM productos WHERE id = ?");
        $stmt->execute([$productoId]);
        $stockActual = (int) $stmt->fetchColumn();
        
        if ($stockActual < $cantidad) {
            throw new Exception("Stock insuficiente. Stock actual: {$stockActual}, Cantidad solicitada: {$cantidad}");
        }
    }
    
    // Registrar movimiento
    $result = $repo->registrarMovimiento(
        $productoId,
        $tipoMovimiento,
        $documentoTipo,
        null, // documento_id
        $documentoCodigo,
        $cantidad,
        $costoUnitario,
        $observaciones,
        $userId
    );
    
    if ($result) {
        SessionManager::setFlash('success', 'Movimiento registrado correctamente');
    } else {
        throw new Exception('Error al registrar el movimiento');
    }
    
} catch (Exception $e) {
    SessionManager::setFlash('error', $e->getMessage());
}

header('Location: index.php');
exit;
?>
