<?php
// 1. SEGURIDAD Y CONTROL DE SESIÓN

session_start();
require_once '../conexion.php';
require_once '../subir_imagen.php'; // Módulo para el manejo de archivos

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'autor') {
    header("Location: ../index.php");
    exit();
}

$id_usuario_sesion = $_SESSION['id_usuario'];
$rol_usuario       = $_SESSION['rol'];
$nombre_usuario    = htmlspecialchars($_SESSION['nombre']);


// 2. PROCESAR EL FORMULARIO (MÉTODO POST)

$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = trim($_POST['titulo']);
    $contenido = trim($_POST['contenido']);
    $etiquetas = trim($_POST['etiquetas']);
    $estado    = $_POST['estado'];
    $nombre_imagen = "";

    if (empty($titulo) || empty($contenido)) {
        $mensaje_error = "El título y el contenido principal son obligatorios.";
    } else {
        // Procesar subida de imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            $resultado_subida = procesarSubidaImagen($_FILES['imagen'], '../uploads/');
            if ($resultado_subida['exito']) {
                $nombre_imagen = $resultado_subida['nombre_archivo'];
            } else {
                $mensaje_error = $resultado_subida['error'];
            }
        }

        // Si no hay errores, insertar en la nueva tabla web_publicaciones
        if (empty($mensaje_error)) {
            $stmt = $conn->prepare("INSERT INTO web_publicaciones (titulo, contenido, imagen, id_autor, etiquetas, estado) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $titulo, $contenido, $nombre_imagen, $id_usuario_sesion, $etiquetas, $estado);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: panel.php?msg=creado");
                exit();
            } else {
                $mensaje_error = "Error al guardar la publicación en la base de datos.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Publicación - Duncan Mascotas</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- NAVBAR DEL PANEL -->
<nav class="navbar navbar-expand-lg navbar-dark bg-vino border-dorado-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center text-dorado fw-bold fs-4" href="panel.php">
            <?php if (file_exists('../uploads/logo duncan.jpeg')): ?>
                <img src="../uploads/logo duncan.jpeg" alt="Logo" style="height: 38px;" class="me-2 rounded">
            <?php else: ?>
                <i class="bi bi-shield-lock me-2 fs-3"></i>
            <?php endif; ?>
            <span>Panel Control</span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <span class="text-light me-2 small d-none d-md-inline">
                <i class="bi bi-person-circle text-dorado me-1 fs-6"></i>
                <strong><?= $nombre_usuario ?></strong>
                <span class="badge badge-rol ms-1"><?= ucfirst($rol_usuario) ?></span>
            </span>
            <a href="panel.php" class="btn btn-outline-dorado btn-sm me-1">
                <i class="bi bi-arrow-left"></i> Volver al Panel
            </a>
        </div>
    </div>
</nav>

<!-- FORMULARIO DE CREACIÓN -->
<main class="container py-5 flex-grow-1" style="max-width: 800px;">
    
    <div class="card shadow-sm border-0 rounded-4 card-blog">
        
        <div class="card-header bg-white py-3 border-bottom border-dorado-bottom">
            <h2 class="h4 fw-bold text-vino mb-0">
                <i class="bi bi-plus-circle text-dorado me-2"></i>Redactar Nueva Publicación
            </h2>
        </div>

        <div class="card-body p-4 p-md-5">

            <?php if (!empty($mensaje_error)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-danger mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($mensaje_error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="crear.php" method="POST" enctype="multipart/form-data">
                
                <div class="mb-4">
                    <label for="titulo" class="form-label fw-bold text-vino">Título Principal</label>
                    <input type="text" name="titulo" id="titulo" class="form-control border-dorado shadow-sm" placeholder="Ej. Los mejores cuidados para un Golden Retriever" required>
                </div>

                <div class="mb-4">
                    <label for="contenido" class="form-label fw-bold text-vino">Contenido del Artículo</label>
                    <textarea name="contenido" id="contenido" rows="8" class="form-control border-dorado shadow-sm" placeholder="Escribe el desarrollo completo del artículo aquí..." required></textarea>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="etiquetas" class="form-label fw-bold text-vino">Etiquetas (Opcional, separadas por comas)</label>
                        <input type="text" name="etiquetas" id="etiquetas" class="form-control border-dorado shadow-sm" placeholder="ej. paseo, salud, cachorros">
                    </div>

                    <div class="col-md-6">
                        <label for="estado" class="form-label fw-bold text-vino">Estado del Artículo</label>
                        <select name="estado" id="estado" class="form-select border-dorado shadow-sm">
                            <option value="publicada">🟢 Publicar Inmediatamente</option>
                            <option value="borrador">🟡 Guardar como Borrador</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4 p-3 bg-light rounded-3 border border-dorado">
                    <label for="imagen" class="form-label fw-bold text-vino">Imagen de Portada (Recomendado)</label>
                    <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
                    <small class="text-muted d-block mt-1">Sube una imagen atractiva que represente el artículo.</small>
                </div>

                <div class="d-flex gap-2 justify-content-end pt-3 border-top">
                    <a href="panel.php" class="btn btn-outline-secondary px-4 fw-semibold">Cancelar</a>
                    <button type="submit" class="btn btn-vino px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Guardar Publicación
                    </button>
                </div>

            </form>
        </div>
    </div>
</main>

<footer class="bg-vino text-white py-3 border-dorado-bottom mt-auto">
    <div class="container text-center">
        <small class="text-dorado">Duncan Mascotas &copy; <?= date('Y') ?></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>