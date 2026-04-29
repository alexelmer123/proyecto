<?php
// Configuración global de la aplicación
define('APP_NAME', 'Vidrios Centro Puno E.I.R.L.');
define('APP_TAGLINE', 'Inventario de vidrios y cristales');
define('APP_VERSION', '1.0.0');
define('BASE_URL','/proyecto/vidrios-inventory');

// Zona horaria por defecto
date_default_timezone_set('America/Bogota');

// Modo de depuración (cambiar a false en producción)
define('DEBUG', true);

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Ruta absoluta para la subida de imágenes de productos
define('UPLOAD_DIR', ROOT . '/public/img/productos');
define('UPLOAD_URL', BASE_URL . '/public/img/productos');

// Stock crítico global (fallback si el producto no define stock_minimo)
define('STOCK_CRITICO_DEFAULT', 5);
