<?php
/**
 * Cerrar Sesión
 */
require_once dirname(__DIR__, 2) . '/config/config.php';

SessionManager::logout();
redirect(SITE_URL . '/views/auth/login.php');
