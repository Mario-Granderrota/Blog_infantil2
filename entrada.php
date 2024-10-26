<?php
require_once 'config.php';
require_once 'funciones.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Establecer tiempo de inactividad permitido (2,5 minutos = 150 segundos)
$tiempo_limite_inactividad = 150;

// Función para verificar si el usuario es administrador
function esAdministrador() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

// Función para verificar la inactividad y gestionar la sesión
function verificarInactividad($tiempo_limite) {
    // Si no hay sesión iniciada o es administrador, no verificar inactividad
    if (!isset($_SESSION['usuario_id']) || esAdministrador()) {
        return;
    }

    // Si no existe tiempo de última actividad, establecerlo
    if (!isset($_SESSION['tiempo_ultima_actividad'])) {
        $_SESSION['tiempo_ultima_actividad'] = time();
        return;
    }

    // Calcular tiempo transcurrido
    $tiempo_transcurrido = time() - $_SESSION['tiempo_ultima_actividad'];

    // Si excede el límite, cerrar sesión
    if ($tiempo_transcurrido > $tiempo_limite) {
        // Guardar mensaje antes de destruir la sesión
        $mensaje = 'Tu sesión ha expirado por inactividad.';
        
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
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['tipo_mensaje'] = 'warning';
        
        // Redirigir a login.php
        header('Location: login.php');
        exit();
    }
}

// Función para actualizar el tiempo de última actividad
function actualizarTiempoActividad() {
    if (isset($_SESSION['usuario_id']) && !esAdministrador()) {
        $_SESSION['tiempo_ultima_actividad'] = time();
    }
}

// Si es una solicitud AJAX para actualizar el tiempo de actividad
if (isset($_GET['actualizar_actividad']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    actualizarTiempoActividad();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit();
}

// Verificar inactividad
verificarInactividad($tiempo_limite_inactividad);

// Actualizar tiempo de actividad en acciones específicas
if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET) || 
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    isset($_SERVER['HTTP_REFERER'])) {
    actualizarTiempoActividad();
}

// Verificar acceso
if (!esEditor() && !esUsuario()) {
    // Si no es editor (por IP) y no ha iniciado sesión como usuario, redirigir a login.php
    redirigir('login.php', 'Debes iniciar sesión para acceder al blog.', 'warning');
    exit();
}

// Obtener el slug de la entrada desde la URL
$slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (!$slug) {
    redirigir('index.php', 'Entrada no especificada.', 'error');
    exit();
}

// Obtener la entrada
$entrada = obtenerEntrada($slug);
if (!$entrada) {
    redirigir('index.php', 'La entrada no existe.', 'error');
    exit();
}

// Obtener las entradas para navegación
$allEntries = obtenerEntradas();
$currentIndex = array_search($entrada, $allEntries);

$previousEntry = $currentIndex < count($allEntries) - 1 ? $allEntries[$currentIndex + 1] : null;
$nextEntry = $currentIndex > 0 ? $allEntries[$currentIndex - 1] : null;

// Procesar comentario si se envía el formulario
$errorComentario = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['actualizar_actividad'])) {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Error de validación CSRF');
    }

    $comentarioTexto = trim($_POST['comentario']);
    $comentarioTexto = strip_tags($comentarioTexto); // Eliminar etiquetas HTML

    if (empty($comentarioTexto)) {
        $errorComentario = 'El comentario no puede estar vacío.';
    } else {
        $comentario = [
            'usuario' => $_SESSION['usuario'] ?? 'Anónimo',
            'fecha' => date('Y-m-d H:i:s'),
            'texto' => $comentarioTexto,
            'entrada_slug' => $slug
        ];
        try {
            guardarComentario($slug, $comentario);
            // Regenerar el ID de sesión después de una acción importante
            session_regenerate_id(true);
            redirigir("entrada.php?slug=" . urlencode($slug), 'Comentario agregado exitosamente.', 'success');
        } catch (Exception $e) {
            $errorComentario = 'Error al guardar el comentario: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

// Obtener los comentarios de la entrada
$comentarios = obtenerComentarios($slug);

// Generar un nuevo token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Incluir el header
$titulo_pagina = htmlspecialchars($entrada['titulo'], ENT_QUOTES, 'UTF-8');
include 'header.php';
?>

<div class="entrada-container">
    <article class="entrada-detalle">
        <h1><?= $titulo_pagina ?></h1>
        <p>
            <small>Por <?= htmlspecialchars($entrada['autor'] ?? 'Anónimo', ENT_QUOTES, 'UTF-8') ?> el <?= date('d/m/Y', strtotime($entrada['fecha'])) ?></small>
        </p>
        <div class="contenido">
            <?php
            // Corregir rutas de imágenes en el contenido
            $contenido = htmlspecialchars_decode($entrada['contenido']);
            // Ajustar rutas de imágenes relativas
            $contenido = preg_replace_callback('/src="(?!http)([^"]+)"/i', function($matches) {
                $src = $matches[1];
                // Si la ruta no comienza con '/', agregar '/'
                if ($src[0] !== '/') {
                    $src = '/' . $src;
                }
                return 'src="' . htmlspecialchars(URL_BASE . $src, ENT_QUOTES, 'UTF-8') . '"';
            }, $contenido);
            echo $contenido;
            ?>
        </div>

        <!-- Botones de navegación entre entradas -->
        <div class="navegacion-entradas">
            <?php if ($previousEntry): ?>
                <a href="entrada.php?slug=<?= urlencode($previousEntry['slug']) ?>" class="button">⟵ Entrada anterior</a>
            <?php endif; ?>
            <?php if ($nextEntry): ?>
                <a href="entrada.php?slug=<?= urlencode($nextEntry['slug']) ?>" class="button">Entrada siguiente ⟶</a>
            <?php endif; ?>
        </div>

        <?php if (esEditor()): ?>
            <a href="editar_entrada.php?slug=<?= urlencode($entrada['slug']) ?>" class="button">Editar Entrada</a>
        <?php endif; ?>
    </article>

    <section class="comentarios">
        <h2>Comentarios</h2>
        <?php if (!empty($comentarios)): ?>
            <?php foreach ($comentarios as $comentario): ?>
                <div class="comentario">
                    <p><strong><?= htmlspecialchars($comentario['usuario'], ENT_QUOTES, 'UTF-8') ?></strong> el <?= date('d/m/Y H:i', strtotime($comentario['fecha'])) ?></p>
                    <p><?= nl2br(htmlspecialchars($comentario['texto'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
        <?php endif; ?>
        <h3>Agregar un comentario</h3>
        <?php if (!empty($errorComentario)): ?>
            <div class="mensaje error"><?= htmlspecialchars($errorComentario, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?slug=<?= urlencode($slug) ?>" accept-charset="UTF-8">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <label for="comentario">Comentario:</label>
            <textarea name="comentario" id="comentario" rows="5" required></textarea>

            <!-- Botón para mostrar/ocultar los emojis -->
            <button type="button" id="toggle-emojis" class="button">Emojis</button>

            <!-- Selector de emojis -->
            <div class="emoji-selector" id="emoji-selector" style="display: none;">
                <?php
            // Categorías de emojis organizados en arrays
                $emojisPorCategoria = [
                    "Caritas felices" => ['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','😋','😍','🥰','😘','😗','😙','😚','🙂','🤗','🤩','😇','🥳'],
                    "Caritas con accesorios" => ['😷','🤠','😎','🧐','🤓','🥸'],
                    "Caritas de salud y malestar" => ['🤢','🤮','🤧','😷','🤒','🤕','🥵','🥶','🥴','😵','😵‍💫','🫠','😪','😴','🤐','😮‍💨'],
                    "Caritas pensativas y sugerentes" => ['🤔','🤨','😐','😑','😶','🙄','😏','😒','😌','🧐','😬','🤥'],
                    "Caritas de frustración y tristeza" => ['☹️','🙁','😕','😟','😔','😞','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','😱','😨','😰','😥','😓','🤯'],
                    "Caritas de sorpresa y asombro" => ['😮','😯','😲','😳','😦','😧','😨','🫢','🫣'],
                    "Animales terrestres" => ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🦍','🦧','🐆','🐅','🦓','🐘','🦏','🦒','🐊','🦘'],
                    "Animales marinos" => ['🦈','🐬','🐳','🐙','🦑'],
                    "Aves e insectos" => ['🐔','🐧','🐦','🐤','🐣','🐥','🦋'],
                    "Animales mitológicos o extintos" => ['🦄','🦖','🦕','🐉','🐲'],
                    "Plantas y flores" => ['🌵','🎄','🌲','🌳','🌴','🌱','🌿','☘️','🍀','🎍','🎋','🍃','🍂','🍁','🍄','🌾','💐','🌷','🌹','🥀','🌺','🌸','🌼','🌻','🪴'],
                    "Frutas y verduras" => ['🍇','🍈','🍉','🍊','🍋','🍌','🍍','🥭','🍎','🍏','🍐','🍑','🍒','🍓','🫐','🥝','🍅','🫒','🥑','🥦','🥬','🥒','🌶️','🫑','🥕','🧄','🧅','🥔','🍆','🥜','🌰'],
                    "Comida y bebidas" => ['🍔','🍟','🍕','🌭','🥪','🌮','🌯','🫔','🥙','🧆','🥚','🍳','🥘','🍲','🥣','🥗','🍿','🧈','🧂','🥫','🍦','🍧','🍨','🍩','🍪','🎂','🍰','🧁','🥧','🍫','🍬','🍭','🍮','🍯','☕','🫖','🍵','🧃','🥛','🍼','🧋','🧉','🍶','🍾','🍷','🍸','🍹','🍺','🍻','🥂','🥃','🥤'],
                    "Clima y fenómenos naturales" => ['☀️','🌤️','⛅','🌥️','☁️','🌦️','🌧️','⛈️','🌩️','🌨️','❄️','☃️','⛄','🌬️','💨','🌪️','🌫️','🌈','🔥','💥','☔','⚡','🌊','🌋'],
                    "Corazones y símbolos de amor" => ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','💌','🫶'],
                    "Símbolos de estados y efectos" => ['✨','💫','💥','💦','💨','💢','🕳️','💤','〰️'],
                    "Símbolos musicales" => ['🎵','🎶'],
                    "Transporte terrestre" => ['🚗','🚕','🚙','🚌','🚎','🏎️','🚓','🚑','🚒','🚐','🚚','🚛','🚜','🏍️','🛵','🚲','🛴','🚂','🚆','🚊','🚉','🚝','🚡','🚠'],
                    "Transporte aéreo" => ['✈️','🛫','🛬','🛩️','🚁'],
                    "Transporte acuático" => ['🛳️','⛴️','🚢','🛶','⛵','🚤','🛥️','⚓'],
                    "Espacio y astronomía" => ['🌍','🌎','🌏','🌞','🌠','🌌','☄️','🚀','👨‍🚀','👩‍🚀','🛸','🛰️'],
                    "Cuerpos celestes y fenómenos espaciales" => ['🌕','🌖','🌗','🌘','🌑','🌒','🌓','🌔','🌚','🌝'],
                    "Deportes y actividades físicas" => ['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🏊','🏄','🚣','🎣','🤿','⛷️','🏂','🛷','⛸️','🏒','🥌','🏓','🏸','🥊','🥋','🏹','🎳','🏏','🏑','🥍','🚴','🤸','🤼','🤾','🤹','🎽'],
                    "Tecnología personal y electrónica" => ['📱','💻','⌨️','🖥️','🖨️','📷','📹','🎥','📺','📻','🎙️','🎚️','🎛️','📡','🔋','🔌','💡','🔦','🕯️'],
                    "Objetos cotidianos" => ['📚','📖','🖊️','✏️','🔍','🔑','🗝️','🧸','🪑','🛏️','🛋️','🚪','🪞','🪟','🧺','🧻','🧼','🧽','🧴','🛁','🚿','🚽','🧹','🧶','🧵','🪡','👓','🕶️','🥽','🧳'],
                    "Banderas y símbolos internacionales" => ['🏳️','🏴','🏁','🚩','🏳️‍🌈','🏳️‍⚧️','🇺🇳'],
                    "Símbolos de advertencia y regulación" => ['♻️','⚜️','🔱','📛','🔰','⭕','✅','❌','❓','❗','〽️','⚠️','🚸','☢️','☣️','🆚','🚫','🚭','🚯','🚳','🚱','🚷'],
                    "Manos y gestos" => ['👍','👎','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','👇','☝️','👋','🤚','🖐️','✋','🖖','👏','🙌','👐','🤲','🤝','🙏','✍️','🫰','🫶','🫷','🫸'],
                    "Festividades" => ['🎄','🎁','🎉','🎊','🎇','🎆','🧨','🎈','🥳','🎂','🎃','🪔','🕎','🎅','🤶','🧑‍🎄','🦃','🎐','🎏'],
                    "Profesiones y ocupaciones" => ['👨‍⚕️','👩‍⚕️','👨‍🏫','👩‍🏫','👨‍💻','👩‍💻','👨‍🌾','👩‍🌾','👨‍🍳','👩‍🍳','👨‍🔧','👩‍🔧','👨‍🏭','👩‍🏭','👨‍🚒','👩‍🚒','👮‍♂️','👮‍♀️','👨‍✈️','👩‍✈️','🧑‍⚖️','👨‍⚖️','👩‍⚖️','👨‍🚀','👩‍🚀','👨‍🎨','👩‍🎨','👨‍🔬','👩‍🔬','👨‍🎤','👩‍🎤','👨‍🦳','👩‍🦳','🧑‍🔧']
                ];
                ?>
                <?php foreach ($emojisPorCategoria as $categoria => $emojis): ?>
                    <div class="emoji-category">
                        <h4><?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?></h4>
                        <div class="emoji-container" style="display: none;">
                            <?php foreach ($emojis as $emoji): ?>
                                <span class="emoji" data-emoji="<?= htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="button">Enviar Comentario</button>
        </form>
    </section>

    <!-- Entradas recientes -->
    <section class="entradas-recientes">
        <h2>Entradas Recientes</h2>
        <div class="lista-entradas-recientes">
            <?php
            $entradasRecientes = array_slice($allEntries, 0, 5);
            foreach ($entradasRecientes as $entry):
                $entrySlug = $entry['slug'];
                $entryTitulo = htmlspecialchars($entry['titulo'], ENT_QUOTES, 'UTF-8');
                $entryFecha = date('d/m/Y', strtotime($entry['fecha']));
                // Extraer la primera imagen del contenido
                $entryContenido = htmlspecialchars_decode($entry['contenido']);
                $imagenSrc = extraerPrimeraImagen($entryContenido);
                if ($imagenSrc) {
                    // Construir URL absoluta de la imagen
                    $imagenUrl = construirUrlImagen($imagenSrc);
                }
                ?>
                <div class="entrada-resumen">
                    <a href="entrada.php?slug=<?= urlencode($entrySlug) ?>">
                        <?php if ($imagenSrc): ?>
                            <img src="<?= htmlspecialchars($imagenUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Imagen de <?= $entryTitulo ?>" style="width: 100px; height: auto;" loading="lazy">
                        <?php else: ?>
                            <img src="<?= htmlspecialchars(URL_BASE . '/ruta/a/imagen_predeterminada.jpg', ENT_QUOTES, 'UTF-8') ?>" alt="Imagen predeterminada" style="width: 100px; height: auto;" loading="lazy">
                        <?php endif; ?>
                        <h3><?= $entryTitulo ?></h3>
                        <p><small><?= $entryFecha ?></small></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<!-- Estilos adicionales para el botón y el selector de emojis -->
<style>
    /* Estilos para el botón de mostrar/ocultar emojis */
    #toggle-emojis {
        margin-top: 10px;
    }
    /* Estilos para la selección de emojis */
    .emoji-selector {
        margin-top: 10px;
        background-color: #f8f9fa;
        padding: 10px;
        border: 1px solid #ddd;
        max-height: 300px;
        overflow-y: auto;
    }
    .emoji-category {
        margin-bottom: 10px;
    }
    .emoji-category h4 {
        margin: 0;
        cursor: pointer;
        background-color: #e9ecef;
        padding: 5px;
        border-radius: 3px;
    }
    .emoji-container {
        display: flex;
        flex-wrap: wrap;
        padding: 5px 0;
    }
    .emoji {
        font-size: 24px;
        margin: 3px;
        cursor: pointer;
    }
</style>

<script>
    // JavaScript para manejar la interacción con las categorías y emojis

    // Mostrar/ocultar el selector completo de emojis al hacer clic en el botón
    document.getElementById('toggle-emojis').addEventListener('click', () => {
        const emojiSelector = document.getElementById('emoji-selector');
        emojiSelector.style.display = emojiSelector.style.display === 'none' || emojiSelector.style.display === '' ? 'block' : 'none';
    });

    // Mostrar/ocultar emojis al hacer clic en las categorías
    document.querySelectorAll('.emoji-category h4').forEach(categoryHeader => {
        categoryHeader.addEventListener('click', () => {
            const container = categoryHeader.nextElementSibling;
            container.style.display = container.style.display === 'none' ? 'flex' : 'none';
        });
    });

    // Agregar emoji al comentario al hacer clic
    document.querySelectorAll('.emoji').forEach(emoji => {
        emoji.addEventListener('click', event => {
            const emojiChar = event.target.getAttribute('data-emoji');
            const textarea = document.getElementById('comentario');
            textarea.value += emojiChar;
            textarea.focus();
        });
    });

    // Función para actualizar el tiempo de última actividad mediante una solicitud AJAX
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
                    const response = await fetch('entrada.php?actualizar_actividad=1', {
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
