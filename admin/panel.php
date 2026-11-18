<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../conexion.php';

// Proteger ruta: Solo Administrador
requerir_rol([ROL_ADMIN], '../login.php', '../visor/dashboard.php');

// Estadísticas Globales del Blog
$stats = $conn->query("SELECT 
    COALESCE(SUM(estado='publicada'),0) AS pub, 
    COALESCE(SUM(estado='borrador'),0) AS bor 
    FROM web_publicaciones")->fetch_assoc();

// Listado Completo de Publicaciones (Todas)
$stmt = $conn->query("SELECT p.id_publicacion, p.titulo, p.estado, p.etiquetas, p.fecha_publicacion, u.nom_usr AS autor 
                      FROM web_publicaciones p 
                      JOIN usuario u ON p.id_autor = u.id_usr 
                      ORDER BY p.fecha_publicacion DESC");

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Duncan Mascotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<!-- 3. BARRA DE NAVEGACIÓN DEL PANEL DE CONTROL -->
<nav class="navbar navbar-expand-lg navbar-dark bg-vino border-dorado-bottom shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center text-dorado fw-bold fs-4" href="panel.php">
            <?php if (file_exists('../uploads/logo duncan.jpeg')): ?>
                <img src="../uploads/logo duncan.jpeg" alt="Logo" style="height: 38px;" class="me-2 rounded">
            <?php else: ?>
                <i class="bi bi-shield-dog me-2 fs-3"></i>
            <?php endif; ?>
            Panel de Control
        </a>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Usuario autenticado en la sesión -->
            <span class="text-light me-2 small d-none d-md-inline">
                <i class="bi bi-person-circle text-dorado me-1 fs-6"></i>
                <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
                <span class="badge bg-secondary ms-1"><?= ucfirst($_SESSION['rol']) ?></span>
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

<main class="container py-4 flex-grow-1">
    <?php if ($msg === 'eliminado'): ?>
        <div class="alert alert-warning alert-dismissible fade show border-dorado shadow-sm mb-4">
            <i class="bi bi-trash-fill me-2 text-warning"></i>La publicación fue eliminada permanentemente del sistema.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="h3 fw-bold text-vino mb-1"><i class="bi bi-speedometer2 text-dorado me-2"></i>Gestión Global (Admin)</h2>
            <p class="text-muted small mb-0">Administra, edita o elimina cualquier publicación de la comunidad.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <!-- Redirección al formulario único -->
            <a href="../visor/formulario.php" class="btn btn-vino fw-semibold shadow-sm"><i class="bi bi-plus-circle me-1"></i> Nueva Publicación</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-vino text-white"><div class="card-body p-3">
                <span class="text-dorado small fw-bold text-uppercase">Total Publicadas</span>
                <h3 class="display-6 fw-bold mb-0"><?= (int)$stats['pub'] ?></h3>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-white border-start border-4 border-dorado"><div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase">Total Borradores</span>
                <h3 class="display-6 fw-bold mb-0 text-vino"><?= (int)$stats['bor'] ?></h3>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-vino">
                        <tr>
                            <th class="ps-4">Título</th>
                            <th>Autor</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($stmt->num_rows === 0): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No hay publicaciones en el sistema.</td></tr>
                    <?php endif; ?>
                    <?php while ($row = $stmt->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-vino"><?= e($row['titulo']) ?></td>
                            <td class="small"><i class="bi bi-person me-1"></i><?= e($row['autor']) ?></td>
                            <td class="small">
                                <span class="badge <?= $row['estado'] === 'publicada' ? 'bg-success text-white' : 'bg-warning text-dark' ?> border"><?= ucfirst($row['estado']) ?></span>
                            </td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($row['fecha_publicacion'])) ?></td>
                            <td class="text-center text-nowrap">
                                <a href="../publicacion.php?id=<?= (int)$row['id_publicacion'] ?>" class="btn btn-outline-secondary btn-sm" title="Leer"><i class="bi bi-eye"></i></a>
                                <a href="../visor/formulario.php?id=<?= (int)$row['id_publicacion'] ?>" class="btn btn-outline-warning text-dark btn-sm" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                
                                <!-- CORREGIDO: Redirige a ../visor/eliminar.php -->
                                <form method="POST" action="../visor/eliminar.php" class="d-inline form-eliminar" data-titulo="<?= e($row['titulo']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$row['id_publicacion'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </form>
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
<script>
document.querySelectorAll('.form-eliminar').forEach(form => {
    form.addEventListener('submit', ev => {
        ev.preventDefault();
        Swal.fire({
            title: '¿Eliminar publicación?', text: `Se borrará permanentemente «${form.dataset.titulo}».`,
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#6B1D2F', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        }).then(r => { if (r.isConfirmed) form.submit(); });
    });
});
</script>
</body>
</html>e