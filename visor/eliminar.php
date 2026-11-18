<?php
session_start();
require_once '../conexion.php';
require_once '../includes/auth.php'; 

// Permitir que tanto ADMIN como VISOR entren
requerir_rol([ROL_VISOR, ROL_ADMIN], '../login.php', '../index.php');

$id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : usuario_id();
$rol_actual = $_SESSION['rol'] ?? '';
$es_admin = ($rol_actual === 'administrador' || $rol_actual === ROL_ADMIN);

// Ruta dinámica de regreso
$ruta_retorno = $es_admin ? "../admin/panel.php" : "dashboard.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id_publicacion = intval($_POST['id']);
    
    // Consultar para borrar imagen
    if ($es_admin) {
        // Admin puede buscar cualquier publicación
        $stmt = $conn->prepare("SELECT imagen FROM web_publicaciones WHERE id_publicacion = ?");
        $stmt->bind_param("i", $id_publicacion);
    } else {
        // Visor solo puede buscar la suya
        $stmt = $conn->prepare("SELECT imagen FROM web_publicaciones WHERE id_publicacion = ? AND id_autor = ?");
        $stmt->bind_param("ii", $id_publicacion, $id_usuario);
    }
    
    $stmt->execute();
    $res = $stmt->get_result();

    // Si encontró el post, procede a borrar
    if ($post = $res->fetch_assoc()) {
        // 1. Borrar la foto de la carpeta uploads (si existe)
        if (!empty($post['imagen']) && file_exists('../uploads/' . $post['imagen'])) {
            unlink('../uploads/' . $post['imagen']);
        }

        // 2. Borrar de la base de datos
        if ($es_admin) {
            $stmt_del = $conn->prepare("DELETE FROM web_publicaciones WHERE id_publicacion = ?");
            $stmt_del->bind_param("i", $id_publicacion);
        } else {
            $stmt_del = $conn->prepare("DELETE FROM web_publicaciones WHERE id_publicacion = ? AND id_autor = ?");
            $stmt_del->bind_param("ii", $id_publicacion, $id_usuario);
        }
        $stmt_del->execute();
        $stmt_del->close();
    }
    $stmt->close();
}

// Regresar al panel correspondiente con mensaje de éxito
header("Location: " . $ruta_retorno . "?msg=eliminado");
exit();
?>