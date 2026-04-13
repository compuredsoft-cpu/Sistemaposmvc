<?php
/**
 * Script de diagnóstico para login
 */
require_once __DIR__ . '/config/config.php';

echo "<h2>Diagnóstico de Login</h2>";

// 1. Verificar conexión a BD
echo "<h3>1. Conexión a Base de Datos</h3>";
try {
    $db = Database::getConnection();
    echo "✅ Conexión exitosa<br>";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Verificar si existe el usuario admin
echo "<h3>2. Usuario 'admin' en la BD</h3>";
$stmt = $db->prepare("SELECT id, username, email, password, estado FROM usuarios WHERE username = ?");
$stmt->execute(['admin']);
$user = $stmt->fetch();

if ($user) {
    echo "✅ Usuario encontrado:<br>";
    echo "- ID: " . $user['id'] . "<br>";
    echo "- Username: " . $user['username'] . "<br>";
    echo "- Email: " . $user['email'] . "<br>";
    echo "- Estado: " . $user['estado'] . "<br>";
    echo "- Hash en BD: " . substr($user['password'], 0, 20) . "...<br>";
    
    // 3. Verificar contraseña
    echo "<h3>3. Verificación de Contraseña</h3>";
    $testPassword = 'admin123';
    if (password_verify($testPassword, $user['password'])) {
        echo "✅ La contraseña 'admin123' es CORRECTA<br>";
    } else {
        echo "❌ La contraseña 'admin123' no coincide<br>";
        
        // Generar nuevo hash
        echo "<h4>Nuevo hash para 'admin123':</h4>";
        $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
        echo "<code>" . $newHash . "</code><br>";
        
        // Actualizar automáticamente
        echo "<br>🔄 <b>Actualizando contraseña...</b><br>";
        $update = $db->prepare("UPDATE usuarios SET password = ? WHERE username = ?");
        if ($update->execute([$newHash, 'admin'])) {
            echo "✅ Contraseña actualizada. <a href='views/auth/login.php'>Intenta login ahora</a><br>";
        } else {
            echo "❌ Error al actualizar<br>";
        }
    }
} else {
    echo "❌ Usuario 'admin' NO encontrado en la BD<br>";
    
    // Mostrar usuarios existentes
    echo "<h3>Usuarios existentes:</h3>";
    $stmt = $db->query("SELECT id, username, email FROM usuarios LIMIT 5");
    $users = $stmt->fetchAll();
    if (empty($users)) {
        echo "⚠️ No hay usuarios en la tabla. La BD puede estar vacía.<br>";
    } else {
        foreach ($users as $u) {
            echo "- " . $u['username'] . " (" . $u['email'] . ")<br>";
        }
    }
}

echo "<hr><a href='views/auth/login.php'>Ir al Login</a> | <a href='install.php'>Reinstalar</a>";
