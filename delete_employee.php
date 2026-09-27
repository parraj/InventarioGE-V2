<?php
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

// Validar ID
$id = (int)$_GET['id'];
$client = find_by_id('employees', $id);

if (!$client) {
    $session->msg('d', 'ID del empleado no válido.');
    redirect('employee.php', false);
}

// Eliminar cliente
$deleted = delete_by_id_status('employees', $id);

if ($deleted) {
    $session->msg('s', 'Empleado eliminado satisfactoriamente.');
} else {
    $session->msg('d', 'Ocurrió un error, no se pudo eliminar el empleado.');
}

// Siempre regresar al listado
redirect('employee.php', false);