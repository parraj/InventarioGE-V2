<?php
$page_title = 'Añadir Ubicación';
require_once('includes/load.php');
page_require_level(2);

$req_fields = ['name', 'address', 'location_type'];
validate_fields($req_fields);

if (empty($errors)) {

    $name = remove_junk($db->escape($_POST['name']));
    $address = remove_junk($db->escape($_POST['address']));
    $location_type = remove_junk($db->escape($_POST['location_type']));
    $account = (int)$_SESSION['account'];
    $date = make_date();

    if ($location_type !== 'Interna' && $location_type !== 'Externa') {
        $session->msg('d', 'El tipo de ubicación no es válido.');
        redirect('location.php', false);
    }

    $sql = "INSERT INTO locations (
        name,
        address,
        location_type,
        account,
        date
    ) VALUES (
        '{$name}',
        '{$address}',
        '{$location_type}',
        '{$account}',
        '{$date}'
    )";

    if ($db->query($sql)) {
        $session->msg('s', "Ubicación de inventario creada satisfactoriamente.");
    } else {
        $session->msg('d', "Ocurrió un error, no se pudo crear la ubicación de inventario.");
    }

} else {
    $session->msg("d", $errors);
}

redirect('location.php', false);
?>