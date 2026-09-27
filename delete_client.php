<?php
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

// Validar ID
$id = (int)$_GET['id'];
$client = find_by_id('clients', $id);

if (!$client) {
    $session->msg('d', 'ID del cliente no válido.');
    redirect('client.php', false);
}

// Eliminar cliente
$deleted = delete_by_id_status('clients', $id);

if ($deleted) {
    $session->msg('s', 'Cliente eliminado satisfactoriamente.');
} else {
    $session->msg('d', 'Ocurrió un error, no se pudo eliminar el cliente.');
}

// Siempre regresar al listado
redirect('client.php', false);
