<?php
/**
 * Guardar Edición de Producto (desde modal wizard)
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
SessionManager::requirePermission('almacen');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    $repo = new ProductoRepository();
    $userId = SessionManager::getUserId();
    
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID de producto no válido');
    }
    
    // Buscar producto existente
    $productos = $repo->findAllWithCategories(['id' => $id], 'p.id DESC', 1);
    if (empty($productos['items'])) {
        throw new Exception('Producto no encontrado');
    }
    
    $producto = $productos['items'][0];
    
    // Actualizar datos (no tocar stock_actual, se gestiona por Kardex)
    $producto->codigo = $_POST['codigo'] ?? $producto->codigo;
    $producto->codigo_barras = $_POST['codigo_barras'] ?? null;
    $producto->nombre = $_POST['nombre'] ?? $producto->nombre;
    $producto->descripcion = $_POST['descripcion'] ?? null;
    $producto->categoria_id = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
    $producto->unidad_medida = $_POST['unidad_medida'] ?? 'UN';
    $producto->precio_costo = floatval($_POST['precio_costo'] ?? $producto->precio_costo);
    $producto->precio_venta = floatval($_POST['precio_venta'] ?? $producto->precio_venta);
    $producto->precio_mayorista = !empty($_POST['precio_mayorista']) ? floatval($_POST['precio_mayorista']) : null;
    $producto->stock_minimo = intval($_POST['stock_minimo'] ?? $producto->stock_minimo);
    $producto->stock_maximo = intval($_POST['stock_maximo'] ?? $producto->stock_maximo);
    $producto->ubicacion = $_POST['ubicacion'] ?? null;
    $producto->estado = isset($_POST['estado']) ? 1 : 0;
    
    $result = $repo->save($producto, $userId);
    
    if ($result) {
        SessionManager::setFlash('success', 'Producto actualizado correctamente');
    } else {
        throw new Exception('Error al actualizar el producto');
    }
    
} catch (Exception $e) {
    SessionManager::setFlash('error', $e->getMessage());
}

header('Location: index.php');
exit;
?>
