<?php
// ==============================================================================
// 1. INICIO DE SESIÓN Y REDIRECCIÓN SI YA ESTÁ AUTENTICADO
// ==============================================================================
session_start();

if (isset($_SESSION['id_usuario'])) {
    // Si el usuario ya tiene sesión abierta, lo mandamos según su rol
    $rol_sesion = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
    if (in_array($rol_sesion, ['administrador', 'admin', '2'], true)) {
        header("Location: admin/panel.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

require_once 'conexion.php'; // Conexión MySQLi a la base de datos sistema_duncan

$mensaje_error = "";

// ==============================================================================
// 2. PROCESAMIENTO DEL FORMULARIO DE INICIO DE SESIÓN (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $mensaje_error = "Por favor, completa todos los campos.";
    } else {
        // Consulta preparada (evita inyección SQL) sobre la tabla compartida `usuario`
        $stmt = $conn->prepare("SELECT id_usr, nom_usr, pass, tip_usu FROM usuario WHERE mail = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();

            // Verifica contraseña encriptada (password_hash/bcrypt) o texto plano (compatibilidad con app móvil)
            if (password_verify($password, $usuario['pass']) || $password === $usuario['pass']) {
                // Regeneramos el ID de sesión para mayor seguridad al autenticar
                session_regenerate_id(true);

                // Guardamos los datos básicos del usuario en la sesión
                $_SESSION['id_usuario'] = (int) $usuario['id_usr'];
                $_SESSION['nombre']     = $usuario['nom_usr'];
                $_SESSION['tip_usu']    = $usuario['tip_usu'];

                // Normalizamos el valor de tip_usu para aceptar 'admin', 'administrador' o 2
                // (Si en tu BD el admin usa el número 1, cambia '2' por '1' en esta línea)
                $rol_bd   = strtolower(trim((string) $usuario['tip_usu']));
                $es_admin = in_array($rol_bd, ['admin', 'administrador', '2'], true);

                // Asignamos el rol estándar que usa toda la aplicación web
                $_SESSION['rol'] = $es_admin ? 'administrador' : 'visor';

                // Redirigimos al panel si es administrador, o al blog público si es usuario normal
                header("Location: " . ($es_admin ? "admin/panel.php" : "index.php"));
                exit();
            } else {
                $mensaje_error = "La contraseña ingresada es incorrecta.";
            }
        } else {
            $mensaje_error = "No existe ninguna cuenta asociada a este correo electrónico.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Duncan Mascotas</title>
    <!-- Bootstrap 5, íconos y estilos propios -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4 card-blog overflow-hidden">

                <!-- Encabezado con identidad visual (Vino y Dorado) -->
                <div class="bg-vino p-4 text-center border-bottom border-dorado-bottom">
                    <a href="index.php" class="text-decoration-none">
                        <?php if (file_exists('uploads/logo duncan.jpeg')): ?>
                            <img src="uploads/logo duncan.jpeg" alt="Logo Duncan Mascotas" style="height: 60px;" class="mb-3 rounded shadow-sm">
                        <?php else: ?>
                            <i class="bi bi-shield-lock-fill text-dorado display-4 mb-2 d-block"></i>
                        <?php endif; ?>
                        <h2 class="h4 text-dorado fw-bold mb-0">Duncan Mascotas</h2>
                    </a>
                </div>

                <!-- Cuerpo del formulario de acceso -->
                <div class="card-body p-4 p-md-5">
                    <h3 class="h5 fw-bold text-vino text-center mb-4">Iniciar Sesión</h3>

                    <!-- Alerta de error si fallan las credenciales -->
                    <?php if (!empty($mensaje_error)): ?>
                        <div class="alert alert-danger py-2 px-3 small border-danger">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold text-vino small">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-dorado text-vino"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" class="form-control border-dorado shadow-sm" placeholder="tu@correo.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold text-vino small">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-dorado text-vino"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" id="password" class="form-control border-dorado shadow-sm" placeholder="********" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-vino w-100 py-2 fw-bold mb-3 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                        </button>

                        <div class="text-center text-muted small">
                            ¿No tienes una cuenta? <a href="registro.php" class="text-dorado-oscuro fw-bold text-decoration-none">Regístrate aquí</a>
                        </div>
                        <div class="text-center mt-3">
                            <a href="index.php" class="text-secondary small text-decoration-none"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 