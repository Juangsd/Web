<?php
// Configuración básica del sistema
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'certi_celen');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

if (!defined('SITE_NAME')) define('SITE_NAME', 'CERTI-CELEN');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost/certi-celen');

// Definir rutas absolutas
if (!defined('BASE_PATH')) define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', BASE_PATH . '/uploads/');
if (!defined('QR_DIR')) define('QR_DIR', BASE_PATH . '/qr_codes/');

// Configuración para generación de PDF
if (!defined('PDF_TEMPLATE')) define('PDF_TEMPLATE', BASE_PATH . '/templates/certificado.html');

// Zona horaria
date_default_timezone_set('America/Lima');

// Configuración de sesión mejorada
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Crear directorios necesarios si no existen
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if (!file_exists(QR_DIR)) {
    mkdir(QR_DIR, 0755, true);
}
?>