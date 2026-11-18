<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../conexion.php';

// PERMITIR ACCESO A AMBOS ROLES
requerir_rol([ROL_VISOR, ROL_ADMIN], '../login.php', '../index.php');

$id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : usuario_id();
$rol_actual = $_SESSION['rol'] ?? '';
$es_admin = ($rol_actual === 'administrador' || $rol_actual === ROL_ADMIN);

$id_pub = isset($_GET['id']) ? intval($_GET['id']) : 0;
$accion = $id_pub > 0 ? "Editar" : "Crear";
$error = "";
$ruta_retorno = $es_admin ? "../admin/panel.php" : "dashboard.php";

$pub = ['titulo' => '', 'contenido' => '', 'etiquetas' => '', 'estado' => 'publicada', 'imagen' => ''];
if ($id_pub > 0) {
    if ($es_admin) {
        $stmt = $conn->prepare("SELECT * FROM web_publicaciones WHERE id_publicacion = ?");
        $stmt->bind_param("i", $id_pub);
    } else {
        $stmt = $conn->prepare("SELECT * FROM web_publicaciones WHERE id_publicacion = ? AND id_autor = ?");
        $stmt->bind_param("ii", $id_pub, $id_usuario);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        header("Location: " . $ruta_retorno);
        exit();
    }
    $pub = $res->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $contenido = trim($_POST['contenido']);
    $etiquetas = trim($_POST['etiquetas']);
    $estado = $_POST['estado'] === 'borrador' ? 'borrador' : 'publicada';
    
    $imagen = $pub['imagen']; 
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $nombre_img = time() . "_" . uniqid() . "." . $ext;
            $ruta_destino = "../uploads/" . $nombre_img;
            if (!is_dir("../uploads/")) { mkdir("../uploads/", 0777, true); }
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                $imagen = $nombre_img;
            }
        } else {
            $error = "Formato de imagen no válido.";
        }
    }

    if (!empty($titulo) && !empty($contenido)) {
        if ($id_pub > 0) {
            if ($es_admin) {
                $stmt = $conn->prepare("UPDATE web_publicaciones SET titulo=?, contenido=?, etiquetas=?, estado=?, imagen=? WHERE id_publicacion=?");
                $stmt->bind_param("sssssi", $titulo, $contenido, $etiquetas, $estado, $imagen, $id_pub);
            } else {
                $stmt = $conn->prepare("UPDATE web_publicaciones SET titulo=?, contenido=?, etiquetas=?, estado=?, imagen=? WHERE id_publicacion=? AND id_autor=?");
                $stmt->bind_param("sssssii", $titulo, $contenido, $etiquetas, $estado, $imagen, $id_pub, $id_usuario);
            }
            $stmt->execute();
            header("Location: " . $ruta_retorno . "?msg=actualizado");
        } else {
            $stmt = $conn->prepare("INSERT INTO web_publicaciones (titulo, contenido, etiquetas, estado, imagen, id_autor) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $titulo, $contenido, $etiquetas, $estado, $imagen, $id_usuario);
            $stmt->execute();
            header("Location: " . $ruta_retorno . "?msg=creado");
        }
        exit();
    } else {
        $error = "El título y el contenido son obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $accion ?> Publicación - Duncan Mascotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-vino border-dorado-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center text-dorado fw-bold fs-4" href="<?= $ruta_retorno ?>">
            <i class="bi bi-shield-lock me-2 fs-3"></i><span>Panel Control</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-light me-2 small d-none d-md-inline">
                <i class="bi bi-person-circle text-dorado me-1 fs-6"></i>
                <strong><?= e($_SESSION['nombre']) ?></strong>
                <span class="badge badge-rol ms-1"><?= ucfirst(usuario_rol()) ?></span>
            </span>
            <a href="<?= $ruta_retorno ?>" class="btn btn-outline-dorado btn-sm me-1">
                <i class="bi bi-arrow-left"></i> Volver al Panel
            </a>
        </div>
    </div>
</nav>

<main class="container py-5 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-vino text-white py-4 px-4 px-md-5 border-dorado-bottom">
                    <h3 class="mb-0 fw-bold d-flex align-items-center">
                        <i class="bi <?= $id_pub > 0 ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-dorado me-3 fs-2"></i>
                        <span><?= $accion ?> Publicación</span>
                    </h3>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-danger mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-vino">Título Principal</label>
                            <input type="text" name="titulo" class="form-control border-dorado shadow-sm" value="<?= htmlspecialchars($pub['titulo']) ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-vino">Contenido o descripción</label>
                            <textarea name="contenido" class="form-control border-dorado shadow-sm" rows="8" required><?= htmlspecialchars($pub['contenido']) ?></textarea>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-vino">Etiquetas (Separadas por comas)</label>
                                <input type="text" name="etiquetas" class="form-control border-dorado shadow-sm" placeholder="Ej: salud, perros, cuidados" value="<?= htmlspecialchars($pub['etiquetas']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-vino">Estado del Artículo</label>
                                <select name="estado" class="form-select border-dorado shadow-sm">
                                    <option value="publicada" <?= $pub['estado'] === 'publicada' ? 'selected' : '' ?>>🟢 Publicada (Visible al público)</option>
                                    <option value="borrador" <?= $pub['estado'] === 'borrador' ? 'selected' : '' ?>>🟡 Borrador (Oculto)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-5">
                            <label class="form-label fw-bold text-vino">Imagen de portada</label>
                            <input type="file" name="imagen" class="form-control border-dorado shadow-sm" accept="image/*">
                            <?php if ($pub['imagen']): ?>
                                <div class="mt-3 card border-dorado p-2 bg-light d-inline-block">
                                    <span class="text-muted small d-block mb-2"><i class="bi bi-image text-vino me-1"></i>Imagen actual:</span>
                                    <img src="../uploads/<?= htmlspecialchars($pub['imagen']) ?>" alt="Vista previa" class="img-preview rounded shadow-sm" style="max-height: 150px; width: auto;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr class="border-dorado opacity-50 mb-4">
                        
                        <div class="d-flex justify-content-end gap-3">
                            <a href="<?= $ruta_retorno ?>" class="btn btn-outline-secondary px-4">Cancelar</a>
                            <button type="submit" class="btn btn-vino px-4 shadow-sm">
                                <i class="bi bi-save me-2"></i>Guardar Publicación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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