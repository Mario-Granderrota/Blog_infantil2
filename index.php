<?php
require_once 'config.php';
require_once 'funciones.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Establecer tiempo de inactividad permitido (2,5 minutos = 150 segundos)
$tiempo_limite_inactividad = 150;

// Función para actualizar el tiempo de última actividad
function actualizarTiempoUltimaActividad() {
    $_SESSION['tiempo_ultima_actividad'] = time();
}

// Función para verificar si el usuario es administrador
function esAdministrador() {
    // Asumiendo que el rol del usuario está almacenado en 'usuario_rol'
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

// Si es una solicitud AJAX para actualizar el tiempo de actividad
if (isset($_GET['actualizar_actividad']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    actualizarTiempoUltimaActividad();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit();
}

// Comprobar si la sesión está activa y si se debe cerrar por inactividad
if (isset($_SESSION['tiempo_ultima_actividad'])) {
    $tiempo_transcurrido = time() - $_SESSION['tiempo_ultima_actividad'];

    if ($tiempo_transcurrido > $tiempo_limite_inactividad && !esAdministrador()) {
        // Guardar mensaje antes de destruir la sesión
        $_SESSION['mensaje'] = 'Tu sesión ha expirado por inactividad.';
        $_SESSION['tipo_mensaje'] = 'warning';

        // Limpiar variables de sesión pero mantener el mensaje
        $_SESSION = array();

        // Destruir la cookie de sesión si existe
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destruir la sesión
        session_destroy();

        // Iniciar nueva sesión para el mensaje
        session_start();
        $_SESSION['mensaje'] = 'Tu sesión ha expirado por inactividad.';
        $_SESSION['tipo_mensaje'] = 'warning';

        // Redirigir a login.php
        header('Location: login.php');
        exit();
    }
}

// Actualizar la marca de tiempo de la última actividad en varias situaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET) || 
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    isset($_SERVER['HTTP_REFERER'])) {
    actualizarTiempoUltimaActividad();
}

// Verificar acceso
if (!esEditor() && !esUsuario()) {
    // Si no es editor (por IP) y no ha iniciado sesión como usuario, redirigir a login.php
    redirigir('login.php', 'Debes iniciar sesión para acceder al blog.', 'warning');
    exit();
}

// Paginación
$entradasPorPagina = 5;
$paginaActual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?: 1;
$inicio = ($paginaActual - 1) * $entradasPorPagina;

// Obtener todas las entradas
$todasLasEntradas = obtenerEntradas();
$totalEntradas = count($todasLasEntradas);

// Paginar las entradas en memoria
$entradas = array_slice($todasLasEntradas, $inicio, $entradasPorPagina);

// Calcular total de páginas
$totalPaginas = ceil($totalEntradas / $entradasPorPagina);

// Generar un nuevo token CSRF para la acción de eliminación
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Incluir el header
$titulo_pagina = htmlspecialchars(NOMBRE_SITIO, ENT_QUOTES, 'UTF-8');
include 'header.php';
?>

<div class="entradas-container">
    <?php if (!empty($entradas)): ?>
        <?php foreach ($entradas as $entrada): ?>
            <article class="entrada">
                <div class="entrada-contenido">
                    <?php
                    // Extraer la primera imagen del contenido
                    $imagenSrc = extraerPrimeraImagen($entrada['contenido']);
                    if ($imagenSrc): 
                        // Asegurarse de que la ruta de la imagen sea absoluta
                        $imagenUrl = construirUrlImagen($imagenSrc);
                    ?>
                        <div class="entrada-imagen">
                            <img src="<?= htmlspecialchars($imagenUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Imagen de <?= htmlspecialchars($entrada['titulo'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="entrada-texto">
                        <h2><?= htmlspecialchars($entrada['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p>
                            <small>Por <?= htmlspecialchars($entrada['autor'] ?? 'Anónimo', ENT_QUOTES, 'UTF-8') ?> el <?= date('d/m/Y', strtotime($entrada['fecha'])) ?></small>
                        </p>
                        <p><?= htmlspecialchars(substr(strip_tags($entrada['contenido']), 0, 150), ENT_QUOTES, 'UTF-8') ?>...</p>
                        <a href="entrada.php?slug=<?= urlencode($entrada['slug']) ?>" class="button">Leer más</a>
                        <?php if (esEditor()): ?>
                            <form action="acciones.php" method="POST" style="display:inline;">
                                <input type="hidden" name="accion" value="borrar_entrada">
                                <input type="hidden" name="slug" value="<?= htmlspecialchars($entrada['slug'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="button eliminar-entrada" onclick="return confirm('¿Estás seguro de que deseas eliminar esta entrada?');">Eliminar Entrada</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($totalPaginas > 1): ?>
            <nav class="paginacion" aria-label="Navegación de páginas">
                <?php if ($paginaActual > 1): ?>
                    <a href="?pagina=<?= $paginaActual - 1 ?>" aria-label="Página anterior">&laquo; Anterior</a>
                <?php endif; ?>

                <?php 
                $rangoInicio = max(1, $paginaActual - 2);
                $rangoFin = min($totalPaginas, $paginaActual + 2);
                if ($rangoInicio > 1): 
                ?>
                    <a href="?pagina=1">1</a>
                    <?php if ($rangoInicio > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $rangoInicio; $i <= $rangoFin; $i++): ?>
                    <?php if ($i == $paginaActual): ?>
                        <span aria-current="page"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php 
                if ($rangoFin < $totalPaginas): 
                    if ($rangoFin < $totalPaginas - 1): 
                ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?pagina=<?= $totalPaginas ?>"><?= $totalPaginas ?></a>
                <?php endif; ?>

                <?php if ($paginaActual < $totalPaginas): ?>
                    <a href="?pagina=<?= $paginaActual + 1 ?>" aria-label="Página siguiente">Siguiente &raquo;</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <p>No hay entradas disponibles.</p>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables para el control de inactividad
        let tiempoUltimaActividad = Date.now();
        const intervaloActualizacion = 30000; // 30 segundos
        const tiempoLimiteInactividad = <?php echo $tiempo_limite_inactividad * 1000; ?>; // Convertir a milisegundos
        let temporizadorInactividad;
        
        // Función para actualizar el tiempo de actividad
        async function actualizarActividad() {
            const ahora = Date.now();
            if (ahora - tiempoUltimaActividad >= intervaloActualizacion) {
                tiempoUltimaActividad = ahora;
                try {
                    const response = await fetch('index.php?actualizar_actividad=1', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error('Error en la actualización de actividad');
                    }
                    
                    const data = await response.json();
                    if (data.status !== 'success') {
                        window.location.href = 'login.php';
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            }
        }

        // Función para reiniciar el temporizador de inactividad
        function reiniciarTemporizadorInactividad() {
            clearTimeout(temporizadorInactividad);
            temporizadorInactividad = setTimeout(() => {
                window.location.href = 'login.php';
            }, tiempoLimiteInactividad);
            actualizarActividad();
        }

        // Eventos para detectar actividad del usuario
        const eventos = ['mousemove', 'keypress', 'scroll', 'click', 'touchstart'];
        eventos.forEach(evento => {
            document.addEventListener(evento, reiniciarTemporizadorInactividad);
        });

        // Iniciar el temporizador
        reiniciarTemporizadorInactividad();

        // Verificar periódicamente la sesión
        setInterval(actualizarActividad, intervaloActualizacion);
    });
</script>

<?php 
// Función para extraer la primera imagen del contenido
function extraerPrimeraImagen($contenido) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . mb_convert_encoding($contenido, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();
    $imagenes = $dom->getElementsByTagName('img');
    return $imagenes->length > 0 ? $imagenes->item(0)->getAttribute('src') : '';
}

// Función para construir la URL absoluta de una imagen
function construirUrlImagen($src) {
    // Verificar si la ruta ya es absoluta
    if (filter_var($src, FILTER_VALIDATE_URL)) {
        return $src;
    }
    // Asegurarse de que la ruta comience con '/'
    $src = '/' . ltrim($src, '/');
    return rtrim(URL_BASE, '/') . $src;
}

include 'footer.php'; 
?>
