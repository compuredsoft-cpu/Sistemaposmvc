<?php
/**
 * Vista de Error 403 - Acceso Prohibido
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso Prohibido | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%);
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            text-align: center;
        }
        
        .error-icon {
            font-size: 80px;
            color: #dc2626;
            margin-bottom: 20px;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 800;
            color: #dc2626;
            line-height: 1;
            margin-bottom: 10px;
        }
        
        .error-title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        .error-message {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
            color: white;
        }
        
        .btn-home i {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <i class="bi bi-shield-x error-icon"></i>
        <div class="error-code">403</div>
        <h1 class="error-title">Acceso Prohibido</h1>
        <p class="error-message">
            No tienes los permisos necesarios para acceder a esta página. 
            Si crees que esto es un error, contacta al administrador del sistema.
        </p>
        <a href="<?php echo SITE_URL; ?>/views/dashboard/index.php" class="btn-home">
            <i class="bi bi-house-door"></i>
            Ir al Inicio
        </a>
    </div>
</body>
</html>
