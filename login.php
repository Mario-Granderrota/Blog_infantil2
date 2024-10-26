<?php
// login.php
require_once 'config.php';
require_once 'funciones.php';

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitizar($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $emojisSeleccionados = json_decode($_POST['emojis'] ?? '[]', true);

    if (empty($usuario) || empty($contrasena)) {
        $error = 'Por favor, ingresa usuario y contraseña.';
    } elseif (!is_array($emojisSeleccionados) || count($emojisSeleccionados) !== 3) {
        $error = 'Debes seleccionar exactamente tres emojis.';
    } else {
        $rol = autenticarUsuario($usuario, $contrasena);
        if ($rol) {
            if (!verificarEmojisNoRepetidos($usuario, $emojisSeleccionados)) {
                $error = 'No puedes repetir emojis de tus 5 últimas sesiones.';
            } else {
                agregarRegistroAcceso($usuario, $emojisSeleccionados);
                $_SESSION['usuario'] = $usuario;
                $_SESSION['rol'] = $rol;
                redirigir('perfil.php', 'Has iniciado sesión correctamente.', 'success');
            }
        } else {
            $error = 'Usuario o contraseña incorrectos.';
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

// Mostrar menú si la IP es de la editora
$ocultar_menu = !esEditor();

include 'header.php';
?>
<div class="login-container">
    <h1>Iniciar Sesión</h1>
    <form method="post" id="login-form">
        <label for="usuario">Usuario:</label>
        <input type="text" name="usuario" id="usuario" required>

        <label for="contrasena">Contraseña:</label>
        <input type="password" name="contrasena" id="contrasena" required>

        <!-- Instrucciones sobre la selección de emojis -->
        <div class="emoji-instructions">
            <p>
                Además de tu nombre de usuario y contraseña, debes elegir <strong>tres emojis</strong>. 
                Es importante que esta elección esté basada en una lógica personal y razonada, 
                de modo que puedas recordar fácilmente tu selección en futuros inicios de sesión.
            </p>
            <p>
                <em>Ejemplos de lógica de selección:</em>
                <ul>
                    <li><strong>Característica Común:</strong> Emojis que representen tus hobbies favoritos, como 🎨 (arte), 🎸 (música) y ⚽ (deportes).</li>
                    <li><strong>Iniciales Comunes:</strong> Emojis que empiecen con la misma letra, como 🐶 (Dog), 🍩 (Donut) y 🚗 (Dacia).</li>
                    <li><strong>Significado Personal:</strong> Emojis que formen una pequeña historia o tengan un significado especial para ti, como 🌙 (luna), ⭐ (estrella) y 🌟 (brillo).</li>
                </ul>
            </p>
            <p>
                <span title="Selecciona tres emojis basados en una lógica que te sea fácil de recordar en el futuro, que no podrás ver en esta sesión.">
                    ℹ️
                </span> Piensa en una lógica que te ayude a recordar tus emojis seleccionados y no se la expliques a nadie. Así, si notas en alguno de tus accesos que no pareces ser tú quien ha iniciado alguna de las sesiones, se lo comentas a la dueña del blog para que establezca medidas de seguridad.
            </p>
        </div>

        <!-- Sección de Ejemplos de Selección de Emojis -->
        <div class="emoji-examples">
            <h3>Ejemplos de Selección de Emojis:</h3>
            <div class="example">
                <p><strong>Característica Común:</strong> 🎨 🎸 ⚽</p>
            </div>
            <div class="example">
                <p><strong>Iniciales Comunes:</strong> 🐶 🍩 🚗</p>
            </div>
            <div class="example">
                <p><strong>Significado Personal:</strong> 🌙 ⭐ 🌟</p>
            </div>
        </div>

        <!-- Botón para mostrar/ocultar los emojis -->
        <button type="button" id="toggle-emojis" class="button">Seleccionar Emojis</button>

        <!-- Selector de emojis -->
        <div class="emoji-selector" id="emoji-selector" style="display: none;">
            <?php foreach ($emojisPorCategoria as $categoria => $emojis): ?>
                <div class="emoji-category">
                    <h4><?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?></h4>
                    <div class="emoji-container" style="display: none;">
                        <?php foreach ($emojis as $emoji): ?>
                            <span class="emoji" data-emoji="<?= htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <input type="hidden" name="emojis" id="emojis-input">

        <div id="selected-emojis">
            <p><strong>Emojis seleccionados:</strong></p>
            <div id="selected-emojis-list"></div>
        </div>

        <button type="submit" class="button">Ingresar</button>
    </form>
    <?php if ($error): ?>
        <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
</div>

<!-- Estilos adicionales para el botón y el selector de emojis -->
<style>
    /* Estilos para el contenedor de instrucciones */
    .emoji-instructions {
        background-color: #f1f3f5;
        padding: 15px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    .emoji-instructions ul {
        list-style-type: disc;
        margin-left: 20px;
    }
    .emoji-instructions span[title] {
        cursor: pointer;
        font-size: 18px;
    }

    /* Estilos para la sección de ejemplos */
    .emoji-examples {
        background-color: #e9ecef;
        padding: 15px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    .emoji-examples .example {
        margin-bottom: 10px;
    }
    .emoji-examples p {
        margin: 0;
    }

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
        max-height: 400px;
        overflow-y: auto;
        border-radius: 5px;
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
        user-select: none;
    }
    .emoji-container {
        display: flex;
        flex-wrap: wrap;
        padding: 5px 0;
    }
    .emoji {
        font-size: 24px;
        margin: 5px;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .emoji:hover {
        transform: scale(1.2);
    }
    .emoji.selected {
        background-color: #d1e7dd;
        border-radius: 4px;
        padding: 2px;
    }
    #selected-emojis {
        margin-top: 15px;
    }
    #selected-emojis-list .emoji {
        font-size: 24px;
        margin: 3px;
    }
    /* Mensajes de error */
    .mensaje.error {
        color: #dc3545;
        margin-top: 15px;
        padding: 10px;
        border: 1px solid #dc3545;
        border-radius: 5px;
        background-color: #f8d7da;
    }

    /* Responsividad */
    @media (max-width: 600px) {
        .emoji-container {
            justify-content: center;
        }
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
            container.style.display = container.style.display === 'none' || container.style.display === '' ? 'flex' : 'none';
        });
    });

    const selectedEmojis = [];
    const emojisInput = document.getElementById('emojis-input');
    const selectedEmojisList = document.getElementById('selected-emojis-list');

    function updateSelectedEmojisDisplay() {
        selectedEmojisList.innerHTML = '';
        selectedEmojis.forEach(emoji => {
            const emojiSpan = document.createElement('span');
            emojiSpan.textContent = emoji;
            emojiSpan.classList.add('emoji');
            selectedEmojisList.appendChild(emojiSpan);
        });
    }

    // Manejar la selección y deselección de emojis
    document.querySelectorAll('.emoji').forEach(emoji => {
        emoji.addEventListener('click', event => {
            const emojiChar = event.target.getAttribute('data-emoji');
            if (event.target.classList.contains('selected')) {
                event.target.classList.remove('selected');
                const index = selectedEmojis.indexOf(emojiChar);
                if (index > -1) {
                    selectedEmojis.splice(index, 1);
                }
            } else {
                if (selectedEmojis.length >= 3) {
                    alert('Solo puedes seleccionar tres emojis.');
                    return;
                }
                event.target.classList.add('selected');
                selectedEmojis.push(emojiChar);
            }
            emojisInput.value = JSON.stringify(selectedEmojis);
            updateSelectedEmojisDisplay();
        });
    });

    updateSelectedEmojisDisplay();
</script>

<?php include 'footer.php'; ?>
