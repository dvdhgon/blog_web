<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Si el usuario es administrador, lo mandamos a su propia carpeta
if ($_SESSION['rol'] == 'administrador') {
    header("Location: admin/panel.php");
    exit();
}

$nombre = htmlspecialchars($_SESSION['nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Blog Web</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-journal-code"></i> Mi Blog Web</a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3">Hola, <strong><?= $nombre ?></strong> (Visor)</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="text-center mb-5 mt-4">
        <h1 class="display-4 fw-bold">Últimas Publicaciones</h1>
        <p class="text-muted fs-5">Explora el contenido de nuestro blog, lee y participa en los comentarios.</p>
    </div>

    <!-- Grid de publicaciones (Visual) -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <svg class="bd-placeholder-img card-img-top" width="100%" height="200" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false"><rect width="100%" height="100%" fill="#e9ecef"></rect><text x="35%" y="50%" fill="#adb5bd" dy=".3em">Imagen Blog</text></svg>
                <div class="card-body d-flex flex-column">
                    <span class="badge bg-primary w-25 mb-2">Tecnología</span>
                    <h5 class="card-title fw-bold">El futuro de la IA</h5>
                    <p class="card-text text-muted">Descubre cuáles serán las tendencias tecnológicas para el próximo año...</p>
                    <a href="#" class="btn btn-outline-dark mt-auto"><i class="bi bi-book"></i> Leer artículo</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>