<?php
/**
 * Sincronizar stock de productos con movimientos de kardex
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('almacen');

try {
    $db = Database::getConnection();
    
    // Recalcular stock basado en el último movimiento de kardex para cada producto
    $sql = "UPDATE productos p 
            LEFT JOIN (
                SELECT producto_id, stock_nuevo 
                FROM kardex k1 
                WHERE id = (
                    SELECT MAX(id) FROM kardex k2 WHERE k2.producto_id = k1.producto_id
                )
            ) ultimo_kardex ON p.id = ultimo_kardex.producto_id
            SET p.stock_actual = COALESCE(ultimo_kardex.stock_nuevo, p.stock_actual)
            WHERE ultimo_kardex.stock_nuevo IS NOT NULL";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute();
    
    $affected = $stmt->rowCount();
    
    SessionManager::setFlash('success', "Stock sincronizado. {$affected} productos actualizados.");
    
} catch (Exception $e) {
    SessionManager::setFlash('error', 'Error: ' . $e->getMessage());
}

header('Location: index.php');
exit;
?>
