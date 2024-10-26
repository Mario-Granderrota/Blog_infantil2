<?php
// comentarios.php
require_once 'config.php';
require_once 'funciones.php';

// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Establecer el encabezado de respuesta
header('Content-Type: application/json; charset=utf-8');

// Configuración de Rate Limiting (opcional)
// Define el número máximo de solicitudes permitidas por minuto
define('MAX_REQUESTS_PER_MINUTE', 60);

// Obtener la IP del cliente
$clientIp = $_SERVER['REMOTE_ADDR'];

// Iniciar el contador de solicitudes si no existe
if (!isset($_SESSION['rate_limit'][$clientIp])) {
    $_SESSION['rate_limit'][$clientIp] = [
        'requests' => 0,
        'last_request_time' => time()
    ];
}

// Obtener la información de rate limiting para la IP
$rateLimitInfo = &$_SESSION['rate_limit'][$clientIp];

// Resetear el contador si ha pasado más de un minuto
if (time() - $rateLimitInfo['last_request_time'] > 60) {
    $rateLimitInfo['requests'] = 0;
    $rateLimitInfo['last_request_time'] = time();
}

// Incrementar el contador de solicitudes
$rateLimitInfo['requests']++;

// Verificar si se ha excedido el límite de solicitudes
if ($rateLimitInfo['requests'] > MAX_REQUESTS_PER_MINUTE) {
    respuestaJson(['error' => 'Demasiadas solicitudes. Por favor, intenta de nuevo más tarde.'], 429);
}

// Función para enviar la respuesta en formato JSON
function respuestaJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    // Agregar encabezados de seguridad adicionales
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Función para validar el slug de la entrada
function validarSlug($slug) {
    // Permitir solo letras, números, guiones y guiones bajos
    return preg_match('/^[a-zA-Z0-9_-]+$/', $slug);
}

// Función para verificar el token CSRF
function verificarCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Manejar las solicitudes GET (obtener comentarios)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $entrada_slug = filter_input(INPUT_GET, 'entrada_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if (!$entrada_slug || !validarSlug($entrada_slug)) {
        respuestaJson(['error' => 'ID de entrada no válido'], 400);
    }

    try {
        $comentarios = obtenerComentarios($entrada_slug);
        // Sanitizar los comentarios antes de enviarlos
        $comentariosSanitizados = array_map(function($comentario) {
            return [
                'usuario' => htmlspecialchars($comentario['usuario'], ENT_QUOTES, 'UTF-8'),
                'texto' => nl2br(htmlspecialchars($comentario['texto'], ENT_QUOTES, 'UTF-8')),
                'fecha' => htmlspecialchars($comentario['fecha'], ENT_QUOTES, 'UTF-8')
            ];
        }, $comentarios);
    } catch (Exception $e) {
        // Registrar el error en el log del servidor
        error_log('Error al obtener comentarios: ' . $e->getMessage());
        respuestaJson(['error' => 'Error al obtener comentarios'], 500);
    }

    respuestaJson(['comentarios' => $comentariosSanitizados]);
}

// Manejar las solicitudes POST (agregar un nuevo comentario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos de la solicitud
    $accion = filter_input(INPUT_POST, 'accion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $entrada_slug = filter_input(INPUT_POST, 'entrada_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $comentario_texto = trim($_POST['comentario'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Verificar la acción
    if ($accion !== 'agregar') {
        respuestaJson(['error' => 'Acción no reconocida'], 400);
    }

    // Validar el slug de la entrada
    if (empty($entrada_slug) || !validarSlug($entrada_slug)) {
        respuestaJson(['error' => 'ID de entrada no válido'], 400);
    }

    // Verificar la presencia y validez del token CSRF
    if (!verificarCsrfToken($csrf_token)) {
        respuestaJson(['error' => 'Token CSRF inválido'], 403);
    }

    // Verificar si el usuario está autenticado
    if (!isset($_SESSION['usuario'])) {
        respuestaJson(['error' => 'Usuario no autenticado'], 401);
    }

    // Validar el contenido del comentario
    if (empty($comentario_texto)) {
        respuestaJson(['error' => 'El comentario no puede estar vacío'], 400);
    }

    // Opcional: Limitar la longitud del comentario
    $maxLength = 1000; // Ajusta según tus necesidades
    if (mb_strlen($comentario_texto) > $maxLength) {
        respuestaJson(['error' => "El comentario no puede exceder los $maxLength caracteres"], 400);
    }

    // Sanitizar el texto del comentario
    $comentario_texto = htmlspecialchars(strip_tags($comentario_texto), ENT_QUOTES, 'UTF-8');

    // Preparar el comentario para guardar
    $nombre_usuario = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');
    $comentario = [
        'usuario' => $nombre_usuario,
        'texto' => $comentario_texto,
        'fecha' => date("Y-m-d H:i:s")
    ];

    try {
        guardarComentario($entrada_slug, $comentario);
        respuestaJson(['mensaje' => 'Comentario agregado con éxito', 'comentario' => $comentario], 201);
    } catch (Exception $e) {
        // Registrar el error en el log del servidor
        error_log('Error al guardar comentario: ' . $e->getMessage());
        respuestaJson(['error' => 'Error al guardar el comentario'], 500);
    }
}

// Si llegamos aquí, el método HTTP no está soportado
respuestaJson(['error' => 'Método no soportado'], 405);
?>
