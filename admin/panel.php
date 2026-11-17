<?php
// 1. CONTROL DE SESIÓN Y SEGURIDAD POR ROLES
session_start();
require_once '../conexion.php'; // Conexión a la base de datos MySQL

// Validar que exista una sesión iniciada
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Control de acceso: Solo 'administrador' y 'autor' pueden ingresar al panel (RF-WEB-02)
if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'autor') {
    header("Location: ../index.php");
    exit();
}

$id_usuario_sesion = $_SESSION['id_usuario'];
$nombre_usuario    = htmlspecialchars($_SESSION['nombre']);
$rol_usuario       = $_SESSION['rol'];

// 2. CONSULTAS DE ESTADÍSTICAS Y PUBLICACIONES
// Si es Administrador ve la métrica global; si es Autor sólo ve sus propias publicaciones
if ($rol_usuario === 'administrador') {
    $sql_act = "SELECT COUNT(*) AS total FROM web_publicaciones WHERE estado = 'publicada'";
    $sql_bor = "SELECT COUNT(*) AS total FROM web_publicaciones WHERE estado = 'borrador'";
    $sql_list = "SELECT p.id_publicacion, p.titulo, p.estado, p.etiquetas, p.fecha_publicacion, u.nom_usr AS autor 
                 FROM web_publicaciones p 
                 JOIN usuario u ON p.id_autor = u.id_usr 
                 ORDER BY p.fecha_publicacion DESC";
} else {
    // Rol 'autor': Filtra únicamente sus propios contenidos (RF-WEB-03)
    $sql_act = "SELECT COUNT(*) AS total FROM web_publicaciones WHERE estado = 'publicada' AND id_autor = $id_usuario_sesion";
    $sql_bor = "SELECT COUNT(*) AS total FROM web_publicaciones WHERE estado = 'borrador' AND id_autor = $id_usuario_sesion";
    $sql_list = "SELECT p.id_publicacion, p.titulo, p.estado, p.etiquetas, p.fecha_publicacion, u.nom_usr AS autor 
                 FROM web_publicaciones p 
                 JOIN usuario u ON p.id_autor = u.id_usr 
                 WHERE p.id_autor = $id_usuario_sesion 
                 ORDER BY p.fecha_publicacion DESC";
}

$res_act = $conn->query($sql_act);
$total_activas = $res_act ? $res_act->fetch_assoc()['total'] : 0;

$res_bor = $conn->query($sql_bor);
$total_borradores = $res_bor ? $res_bor->fetch_assoc()['total'] : 0;

// Ejecución de la consulta principal para llenar la tabla
$resultado_publicaciones = $conn->query($sql_list);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Duncan Mascotas</title>

    <!-- Bootstrap 5 e Iconos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Hoja de estilos externa centralizada (Subiendo un nivel de carpeta) -->
    <link rel="stylesheet" href="../css/styles.css">

    <!-- Librería SweetAlert2 para modales modernos e interactivos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

     <!-- 3. BARRA DE NAVEGACIÓN DEL PANEL DE CONTROL -->
<nav class="navbar navbar-expand-lg navbar-dark bg-vino border-dorado-bottom shadow-sm sticky-top">
    <div class="container">
        <!-- Logotipo e Identidad -->
        <a class="navbar-brand d-flex align-items-center text-dorado fw-bold fs-4" href="panel.php">
            <?php if (file_exists('../uploads/logo duncan.jpeg')): ?>
                <img src="../uploads/logo duncan.jpeg" alt="Logo Duncan Mascotas" style="height: 38px;" class="me-2 rounded">
            <?php else: ?>
                <i class="bi bi-shield-lock me-2 fs-3"></i>
            <?php endif; ?>
            <span>Panel Control</span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <!-- Usuario autenticado en la sesión -->
            <span class="text-light me-2 small d-none d-md-inline">
                <i class="bi bi-person-circle text-dorado me-1 fs-6"></i>
                <strong><?= $nombre_usuario ?></strong>
                <span class="badge badge-rol ms-1"><?= ucfirst($rol_usuario) ?></span>
            </span>

            <!-- Botón para ver el blog público -->
            <a href="../index.php" class="btn btn-outline-dorado btn-sm me-1">
                <i class="bi bi-globe"></i> Ver Blog
            </a>

            <!-- Cerrar sesión -->
            <a href="../logout.php" class="btn btn-sm btn-light text-vino fw-semibold">
                <i class="bi bi-box-arrow-right"></i> Salir
            </a>
        </div>
    </div>
</nav>

<!-- 4. CONTENIDO PRINCIPAL DEL PANEL -->
<main class="container py-4 flex-grow-1">

    <!-- Notificaciones del Sistema (Alertas dinámicas por parámetro GET) -->
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'creado'): ?>
            <div class="alert alert-success alert-dismissible fade show border-dorado shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2 text-success"></i>¡Publicación creada exitosamente!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'actualizado'): ?>
            <div class="alert alert-info alert-dismissible fade show border-dorado shadow-sm mb-4" role="alert">
                <i class="bi bi-pencil-square me-2 text-info"></i>Publicación actualizada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'eliminado'): ?>
            <div class="alert alert-warning alert-dismissible fade show border-dorado shadow-sm mb-4" role="alert">
                <i class="bi bi-trash-fill me-2 text-warning"></i>La publicación fue eliminada del sistema.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Encabezado del Módulo -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="h3 fw-bold text-vino mb-1">
                <i class="bi bi-speedometer2 text-dorado me-2"></i>Gestión de Publicaciones
            </h2>
            <p class="text-muted small mb-0">
                <?= $rol_usuario === 'administrador' ? 'Administra todas las publicaciones, borradores y contenidos del blog.' : 'Redacta y administra tus artículos sobre bienestar y paseo canino.' ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="crear.php" class="btn btn-vino fw-semibold shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nueva Publicación
            </a>
        </div>
    </div>

    <!-- TARJETAS DE ESTADÍSTICAS -->
    <div class="row g-3 mb-4">
        <!-- Entradas Publicadas -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 bg-vino text-white overflow-hidden">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-dorado small fw-bold text-uppercase">Publicadas</span>
                            <h3 class="display-6 fw-bold mb-0 text-white"><?= $total_activas ?></h3>
                        </div>
                        <i class="bi bi-file-earmark-check fs-1 text-dorado opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Borradores -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-4 border-dorado overflow-hidden">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Borradores</span>
                            <h3 class="display-6 fw-bold mb-0 text-vino"><?= $total_borradores ?></h3>
                        </div>
                        <i class="bi bi-file-earmark-medical fs-1 text-vino opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE PUBLICACIONES -->
    <div class="card shadow-sm border-0 rounded-3 card-blog">
        <div class="card-header bg-white py-3 border-bottom border-dorado-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-vino fw-bold fs-6">
                <i class="bi bi-list-stars text-dorado me-2"></i>Entradas Registradas
            </h5>
            <span class="badge badge-dorado"><?= $resultado_publicaciones ? $resultado_publicaciones->num_rows : 0 ?> Registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-vino">
                        <tr>
                            <th class="ps-4">Título</th>
                            <th>Autor</th>
                            <th>Estado</th>
                            <th>Etiquetas</th>
                            <th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado_publicaciones && $resultado_publicaciones->num_rows > 0): ?>
                            <?php while ($row = $resultado_publicaciones->fetch_assoc()): ?>
                                <tr>
                                    <!-- Título -->
                                    <td class="ps-4 fw-bold text-vino">
                                        <?= htmlspecialchars($row['titulo']) ?>
                                    </td>

                                    <!-- Autor -->
                                    <td class="small text-muted">
                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($row['autor']) ?>
                                    </td>

                                    <!-- Estado -->
                                    <td>
                                        <?php if ($row['estado'] === 'publicada'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                <i class="bi bi-check-circle me-1"></i>Publicada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">
                                                <i class="bi bi-clock-history me-1"></i>Borrador
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Etiquetas (RF-WEB-03) -->
                                    <td class="small">
                                        <?php if (!empty($row['etiquetas'])): ?>
                                            <?php 
                                            $tags = explode(',', $row['etiquetas']);
                                            foreach (array_slice($tags, 0, 2) as $t): 
                                            ?>
                                                <span class="badge badge-dorado me-1">#<?= htmlspecialchars(trim($t)) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted opacity-50">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Fecha -->
                                    <td class="small text-muted">
                                        <?= date('d/m/Y', strtotime($row['fecha_publicacion'])) ?>
                                    </td>

                                    <!-- BOTONES DE ACCIÓN (Con activación del SweetAlert2) -->
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <!-- Editar -->
                                            <a href="editar.php?id=<?= $row['id_publicacion'] ?>" 
                                               class="btn btn-outline-warning text-dark" 
                                               title="Editar publicación">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <!-- Eliminar (Llama a la función JS confirmarBorrado) -->
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    onclick="confirmarBorrado(<?= $row['id_publicacion'] ?>, '<?= htmlspecialchars(addslashes($row['titulo'])) ?>');" 
                                                    title="Eliminar publicación">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block text-vino mb-2"></i>
                                    No hay publicaciones registradas. Haz clic en <strong>Nueva Publicación</strong> para comenzar.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- PIE DE PÁGINA -->
<footer class="bg-vino text-white py-3 border-dorado-bottom mt-auto">
    <div class="container text-center">
        <small class="text-dorado fw-semibold">Duncan Mascotas - Panel de Control &copy; <?= date('Y') ?></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- 5. SCRIPT DE CONFIRMACIÓN CON SWEETALERT2 (OPCIÓN 2) -->
<script>
/**
 * Lanza el modal elegante de SweetAlert2 con los colores de Duncan Mascotas
 * @param {number} id - ID único de la publicación
 * @param {string} titulo - Título de la entrada a eliminar
 */
function confirmarBorrado(id, titulo) {
    Swal.fire({
        title: '¿Eliminar publicación?',
        html: `¿Estás seguro de que deseas eliminar permanentemente:<br><strong class="text-danger">"${titulo}"</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6B1D2F', // Color Vino oficial
        cancelButtonColor: '#6c757d',   // Gris secundario
        confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'border border-warning rounded-4 shadow-lg'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirige al script backend de eliminación si el usuario confirma
            window.location.href = 'eliminar.php?id=' + id;
        }
    });
}
</script>

</body>
</html>