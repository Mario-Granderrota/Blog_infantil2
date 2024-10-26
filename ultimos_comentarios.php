<?php
// ultimos_comentarios.php
require_once 'config.php';
require_once 'funciones.php';

// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario es editor basado en la IP
if (!esEditor()) {
    header("Location: index.php");
    exit;
}

// Obtener los últimos 15 comentarios
try {
    $comentarios = obtenerUltimosComentarios(15);
} catch (Exception $e) {
    $comentarios = [];
    $error = 'Error al obtener los comentarios: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// Verificar si hay un mensaje en la sesión
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
} else {
    $mensaje = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Últimos Comentarios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsive -->
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Estilos específicos para los botones de borrar */
        .button-borrar {
            background-color: #f44336; /* Rojo */
            color: white;
            border: none;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .button-borrar:hover {
            background-color: #d32f2f;
        }

        /* Estilos para la lista de comentarios */
        ul.lista-comentarios {
            list-style-type: none;
            padding: 0;
        }

        ul.lista-comentarios li {
            background-color: #f9f9f9;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        ul.lista-comentarios li h3 {
            margin-top: 0;
        }

        ul.lista-comentarios li p {
            margin-bottom: 10px;
        }

        ul.lista-comentarios li .acciones {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Últimos Comentarios</h1>
        </header>
        <nav>
            <button id="menu-toggle">☰ Menú</button>
            <ul id="nav-menu">
                <li><a href="admin.php">Panel de Administración</a></li>
                <li><a href="index.php">Inicio</a></li>
                <!-- La editora no necesita cerrar sesión -->
            </ul>
        </nav>
        <main>
            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?= htmlspecialchars($mensaje['tipo'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($mensaje['texto'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="mensaje error">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if (empty($comentarios)): ?>
                <p>No hay comentarios recientes.</p>
            <?php else: ?>
                <ul class="lista-comentarios">
                    <?php foreach ($comentarios as $comentario): ?>
                        <li>
                            <h3>Entrada: 
                                <a href="entrada.php?slug=<?= urlencode($comentario['entrada_slug']); ?>">
                                    <?= htmlspecialchars($comentario['entrada_titulo'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </h3>
                            <p><strong>Usuario:</strong> <?= htmlspecialchars($comentario['usuario'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>Fecha:</strong> <?= htmlspecialchars($comentario['fecha'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><?= nl2br(htmlspecialchars($comentario['texto'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <div class="acciones">
                                <!-- Botón para borrar el comentario -->
                                <a href="acciones.php?accion=borrar_comentario&entrada_slug=<?= urlencode($comentario['entrada_slug']); ?>&comentario_id=<?= urlencode($comentario['id']); ?>" 
                                   class="button-borrar" 
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar este comentario?');">
                                    Borrar
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
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
    </script>
</body>
</html>
