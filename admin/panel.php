<?php
// 1. CONTROL DE SESIÓN Y SEGURIDAD POR ROLES
session_start();
require_once '../conexion.php'; // Conexión a la base de datos MySQL

// Validar que exista una sesión iniciada
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Control de acceso corregido: Permitimos acceso si el rol es el número 2 o la palabra 'administrador'
if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] != 2 && $_SESSION['rol'] !== 'autor') {
    header("Location: ../index.php");
    exit();
}

$id_usuario_sesion = $_SESSION['id_usuario'];
$nombre_usuario    = htmlspecialchars($_SESSION['nombre']);
$rol_usuario       = $_SESSION['rol'];
// 2. Consultas a la base de datos (Usando web_publicaciones)
$sql_act = "SELECT COUNT(*) AS total FROM web_publicaciones WHERE estado = 'publicada'";
$res_act = $conn->query($sql_act);
$total_activas = $res_act ? $res_act->fetch_assoc()['total'] : 0;

$sql_bor = "SELECT COUNT(*) AS total FROM web_publicaciones WHERE estado = 'borrador'";
$res_bor = $conn->query($sql_bor);
$total_borradores = $res_bor ? $res_bor->fetch_assoc()['total'] : 0;

$sql_list = "SELECT p.id_publicacion, p.titulo, p.estado, p.etiquetas, p.fecha_publicacion, u.nom_usr AS autor 
             FROM web_publicaciones p 
             JOIN usuario u ON p.id_autor = u.id_usr 
             ORDER BY p.fecha_publicacion DESC";
$resultado_publicaciones = $conn->query($sql_list);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Duncan Mascotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Estilos de Color Vino y Dorado -->
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<!-- BARRA DE NAVEGACIÓN -->
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

<!-- CONTENIDO DEL PANEL -->
<main class="container py-5 flex-grow-1">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold text-vino"><i class="bi bi-speedometer2 text-dorado me-2"></i>Gestión de Publicaciones</h2>
            <p class="text-muted">Administra el contenido del blog de Duncan Mascotas.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <!-- Botón hacia el formulario de crear -->
            <a href="../visor/formulario.php" class="btn btn-vino shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nueva Publicación
            </a>
        </div>
    </div>

    <!-- TARJETAS DE ESTADÍSTICAS -->
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

    <!-- TABLA DE PUBLICACIONES -->
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
                                <td class="px-4 fw-medium text-dark"><?= htmlspecialchars($row['titulo']) ?></td>
                                <td><?= htmlspecialchars($row['autor']) ?></td>
                                <td>
                                    <?php if (!empty($row['etiquetas'])): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($row['etiquetas']) ?></span>
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
                                        <a href="../publicacion.php?id=<?= $row['id_publicacion'] ?>" class="btn btn-light border" title="Ver" target="_blank">
                                            <i class="bi bi-eye text-primary"></i>
                                        </a>
                                        <a href="../visor/formulario.php?id=<?= $row['id_publicacion'] ?>" class="btn btn-light border" title="Editar">
                                            <i class="bi bi-pencil text-warning"></i>
                                        </a>
                                        <a href="eliminar.php?id=<?= $row['id_publicacion'] ?>" class="btn btn-light border text-danger" title="Eliminar" onclick="return confirm('¿Seguro que deseas eliminar esta publicación?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
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
</body>
</html>