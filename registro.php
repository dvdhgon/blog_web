<?php
session_start();

if (isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';

$mensaje_error = "";
$mensaje_exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre        = trim($_POST['nombre']);
    $email         = trim($_POST['email']);
    $password      = $_POST['password'];
    $password_conf = $_POST['password_conf'];

    if (empty($nombre) || empty($email) || empty($password) || empty($password_conf)) {
        $mensaje_error = "Todos los campos son obligatorios.";
    } elseif ($password !== $password_conf) {
        $mensaje_error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 8) {
        $mensaje_error = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        // Verificar si el correo ya existe en tu DB móvil unificada
        $stmt_check = $conn->prepare("SELECT id_usr FROM usuario WHERE mail = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            $mensaje_error = "El correo electrónico ya se encuentra registrado.";
        } else {
            // Encriptar la contraseña (RF-WEB-01 / Seguridad)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $tip_usu = 'user'; // 1 = Cliente normal en tu app (equivale a visor en web)
            
            // Insertar en tu tabla usuario
            $stmt_ins = $conn->prepare("INSERT INTO usuario (nom_usr, mail, pass, tip_usu) VALUES (?, ?, ?, ?)");
            $stmt_ins->bind_param("sssi", $nombre, $email, $password_hash, $tip_usu);
            
            if ($stmt_ins->execute()) {
                $mensaje_exito = "¡Cuenta creada con éxito! Ya puedes iniciar sesión.";
            } else {
                $mensaje_error = "Ocurrió un error al crear la cuenta. Inténtalo de nuevo.";
            }
            $stmt_ins->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Duncan Mascotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 card-blog overflow-hidden">
                
                <div class="bg-vino p-4 text-center border-bottom border-dorado-bottom">
                    <a href="index.php" class="text-decoration-none">
                        <?php if (file_exists('uploads/logo duncan.jpeg')): ?>
                            <img src="uploads/logo duncan.jpeg" alt="Logo Duncan Mascotas" style="height: 60px;" class="mb-3 rounded shadow-sm">
                        <?php else: ?>
                            <i class="bi bi-person-plus-fill text-dorado display-4 mb-2 d-block"></i>
                        <?php endif; ?>
                        <h2 class="h4 text-dorado fw-bold mb-0">Únete a la Comunidad</h2>
                    </a>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    <?php if (!empty($mensaje_exito)): ?>
                        <div class="alert alert-success py-3 text-center border-success mb-4">
                            <i class="bi bi-check-circle-fill me-1 fs-5 d-block mb-1"></i>
                            <?= htmlspecialchars($mensaje_exito) ?><br>
                            <a href="login.php" class="btn btn-success btn-sm mt-2 fw-bold px-4">Ir a Iniciar Sesión</a>
                        </div>
                    <?php else: ?>
                        
                        <h3 class="h5 fw-bold text-vino text-center mb-4">Crear nueva cuenta</h3>

                        <?php if (!empty($mensaje_error)): ?>
                            <div class="alert alert-danger py-2 px-3 small border-danger">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($mensaje_error) ?>
                            </div>
                        <?php endif; ?>

                        <form action="registro.php" method="POST">
                            <div class="mb-3">
                                <label for="nombre" class="form-label fw-semibold text-vino small">Nombre Completo</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-dorado text-vino"><i class="bi bi-person"></i></span>
                                    <input type="text" name="nombre" id="nombre" class="form-control border-dorado shadow-sm" placeholder="Ej. Juan Pérez" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold text-vino small">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-dorado text-vino"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control border-dorado shadow-sm" placeholder="tu@correo.com" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-semibold text-vino small">Contraseña</label>
                                    <input type="password" name="password" id="password" class="form-control border-dorado shadow-sm" placeholder="Mínimo 6 caracteres" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="password_conf" class="form-label fw-semibold text-vino small">Confirmar Contraseña</label>
                                    <input type="password" name="password_conf" id="password_conf" class="form-control border-dorado shadow-sm" placeholder="Repite tu contraseña" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-vino w-100 py-2 fw-bold mb-3 shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Registrarse
                            </button>

                            <div class="text-center text-muted small">
                                ¿Ya tienes una cuenta? <a href="login.php" class="text-dorado-oscuro fw-bold text-decoration-none">Inicia sesión aquí</a>
                            </div>
                            <div class="text-center mt-3">
                                <a href="index.php" class="text-secondary small text-decoration-none"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>