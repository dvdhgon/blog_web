<?php
// 1. Carga la lógica y consultas a la base de datos
require_once 'controladores/controlador_index.php';

// 2. Parte superior de la página (Head y Navbar)
require_once 'vistas/header.php';

// 3. Carga el contenido principal (Artículos y Paginación)
require_once 'vistas/vista_index.php';

// 4. Carga la parte inferior (Footer y Scripts)
require_once 'vistas/footer.php';
?>