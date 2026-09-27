<?php
function upload_image($file, $folder = 'uploads/products/', $seed = null)
{
    // Si no se subió archivo
    if (!isset($file) || $file['error'] !== 0) {
        return null;
    }

    // Crear carpeta principal si no existe
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    // Carpeta de thumbnails
    $thumbFolder = rtrim($folder, '/') . '/thumbnails/';

    if (!is_dir($thumbFolder)) {
        mkdir($thumbFolder, 0777, true);
    }

    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Validar extensión
    if (!in_array($file_ext, $allowed)) {
        return false;
    }

    // Validar tamaño (5MB)
    if ($file_size > 5 * 1024 * 1024) {
        return false;
    }

    // Generar nombre único
    if ($seed) {
        $new_name = uniqid('img_' . $seed, true) . '.' . $file_ext;
    } else {
        $new_name = uniqid('img_', true) . '.' . $file_ext;
    }
    $full_path = rtrim($folder, '/') . '/' . $new_name;

    // Mover archivo original
    if (is_uploaded_file($file_tmp)) {
        if (!move_uploaded_file($file_tmp, $full_path)) {
            return false;
        }
    } else {
        if (!copy($file_tmp, $full_path)) {
            return false;
        }
    }

    // ===== CREAR THUMBNAIL =====

    // Obtener info de imagen
    list($width, $height, $type) = getimagesize($full_path);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($full_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($full_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($full_path);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($full_path);
            break;
        default:
            return $full_path; // no rompe el flujo
    }

    // Tamaño thumbnail
    $thumbWidth = 300;
    $thumbHeight = 300;

    // Mantener proporción
    $ratio = min($thumbWidth / $width, $thumbHeight / $height);
    $newWidth = (int) ($width * $ratio);
    $newHeight = (int) ($height * $ratio);

    // Crear lienzo
    $thumb = imagecreatetruecolor($newWidth, $newHeight);

    // Fondo blanco (para PNG)
    $white = imagecolorallocate($thumb, 255, 255, 255);
    imagefill($thumb, 0, 0, $white);

    // Redimensionar
    imagecopyresampled(
        $thumb,
        $source,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $width,
        $height
    );

    // Nombre del thumbnail en JPG
    $thumbName = pathinfo($new_name, PATHINFO_FILENAME) . '.jpg';
    $thumbPath = $thumbFolder . $thumbName;

    // Guardar thumbnail (JPG optimizado)
    imagejpeg($thumb, $thumbPath, 75);

    // Liberar memoria
    imagedestroy($source);
    imagedestroy($thumb);

    // Retornar ruta de la imagen original (como ya hacías)
    return $full_path;
}

?>