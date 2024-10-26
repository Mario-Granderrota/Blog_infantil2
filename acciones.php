<?php
require_once 'config.php';
require_once 'funciones.php';

// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario es editor
if (!esEditor()) {
    redirigir('index.php', 'Acceso denegado.', 'error');
    exit();
}

// Verificar que se haya enviado una acción
$accion = filter_input(INPUT_POST, 'accion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (!$accion) {
    $accion = filter_input(INPUT_GET, 'accion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
}

if (!$accion) {
    redirigir('admin.php', 'Acción no especificada.', 'error');
    exit();
}

// Verificar el token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirigir('admin.php', 'Token CSRF inválido.', 'error');
        exit();
    }
}

// Procesar la acción
try {
    switch ($accion) {
        case 'borrar_entrada':
            manejarBorradoEntrada();
            break;
        case 'borrar_comentario':
            manejarBorradoComentario();
            break;
        // Agrega más casos según tus necesidades
        default:
            throw new Exception('Acción no reconocida.');
    }
} catch (Exception $e) {
    redirigir('admin.php', 'Error: ' . $e->getMessage(), 'error');
}

/**
 * Maneja el borrado de una entrada.
 */
function manejarBorradoEntrada() {
    $slug = filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (!$slug) {
        $slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    if (!$slug) {
        throw new Exception('Parámetros inválidos para eliminar la entrada.');
    }

    // Intentar borrar la entrada
    try {
        borrarEntrada($slug);
        redirigir('admin.php', 'Entrada eliminada exitosamente.', 'success');
    } catch (Exception $e) {
        throw new Exception('Error al eliminar la entrada: ' . $e->getMessage());
    }
}

/**
 * Maneja el borrado de un comentario.
 */
function manejarBorradoComentario() {
    $entradaSlug = filter_input(INPUT_GET, 'entrada_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $comentarioId = filter_input(INPUT_GET, 'comentario_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!$entradaSlug || !$comentarioId) {
        throw new Exception('Parámetros inválidos para eliminar el comentario.');
    }

    // Intentar borrar el comentario
    try {
        borrarComentario($entradaSlug, $comentarioId);
        redirigir('ultimos_comentarios.php', 'Comentario eliminado exitosamente.', 'success');
    } catch (Exception $e) {
        throw new Exception('Error al eliminar el comentario: ' . $e->getMessage());
    }
}
?>
