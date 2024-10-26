<?php
// Configuración de codificación
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// ============================
// Configuración general
// ============================
define('NOMBRE_SITIO', 'El Blog de Mi Hija'); // Nombre del sitio web
define('URL_BASE', 'https://tudominio.com/'); // URL base del sitio, terminando con '/'

// ============================
// Rutas de directorios
// ============================
// Directorio actual es donde está config.php (carpeta del blog)
define('DIRECTORIO_BASE', __DIR__ . '/');

// Ruta al directorio de uploads dentro de CKEditor5
define('DIRECTORIO_UPLOADS', realpath(__DIR__ . '/../CKEditor5/uploads/') . '/'); // Ajusta el nombre de la carpeta si es necesario

// URL pública de la carpeta de imágenes
define('URL_IMAGENES', URL_BASE . 'CKEditor5/uploads/');

// ============================
// Otras rutas de directorios
// ============================
define('DIRECTORIO_ENTRADAS', DIRECTORIO_BASE . 'entradas/');
define('DIRECTORIO_USUARIOS', DIRECTORIO_BASE . 'usuarios/');
define('DIRECTORIO_COMENTARIOS', DIRECTORIO_BASE . 'comentarios/');
define('DIRECTORIO_ACCESOS', DIRECTORIO_BASE . 'accesos/'); // Directorio para registros de accesos

// ============================
// Configuración de seguridad
// ============================
define('IP_EDITOR', 'xxx.yyy.zzz.xxx'); // Reemplaza por la IP fija del editor  

// ============================
// Configuración de depuración
// ============================
define('DEBUG_MODE', false); // Cambia a false en producción para ocultar mensajes de error detallados

// Configurar el nivel de reporte de errores
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // En producción, no mostrar errores y registrar en un archivo de log
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error_log.txt');
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}

// ============================
// Crear directorios si no existen
// ============================
$directorios = [
    DIRECTORIO_ENTRADAS,
    DIRECTORIO_USUARIOS,
    DIRECTORIO_COMENTARIOS,
    DIRECTORIO_ACCESOS,
    DIRECTORIO_UPLOADS
];
foreach ($directorios as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true); // Crear directorios con permisos adecuados
    }
}

// ============================
// Incluir funciones
// ============================
require_once 'funciones.php';
