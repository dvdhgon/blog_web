<?php
session_start();
require_once 'conexion.php'; 

$esta_autenticado = isset($_SESSION['id_usuario']);
$nombre_usuario   = $esta_autenticado ? htmlspecialchars($_SESSION['nombre']) : '';
$rol_usuario      = $esta_autenticado ? $_SESSION['rol'] : '';

$por_pagina = 6;
$pagina_actual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

$where_sql = "WHERE p.estado = 'publicada'";
$params = [];
$tipos = "";

$termino_busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$tag_busqueda = isset($_GET['tag']) ? trim($_GET['tag']) : '';

if (!empty($termino_busqueda)) {
    $where_sql .= " AND (p.titulo LIKE ? OR p.contenido LIKE ? OR p.etiquetas LIKE ?)";
    $like_q = "%" . $termino_busqueda . "%";
    $params = array_merge($params, [$like_q, $like_q, $like_q]);
    $tipos .= "sss";
} elseif (!empty($tag_busqueda)) {
    $where_sql .= " AND p.etiquetas LIKE ?";
    $like_tag = "%" . $tag_busqueda . "%";
    $params[] = $like_tag;
    $tipos .= "s";
}

// Apuntar a la nueva tabla web_publicaciones
$sql_count = "SELECT COUNT(*) AS total FROM web_publicaciones p " . $where_sql;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) $stmt_count->bind_param($tipos, ...$params);
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);
$stmt_count->close();

// Cambiamos 'usuarios' por 'usuario' y ajustamos nom_usr y id_usr
$sql_list = "SELECT p.id_publicacion, p.titulo, p.contenido, p.imagen, p.etiquetas, p.fecha_publicacion, u.nom_usr AS autor 
             FROM web_publicaciones p 
             JOIN usuario u ON p.id_autor = u.id_usr 
             $where_sql 
             ORDER BY p.fecha_publicacion DESC LIMIT ? OFFSET ?";

$params[] = $por_pagina;
$params[] = $offset;
$tipos .= "ii";

$stmt_list = $conn->prepare($sql_list);
$stmt_list->bind_param($tipos, ...$params);
$stmt_list->execute();
$resultado = $stmt_list->get_result();
?>