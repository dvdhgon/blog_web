<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../conexion.php';

// Solo permitimos el acceso a usuarios con rol visor
requerir_rol([ROL_VISOR], '../login.php', '../admin/panel.php');

$id_usuario = usuario_id();
$vista = (isset($_GET['vista']) && $_GET['vista'] === 'blog') ? 'blog' : 'mias';

// 1. Obtener estadísticas del visor
$stmt_stats = $conn->prepare("SELECT 
    COALESCE(SUM(estado='publicada'), 0) AS pub, 
    COALESCE(SUM(estado='borrador'), 0) AS bor 
    FROM web_publicaciones WHERE id_autor = ?");
$stmt_stats->bind_param("i", $id_usuario);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// 2. Obtener total de publicaciones en el blog general
$res_total = $conn->query("SELECT COUNT(*) AS t FROM web_publicaciones WHERE estado = 'publicada'");
$total_blog = $res_total->fetch_assoc()['t'];

// 3. Consultar las publicaciones según la vista seleccionada
if ($vista === 'mias') {
    $stmt_list = $conn->prepare("SELECT id_publicacion, titulo, estado, etiquetas, fecha_publicacion 
                                 FROM web_publicaciones 
                                 WHERE id_autor = ? 
                                 ORDER BY fecha_publicacion DESC");
    $stmt_list->bind_param("i", $id_usuario);
} else {
    $stmt_list = $conn->prepare("SELECT p.id_publicacion, p.titulo, p.etiquetas, p.fecha_publicacion, u.nom_usr AS autor 
                                 FROM web_publicaciones p 
                                 JOIN usuario u ON p.id_autor = u.id_usr 
                                 WHERE p.estado = 'publicada' 
                                 ORDER BY p.fecha_publicacion DESC LIMIT 50");
}
$stmt_list->execute();
$filas = $stmt_list->get_result();

$mensajes = [
    'creado'      => ['success', 'Publicación creada exitosamente.'],
    'actualizado' => ['info',    'Publicación actualizada correctamente.'],
    'eliminado'   => ['warning', 'La publicación fue eliminada de forma permanente.'],
];
$msg_key = $_GET['msg'] ?? '';
$msg = $mensajes[$msg_key] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Dashboard - Duncan Mascotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-vino border-dorado-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center text-dorado fw-bold fs-4" href="dashboard.php">
            <i class="bi bi-shield-lock me-2 fs-3"></i><span>Dashboard Visor</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-light me-3 small">
                <i class="bi bi-person-circle text-dorado me-1"></i><strong><?= e($_SESSION['nombre']) ?></strong>
            </span>
            <a href="../index.php" class="btn btn-outline-dorado btn-sm"><i class="bi bi-globe"></i> Ver Blog</a>
            <a href="../logout.php" class="btn btn-sm btn-light text-vino fw-semibold"><i class="bi bi-box-arrow-right"></i> Salir</a>
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg[0] ?> alert-dismissible fade show border-dorado shadow-sm mb-4">
            <?= e($msg[1]) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="h3 fw-bold text-vino mb-1"><i class="bi bi-speedometer2 text-dorado me-2"></i>Bienvenido, <?= e($_SESSION['nombre']) ?></h2>
            <p class="text-muted small mb-0">Gestiona tus artículos o explora el contenido público de la comunidad.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="formulario.php" class="btn btn-vino fw-semibold shadow-sm"><i class="bi bi-plus-circle me-1"></i> Nueva Publicación</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-vino text-white">
                <div class="card-body p-3">
                    <span class="text-dorado small fw-bold text-uppercase">Mis publicadas</span>
                    <h3 class="display-6 fw-bold mb-0"><?= (int)$stats['pub'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white border-start border-4 border-dorado">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">Mis borradores</span>
                    <h3 class="display-6 fw-bold mb-0 text-vino"><?= (int)$stats['bor'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white border-start border-4 border-dorado">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">Blog (Total)</span>
                    <h3 class="display-6 fw-bold mb-0 text-vino"><?= $total_blog ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $vista === 'mias' ? 'active text-vino fw-bold' : 'text-secondary' ?>" href="?vista=mias"><i class="bi bi-journal-text me-1"></i>Mis publicaciones</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $vista === 'blog' ? 'active text-vino fw-bold' : 'text-secondary' ?>" href="?vista=blog"><i class="bi bi-journal-richtext me-1"></i>Publicaciones del blog</a>
        </li>
    </ul>

    <!-- Tabla -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-vino">
                        <tr>
                            <th class="ps-4">Título</th>
                            <th><?= $vista === 'mias' ? 'Estado' : 'Autor' ?></th>
                            <th>Etiquetas</th>
                            <th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($filas->num_rows === 0): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron publicaciones en esta sección.</td></tr>
                    <?php endif; ?>
                    <?php while ($row = $filas->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-vino"><?= e($row['titulo']) ?></td>
                            <td class="small">
                                <?php if ($vista === 'mias'): ?>
                                    <span class="badge <?= $row['estado'] === 'publicada' ? 'bg-success text-white' : 'bg-warning text-dark' ?> border"><?= ucfirst($row['estado']) ?></span>
                                <?php else: ?>
                                    <i class="bi bi-person-fill text-muted me-1"></i><?= e($row['autor']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?php foreach (explode(',', (string)$row['etiquetas']) as $t): $t = trim($t); if($t): ?>
                                    <span class="badge bg-light text-dark border me-1">#<?= e($t) ?></span>
                                <?php endif; endforeach; ?>
                            </td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($row['fecha_publicacion'])) ?></td>
                            <td class="text-center text-nowrap">
                                <a href="../publicacion.php?id=<?= (int)$row['id_publicacion'] ?>" class="btn btn-outline-secondary btn-sm" title="Leer"><i class="bi bi-eye"></i></a>
                                <?php if ($vista === 'mias'): ?>
                                    <a href="formulario.php?id=<?= (int)$row['id_publicacion'] ?>" class="btn btn-outline-warning text-dark btn-sm" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<footer class="bg-vino text-white py-3 border-dorado-bottom mt-auto">
    <div class="container text-center"><small class="text-dorado">Duncan Mascotas &copy; <?= date('Y') ?></small></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>