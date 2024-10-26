<?php
// funciones.php

// ============================
// Configuración y constantes
// ============================

// Las rutas y constantes están definidas en config.php
require_once 'config.php';

// ============================
// Funciones de usuarios
// ============================

/**
 * Obtiene la lista de todos los usuarios.
 *
 * @return array Lista de usuarios.
 * @throws Exception Si hay un error al leer el archivo.
 */
function obtenerUsuarios() {
    $archivo = DIRECTORIO_USUARIOS . 'usuarios.json';
    if (!file_exists($archivo)) {
        return [];
    }
    $contenido = file_get_contents($archivo);
    if ($contenido === false) {
        throw new Exception("Error al leer el archivo de usuarios");
    }
    $usuarios = json_decode($contenido, true);
    if (!is_array($usuarios)) {
        $usuarios = [];
    }
    return $usuarios;
}

/**
 * Guarda la lista de usuarios en el archivo usuarios.json.
 *
 * @param array $usuarios Lista de usuarios a guardar.
 * @throws Exception Si ocurre un error al guardar el archivo.
 */
function guardarUsuarios($usuarios) {
    $archivo = DIRECTORIO_USUARIOS . 'usuarios.json';
    $contenido = json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($contenido === false) {
        throw new Exception("Error al codificar los usuarios a JSON");
    }
    $resultado = file_put_contents($archivo, $contenido);
    if ($resultado === false) {
        throw new Exception("Error al guardar los usuarios");
    }
}

/**
 * Registra un nuevo usuario.
 *
 * @param string $nombreUsuario Nombre de usuario.
 * @param string $contrasena Contraseña del usuario.
 * @return bool true si se registró correctamente, false si el usuario ya existe.
 * @throws Exception Si hay un error al registrar el usuario.
 */
function registrarUsuario($nombreUsuario, $contrasena) {
    $usuarios = obtenerUsuarios();
    if (isset($usuarios[$nombreUsuario])) {
        return false;
    }
    $usuarios[$nombreUsuario] = [
        'hash' => password_hash($contrasena, PASSWORD_DEFAULT),
        'fecha_registro' => date('Y-m-d H:i:s'),
        'rol' => 'usuario' // Asignar rol por defecto
    ];
    guardarUsuarios($usuarios);
    return true;
}

/**
 * Modifica la contraseña de un usuario existente.
 *
 * @param string $nombreUsuario Nombre de usuario.
 * @param string $nuevaContrasena Nueva contraseña del usuario.
 * @return bool true si se modificó correctamente, false si el usuario no existe.
 * @throws Exception Si hay un error al modificar la contraseña.
 */
function modificarContrasenaUsuario($nombreUsuario, $nuevaContrasena) {
    $usuarios = obtenerUsuarios();
    if (!isset($usuarios[$nombreUsuario])) {
        return false;
    }
    $usuarios[$nombreUsuario]['hash'] = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
    guardarUsuarios($usuarios);
    return true;
}

/**
 * Borra un usuario del sistema.
 *
 * @param string $nombreUsuario Nombre del usuario a borrar.
 * @return bool true si se borró correctamente, false si el usuario no existe.
 * @throws Exception Si hay un error al borrar el usuario.
 */
function borrarUsuario($nombreUsuario) {
    $usuarios = obtenerUsuarios();
    if (!isset($usuarios[$nombreUsuario])) {
        return false;
    }
    unset($usuarios[$nombreUsuario]);
    guardarUsuarios($usuarios);
    // Eliminar registro de accesos
    $archivoAccesos = DIRECTORIO_ACCESOS . sanitizarNombreArchivo($nombreUsuario) . '_accesos.json';
    if (file_exists($archivoAccesos)) {
        unlink($archivoAccesos);
    }
    return true;
}

/**
 * Autentica a un usuario utilizando su nombre de usuario y contraseña.
 *
 * @param string $nombreUsuario Nombre de usuario.
 * @param string $contrasena Contraseña del usuario.
 * @return string|false Rol del usuario si es autenticado, false en caso contrario.
 * @throws Exception Si hay un error al autenticar el usuario.
 */
function autenticarUsuario($nombreUsuario, $contrasena) {
    $usuarios = obtenerUsuarios();
    if (isset($usuarios[$nombreUsuario]) && password_verify($contrasena, $usuarios[$nombreUsuario]['hash'])) {
        return $usuarios[$nombreUsuario]['rol'] ?? 'usuario';
    }
    return false;
}

// ============================
// Funciones de autenticación
// ============================

/**
 * Verifica si el usuario es un editor basado en la dirección IP fija.
 *
 * @return bool true si es editor, false en caso contrario.
 */
function esEditor() {
    // Verificar si la IP del solicitante coincide con IP_EDITOR
    if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === IP_EDITOR) {
        return true;
    }
    return false;
}

/**
 * Verifica si el usuario tiene el rol de usuario autenticado.
 *
 * @return bool true si es usuario, false en caso contrario.
 */
function esUsuario() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'usuario';
}

/**
 * Redirige a una URL con un mensaje opcional.
 *
 * @param string $url URL de destino.
 * @param string $mensaje Mensaje opcional.
 * @param string $tipo Tipo de mensaje (info, success, warning, error).
 */
function redirigir($url, $mensaje = '', $tipo = 'info') {
    if (!empty($mensaje)) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['mensaje'] = [
            'tipo' => $tipo,
            'texto' => $mensaje
        ];
    }
    header("Location: $url");
    exit;
}

// ============================
// Funciones de entradas
// ============================

/**
 * Genera un slug único a partir de una cadena dada.
 *
 * @param string $cadena La cadena de la cual generar el slug.
 * @return string El slug generado.
 */
function generarSlug($cadena) {
    // Convertir a minúsculas
    $slug = mb_strtolower($cadena, 'UTF-8');
    // Eliminar acentos y caracteres especiales
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
    // Reemplazar caracteres no alfanuméricos por guiones
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    // Eliminar guiones duplicados
    $slug = preg_replace('/-+/', '-', $slug);
    // Eliminar guiones al inicio y al final
    $slug = trim($slug, '-');
    // Asegurar que el slug sea único
    $slugOriginal = $slug;
    $contador = 1;
    while (file_exists(DIRECTORIO_ENTRADAS . $slug . '.json')) {
        $slug = $slugOriginal . '-' . $contador;
        $contador++;
    }
    return $slug;
}

/**
 * Obtiene todas las entradas del blog.
 *
 * @return array Lista de entradas ordenadas por fecha descendente.
 * @throws Exception Si hay un error al leer las entradas.
 */
function obtenerEntradas() {
    $entradas = [];
    $archivos = glob(DIRECTORIO_ENTRADAS . '*.json');
    foreach ($archivos as $archivo) {
        $contenido = file_get_contents($archivo);
        if ($contenido === false) {
            throw new Exception("Error al leer el archivo de entrada: " . basename($archivo));
        }
        $entrada = json_decode($contenido, true);
        if (!is_array($entrada)) {
            continue; // Saltar entradas corruptas
        }
        $entradas[] = $entrada;
    }
    usort($entradas, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    return $entradas;
}

/**
 * Obtiene una entrada específica del blog.
 *
 * @param string $slug Slug de la entrada.
 * @return array|null Datos de la entrada o null si no existe.
 * @throws Exception Si hay un error al leer la entrada.
 */
function obtenerEntrada($slug) {
    $archivo = DIRECTORIO_ENTRADAS . sanitizarNombreArchivo($slug) . '.json';
    if (!file_exists($archivo)) {
        return null;
    }
    $contenido = file_get_contents($archivo);
    if ($contenido === false) {
        throw new Exception("Error al leer la entrada: $slug");
    }
    $entrada = json_decode($contenido, true);
    if (!is_array($entrada)) {
        throw new Exception("Error al decodificar la entrada: $slug");
    }
    return $entrada;
}

/**
 * Guarda una entrada en un archivo JSON.
 *
 * @param array $entrada Datos de la entrada a guardar.
 * @throws Exception Si ocurre un error al guardar la entrada.
 */
function guardarEntrada($entrada) {
    // Extraer imágenes del contenido y guardarlas en el array de la entrada
    $imagenes = extraerImagenesDelContenido($entrada['contenido']);
    $entrada['imagenes'] = $imagenes;

    $archivo = DIRECTORIO_ENTRADAS . sanitizarNombreArchivo($entrada['slug']) . '.json';
    $contenido = json_encode($entrada, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($contenido === false) {
        throw new Exception("Error al codificar la entrada a JSON: " . $entrada['slug']);
    }
    $resultado = file_put_contents($archivo, $contenido);
    if ($resultado === false) {
        throw new Exception("Error al guardar la entrada: " . $entrada['slug']);
    }
}

/**
 * Extrae las rutas de las imágenes del contenido de una entrada.
 *
 * @param string $contenido El contenido HTML de la entrada.
 * @return array Lista de rutas de imágenes encontradas.
 */
function extraerImagenesDelContenido($contenido) {
    $imagenes = [];
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $contenido);
    libxml_clear_errors();
    $imgs = $dom->getElementsByTagName('img');
    foreach ($imgs as $img) {
        $src = $img->getAttribute('src');
        if ($src) {
            $imagenes[] = $src;
        }
    }
    return $imagenes;
}

/**
 * Borra una entrada del blog y sus comentarios e imágenes asociadas.
 *
 * @param string $slug Slug de la entrada a borrar.
 * @throws Exception Si ocurre un error al borrar la entrada.
 */
function borrarEntrada($slug) {
    // Verificar que el usuario es un editor antes de permitir la eliminación
    if (!esEditor()) {
        throw new Exception("No tienes permisos para eliminar entradas.");
    }

    $archivo = DIRECTORIO_ENTRADAS . sanitizarNombreArchivo($slug) . '.json';
    if (!file_exists($archivo)) {
        throw new Exception("La entrada no existe: $slug");
    }

    // Obtener la entrada antes de eliminar el archivo
    $entrada = obtenerEntrada($slug);

    if (!unlink($archivo)) {
        throw new Exception("Error al borrar la entrada: $slug");
    }
    // Borrar comentarios asociados a la entrada
    $comentariosArchivos = glob(DIRECTORIO_COMENTARIOS . sanitizarNombreArchivo($slug) . '_*.json');
    foreach ($comentariosArchivos as $comentarioArchivo) {
        unlink($comentarioArchivo);
    }
    // Borrar imágenes asociadas
    if (isset($entrada['imagenes']) && is_array($entrada['imagenes'])) {
        foreach ($entrada['imagenes'] as $imagen) {
            $rutaImagen = __DIR__ . '/' . $imagen;
            if (file_exists($rutaImagen)) {
                unlink($rutaImagen);
            }
        }
    }
}

/**
 * Actualiza una entrada existente.
 *
 * @param string $slug Slug de la entrada a actualizar.
 * @param array $nuevosDatos Datos nuevos de la entrada.
 * @throws Exception Si ocurre un error al actualizar la entrada.
 */
function actualizarEntrada($slug, $nuevosDatos) {
    $entrada = obtenerEntrada($slug);
    if (!$entrada) {
        throw new Exception("La entrada no existe: $slug");
    }
    // Mantener el slug original
    $entrada['titulo'] = $nuevosDatos['titulo'];
    $entrada['contenido'] = $nuevosDatos['contenido'];
    $entrada['fecha'] = date('Y-m-d H:i:s'); // Actualizar la fecha
    guardarEntrada($entrada);
}

// ============================
// Funciones de comentarios
// ============================

/**
 * Guarda un comentario para una entrada específica.
 *
 * @param string $entradaSlug Slug de la entrada.
 * @param array $comentario Datos del comentario.
 * @throws Exception Si ocurre un error al guardar el comentario.
 */
function guardarComentario($entradaSlug, $comentario) {
    // Generar un ID único para el comentario
    $comentarioId = bin2hex(random_bytes(16));
    $comentario['id'] = $comentarioId;
    $comentario['entrada_slug'] = $entradaSlug;

    $archivo = DIRECTORIO_COMENTARIOS . sanitizarNombreArchivo($entradaSlug) . '_' . $comentarioId . '.json';
    $contenido = json_encode($comentario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($contenido === false) {
        throw new Exception("Error al codificar el comentario a JSON");
    }
    $resultado = file_put_contents($archivo, $contenido);
    if ($resultado === false) {
        throw new Exception("Error al guardar el comentario");
    }
}

/**
 * Obtiene los comentarios de una entrada específica.
 *
 * @param string $entradaSlug Slug de la entrada.
 * @return array Lista de comentarios.
 */
function obtenerComentarios($entradaSlug) {
    $comentarios = [];
    $archivos = glob(DIRECTORIO_COMENTARIOS . sanitizarNombreArchivo($entradaSlug) . '_*.json');
    foreach ($archivos as $archivo) {
        $contenido = file_get_contents($archivo);
        if ($contenido === false) {
            continue;
        }
        $comentario = json_decode($contenido, true);
        if (is_array($comentario)) {
            $comentarios[] = $comentario;
        }
    }
    // Ordenar los comentarios por fecha ascendente
    usort($comentarios, function($a, $b) {
        return strtotime($a['fecha']) - strtotime($b['fecha']);
    });
    return $comentarios;
}

/**
 * Obtiene los últimos comentarios para mostrar en el panel de administración.
 *
 * @param int $limite Número máximo de comentarios a obtener.
 * @return array Lista de comentarios.
 */
function obtenerUltimosComentarios($limite = 15) {
    $comentarios = [];
    $archivos = glob(DIRECTORIO_COMENTARIOS . '*.json');

    // Ordenar los archivos por fecha de modificación descendente
    usort($archivos, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $archivos = array_slice($archivos, 0, $limite);

    foreach ($archivos as $archivo) {
        $comentario = json_decode(file_get_contents($archivo), true);
        if ($comentario) {
            $comentario['entrada_titulo'] = obtenerTituloEntradaPorSlug($comentario['entrada_slug']);
            $comentarios[] = $comentario;
        }
    }
    return $comentarios;
}

/**
 * Borra un comentario específico de una entrada.
 *
 * @param string $entradaSlug Slug de la entrada.
 * @param string $comentarioId ID del comentario a borrar.
 * @throws Exception Si ocurre un error al borrar el comentario.
 */
function borrarComentario($entradaSlug, $comentarioId) {
    // Verificar que el usuario es un editor antes de permitir la eliminación
    if (!esEditor()) {
        throw new Exception("No tienes permisos para eliminar comentarios.");
    }

    $archivo = DIRECTORIO_COMENTARIOS . sanitizarNombreArchivo($entradaSlug) . '_' . sanitizarNombreArchivo($comentarioId) . '.json';
    if (!file_exists($archivo)) {
        throw new Exception("El comentario no existe: $comentarioId");
    }
    if (!unlink($archivo)) {
        throw new Exception("Error al borrar el comentario: $comentarioId");
    }
}

/**
 * Obtiene el título de una entrada a partir de su slug.
 *
 * @param string $slug Slug de la entrada.
 * @return string Título de la entrada o 'Título no disponible' si no se encuentra.
 */
function obtenerTituloEntradaPorSlug($slug) {
    $entrada = obtenerEntrada($slug);
    return $entrada ? $entrada['titulo'] : 'Título no disponible';
}

/**
 * Obtiene el total de comentarios en el blog.
 *
 * @return int Total de comentarios.
 * @throws Exception Si hay un error al leer los comentarios.
 */
function obtenerTotalComentarios() {
    $archivos = glob(DIRECTORIO_COMENTARIOS . '*.json');
    if ($archivos === false) {
        throw new Exception("Error al obtener los archivos de comentarios");
    }
    return count($archivos);
}

// ============================
// Funciones de registro de accesos
// ============================

/**
 * Obtiene el registro de accesos de un usuario.
 *
 * @param string $usuario Nombre del usuario.
 * @return array Lista de accesos del usuario.
 * @throws Exception Si hay un error al leer el registro de accesos.
 */
function obtenerRegistroAccesos($usuario) {
    $archivo = DIRECTORIO_ACCESOS . sanitizarNombreArchivo($usuario) . '_accesos.json';
    if (!file_exists($archivo)) {
        return [];
    }
    $contenido = file_get_contents($archivo);
    if ($contenido === false) {
        throw new Exception("Error al leer el registro de accesos: $usuario");
    }
    $registroAccesos = json_decode($contenido, true);
    if (!is_array($registroAccesos)) {
        $registroAccesos = [];
    }
    return $registroAccesos;
}

/**
 * Guarda el registro de accesos de un usuario.
 *
 * @param string $usuario Nombre del usuario.
 * @param array $registroAccesos Registro de accesos a guardar.
 * @throws Exception Si ocurre un error al guardar el registro de accesos.
 */
function guardarRegistroAccesos($usuario, $registroAccesos) {
    $archivo = DIRECTORIO_ACCESOS . sanitizarNombreArchivo($usuario) . '_accesos.json';
    $contenido = json_encode($registroAccesos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($contenido === false) {
        throw new Exception("Error al codificar el registro de accesos a JSON: $usuario");
    }
    $resultado = file_put_contents($archivo, $contenido);
    if ($resultado === false) {
        throw new Exception("Error al guardar el registro de accesos: $usuario");
    }
}

/**
 * Agrega un registro de acceso para un usuario.
 *
 * @param string $usuario Nombre del usuario.
 * @param array $emojis Emojis seleccionados en el acceso.
 * @throws Exception Si ocurre un error al guardar el registro.
 */
function agregarRegistroAcceso($usuario, $emojis) {
    $registroAccesos = obtenerRegistroAccesos($usuario);
    array_unshift($registroAccesos, [
        'fecha' => date('Y-m-d H:i:s'),
        'emojis' => $emojis
    ]);
    $registroAccesos = array_slice($registroAccesos, 0, 5); // Mantener solo los últimos 5 accesos
    guardarRegistroAccesos($usuario, $registroAccesos);
}

/**
 * Verifica que los emojis seleccionados no se repitan en los últimos 5 accesos.
 *
 * @param string $usuario Nombre del usuario.
 * @param array $emojisSeleccionados Emojis seleccionados en el acceso actual.
 * @return bool true si no se repiten, false si hay repetición.
 */
function verificarEmojisNoRepetidos($usuario, $emojisSeleccionados) {
    $registroAccesos = obtenerRegistroAccesos($usuario);
    foreach ($registroAccesos as $acceso) {
        if ($acceso['emojis'] === $emojisSeleccionados) {
            return false;
        }
    }
    return true;
}

// ============================
// Funciones de sanitización
// ============================

/**
 * Sanitiza una cadena de entrada.
 *
 * @param string $dato Cadena a sanitizar.
 * @return string Cadena sanitizada.
 */
function sanitizar($dato) {
    return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza un nombre de archivo, permitiendo solo caracteres válidos.
 *
 * @param string $nombre Nombre de archivo a sanitizar.
 * @return string Nombre de archivo sanitizado.
 */
function sanitizarNombreArchivo($nombre) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $nombre);
}

// ============================
// Funciones adicionales
// ============================

/**
 * Obtiene el nombre del autor basado en el rol o sesión.
 *
 * @return string Nombre del autor.
 */
function obtenerNombreAutor() {
    if (esEditor()) {
        return 'Editora'; // Puedes cambiar este valor por el nombre que desees
    } elseif (esUsuario()) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['usuario'] ?? 'Anónimo';
    } else {
        return 'Anónimo';
    }
}

// ============================
// Manejo de errores y excepciones
// ============================

/**
 * Maneja los errores y excepciones lanzando un mensaje y un código HTTP.
 *
 * @param string $mensaje Mensaje de error.
 * @param int $codigo Código de estado HTTP.
 */
function manejarError($mensaje, $codigo = 500) {
    http_response_code($codigo);
    error_log($mensaje);
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "Error: $mensaje";
    } else {
        echo "Ha ocurrido un error. Por favor, inténtelo de nuevo más tarde.";
    }
    exit;
}

// Configurar el manejador de excepciones no capturadas
set_exception_handler(function($e) {
    manejarError($e->getMessage(), $e->getCode() ?: 500);
});

// Configurar el manejador de errores de PHP
set_error_handler(function($nivel, $mensaje, $archivo, $linea) {
    if (error_reporting() & $nivel) {
        throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
    }
});

// Registrar el cierre del script para restaurar el manejador de errores
register_shutdown_function(function() {
    restore_error_handler();
    restore_exception_handler();
});
?>
