<?php
$page_title = 'Agregar Cuenta';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);
// Procesar formulario desde modal
if (isset($_POST['account'])) {
    $req_fields = ['name', 'opening_time'];
    validate_fields($req_fields);

    if (empty($errors)) {
        $name         = remove_junk($db->escape($_POST['name']));
        $address      = remove_junk($db->escape($_POST['address']));
        $phone        = remove_junk($db->escape($_POST['phone']));
        $mail         = remove_junk($db->escape($_POST['mail']));
        $opening_time = remove_junk($db->escape($_POST['opening_time']));
        $image = upload_image($_FILES['image'], 'uploads/accounts/');
        $date         = make_date();

        $sql = "INSERT INTO accounts (
                    name,
                    address,
                    phone,
                    mail,
                    opening_time,
                    date,
                    image
                ) VALUES (
                    '{$name}',
                    '{$address}',
                    '{$phone}',
                    '{$mail}',
                    '{$opening_time}',
                    '{$date}',
                    '{$image}'
                )";

        if ($db->query($sql)) {
            $session->msg('s', ' Cuenta creada satisfactoriamente.');
        } else {
            $session->msg('d', ' Ocurrió un error, no se pudo crear la cuenta.');
        }

    } else {
        echo $errors;
        $session->msg('d', $errors);
    }
}
redirect('accounts.php', false);