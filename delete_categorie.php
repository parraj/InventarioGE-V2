<?php
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

// Validar ID
$id = (int)$_GET['id'];
$categorie = find_by_id('categories', $id);

if (!$categorie) {
    $session->msg('d', 'ID de la categoría no válido.');
    redirect('categorie.php', false);
}

// Eliminar categoría
$deleted = delete_by_id_status('categories', $id);

if ($deleted) {
    $session->msg('s', 'Categoría eliminada satisfactoriamente.');
} else {
    $session->msg('d', 'Ocurrió un error, no se pudo eliminar la categoría.');
}

// Siempre regresar al listado
redirect('categorie.php', false);