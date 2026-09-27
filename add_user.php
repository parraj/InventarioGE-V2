<?php
$page_title = 'Agregar Usuarios';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

// Procesar formulario desde modal
if (isset($_POST['add_user'])) {

    $req_fields = ['full-name', 'username', 'password', 'level'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $name       = remove_junk($db->escape($_POST['full-name']));
        $username   = remove_junk($db->escape($_POST['username']));
        $password   = remove_junk($db->escape($_POST['password']));
        $user_level = (int)$db->escape($_POST['level']);

        // Encriptar contraseña
        $password = sha1($password);

        $sql = "INSERT INTO users (
                    name,
                    username,
                    password,
                    user_level,
                    status
                ) VALUES (
                    '{$name}',
                    '{$username}',
                    '{$password}',
                    '{$user_level}',
                    '1'
                )";

        if ($db->query($sql)) {
            $session->msg('s', 'Usuario creado satisfactoriamente.');
        } else {
            $session->msg('d', 'Ocurrió un error, no se pudo crear el usuario.');
        }

    } else {
        $session->msg('d', $errors);
    }
}

// Siempre regresar al listado
redirect('users.php', false);
