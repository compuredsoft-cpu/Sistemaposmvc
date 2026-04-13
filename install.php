<?php
    /**
 * Instalador del Sistema POS
 */

    define('APP_ROOT', __DIR__);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['db_host'] ?? 'localhost';
    $name = $_POST['db_name'] ?? 'sistema_pos';
    $user = $_POST['db_user'] ?? 'root';
    $pass = $_POST['db_pass'] ?? '';

    try {
        // Crear conexión temporal
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Leer y ejecutar SQL base
        $sql = file_get_contents(APP_ROOT . '/database/sistema_pos.sql');
        $pdo->exec($sql);

        // Ejecutar migración de pagos modernos
        $migrationSql = file_get_contents(APP_ROOT . '/database/migrations/001_add_modern_payments.sql');
        $pdo->exec($migrationSql);

        // Actualizar archivo de configuración de base de datos
        $configContent = "<?php\nclass Database {\n    private static \$instance = null;\n    private const HOST = '$host';\n    private const DB_NAME = '$name';\n    private const USERNAME = '$user';\n    private const PASSWORD = '$pass';\n    private const CHARSET = 'utf8mb4';\n    public static function getConnection(): PDO {\n        if (self::\$instance === null) {\n            \$dsn = \"mysql:host=\" . self::HOST . \";dbname=\" . self::DB_NAME . \";charset=\" . self::CHARSET;\n            \$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];\n            self::\$instance = new PDO(\$dsn, self::USERNAME, self::PASSWORD, \$options);\n        }\n        return self::\$instance;\n    }\n}\n";
        file_put_contents(APP_ROOT . '/config/database.php', $configContent);

        // Marcar como instalado
        touch(APP_ROOT . '/config/.installed');

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Instalación completada']);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Instalación del Sistema POS</h4>
                    </div>
                    <div class="card-body">
                        <form id="installForm">
                            <div class="mb-3">
                                <label class="form-label">Servidor MySQL</label>
                                <input type="text" name="db_host" class="form-control" value="localhost" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nombre Base de Datos</label>
                                <input type="text" name="db_name" class="form-control" value="sistema_pos" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Usuario MySQL</label>
                                <input type="text" name="db_user" class="form-control" value="root" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña MySQL</label>
                                <input type="password" name="db_pass" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Instalar Sistema</button>
                        </form>
                        <div id="result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('installForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const response = await fetch('install.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            });
            const data = await response.json();
            document.getElementById('result').innerHTML =
                '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>';
            if (data.success) {
                setTimeout(() => window.location.href = './views/auth/login.php', 2000);
            }
        });
    </script>
</body>
</html>
