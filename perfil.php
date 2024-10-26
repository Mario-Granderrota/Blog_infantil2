<?php
require_once 'config.php';
require_once 'funciones.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Establecer tiempo de inactividad permitido (1.5 minutos = 90 segundos)
$tiempo_limite_inactividad = 90;

// Función para verificar si el usuario es administrador
function esAdministrador() {
    // Asumiendo que el rol del usuario está almacenado en 'usuario_rol'
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

// Verificar si el usuario está autenticado
if (!esUsuario()) {
    redirigir('login.php');
}

$usuario = $_SESSION['usuario'];
$registroAccesos = obtenerRegistroAccesos($usuario);

// Eliminar el último acceso del array (el acceso actual)
if (!empty($registroAccesos)) {
    array_shift($registroAccesos);
}

// Función para escapar contenido
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Generar un nuevo token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?= h($usuario) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Incluir el archivo de estilos -->
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Estilos para el menú responsive */
        #nav-menu {
            display: none;
            list-style: none;
            padding: 0;
        }
        #nav-menu li {
            margin: 5px 0;
        }
        #nav-menu a {
            text-decoration: none;
            color: #333;
        }
        /* Estilos para mensajes */
        .mensaje {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .mensaje.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        /* Estilos para emojis */
        .emoji {
            font-size: 24px;
            margin: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Perfil de <?= h($usuario) ?></h1>
        </header>
        <nav>
            <button id="menu-toggle" aria-label="Abrir menú de navegación">☰ Menú</button>
            <ul id="nav-menu">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
        <main>
            <?php
            // Mostrar mensajes si existen
            if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo_mensaje'])) {
                echo '<div class="mensaje ' . h($_SESSION['tipo_mensaje']) . '">' . h($_SESSION['mensaje']) . '</div>';
                unset($_SESSION['mensaje']);
                unset($_SESSION['tipo_mensaje']);
            }
            ?>
            <h2>Historial de Accesos</h2>

            <div class="info-accesos">
                <p><strong>Nota:</strong> Aquí se muestran tus últimos 4 accesos, anteriores a la sesión actual. 
                Tu acceso más reciente no se mostrará hasta que inicies sesión nuevamente.</p>
            </div>

            <div class="mensaje-advertencia">
                <strong>⚠️ Atención:</strong> Recuerda los emojis que seleccionaste en tus accesos anteriores. 
                Si observas que los emojis o los horarios no coinciden con tus accesos habituales, 
                avisa a la editora para restablecer tu contraseña y asegurarte de que nadie más haya accedido a tu cuenta.
            </div>

            <?php if (!empty($registroAccesos)): ?>
                <?php foreach ($registroAccesos as $acceso): ?>
                    <div class="acceso-item">
                        <strong>Fecha:</strong> <?= h($acceso['fecha']) ?><br>
                        <strong>Emojis seleccionados:</strong>
                        <?php
                        $emojis = $acceso['emojis'];
                        if (!is_array($emojis)) {
                            $emojis = json_decode($emojis, true);
                            if (!is_array($emojis)) {
                                $emojis = [$emojis];  // En caso de que sea un solo emoji
                            }
                        }
                        ?>
                        <?php foreach ($emojis as $emoji): ?>
                            <span class="emoji"><?= h($emoji) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay registros de accesos anteriores.</p>
            <?php endif; ?>

            <a href="index.php" class="button">Ir al Blog</a>
        </main>
    </div>

    <script>
    // JavaScript para el menú responsive
    document.getElementById('menu-toggle').addEventListener('click', function() {
        var menu = document.getElementById('nav-menu');
        if (menu.style.display === 'block' || menu.style.display === '') {
            menu.style.display = 'none';
        } else {
            menu.style.display = 'block';
        }
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
                    const response = await fetch('perfil.php?actualizar_actividad=1', {
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

</body>
</html>
