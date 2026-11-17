<?php
session_start();
require_once 'conexion.php';

$id_publicacion = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
$esta_autenticado = isset($_SESSION['id_usuario']);
$id_usuario_sesion = $esta_autenticado ? $_SESSION['id_usuario'] : null;
$nombre_usuario   = $esta_autenticado ? htmlspecialchars($_SESSION['nombre']) : '';
$rol_usuario      = $esta_autenticado ? $_SESSION['rol'] : '';

// ENDPOINT AJAX PARA OBTENER COMENTARIOS EN TIEMPO REAL
if (isset($_GET['action']) && $_GET['action'] === 'obtener_comentarios') {
    header('Content-Type: application/json');
    
    // Se añade c.id_usuario a la consulta para validar quién es el dueño
    $stmt_c = $conn->prepare("SELECT c.id_comentario, c.id_usuario, c.comentario, c.fecha_comentario, u.nom_usr AS usuario, u.tip_usu AS rol_usuario_comentario 
                              FROM web_comentarios c 
                              JOIN usuario u ON c.id_usuario = u.id_usr 
                              WHERE c.id_publicacion = ? 
                              ORDER BY c.fecha_comentario DESC");
    $stmt_c->bind_param("i", $id_publicacion);
    $stmt_c->execute();
    $res = $stmt_c->get_result();
    
    $comentarios = [];
    while ($row = $res->fetch_assoc()) {
        $row['comentario'] = htmlspecialchars($row['comentario']);
        $row['usuario'] = htmlspecialchars($row['usuario']);
        $row['fecha_formateada'] = date('d/m/Y - H:i', strtotime($row['fecha_comentario']));
        
        // Validación: Puede eliminar si es Administrador o si es el dueño del comentario
        $row['puede_eliminar'] = ($rol_usuario === 'administrador' || $row['id_usuario'] == $id_usuario_sesion);
        
        // Formateo del rol para mostrar u ocultar la etiqueta
        $row['rol_texto'] = ($row['rol_usuario_comentario'] == 2 || $row['rol_usuario_comentario'] === 'administrador') ? 'administrador' : 'visor';
        
        $comentarios[] = $row;
    }
    echo json_encode(['total' => count($comentarios), 'comentarios' => $comentarios]);
    exit();
}

// ENDPOINT AJAX PARA GUARDAR UN NUEVO COMENTARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_comentario'])) {
    header('Content-Type: application/json');
    if (!$esta_autenticado) {
        echo json_encode(['exito' => false, 'error' => 'Debes iniciar sesión.']);
        exit();
    }
    $texto = trim($_POST['contenido_comentario']);
    if (empty($texto)) {
        echo json_encode(['exito' => false, 'error' => 'El comentario no puede estar vacío.']);
        exit();
    }
    
    $stmt_ins = $conn->prepare("INSERT INTO web_comentarios (id_publicacion, id_usuario, comentario) VALUES (?, ?, ?)");
    $stmt_ins->bind_param("iis", $id_publicacion, $id_usuario_sesion, $texto);
    if ($stmt_ins->execute()) {
        echo json_encode(['exito' => true]);
    } else {
        echo json_encode(['exito' => false, 'error' => 'Error al guardar en la base de datos.']);
    }
    exit();
}

// MODERACIÓN DE COMENTARIOS (ADMINISTRADOR O AUTOR DEL COMENTARIO)
if (isset($_GET['eliminar_comentario']) && $esta_autenticado && $id_publicacion > 0) {
    $id_comentario_del = intval($_GET['eliminar_comentario']);
    
    // Verificar si el usuario en sesión es el dueño o es administrador
    $stmt_verificar = $conn->prepare("SELECT id_usuario FROM web_comentarios WHERE id_comentario = ?");
    $stmt_verificar->bind_param("i", $id_comentario_del);
    $stmt_verificar->execute();
    $res_verificar = $stmt_verificar->get_result();
    
    if ($com_row = $res_verificar->fetch_assoc()) {
        if ($rol_usuario === 'administrador' || $com_row['id_usuario'] == $id_usuario_sesion) {
            $stmt_del = $conn->prepare("DELETE FROM web_comentarios WHERE id_comentario = ? AND id_publicacion = ?");
            $stmt_del->bind_param("ii", $id_comentario_del, $id_publicacion);
            $stmt_del->execute();
            $stmt_del->close();
        }
    }
    $stmt_verificar->close();
    header("Location: publicacion.php?id=" . $id_publicacion . "#comentarios");
    exit();
}

// CONSULTA DE LA PUBLICACIÓN
$publicacion_existe = false;
$post = null;

if ($id_publicacion > 0) {
    $sql_post = "SELECT p.id_publicacion, p.titulo, p.contenido, p.imagen, p.etiquetas, p.estado, p.fecha_publicacion, u.nom_usr AS autor 
                 FROM web_publicaciones p 
                 JOIN usuario u ON p.id_autor = u.id_usr 
                 WHERE p.id_publicacion = ?";
    $stmt = $conn->prepare($sql_post);
    $stmt->bind_param("i", $id_publicacion);
    $stmt->execute();
    $res_post = $stmt->get_result();

    if ($res_post && $res_post->num_rows > 0) {
        $post = $res_post->fetch_assoc();
        if ($post['estado'] === 'publicada' || $rol_usuario === 'administrador' || $rol_usuario === 'autor') {
            $publicacion_existe = true;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $publicacion_existe ? htmlspecialchars($post['titulo']) : 'Publicación no disponible' ?> - Duncan Mascotas</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css">
    
    <!-- Librería SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .bg-vino { background-color: #722F37 !important; }
        .text-vino { color: #722F37 !important; }
        .border-dorado-bottom { border-bottom: 3px solid #D4AF37 !important; }
        .text-dorado { color: #D4AF37 !important; }
        .btn-vino { background-color: #722F37; color: white; border: none; }
        .btn-vino:hover { background-color: #5a252b; color: white; }
        .btn-outline-dorado { border-color: #D4AF37; color: #D4AF37; }
        .btn-outline-dorado:hover { background-color: #D4AF37; color: white; }
        .badge-dorado { background-color: #D4AF37; color: white; }
        .border-dorado { border-color: #D4AF37 !important; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<!-- NAVBAR SUPERIOR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-vino border-dorado-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center text-dorado fw-bold fs-4" href="index.php">
            <?php if (file_exists('uploads/logo duncan.jpeg')): ?>
                <img src="uploads/logo duncan.jpeg" alt="Logo" style="height: 38px;" class="me-2 rounded">
            <?php else: ?>
                <i class="bi bi-heart-pulse-fill me-2 fs-3"></i>
            <?php endif; ?>
            <span>Duncan Mascotas</span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <?php if ($esta_autenticado): ?>
                <span class="text-light me-2 small">
                    <i class="bi bi-person-circle text-dorado me-1 fs-6"></i>
                    <strong><?= $nombre_usuario ?></strong>
                    <?php if ($rol_usuario !== 'visor'): ?>
                        <span class="badge bg-secondary ms-1"><?= ucfirst($rol_usuario) ?></span>
                    <?php endif; ?>
                </span>

                <?php if ($rol_usuario === 'administrador' || $rol_usuario === 'autor'): ?>
                    <a href="admin/panel.php" class="btn btn-outline-dorado btn-sm">
                        <i class="bi bi-speedometer2"></i> Panel Admin
                    </a>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-sm btn-light text-vino fw-semibold">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-dorado btn-sm px-3">Iniciar Sesión</a>
                <a href="registro.php" class="btn btn-vino btn-sm px-3 border border-light">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- CONTENIDO PRINCIPAL -->
<main class="container py-5 flex-grow-1" style="max-width: 900px;">
    
    <div class="mb-4">
        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Regresar al listado
        </a>
    </div>

    <?php if (!$publicacion_existe): ?>
        <div class="card shadow border-0 rounded-4 p-5 text-center my-4">
            <i class="bi bi-exclamation-diamond text-vino display-1 mb-3"></i>
            <h2 class="text-vino fw-bold mb-2">Publicación no disponible</h2>
            <p class="text-muted mb-4">El artículo solicitado no existe o fue removido.</p>
            <div>
                <a href="index.php" class="btn btn-vino px-4 py-2 fw-semibold">Volver a la página principal</a>
            </div>
        </div>
    <?php else: ?>
        <article class="card shadow border-0 rounded-4 card-blog overflow-hidden mb-5">
            <div class="card-body p-4 p-md-5">
                <h1 class="display-6 fw-bold text-vino mb-3"><?= htmlspecialchars($post['titulo']) ?></h1>

                <div class="d-flex align-items-center justify-content-between text-muted small pb-3 mb-4 border-bottom border-dorado-bottom">
                    <div>
                        <span class="me-3"><i class="bi bi-person-fill text-vino me-1"></i><strong><?= htmlspecialchars($post['autor']) ?></strong></span>
                        <span><i class="bi bi-calendar3 text-vino me-1"></i><?= date('d/m/Y - H:i', strtotime($post['fecha_publicacion'])) ?> hs</span>
                    </div>
                </div>

                <?php if (!empty($post['imagen']) && file_exists('uploads/' . $post['imagen'])): ?>
                    <div class="mb-4 text-center">
                        <img src="uploads/<?= htmlspecialchars($post['imagen']) ?>" class="img-fluid rounded-3 shadow-sm border border-dorado w-100" style="max-height: 480px; object-fit: cover;" alt="Portada: <?= htmlspecialchars($post['titulo']) ?>">
                    </div>
                <?php endif; ?>

                <?php if (!empty($post['etiquetas'])): ?>
                    <div class="mb-4">
                        <?php foreach (explode(',', $post['etiquetas']) as $tag): ?>
                            <?php if (!empty(trim($tag))): ?>
                                <span class="badge badge-dorado me-1 mb-1 fs-6">#<?= htmlspecialchars(trim($tag)) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="fs-5 text-dark mt-4" style="line-height: 1.8; text-align: justify;">
                    <?= nl2br(htmlspecialchars($post['contenido'])) ?>
                </div>
            </div>
        </article>

        <!-- SECCIÓN DE COMENTARIOS EN TIEMPO REAL -->
        <section id="comentarios" class="card shadow-sm border-0 rounded-4 card-blog p-4 p-md-5 mb-4">
            <h3 class="h4 fw-bold text-vino mb-4 pb-2 border-bottom border-dorado-bottom">
                <i class="bi bi-chat-square-text-fill text-dorado me-2"></i>Comentarios 
                <span id="contador-comentarios" class="badge badge-dorado ms-2 fs-6">0</span>
            </h3>

            <?php if ($esta_autenticado): ?>
                <form id="formComentario" class="mb-5">
                    <div id="alertaComentario" class="d-none alert alert-danger py-2 mb-3"></div>
                    <div class="mb-3">
                        <label for="contenido_comentario" class="form-label fw-semibold text-vino">
                            Comentar como <span class="fw-bold"><?= $nombre_usuario ?></span>:
                        </label>
                        <textarea name="contenido_comentario" id="contenido_comentario" rows="3" class="form-control border-dorado shadow-sm" placeholder="Escribe tu opinión..." required></textarea>
                    </div>
                    <button type="submit" id="btnComentar" class="btn btn-vino fw-semibold">
                        <i class="bi bi-send-fill me-1"></i> Publicar Comentario
                    </button>
                </form>
            <?php else: ?>
                <div class="p-4 bg-light rounded-3 text-center border border-dorado mb-5">
                    <i class="bi bi-person-lock fs-2 text-vino mb-2 d-block"></i>
                    <p class="text-muted mb-2">Inicia sesión para poder comentar.</p>
                    <a href="login.php" class="btn btn-vino btn-sm px-4">Iniciar Sesión</a>
                </div>
            <?php endif; ?>

            <!-- Contenedor dinámico de comentarios -->
            <div id="listaComentarios" class="d-flex flex-column gap-3">
                <div class="text-center py-3 text-muted">Cargando comentarios...</div>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer class="bg-vino text-white py-4 border-dorado-bottom mt-auto">
    <div class="container text-center">
        <p class="mb-1 text-dorado fw-bold">Duncan Mascotas &copy; <?= date('Y') ?></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SCRIPT DE TIEMPO REAL Y SWEETALERT -->
<script>
const idPublicacion = <?= $id_publicacion ?>;

// 1. MODAL DE CONFIRMACIÓN CON SWEETALERT2
function confirmarBorradoComentario(idComentario) {
    Swal.fire({
        title: '¿Eliminar comentario?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#722F37', // Color Vino
        cancelButtonColor: '#6c757d',  // Gris secundario
        confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'border border-warning rounded-4 shadow-lg'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `publicacion.php?id=${idPublicacion}&eliminar_comentario=${idComentario}`;
        }
    });
}

// 2. CARGAR COMENTARIOS AJAX
function cargarComentarios() {
    fetch(`publicacion.php?id=${idPublicacion}&action=obtener_comentarios`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('contador-comentarios').innerText = data.total;
            const contenedor = document.getElementById('listaComentarios');
            
            if (data.total === 0) {
                contenedor.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-chat-left-dots fs-3 d-block text-dorado mb-1"></i>
                        Aún no hay comentarios. ¡Sé el primero en opinar!
                    </div>`;
                return;
            }

            let html = '';
            data.comentarios.forEach(com => {
                // Etiqueta de rol (se oculta para visores)
                const badgeRol = (com.rol_texto !== 'visor') 
                    ? `<span class="badge bg-secondary opacity-75 small me-2">${com.rol_texto.charAt(0).toUpperCase() + com.rol_texto.slice(1)}</span>` 
                    : '';

                // Botón borrar: Se muestra si el JSON dice que "puede_eliminar" (Dueño o Admin)
                const botonBorrar = com.puede_eliminar 
                    ? `<button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="confirmarBorradoComentario(${com.id_comentario});" title="Eliminar comentario"><i class="bi bi-trash"></i></button>` 
                    : '';

                html += `
                    <div class="p-3 rounded-3 bg-white border border-1 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong class="text-vino me-2"><i class="bi bi-person-circle text-dorado me-1"></i>${com.usuario}</strong>
                                ${badgeRol}
                                <small class="text-muted">${com.fecha_formateada}</small>
                            </div>
                            ${botonBorrar}
                        </div>
                        <p class="mb-0 text-secondary" style="line-height: 1.5;">${com.comentario.replace(/\n/g, '<br>')}</p>
                    </div>`;
            });
            contenedor.innerHTML = html;
        });
}

// Iniciar AJAX
if (idPublicacion > 0) {
    cargarComentarios();
    setInterval(cargarComentarios, 5000);
}

// 3. ENVIAR COMENTARIO
const formComentario = document.getElementById('formComentario');
if (formComentario) {
    formComentario.addEventListener('submit', function(e) {
        e.preventDefault();
        const textarea = document.getElementById('contenido_comentario');
        const alerta = document.getElementById('alertaComentario');
        const btn = document.getElementById('btnComentar');

        btn.disabled = true;
        const formData = new FormData();
        formData.append('ajax_comentario', '1');
        formData.append('contenido_comentario', textarea.value);

        fetch(`publicacion.php?id=${idPublicacion}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.exito) {
                textarea.value = '';
                alerta.classList.add('d-none');
                cargarComentarios();
            } else {
                alerta.innerText = data.error;
                alerta.classList.remove('d-none');
            }
        });
    });
}
</script>
</body>
</html>