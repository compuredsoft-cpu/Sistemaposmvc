<?php
echo "<h1>PHP Works!</h1>";
echo "Path: " . __DIR__ . "<br>";

$configPath = dirname(__DIR__, 2) . '/config/config.php';
echo "Config path: " . $configPath . "<br>";
echo "Config exists: " . (file_exists($configPath) ? 'YES' : 'NO') . "<br>";

if (file_exists($configPath)) {
    require_once $configPath;
    echo "Config loaded OK<br>";
    
    try {
        $repo = new KardexRepository();
        echo "KardexRepository loaded<br>";
    } catch (Throwable $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
