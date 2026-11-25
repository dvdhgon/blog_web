<?php
// ==============================================================================
// 1. CONTROL DE SESIÓN Y SEGURIDAD POR ROLES
// ==============================================================================
session_start();
require_once '../conexion.php'; // Conexión MySQLi a la base de datos sistema_duncan

// Verificar que exista una sesión iniciada; si no, redirigir al login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Normalizar el rol guardado en sesión para validar permisos de administrador
$rol_actual = strtolower(trim((string) ($_SESSION['rol'] ?? $_SESSION['tip_usu'] ?? '')));
$roles_permitidos = ['administrador', 'admin', '2', 'autor'];

// Si el usuario no tiene rol de administrador, se le devuelve al blog público
if (!in_array($rol_actual, $roles_permitidos, true)) {
    header("Location: ../index.php");
    exit();
}

// Variables seguras para mostrar en la interfaz (evita XSS con htmlspecialchars)
$id_usuario_sesion = (int) $_SESSION['id_usuario'];
$nombre_usuario    = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador', ENT_QUOTES, 'UTF-8');

// ==============================================================================
// 2. CONSULTAS DE MÉTRICAS Y LISTADO DE PUBLICACIONES (sistema_duncan)
// ==============================================================================
// Conteo de artículos con estado 'publicada'
$sql_act = "SELECT COUNT(*) AS total FROM web_publicaciones WHERE estado = 'publicada'";
$res_act = $conn->query($sql_act);
$total_activas = $res_act ? (int) $res_act->fetch_assoc()['total'] : 0;

// Conteo de artículos con estado 'borrador'
$sql_bor = "SELECT COUNT(*) AS total FROM web_publicaciones WHERE estado = 'borrador'";
$res_bor = $conn->query($sql_bor);
$total_borradores = $res_bor ? (int) $res_bor->fetch_assoc()['total'] : 0;

// Listado general de publicaciones uniendo el nombre del autor desde la tabla compartida `usuario`
// Se usa LEFT JOIN para que la publicación aparezca incluso si el usuario autor ya no existe
$sql_list = "SELECT p.id_publicacion, p.titulo, p.estado, p.etiquetas, p.fecha_publicacion,
                    COALESCE(u.nom_usr, 'Sin autor') AS autor
             FROM web_publicaciones p
             LEFT JOIN usuario u ON p.id_autor = u.id_usr
             ORDER BY p.fecha_publicacion DESC";
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

    <!-- Paleta de colores oficial (Vino y Dorado) -->
    <style>
        .border-dorado-bottom { border-bottom: 3px solid #D4AF37 !important; }
        .text-dorado { color: #D4AF37 !important; }
        .text-vino { color: #6B1D2F !important; }
        .bg-vino { background-color: #6B1D2F !important; }
        .btn-vino { background-color: #6B1D2F; color: white; border: none; }
        .btn-vino:hover { background-color: #4a1420; color: white; }
        .btn-outline-dorado { color: #D4AF37; border-color: #D4AF37; }
        .btn-outline-dorado:hover { background-color: #D4AF37; color: white; }
    </style>

    <!-- SweetAlert2 para confirmaciones visuales antes de eliminar -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<!-- ==============================================================================
     3. BARRA DE NAVEGACIÓN DEL PANEL
     ============================================================================== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-vino border-dorado-bottom shadow-sm sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center text-dorado fw-bold fs-4" href="panel.php">
            <?php if (file_exists('../uploads/logo duncan.jpeg')): ?>
                <img src="../uploads/logo duncan.jpeg" alt="Logo Duncan Mascotas" style="height: 38px;" class="me-2 rounded">
            <?php else: ?>
                <i class="bi bi-shield-dog me-2 fs-3"></i>
            <?php endif; ?>
            Panel de Control
        </a>

        <div class="d-flex align-items-center gap-2">
            <!-- Nombre del administrador en sesión -->
            <span class="text-light me-2 small d-none d-md-inline">
                <i class="bi bi-person-circle text-dorado me-1 fs-6"></i>
                Hola, <strong><?= $nombre_usuario ?></strong>
            </span>
            <a href="../index.php" class="btn btn-outline-dorado btn-sm me-1">
                <i class="bi bi-globe"></i> Ver Blog
            </a>
            <a href="../logout.php" class="btn btn-sm btn-light text-vino fw-semibold">
                <i class="bi bi-box-arrow-right"></i> Salir
            </a>
        </div>
    </div>
</nav>

<!-- ==============================================================================
     4. CONTENIDO PRINCIPAL DEL DASHBOARD
     ============================================================================== -->
<main class="container py-5 flex-grow-1">

    <!-- Alertas de retroalimentación tras crear, actualizar o eliminar -->
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'creado'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>¡Publicación creada exitosamente!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'actualizado'): ?>
            <div class="alert alert-info alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-pencil-square me-2"></i>Publicación actualizada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'eliminado'): ?>
            <div class="alert alert-warning alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-trash-fill me-2"></i>Publicación eliminada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Encabezado y botón de nueva publicación -->
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold text-vino"><i class="bi bi-speedometer2 text-dorado me-2"></i>Gestión de Publicaciones</h2>
            <p class="text-muted">Administra el contenido del blog de Duncan Mascotas.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../visor/formulario.php" class="btn btn-vino shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nueva Publicación
            </a>
        </div>
    </div>

    <!-- Tarjetas de resumen (Publicadas y Borradores) -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                        <i class="bi bi-check-circle-fill fs-3"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle text-muted mb-1">Publicaciones Activas</h6>
                        <h2 class="card-title fw-bold mb-0"><?= $total_activas ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3 me-3">
                        <i class="bi bi-file-earmark-text-fill fs-3"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle text-muted mb-1">Borradores</h6>
                        <h2 class="card-title fw-bold mb-0"><?= $total_borradores ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla CRUD de publicaciones -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-4">
            <h5 class="mb-0 fw-bold text-vino"><i class="bi bi-list-ul me-2"></i>Todas las Publicaciones</h5>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3 text-secondary">Título</th>
                        <th class="py-3 text-secondary">Autor</th>
                        <th class="py-3 text-secondary">Etiquetas</th>
                        <th class="py-3 text-secondary">Fecha</th>
                        <th class="py-3 text-secondary">Estado</th>
                        <th class="px-4 py-3 text-end text-secondary">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado_publicaciones && $resultado_publicaciones->num_rows > 0): ?>
                        <?php while ($row = $resultado_publicaciones->fetch_assoc()): ?>
                            <tr>
                                <td class="px-4 fw-medium text-dark"><?= htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['autor'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (!empty($row['etiquetas'])): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($row['etiquetas'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d M Y', strtotime($row['fecha_publicacion'])) ?></td>
                                <td>
                                    <?php if ($row['estado'] === 'publicada'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3">Publicada</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-3">Borrador</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 text-end">
                                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                                        <!-- Ver artículo en el blog público -->
                                        <a href="../publicacion.php?id=<?= (int) $row['id_publicacion'] ?>" class="btn btn-light border" title="Ver" target="_blank">
                                            <i class="bi bi-eye text-primary"></i>
                                        </a>
                                        <!-- Editar artículo en el formulario compartido -->
                                        <a href="../visor/formulario.php?id=<?= (int) $row['id_publicacion'] ?>" class="btn btn-light border" title="Editar">
                                            <i class="bi bi-pencil text-warning"></i>
                                        </a>
                                        <!-- Eliminar artículo con confirmación de SweetAlert2 -->
                                        <button type="button"
                                                class="btn btn-light border text-danger"
                                                title="Eliminar"
                                                onclick="confirmarBorrado(<?= (int) $row['id_publicacion'] ?>, '<?= htmlspecialchars(addslashes($row['titulo']), ENT_QUOTES, 'UTF-8') ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No hay publicaciones aún.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- ==============================================================================
     5. CONFIRMACIÓN DE ELIMINACIÓN CON SWEETALERT2
     ============================================================================== -->
<script>
function confirmarBorrado(id, titulo) {
    Swal.fire({
        title: '¿Eliminar publicación?',
        html: `¿Seguro que deseas eliminar permanentemente:<br><strong class="text-danger">"${titulo}"</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6B1D2F',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'eliminar.php?id=' + id;
        }
    });
}
</script>
</body>
</html>