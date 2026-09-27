<?php
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

// Validar ID
$id = (int)$_GET['id'];

// Eliminar usuario
$deleted = delete_by_id('users', $id);

if ($deleted) {
    $session->msg('s', 'Usuario eliminado satisfactoriamente.');
} else {
    $session->msg('d', 'Ocurrió un error, no se pudo eliminar el usuario.');
}

// Siempre regresar al listado
redirect('users.php', false);
