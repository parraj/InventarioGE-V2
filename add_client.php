<?php
/**
 * Agregar Cliente
 * - Inserta un nuevo cliente
 * - Si el cliente existe con active = 0, lo reactiva y actualiza sus datos
 */

$page_title = 'Agregar Cliente';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(2);

/* =====================================================
 * PROCESAR FORMULARIO (MODAL)
 * ===================================================== */
if (isset($_POST['add_client'])) {

    $req_fields = ['dni', 'name'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $dni     = remove_junk($db->escape($_POST['dni']));
        $name    = remove_junk($db->escape($_POST['name']));
        $phone   = remove_junk($db->escape($_POST['phone']));
        $mail    = remove_junk($db->escape($_POST['mail']));
        $address = remove_junk($db->escape($_POST['address']));
        $account = (int)$_SESSION['account'];
        $date    = make_date();

        /* =====================================================
         * VERIFICAR SI EL CLIENTE YA EXISTE (DNI + ACCOUNT)
         * ===================================================== */
        $check_sql = "SELECT id, active
                      FROM clients
                      WHERE dni = '{$dni}'
                      AND account = '{$account}'
                      LIMIT 1";

        $result = $db->query($check_sql);

        if ($db->num_rows($result) > 0) {

            $client = $db->fetch_assoc($result);

            /* =====================================================
             * CLIENTE YA EXISTE Y ESTÁ ACTIVO
             * ===================================================== */
            if ($client['active'] == 1) {

                $session->msg('d', 'El cliente ya existe.');

            } else {

                /* =====================================================
                 * CLIENTE EXISTE PERO ESTÁ INACTIVO → REACTIVAR
                 * ===================================================== */
                $update_sql = "UPDATE clients SET
                                name    = '{$name}',
                                phone   = '{$phone}',
                                mail    = '{$mail}',
                                address = '{$address}',
                                active  = 1
                               WHERE id = '{$client['id']}'";

                if ($db->query($update_sql)) {
                    $session->msg('s', 'Cliente creado satisfactoriamente.');
                } else {
                    $session->msg('d', 'No se pudo reactivar el cliente.');
                }
            }

        } else {

            /* =====================================================
             * INSERTAR NUEVO CLIENTE
             * ===================================================== */
            $insert_sql = "INSERT INTO clients (
                                dni,
                                name,
                                phone,
                                mail,
                                address,
                                date,
                                account,
                                active
                           ) VALUES (
                                '{$dni}',
                                '{$name}',
                                '{$phone}',
                                '{$mail}',
                                '{$address}',
                                '{$date}',
                                '{$account}',
                                1
                           )";

            if ($db->query($insert_sql)) {
                $session->msg('s', 'Cliente creado satisfactoriamente.');
            } else {
                $session->msg('d', 'Ocurrió un error, no se pudo crear el cliente.');
            }
        }

    } else {
        $session->msg('d', $errors);
    }
}

/* =====================================================
 * REDIRECCIÓN
 * ===================================================== */
redirect('client.php', false);