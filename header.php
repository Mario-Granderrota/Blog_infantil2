<?php
// header.php
if (!isset($titulo_pagina)) {
    $titulo_pagina = NOMBRE_SITIO;
}
// Verificar si hay un mensaje en la sesión (solo para usuarios)
$mensaje = '';
if (esUsuario()) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        unset($_SESSION['mensaje']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo_pagina) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsive -->
    <link rel="stylesheet" href="estilos.css">
    <style>
        header.edit-page-header {
            transition: background 0.3s ease;
        }
        header.edit-page-header h1 {
            transition: color 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="edit-page-header" id="dynamic-header">
            <h1 id="dynamic-header-title"><?= htmlspecialchars(NOMBRE_SITIO) ?></h1>
        </header>
        <nav>
            <?php if (empty($ocultar_menu)): ?>
                <button id="menu-toggle" aria-label="Abrir menú de navegación">☰ Menú</button>
                <ul id="nav-menu">
                    <?php if (esEditor()): ?>
                        <li><a href="admin.php">Panel de Administración</a></li>
                        <li><a href="index.php">Inicio</a></li>
                    <?php elseif (esUsuario()): ?>
                        <li><a href="perfil.php">Perfil</a></li>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="logout.php">Cerrar Sesión</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </nav>
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= htmlspecialchars($mensaje['tipo']) ?>">
                <?= htmlspecialchars($mensaje['texto']) ?>
            </div>
        <?php endif; ?>
        <main>
<script>
    const header = document.getElementById('dynamic-header');
    const headerTitle = document.getElementById('dynamic-header-title');
    
    function rgbToHex(r, g, b) {
        return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
    }
    
    function getLuminance(r, g, b) {
        return 0.299 * r + 0.587 * g + 0.114 * b;
    }
    
    document.addEventListener('mousemove', (e) => {
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;
        
        // Colores más intensos
        const leftColor = [255, 20, 147];  // Rosa más intenso
        const rightColor = [144, 224, 239];  // Azul claro original
        
        const r = Math.round(leftColor[0] * (1 - x) + rightColor[0] * x);
        const g = Math.round(leftColor[1] * (1 - x) + rightColor[1] * x);
        const b = Math.round(leftColor[2] * (1 - x) + rightColor[2] * x);
        
        const backgroundColor = `rgb(${r}, ${g}, ${b})`;
        header.style.background = backgroundColor;
        
        // Cambiar el color del texto de forma más progresiva
        const luminance = getLuminance(r, g, b);
        const textColor = luminance > 128 ? '#333' : '#fff';
        headerTitle.style.color = textColor;
    });
</script>
