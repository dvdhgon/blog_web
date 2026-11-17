<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duncan Mascotas - Cuidado y Paseo Canino</title>
    
    <!-- Bootstrap 5 e Iconos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Hoja de estilos externa -->
    <link rel="stylesheet" href="css/styles.css">
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
            <?php if (isset($esta_autenticado) && $esta_autenticado): ?>
                <span class="text-light me-2 small d-none d-md-inline">
                    <i class="bi bi-person-circle text-dorado me-1 fs-6"></i>
                    <strong><?= htmlspecialchars($nombre_usuario) ?></strong>
                    
                    <!-- Solo mostramos la insignia si es administrador -->
                    <?php if ($rol_usuario === 'administrador'): ?>
                        <span class="badge badge-rol ms-1">Admin</span>
                    <?php endif; ?>
                </span>

                <!-- Botón de Panel Admin: Solo visible para administradores -->
                <?php if ($rol_usuario === 'administrador'): ?>
                    <a href="admin/panel.php" class="btn btn-outline-dorado btn-sm">
                        <i class="bi bi-speedometer2"></i> Panel Admin
                    </a>
                <?php endif; ?>

                <!-- Botón de Cerrar Sesión -->
                <a href="logout.php" class="btn btn-sm btn-light text-vino fw-semibold">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            <?php else: ?>
                <!-- Botones para usuarios no autenticados -->
                <a href="login.php" class="btn btn-outline-dorado btn-sm px-3">Iniciar Sesión</a>
                <a href="registro.php" class="btn btn-vino btn-sm px-3">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</nav>