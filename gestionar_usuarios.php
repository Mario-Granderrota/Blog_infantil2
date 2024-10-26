<?php
require_once 'config.php';
require_once 'funciones.php';

// Verificar si es editor
if (!esEditor()) {
    header("Location: index.php");
    exit;
}

$error = $success = '';

// Procesar el formulario de registro de nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);
    $contrasena = filter_input(INPUT_POST, 'contrasena', FILTER_UNSAFE_RAW);

    if ($usuario && $contrasena) {
        try {
            if (registrarUsuario($usuario, $contrasena)) {
                $success = 'Usuario registrado exitosamente.';
            } else {
                $error = 'El usuario ya existe.';
            }
            // Volver a cargar la lista de usuarios para mostrar la actualización
            $usuarios = obtenerUsuarios();
        } catch (Exception $e) {
            $error = 'Error al registrar el usuario: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = 'Debes completar todos los campos.';
    }
}

// Procesar la solicitud de eliminación de usuario
if (isset($_GET['accion']) && $_GET['accion'] === 'borrar_usuario') {
    $usuarioABorrar = filter_input(INPUT_GET, 'usuario', FILTER_SANITIZE_STRING);
    if ($usuarioABorrar) {
        try {
            if (borrarUsuario($usuarioABorrar)) {
                $success = 'Usuario eliminado exitosamente.';
            } else {
                $error = 'Error al eliminar el usuario. Puede que el usuario no exista.';
            }
        } catch (Exception $e) {
            $error = 'Error al eliminar el usuario: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = 'Parámetros inválidos para eliminar el usuario.';
    }
}

// Obtener la lista de usuarios después de procesar el formulario
$usuarios = obtenerUsuarios();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Gestión de Usuarios</h1>
        </header>
        <nav>
            <button id="menu-toggle">☰ Menú</button>
            <ul id="nav-menu">
                <li><a href="admin.php">Panel de Administración</a></li>
            </ul>
        </nav>
        <main>
            <?php if ($error): ?>
                <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mensaje success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <h2>Registrar Nuevo Usuario</h2>
            <form method="post">
                <label for="usuario">Usuario:</label>
                <input type="text" name="usuario" id="usuario" required>

                <label for="contrasena">Contraseña:</label>
                <input type="password" name="contrasena" id="contrasena" required>

                <button type="submit" class="button">Registrar</button>
            </form>

            <h2>Usuarios Registrados</h2>
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $nombreUsuario => $datosUsuario): ?>
                        <tr>
                            <td><?= htmlspecialchars($nombreUsuario) ?></td>
                            <td>
                                <a href="gestionar_usuario.php?accion=borrar_usuario&usuario=<?= urlencode($nombreUsuario) ?>" 
                                   onclick="return confirm('¿Estás seguro de eliminar este usuario?');" 
                                   class="button">Borrar</a>
                                <a href="modificar_contrasena.php?usuario=<?= urlencode($nombreUsuario) ?>" 
                                   class="button">Modificar Contraseña</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
    <script>
    document.getElementById('menu-toggle').addEventListener('click', function() {
        var menu = document.getElementById('nav-menu');
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    });
    </script>
</body>
</html>
