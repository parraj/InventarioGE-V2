<?php
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

// Validar ID
$id = (int)$_GET['id'];
$client = find_by_id('locations', $id);

if (!$client) {
    $session->msg('d', 'ID de ubicación no válido.');
    redirect('location.php', false);
}

// Eliminar cliente
$deleted = delete_by_id_status('locations', $id);

if ($deleted) {
    $session->msg('s', 'Ubicación eliminada satisfactoriamente.');
} else {
    $session->msg('d', 'Ocurrió un error, no se pudo eliminar la ubicación.');
}

// Siempre regresar al listado
redirect('location.php', false);
