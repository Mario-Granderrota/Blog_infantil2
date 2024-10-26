<?php
require_once 'config.php';

// Verificar si el usuario es un editor
if (!esEditor()) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Acceso denegado']));
}

// Definir tipos de archivos permitidos y tamaño máximo
$tiposPermitidosRegex = '/\.(jpg|jpeg|png|gif|webp)$/i';
$tiposMimePermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$tamanoMaximo = 5 * 1024 * 1024; // 5 MB

// Ruta de sistema de archivos donde se guardarán las imágenes
$directorioSubida = DIRECTORIO_UPLOADS;

// URL pública de la carpeta de imágenes
$urlBase = URL_IMAGENES;

// Asegurarse de que el directorio de subida exista
if (!file_exists($directorioSubida)) {
    mkdir($directorioSubida, 0755, true);
}

// Procesar la solicitud de subida de archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que el campo "upload" esté presente
    if (!isset($_FILES['upload'])) {
        handleUploadError('No se recibió ningún archivo.');
    }

    $archivo = $_FILES['upload'];

    // Validar que el archivo tenga un nombre
    if (empty($archivo['name'])) {
        handleUploadError('El nombre del archivo no puede estar vacío.');
    }

    // Validar el tipo de archivo usando una expresión regular
    if (!preg_match($tiposPermitidosRegex, $archivo['name'])) {
        handleUploadError('Tipo de archivo no permitido. Solo se permiten imágenes (JPEG, PNG, GIF, WebP).');
    }

    // Validar el tipo MIME real del archivo
    $tipoMime = mime_content_type($archivo['tmp_name']);
    if (!in_array($tipoMime, $tiposMimePermitidos)) {
        handleUploadError('El tipo de archivo no es válido.');
    }

    // Validar el tamaño del archivo
    if ($archivo['size'] > $tamanoMaximo) {
        handleUploadError('El archivo excede el tamaño máximo permitido de 5 MB.');
    }

    // Generar un nombre de archivo único
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid() . '.' . $extension;
    $rutaDestino = $directorioSubida . $nombreArchivo;

    // Mover el archivo subido a su destino
    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        // Construir la URL pública de la imagen
        $urlImagen = $urlBase . $nombreArchivo;

        // Devolver la URL de la imagen subida
        header('Content-Type: application/json');
        echo json_encode(['url' => $urlImagen]);
    } else {
        // Error al mover el archivo
        handleUploadError('No se pudo mover el archivo subido. Intente nuevamente.');
    }
} else {
    // Método de solicitud no permitido
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Método no permitido. Utilice POST para subir archivos.']);
}

function handleUploadError($mensaje) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => $mensaje]));
}
?>
