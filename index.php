<?php
session_start();

// Configuración de la base de datos
$host     = "localhost";
$user     = "root";
$password = ""; 
$dbname   = "sistema_duncan"; 
$port     = 3309; 

$conn = new mysqli($host, $user, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ----------------------------------------------------
// 1. Lógica de Búsqueda y Paginación
// ----------------------------------------------------
$por_pagina = 6;
$pagina_actual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

$where_sql = "WHERE p.estado = 'publicada'";
$params = [];
$tipos = "";

$termino_busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!empty($termino_busqueda)) {
    $where_sql .= " AND (p.titulo LIKE ? OR p.contenido LIKE ? OR p.etiquetas LIKE ?)";
    $like_q = "%" . $termino_busqueda . "%";
    $params = array_merge($params, [$like_q, $like_q, $like_q]);
    $tipos .= "sss";
}

// Conteo total de publicaciones
$sql_count = "SELECT COUNT(*) AS total FROM web_publicaciones p " . $where_sql;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($tipos, ...$params);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);
$stmt_count->close();

// Obtener las publicaciones
$sql_list = "SELECT p.id_publicacion, p.titulo, p.contenido, p.imagen, p.etiquetas, p.fecha_publicacion, u.nom_usr AS autor 
             FROM web_publicaciones p 
             JOIN usuario u ON p.id_autor = u.id_usr 
             $where_sql 
             ORDER BY p.fecha_publicacion DESC LIMIT ? OFFSET ?";

$params[] = $por_pagina;
$params[] = $offset;
$tipos .= "ii";

$stmt_list = $conn->prepare($sql_list);
$stmt_list->bind_param($tipos, ...$params);
$stmt_list->execute();
$resultado = $stmt_list->get_result();

// Variables de usuario para el menú superior
$esta_autenticado = isset($_SESSION['id_usuario']);
$nombre_usuario   = $esta_autenticado ? htmlspecialchars($_SESSION['nombre']) : '';
$rol_usuario      = $esta_autenticado ? $_SESSION['rol'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<link rel="stylesheet" href="css/styles.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Blog Web</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        .border-dorado { border-color: #D4AF37 !important; }
        .text-dorado { color: #D4AF37 !important; }
        .text-vino { color: #6B1D2F !important; }
        .bg-vino { background-color: #6B1D2F !important; }
        .btn-vino { background-color: #6B1D2F; color: white; border: none; }
        .btn-vino:hover { background-color: #4a1420; color: white; }
        .btn-outline-dorado {
        color: #D4AF37 !important;
        border: 1px solid #D4AF37 !important;
        }
        .btn-outline-dorado:hover {
        background-color: #D4AF37 !important;
        color: #6B1D2F !important;
}
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- BARRA DE NAVEGACIÓN -->
<nav class="navbar navbar-expand-lg navbar-dark bg-vino border-dorado-bottom shadow-sm">
    <div class="container">
        <!-- Logotipo e Identidad de Marca -->
        <a class="navbar-brand d-flex align-items-center text-dorado fw-bold fs-4" href="index.php">
            <img src="uploads/logo duncan.jpeg" alt="Logotipo Duncan Mascotas" style="height: 40px; width: auto;" class="me-2 rounded">
            <span>Duncan Mascotas</span>
        </a>
        
        <div class="d-flex align-items-center gap-2">
            <?php if ($esta_autenticado): ?>
                <span class="text-light me-2 small d-none d-md-inline">
                    <i class="bi bi-person-circle text-dorado me-1 fs-6"></i>
                    Hola, <strong><?= htmlspecialchars($nombre_usuario) ?></strong>
                </span>

                <!-- Botón de Panel Admin: Solo visible para administradores -->
                <?php if ($rol_usuario === 'admin' || $rol_usuario === 'administrador'): ?>
                    <a href="admin/panel.php" class="btn btn-outline-dorado btn-sm me-1">
                        <i class="bi bi-speedometer2"></i> Panel Admin
                    </a>
                <?php endif; ?>

                <!-- Botón de Salir -->
                <a href="logout.php" class="btn btn-sm btn-light text-vino fw-semibold">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            <?php else: ?>
                <!-- Botón para visitantes -->
                <a href="login.php" class="btn btn-outline-dorado btn-sm">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- CONTENIDO PRINCIPAL -->
<div class="container pb-5 flex-grow-1">
    
    <div class="text-center mb-5 mt-4">
        <h1 class="display-4 fw-bold">Últimas Publicaciones</h1>
        <p class="text-muted fs-5">Explora el contenido de nuestro blog, lee y participa en los comentarios.</p>
    </div>

    <!-- Buscador -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-6">
            <form action="index.php" method="GET" class="d-flex">
                <input type="text" name="q" class="form-control me-2 border-dorado" placeholder="Buscar temas..." value="<?= htmlspecialchars($termino_busqueda) ?>">
                <button class="btn btn-vino" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </form>
        </div>
    </div>

    <!-- Grid de Publicaciones Reales (Desde la Base de Datos) -->
    <div class="row g-4 mb-5">
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <?php while ($row = $resultado->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6">
                    <article class="card h-100 shadow-sm border-0">
                        <?php if (!empty($row['imagen']) && file_exists('uploads/' . $row['imagen'])): ?>
                            <img src="uploads/<?= htmlspecialchars($row['imagen']) ?>" class="card-img-top" alt="Imagen Blog" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <!-- Imagen por defecto de tu diseño anterior -->
                            <svg class="bd-placeholder-img card-img-top" width="100%" height="200" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false">
                                <rect width="100%" height="100%" fill="#e9ecef"></rect>
                                <text x="35%" y="50%" fill="#adb5bd" dy=".3em">Imagen Blog</text>
                            </svg>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <?php if (!empty($row['etiquetas'])): ?>
                                <span class="badge bg-primary w-25 mb-2"><?= htmlspecialchars($row['etiquetas']) ?></span>
                            <?php endif; ?>
                            
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($row['titulo']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars(substr($row['contenido'], 0, 100)) ?>...</p>
                            
                            <a href="publicacion.php?id=<?= $row['id_publicacion'] ?>" class="btn btn-outline-dark mt-auto"><i class="bi bi-book"></i> Leer artículo</a>
                        </div>
                    </article>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <h4 class="text-muted">No se encontraron artículos publicados.</h4>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>