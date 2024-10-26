<?php
// admin.php
require_once 'config.php'; // Incluye config.php que a su vez incluye funciones.php

// Verificar si es editor basado en la IP
if (!esEditor()) {
    redirigir('index.php', 'Acceso no autorizado.', 'warning');
    exit;
}

// Obtener estadísticas básicas para el panel de administración
try {
    $totalEntradas = count(obtenerEntradas());
} catch (Exception $e) {
    $totalEntradas = 'Error al obtener';
}

try {
    $totalUsuarios = count(obtenerUsuarios());
} catch (Exception $e) {
    $totalUsuarios = 'Error al obtener';
}

try {
    $totalComentarios = obtenerTotalComentarios(); // Asegúrate de que esta función esté definida correctamente
} catch (Exception $e) {
    $totalComentarios = 'Error al obtener';
}

// No es necesario manejar mensajes de sesión para la editora
$mensaje = '';
?>
<!DOCTYPE html>
<html lang="es" data-theme="claro">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
    <!-- Estilos adicionales para el panel de administración -->
    <style>
        /* Estilos específicos para el panel de administración */
        /* Aquí puedes agregar los estilos específicos */
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Panel de Administración</h1>
        </header>
        <nav>
            <button id="menu-toggle">☰ Menú</button>
            <ul id="nav-menu">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="admin.php">Panel de Administración</a></li>
                <!-- La editora no necesita cerrar sesión -->
            </ul>
        </nav>
        <main>
            <?php if (!empty($mensaje)): ?>
                <div class="mensaje-exito"><?= htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>
            <!-- Sección de estadísticas básicas -->
            <div class="stats">
                <div>
                    <h3>Total de Entradas</h3>
                    <p><?= htmlspecialchars($totalEntradas); ?></p>
                </div>
                <div>
                    <h3>Total de Usuarios</h3>
                    <p><?= htmlspecialchars($totalUsuarios); ?></p>
                </div>
                <div>
                    <h3>Total de Comentarios</h3>
                    <p><?= htmlspecialchars($totalComentarios); ?></p>
                </div>
            </div>
            <ul>
                <li><a href="nueva_entrada.php" class="button">📝 Nueva Entrada</a></li>
                <li><a href="gestionar_usuarios.php" class="button">👥 Gestionar Usuarios</a></li>
                <li><a href="ultimos_comentarios.php" class="button">💬 Últimos Comentarios</a></li>
                <li><a href="index.php" class="button">🏠 Ver Blog</a></li>
            </ul>
            <!-- Mensajes informativos para el administrador -->
            <p>Bienvenida al panel de administración. Desde aquí puedes gestionar las entradas del blog, administrar usuarios y realizar otras tareas de mantenimiento.</p>
            <p>Recuerda revisar periódicamente las entradas y usuarios para mantener el contenido actualizado y seguro.</p>
        </main>
    </div>
    <footer>
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(NOMBRE_SITIO) ?>. Todos los derechos reservados.</p>
    </footer>
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
