<?php
// CONFIGURACIÓN
$carpetaOrigen = 'uploads/products/';       
$carpetaThumbs = 'uploads/products/thumbnails/';   
$anchoThumb = 300;
$altoThumb = 300;

// Crear carpeta de thumbnails si no existe
if (!is_dir($carpetaThumbs)) {
    mkdir($carpetaThumbs, 0755, true);
}

// Función para crear thumbnail en JPG
function crearThumbnail($origen, $destino, $ancho, $alto) {

    list($anchoOrig, $altoOrig, $tipo) = getimagesize($origen);

    // Crear imagen original según tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imgOrig = imagecreatefromjpeg($origen);
            break;
        case IMAGETYPE_PNG:
            $imgOrig = imagecreatefrompng($origen);
            break;
        case IMAGETYPE_GIF:
            $imgOrig = imagecreatefromgif($origen);
            break;
        default:
            return false;
    }

    // Mantener proporción
    $ratio = min($ancho / $anchoOrig, $alto / $altoOrig);
    $nuevoAncho = (int)($anchoOrig * $ratio);
    $nuevoAlto = (int)($altoOrig * $ratio);

    // Crear lienzo
    $thumb = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

    // Fondo blanco (para PNG con transparencia)
    $blanco = imagecolorallocate($thumb, 255, 255, 255);
    imagefill($thumb, 0, 0, $blanco);

    // Redimensionar
    imagecopyresampled(
        $thumb, $imgOrig,
        0, 0, 0, 0,
        $nuevoAncho, $nuevoAlto,
        $anchoOrig, $altoOrig
    );

    // Guardar SIEMPRE como JPG
    imagejpeg($thumb, $destino, 75);

    // Liberar memoria
    imagedestroy($imgOrig);
    imagedestroy($thumb);

    return true;
}

// Leer archivos
$archivos = scandir($carpetaOrigen);

echo "<pre>";

foreach ($archivos as $archivo) {

    $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {

        $rutaOriginal = $carpetaOrigen . $archivo;

        // Nombre del thumbnail en JPG
        $nombreThumb = pathinfo($archivo, PATHINFO_FILENAME) . '.jpg';
        $rutaThumb = $carpetaThumbs . $nombreThumb;

        // Crear solo si no existe
        if (!file_exists($rutaThumb)) {

            if (crearThumbnail($rutaOriginal, $rutaThumb, $anchoThumb, $altoThumb)) {
                echo "✔ Thumbnail creado: $nombreThumb\n";
            } else {
                echo "✖ Error con: $archivo\n";
            }

        } else {
            echo "➖ Ya existe: $nombreThumb\n";
        }
    }
}

echo "</pre>";
?>