<?php
// 1. SEGURIDAD Y CONTROL DE SESIÓN
session_start();
require_once '../conexion.php';
require_once '../subir_imagen.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'autor') {
    header("Location: ../index.php");
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: panel.php");
    exit();
}
$id_publicacion = intval($_GET['id']);
$id_usuario_sesion = $_SESSION['id_usuario'];
$rol_usuario = $_SESSION['rol'];
$nombre_usuario = htmlspecialchars($_SESSION['nombre']);

// 2. OBTENER DATOS DE LA PUBLICACIÓN
$stmt = $conn->prepare("SELECT * FROM web_publicaciones WHERE id_publicacion = ?");
$stmt->bind_param("i", $id_publicacion);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: panel.php");
    exit();
}

$post = $res->fetch_assoc();
$stmt->close();

// Permitir editar solo si es administrador o el autor del post
if ($rol_usuario !== 'administrador' && $post['id_autor'] != $id_usuario_sesion) {
    header("Location: panel.php");
    exit();
}

// 3. PROCESAR ACTUALIZACIÓN DEL FORMULARIO
$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = trim($_POST['titulo']);
    $contenido = trim($_POST['contenido']);
    $etiquetas = trim($_POST['etiquetas']);
    $estado    = $_POST['estado'];

    if (empty($titulo) || empty($contenido)) {
        $mensaje_error = "El título y el contenido son obligatorios.";
    } else {
        $nombre_imagen = $post['imagen'];

        // Si el usuario subió una nueva imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            $resultado_subida = procesarSubidaImagen($_FILES['imagen'], '../uploads/');
            if ($resultado_subida['exito']) {
                // Borrar la foto previa del servidor si existía
                if (!empty($post['imagen']) && file_exists('../uploads/' . $post['imagen'])) {
                    unlink('../uploads/' . $post['imagen']);
                }
                $nombre_imagen = $resultado_subida['nombre_archivo'];
            } else {
                $mensaje_error = $resultado_subida['error'];
            }
        }

        if (empty($mensaje_error)) {
            // Se cambió "publicaciones" a "web_publicaciones"
            $stmt_upd = $conn->prepare("UPDATE web_publicaciones SET titulo = ?, contenido = ?, imagen = ?, etiquetas = ?, estado = ? WHERE id_publicacion = ?");
            $stmt_upd->bind_param("sssssi", $titulo, $contenido, $nombre_imagen, $etiquetas, $estado, $id_publicacion);

            if ($stmt_upd->execute()) {
                $stmt_upd->close();
                header("Location: panel.php?msg=actualizado");
                exit();
            } else {
                $mensaje_error = "Error al actualizar en la base de datos.";
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
    <title>Editar Publicación - Duncan Mascotas</title>
    
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
                <?php if ($rol_usuario !== 'visor'): ?>
                    <span class="badge badge-rol ms-1"><?= ucfirst($rol_usuario) ?></span>
                <?php endif; ?>
            </span>
            <a href="panel.php" class="btn btn-outline-dorado btn-sm me-1">
                <i class="bi bi-arrow-left"></i> Volver al Panel
            </a>
        </div>
    </div>
</nav>

<!-- FORMULARIO DE EDICIÓN -->
<main class="container py-5 flex-grow-1" style="max-width: 800px;">
    
    <div class="card shadow-sm border-0 rounded-4 card-blog">
        
        <div class="card-header bg-white py-3 border-bottom border-dorado-bottom">
            <h2 class="h4 fw-bold text-vino mb-0">
                <i class="bi bi-pencil-square text-dorado me-2"></i>Editar Publicación
            </h2>
        </div>

        <div class="card-body p-4 p-md-5">

            <?php if (!empty($mensaje_error)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-danger mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($mensaje_error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="editar.php?id=<?= $id_publicacion ?>" method="POST" enctype="multipart/form-data">
                
                <!-- Título Principal -->
                <div class="mb-4">
                    <label for="titulo" class="form-label fw-bold text-vino">Título Principal</label>
                    <input type="text" name="titulo" id="titulo" class="form-control border-dorado shadow-sm" value="<?= htmlspecialchars($post['titulo']) ?>" required>
                </div>

                <!-- Contenido del Artículo -->
                <div class="mb-4">
                    <label for="contenido" class="form-label fw-bold text-vino">Contenido del Artículo</label>
                    <textarea name="contenido" id="contenido" rows="8" class="form-control border-dorado shadow-sm" required><?= htmlspecialchars($post['contenido']) ?></textarea>
                </div>

                <!-- Etiquetas y Estado -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="etiquetas" class="form-label fw-bold text-vino">Etiquetas (Separadas por comas)</label>
                        <input type="text" name="etiquetas" id="etiquetas" class="form-control border-dorado shadow-sm" placeholder="ej. paseo, salud, nutricion" value="<?= htmlspecialchars($post['etiquetas']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="estado" class="form-label fw-bold text-vino">Estado del Artículo</label>
                        <select name="estado" id="estado" class="form-select border-dorado shadow-sm">
                            <option value="publicada" <?= $post['estado'] === 'publicada' ? 'selected' : '' ?>>🟢 Publicada (Visible al público)</option>
                            <option value="borrador" <?= $post['estado'] === 'borrador' ? 'selected' : '' ?>>🟡 Borrador (Oculto)</option>
                        </select>
                    </div>
                </div>

                <!-- Imagen Actual y Carga de Nueva Imagen -->
                <div class="mb-4 p-3 bg-light rounded-3 border border-dorado">
                    <label for="imagen" class="form-label fw-bold text-vino">Cambiar Imagen de Portada (Opcional)</label>
                    <input type="file" name="imagen" id="imagen" class="form-control mb-3" accept="image/*">
                    
                    <?php if (!empty($post['imagen']) && file_exists('../uploads/' . $post['imagen'])): ?>
                        <div class="text-center">
                            <span class="small text-muted d-block mb-2">Imagen actual registrada:</span>
                            <img src="../uploads/<?= htmlspecialchars($post['imagen']) ?>" class="img-fluid rounded-3 shadow-sm border border-dorado" style="max-height: 200px; object-fit: cover;" alt="Portada actual">
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Botones de Acción -->
                <div class="d-flex gap-2 justify-content-end pt-3 border-top">
                    <a href="panel.php" class="btn btn-outline-secondary px-4 fw-semibold">Cancelar</a>
                    <button type="submit" class="btn btn-vino px-4 fw-semibold">
                        <i class="bi bi-check-circle me-1"></i> Guardar Cambios
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