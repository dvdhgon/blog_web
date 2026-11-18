<?php 
session_start(); 
require_once '../conexion.php';

if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'autor')) { 
    header("Location: ../index.php"); 
    exit(); 
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {  
    $id_publicacion = intval($_GET['id']); 
    $id_usuario_sesion = $_SESSION['id_usuario']; 
    $rol_usuario = $_SESSION['rol'];

    // 1. Seleccionamos desde la nueva tabla "web_publicaciones"
    $stmt = $conn->prepare("SELECT id_autor, imagen FROM web_publicaciones WHERE id_publicacion = ?");
    $stmt->bind_param("i", $id_publicacion);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($post = $res->fetch_assoc()) {
        if ($rol_usuario === 'administrador' || $post['id_autor'] == $id_usuario_sesion) {
            
            // 2. Eliminar comentarios asociados en "web_comentarios"
            $stmt_c = $conn->prepare("DELETE FROM web_comentarios WHERE id_publicacion = ?");
            $stmt_c->bind_param("i", $id_publicacion);
            $stmt_c->execute();
            $stmt_c->close();

            // 3. Eliminar la imagen física del servidor
            if (!empty($post['imagen']) && file_exists('../uploads/' . $post['imagen'])) {
                unlink('../uploads/' . $post['imagen']);
            }

            // 4. Eliminar la publicación en "web_publicaciones"
            $stmt_del = $conn->prepare("DELETE FROM web_publicaciones WHERE id_publicacion = ?");
            $stmt_del->bind_param("i", $id_publicacion);
            $stmt_del->execute();
            $stmt_del->close();
        }
    }
    $stmt->close();
}
header("Location: panel.php?msg=eliminado"); 
exit(); 
?>