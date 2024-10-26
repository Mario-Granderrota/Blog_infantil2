<?php
require_once 'config.php'; // Incluir el archivo de configuración
require_once 'funciones.php';

// Habilitar la visualización de errores (solo para depuración)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Establecer la codificación a UTF-8
header('Content-Type: text/html; charset=utf-8');

// Verificar si el usuario tiene permisos de editor
if (!esEditor()) {
    header("Location: index.php");
    exit;
}

$titulo = '';
$contenido = '';
$error = '';

// Procesar el formulario al enviarse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancelar'])) {
        header("Location: admin.php");
        exit;
    }

    $titulo = sanitizar($_POST['titulo'] ?? '');
    $contenido = $_POST['contenido'] ?? '';

    if ($titulo === '' || $contenido === '') {
        $error = "El título y el contenido no pueden estar vacíos.";
    } else {
        $slug = generarSlug($titulo);
        $entrada = [
            'slug' => $slug,
            'titulo' => $titulo,
            'contenido' => $contenido,
            'autor' => obtenerNombreAutor(), // Utiliza la función definida en funciones.php
            'fecha' => date('Y-m-d H:i:s')
        ];

        try {
            guardarEntrada($entrada);
            header("Location: admin.php");
            exit;
        } catch (Exception $e) {
            $error = "Error al guardar la entrada: " . $e->getMessage();
        }
    }
}
// Categorías de emojis organizados en arrays
$emojisPorCategoria = [
                    // Emociones y Caritas
                    "Caras Sonrientes" => ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '🫠', '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😙', '🥲'],
                    "Caritas con Accesorios" => ['😷', '🤠', '😎', '🧐', '🤓', '🥸', '🤡', '👻', '💀', '☠️', '👽', '🤖', '🎭', '🥳'],
                    "Caritas de Salud y Malestar" => ['🤢', '🤮', '🤧', '🤒', '🤕', '🥵', '🥶', '🥴', '😵', '😵‍💫', '😪', '😴', '🤐', '😮‍💨'],
                    "Caritas Pensativas" => ['🤔', '🤨', '😐', '😑', '😶', '🙄', '😏', '😒', '😌', '🧐', '😬', '🤥', '🫡', '🤫', '🤭'],
                    "Caritas de Tristeza y Frustración" => ['☹️', '🙁', '😕', '😟', '😔', '😞', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '😱', '😨', '😰', '😥', '😓', '🤯', '😶‍🌫️'],
                    "Caritas de Sorpresa" => ['😮', '😯', '😲', '😳', '😦', '😧', '😱', '🤯', '🫢', '🫣'],

                    // Animales
                    "Animales Terrestres" => ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🦍', '🦧', '🐆', '🐅', '🦓', '🐘', '🦏', '🦒', '🐊', '🦘', '🦬', '🦣', '🦫', '🦨', '🦡', '🦥'],
                    "Animales Marinos" => ['🦈', '🐬', '🐳', '🐋', '🐟', '🐠', '🐡', '🦭', '🐙', '🦑', '🦐', '🦞', '🦀', '🐚'],
                    "Aves e Insectos" => ['🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦋', '🐛', '🐌', '🐞', '🐜', '🪲', '🪳', '🦗', '🕷️', '🦅', '🦆', '🦢', '🦉', '🦤', '🪶'],
                    "Animales Mitológicos" => ['🦄', '🐉', '🐲', '🦖', '🦕', '🦩', '🦚', '🦜'],

                    // Naturaleza
                    "Plantas y Flores" => ['🌵', '🎄', '🌲', '🌳', '🌴', '🌱', '🌿', '☘️', '🍀', '🎍', '🎋', '🍃', '🍂', '🍁', '🍄', '🌾', '💐', '🌷', '🌹', '🥀', '🌺', '🌸', '🌼', '🌻', '🪴', '🪸', '🪷'],
                    "Frutas y Verduras" => ['🍇', '🍈', '🍉', '🍊', '🍋', '🍌', '🍍', '🥭', '🍎', '🍏', '🍐', '🍑', '🍒', '🍓', '🫐', '🥝', '🍅', '🫒', '🥑', '🥦', '🥬', '🥒', '🌶️', '🫑', '🥕', '🧄', '🧅', '🥔', '🍆', '🥜', '🌰', '🫘', '🥥'],

                    // Alimentos y Bebidas
                    "Comidas Preparadas" => ['🍔', '🍟', '🍕', '🌭', '🥪', '🌮', '🌯', '🫔', '🥙', '🧆', '🥚', '🍳', '🥘', '🍲', '🥣', '🥗', '🫕', '🥫'],
                    "Postres y Dulces" => ['🍦', '🍧', '🍨', '🍩', '🍪', '🎂', '🍰', '🧁', '🥧', '🍫', '🍬', '🍭', '🍮', '🍯', '🍡', '🍘', '🍙', '🍚'],
                    "Bebidas" => ['☕', '🫖', '🍵', '🧃', '🥛', '🍼', '🧋', '🧉', '🍶', '🍾', '🍷', '🍸', '🍹', '🍺', '🍻', '🥂', '🥃', '🥤', '🧊'],

                    // Clima y Fenómenos Naturales
                    "Clima y Fenómenos Naturales" => ['☀️', '🌤️', '⛅', '🌥️', '☁️', '🌦️', '🌧️', '⛈️', '🌩️', '🌨️', '❄️', '☃️', '⛄', '🌬️', '💨', '🌪️', '🌫️', '🌈', '🔥', '💥', '☔', '⚡', '🌊', '🌋', '☄️', '💫', '⭐', '🌟', '✨', '🌙', '🌚', '🌝'],

                    // Símbolos y Corazones
                    "Corazones y Amor" => ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '💌', '🫶', '💑', '💏', '💋'],
                    "Símbolos de Estados y Efectos" => ['✨', '💫', '💥', '💦', '💨', '💢', '🕳️', '💤', '〰️', '💬', '🗯️', '💭', '🔔', '🔕', '❗', '❓', '💯', '✅', '❌'],
                    "Símbolos Musicales" => ['🎵', '🎶', '🎼', '🎹', '🥁', '🎸', '🎺', '🎷', '🪗', '🪘', '🎻', '🪕'],

                    // Transporte
                    "Transporte Terrestre" => ['🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐', '🚚', '🚛', '🚜', '🏍️', '🛵', '🚲', '🛴', '🚂', '🚆', '🚊', '🚉', '🚝', '🚡', '🚠', '🛞', '🛻'],
                    "Transporte Aéreo" => ['✈️', '🛫', '🛬', '🛩️', '🚁', '🛸', '🪂', '🛺'],
                    "Transporte Acuático" => ['🛳️', '⛴️', '🚢', '🛶', '⛵', '🚤', '🛥️', '⚓', '🛟'],

                    // Gente y Partes del Cuerpo
                    "Personas y Gente" => ['👶', '👦', '👧', '👨', '👩', '👱‍♂️', '👱‍♀️', '🧓', '👴', '👵', '👮', '👷', '💂', '👳', '👲', '🧔', '🧕', '👰', '🤵', '🤰', '👼', '🤱', '🙇', '🧑‍🦰', '🧑‍🦱', '🧑‍🦳', '🧑‍🦲'],
                    "Partes del Cuerpo" => ['👁️', '👀', '👂', '👃', '👄', '👅', '🦷', '👋', '🤚', '🖐️', '✋', '✍️', '🤲', '🦾', '🦿', '🦵', '🦶', '🫀', '🫁', '🧠'],

                    // Viajes y Lugares
                    "Paisajes y Lugares" => ['🏕️', '🏖️', '🏜️', '🏝️', '🏞️', '🏟️', '🏛️', '🏗️', '🛤️', '🛣️', '🗺️', '🗾', '🏔️', '⛰️', '🌋', '🗻', '🏙️', '🌄', '🌅', '🌆', '🌇', '🌃', '🌉'],
                    "Edificios" => ['🏠', '🏡', '🏢', '🏣', '🏤', '🏥', '🏦', '🏨', '🏩', '🏪', '🏫', '🏬', '🏭', '🏯', '🏰', '🕍', '⛪', '🕌', '🕋', '🛕', '💒', '⛩️'],

                    // Actividades y Deportes
                    "Deportes" => ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '⛳', '🪁', '🤿', '🎣'],
                    "Entretenimiento y Juegos" => ['🎲', '🎮', '🕹️', '🎯', '🎳', '🎪', '🎨', '🎭', '🎟️', '🎫', '🎖️', '🏆', '🏅', '🥇', '🥈', '🥉', '⚜️']
                ];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Entrada</title>
    <link rel="stylesheet" href="estilos.css"> <!-- Archivo de estilos CSS principal -->
    <link rel="stylesheet" href="/CKEditor5/ckeditor5/ckeditor5.css"> <!-- Estilos de CKEditor -->
    <style>
        /* Estilos adicionales para el botón y el selector de emojis */
        #toggle-emojis {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
        /* Estilos para la barra lateral de emojis */
        #emoji-sidebar {
            display: none;
            position: fixed;
            top: 50px;
            right: 0;
            width: 250px; /* Ancho fijo para no cubrir la entrada */
            height: calc(100% - 60px);
            overflow-y: auto;
            background-color: #f8f9fa;
            border-left: 1px solid #ddd;
            padding: 10px;
            z-index: 999;
        }
        .emoji-category {
            margin-bottom: 15px;
        }
        .emoji-category h4 {
            margin: 0;
            cursor: pointer;
            background-color: #e9ecef;
            padding: 5px;
            border-radius: 3px;
        }
        .emoji-container {
            display: none;
            flex-wrap: wrap;
            padding: 5px 0;
        }
        .emoji {
            font-size: 24px;
            margin: 3px;
            cursor: pointer;
        }
        .edit-page-container {
            margin-right: 260px; /* Deja espacio para la barra lateral */
        }
        .main-container {
            padding: 20px;
        }
        .edit-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .edit-page-header h1 {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="edit-page-container">
        <div class="main-container">
            <header class="edit-page-header">
                <h1>Nueva Entrada</h1> <!-- Título principal -->
                <!-- Botón para mostrar/ocultar los emojis -->
                <button id="toggle-emojis" class="button">Emojis</button>
            </header>
            <form method="post">
                <label for="titulo">Título:</label>
                <input type="text" name="titulo" id="titulo" value="<?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?>" required>

                <label for="editor">Contenido:</label>
                <div id="editor"><?php echo htmlspecialchars($contenido, ENT_QUOTES, 'UTF-8'); ?></div> <!-- Contenedor de CKEditor -->

                <!-- Campo oculto para el contenido -->
                <textarea name="contenido" id="contenido" style="display:none;"></textarea>

                <button type="submit" class="button">Publicar</button> <!-- Botón para publicar -->
                <button type="button" name="cancelar" class="button button-cancel" id="cancelar-btn">Cancelar</button> <!-- Botón para cancelar -->
            </form>

            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div> <!-- Mostrar error si existe -->
            <?php endif; ?>
        </div>

        <!-- Barra lateral de emojis -->
        <div id="emoji-sidebar">
            <?php foreach ($emojisPorCategoria as $categoria => $emojis): ?>
                <div class="emoji-category">
                    <h4><?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?></h4> <!-- Encabezado de la categoría -->
                    <div class="emoji-container">
                        <?php foreach ($emojis as $emoji): ?>
                            <span class="emoji"><?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'); ?></span> <!-- Emojis -->
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Importmap para CKEditor -->
    <script type="importmap">
        {
            "imports": {
                "ckeditor5": "/CKEditor5/ckeditor5/ckeditor5.js",
                "ckeditor5/": "/CKEditor5/ckeditor5/"
            }
        }
    </script>

    <!-- Script para inicializar CKEditor y manejar eventos -->
    <script type="module">
        import {
            ClassicEditor,
            Essentials,
            Paragraph,
            Bold,
            Italic,
            Underline,
            Strikethrough,
            FontFamily,
            FontSize,
            FontColor,
            FontBackgroundColor,
            Alignment,
            List,
            Indent,
            Link,
            Image,
            ImageToolbar,
            ImageCaption,
            ImageStyle,
            ImageResize,
            ImageUpload,
            BlockQuote,
            Table,
            TableToolbar,
            TableProperties,
            TableCellProperties,
            SimpleUploadAdapter
        } from 'ckeditor5'; // Importar CKEditor y sus plugins

        // Crear la instancia de CKEditor
        ClassicEditor
            .create(document.querySelector('#editor'), {
                plugins: [
                    Essentials, Paragraph, Bold, Italic, Underline, Strikethrough,
                    FontFamily, FontSize, FontColor, FontBackgroundColor, Alignment,
                    List, Indent, Link, Image, ImageToolbar, ImageCaption, ImageStyle,
                    ImageResize, ImageUpload, BlockQuote, Table, TableToolbar,
                    TableProperties, TableCellProperties, SimpleUploadAdapter
                ],
                toolbar: [
                    'undo', 'redo', '|', 'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                    'alignment', '|',
                    'numberedList', 'bulletedList', '|',
                    'outdent', 'indent', '|',
                    'link', 'blockQuote', '|',
                    'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties', '|',
                    'imageUpload'
                ],
                fontFamily: {
                    options: [
                        'default',
                        'Arial, Helvetica, sans-serif',
                        'Courier New, Courier, monospace',
                        'Georgia, serif',
                        'Lucida Sans Unicode, Lucida Grande, sans-serif',
                        'Tahoma, Geneva, sans-serif',
                        'Times New Roman, Times, serif',
                        'Trebuchet MS, Helvetica, sans-serif',
                        'Verdana, Geneva, sans-serif'
                    ]
                },
                image: {
                    resizeOptions: [
                        {
                            name: 'resizeImage:original',
                            value: null,
                            label: 'Original'
                        },
                        {
                            name: 'resizeImage:25',
                            value: '25',
                            label: '25%'
                        },
                        {
                            name: 'resizeImage:50',
                            value: '50',
                            label: '50%'
                        },
                        {
                            name: 'resizeImage:75',
                            value: '75',
                            label: '75%'
                        }
                    ],
                    toolbar: [
                        'imageTextAlternative', '|',
                        'imageStyle:alignLeft', 'imageStyle:alignCenter', 'imageStyle:alignRight', '|',
                        'imageStyle:wrapText', 'imageStyle:breakText', '|',
                        'resizeImage'
                    ],
                    styles: [
                        'alignLeft',
                        'alignCenter',
                        'alignRight',
                        'wrapText',
                        'breakText'
                    ]
                },
                table: {
                    contentToolbar: [
                        'tableColumn',
                        'tableRow',
                        'mergeTableCells',
                        'tableProperties',
                        'tableCellProperties'
                    ],
                    tableProperties: {
                        alignment: [ 'left', 'center', 'right' ]
                    },
                    tableCellProperties: {
                        // Configuración adicional para las celdas, si es necesario
                    }
                },
                simpleUpload: {
                    uploadUrl: 'upload.php', // Ruta para la carga de imágenes (ajústala si es necesario)
                    // Puedes agregar headers o configuraciones adicionales si lo requieres
                }
            })
            .then(editor => {
                window.editor = editor; // Hacer que el editor esté disponible globalmente

                // Al enviar el formulario, actualizar el textarea oculto con el contenido del editor
                document.querySelector('form').addEventListener('submit', event => {
                    document.querySelector('#contenido').value = editor.getData(); // Obtener el contenido del editor

                    if (document.querySelector('#titulo').value.trim() === '' || document.querySelector('#contenido').value.trim() === '') {
                        event.preventDefault(); // Evitar el envío si el título o contenido están vacíos
                        alert('El título y el contenido no pueden estar vacíos.');
                    }
                });

                // Manejar el clic en el botón 'Cancelar'
                document.getElementById('cancelar-btn').addEventListener('click', () => {
                    window.location.href = 'admin.php';
                });

                // Evento para mostrar/ocultar las categorías de emojis individualmente
                document.querySelectorAll('.emoji-category h4').forEach(categoryHeader => {
                    categoryHeader.addEventListener('click', () => {
                        const container = categoryHeader.nextElementSibling;
                        container.style.display = container.style.display === 'none' || container.style.display === '' ? 'flex' : 'none';
                    });
                });

                // Evento para el botón de mostrar/ocultar el panel de emojis
                const toggleEmojisButton = document.getElementById('toggle-emojis');
                const emojiSidebar = document.getElementById('emoji-sidebar');

                toggleEmojisButton.addEventListener('click', () => {
                    emojiSidebar.style.display = emojiSidebar.style.display === 'none' || emojiSidebar.style.display === '' ? 'block' : 'none';
                });

                // Evento de clic en los emojis para insertarlos en el editor
                document.querySelectorAll('.emoji').forEach(emoji => {
                    emoji.addEventListener('click', event => {
                        const emojiText = event.target.textContent;
                        editor.model.change(writer => {
                            const insertPosition = editor.model.document.selection.getFirstPosition();
                            writer.insertText(emojiText, insertPosition);
                        });
                    });
                });
            })
            .catch(error => {
                console.error('Hubo un problema al cargar el editor:', error); // Mostrar error si ocurre
            });
    </script>
</body>
</html>
