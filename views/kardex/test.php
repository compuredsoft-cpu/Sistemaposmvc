<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/config/config.php';

echo "<h1>Test Kardex</h1>";

try {
    $repo = new KardexRepository();
    echo "KardexRepository OK<br>";
    
    $productoRepo = new ProductoRepository();
    echo "ProductoRepository OK<br>";
    
    $productos = $productoRepo->findAllActive();
    echo "Productos count: " . count($productos) . "<br>";
    
    $movimientos = $repo->findAllWithFilters([], 1);
    echo "Movimientos count: " . $movimientos['total'] . "<br>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
