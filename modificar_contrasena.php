<?php
require_once 'config.php';
require_once 'funciones.php';

// Verificar si es editor
if (!esEditor()) {
    header("Location: index.php");
    exit;
}

$error = '';
$exito = '';
$nombreUsuario = filter_input(INPUT_GET, 'usuario', FILTER_SANITIZE_STRING);

if (!$nombreUsuario) {
    header("Location: gestionar_usuarios.php");
    exit;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevaContrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmarContrasena = $_POST['confirmar_contrasena'] ?? '';

    if ($nuevaContrasena === '' || $confirmarContrasena === '') {
        $error = 'Por favor, completa todos los campos.';
    } elseif ($nuevaContrasena !== $confirmarContrasena) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        if (modificarContrasenaUsuario($nombreUsuario, $nuevaContrasena)) {
            $exito = 'Contraseña modificada exitosamente.';
            $_SESSION['mensaje'] = 'Contraseña modificada exitosamente.';
            header("Location: gestionar_usuarios.php");
            exit;
        } else {
            $error = 'Error al modificar la contraseña.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Contraseña</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .mensaje {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .error {
            background-color: #f2dede;
            border-color: #ebccd1;
            color: #a94442;
        }
        .exito {
            background-color: #dff0d8;
            border-color: #d6e9c6;
            color: #3c763d;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Modificar Contraseña de <?php echo htmlspecialchars($nombreUsuario); ?></h1>
        </header>
        <nav>
            <a href="gestionar_usuarios.php">Volver a Gestión de Usuarios</a>
        </nav>
        <main>
            <?php if ($error): ?>
                <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($exito): ?>
                <div class="mensaje exito"><?php echo htmlspecialchars($exito); ?></div>
            <?php else: ?>
                <form method="post">
                    <label for="nueva_contrasena">Nueva Contraseña:</label>
                    <input type="password" name="nueva_contrasena" id="nueva_contrasena" required>
                    
                    <label for="confirmar_contrasena">Confirmar Contraseña:</label>
                    <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" required>
                    
                    <button type="submit" class="button">Modificar Contraseña</button>
                </form>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
