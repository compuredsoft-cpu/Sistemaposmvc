<?php
/**
 * Sistema POS - Punto de Entrada Principal
 */

// Definir constantes
define('APP_ROOT', __DIR__);

// Incluir configuración
require_once APP_ROOT . '/config/config.php';

// Verificar si está instalado
if (!file_exists(APP_ROOT . '/config/.installed')) {
    redirect(SITE_URL . '/install.php');
}

// Verificar autenticación
SessionManager::requireAuth();

// Redirigir al dashboard
redirect(SITE_URL . '/views/dashboard/index.php');
