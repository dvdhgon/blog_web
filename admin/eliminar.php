<?php
session_start();
require_once '../conexion.php';
require_once '../includes/auth.php'; //Verificación centralizada

// Verificar que sea admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== ROL_ADMIN) {
    header("Location: ../index.php");
    exit();
}

// Recibe por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id_publicacion = intval($_POST['id']);
    
    // 1. Consultar la imagen para borrarla del disco
    $stmt = $conn->prepare("SELECT imagen FROM web_publicaciones WHERE id_publicacion = ?");
    $stmt->bind_param("i", $id_publicacion);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($post = $res->fetch_assoc()) {
        // 2. Eliminar la imagen física del servidor
        if (!empty($post['imagen']) && file_exists('../uploads/' . $post['imagen'])) {
            unlink('../uploads/' . $post['imagen']);
        }

        // 3. Eliminar la publicación (Los comentarios se borran solos si tienes CASCADE)
        // Como es admin, borra sin importar el id_autor
        $stmt_del = $conn->prepare("DELETE FROM web_publicaciones WHERE id_publicacion = ?");
        $stmt_del->bind_param("i", $id_publicacion);
        $stmt_del->execute();
        $stmt_del->close();
    }
    $stmt->close();
}

header("Location: panel.php?msg=eliminado");
exit();
?>