<?php
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

// Validar ID
$id = (int)$_GET['id'];
$product = find_by_id('products', $id);

if (!$product) {
    $session->msg('d', 'ID del producto no válido.');
    redirect('product.php', false);
}

// Eliminar producto
$deleted = delete_by_id_status('products', $id);

if ($deleted) {
    $session->msg('s', 'Producto eliminado satisfactoriamente.');
} else {
    $session->msg('d', 'Ocurrió un error, no se pudo eliminar el producto.');
}

// Siempre regresar al listado
redirect('product.php', false);
