<?php
/**
 * MÓDULO DE GESTIÓN Y SUBIDA DE IMÁGENES - DUNCAN MASCOTAS
 * Este archivo contiene la función centralizada para procesar, validar y almacenar
 * imágenes en el servidor, evitando duplicación de código en crear.php y editar.php.
 */

function procesarSubidaImagen($fileInput, $directorioDestino = '../uploads/') {
    // 1. Resultado por defecto (retornará un arreglo con el estado de la operación)
    $respuesta = [
        'exito' => false,
        'nombre_archivo' => null,
        'error' => ''
    ];

    // 2. Comprobar si se envió un archivo y si no ocurrió un error durante el envío HTTP
    if (!isset($fileInput) || $fileInput['error'] !== UPLOAD_ERR_OK) {
        // Si no se subió imagen (campo opcional), retornamos éxito con valor nulo
        if ($fileInput['error'] === UPLOAD_ERR_NO_FILE) {
            $respuesta['exito'] = true;
            return $respuesta;
        }
        $respuesta['error'] = "Ocurrió un error al transferir la imagen al servidor.";
        return $respuesta;
    }

    // 3. Obtener metadatos del archivo subido
    $nombreOriginal = $fileInput['name'];
    $rutaTemporal = $fileInput['tmp_name'];
    $tamanoArchivo = $fileInput['size'];

    // 4. Extraer la extensión del archivo y convertira a minúsculas
    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

    // 5. Definir la lista blanca de extensiones permitidas (Seguridad)
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($extension, $extensionesPermitidas)) {
        $respuesta['error'] = "Formato no permitido. Solo se aceptan imágenes JPG, JPEG, PNG, WEBP o GIF.";
        return $respuesta;
    }

    // 6. Validar tamaño máximo permitido (Ejemplo: Máximo 5 Megabytes)
    $tamanoMaximo = 5 * 1024 * 1024; // 5MB en bytes
    if ($tamanoArchivo > $tamanoMaximo) {
        $respuesta['error'] = "La imagen supera el tamaño máximo permitido de 5 MB.";
        return $respuesta;
    }

    // 7. Verificar que el archivo sea realmente una imagen leyendo su cabecera MIME
    $checkMime = getimagesize($rutaTemporal);
    if ($checkMime === false) {
        $respuesta['error'] = "El archivo seleccionado no es una imagen válida.";
        return $respuesta;
    }

    // 8. Crear la carpeta de destino automáticamente si no existe en el disco
    if (!is_dir($directorioDestino)) {
        mkdir($directorioDestino, 0755, true);
    }

    // 9. Generar un nombre único para la imagen evitando que se sobrescriban archivos con el mismo nombre
    // Se combina un hash MD5 de la fecha/hora actual con un identificador único aleatorio
    $nombreFinal = "duncan_" . md5(uniqid(microtime(), true)) . "." . $extension;
    $rutaCompletaDestino = $directorioDestino . $nombreFinal;

    // 10. Mover la imagen desde la carpeta temporal de PHP a la carpeta 'uploads/' definitiva
    if (move_uploaded_file($rutaTemporal, $rutaCompletaDestino)) {
        $respuesta['exito'] = true;
        $respuesta['nombre_archivo'] = $nombreFinal;
    } else {
        $respuesta['error'] = "No se pudo guardar la imagen en el directorio de destino. Revisa permisos de carpeta.";
    }

    return $respuesta;
}
?>