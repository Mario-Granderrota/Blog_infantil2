<?php
// footer.php
?>
        </main>
    </div>
    <footer>
        <p>&copy; <?= date('Y'); ?> <?= htmlspecialchars(NOMBRE_SITIO); ?>. Todos los derechos reservados.</p>
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
