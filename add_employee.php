<?php
$page_title = 'Agregar empleado';
require_once('includes/load.php');

/**
 * Nivel requerido para acceder al módulo.
 */
page_require_level(1);


/**
 * Procesa la creación o reactivación de un empleado.
 *
 * Flujo de ejecución:
 *
 * 1. Valida los campos requeridos enviados desde la modal.
 * 2. Busca si ya existe un empleado con el mismo DNI en la misma sede (account).
 * 3. Si existe y está activo → se muestra mensaje de error.
 * 4. Si existe pero está inactivo → se reactiva y actualiza su información.
 * 5. Si no existe → se inserta un nuevo empleado.
 *
 * El formulario proviene de la modal ubicada en employee.php
 */
if (isset($_POST['add_employee'])) {

    $req_fields = array('dni','name');
    validate_fields($req_fields);

    if (empty($errors)) {

        $dni   = remove_junk($db->escape($_POST['dni']));
        $name  = remove_junk($db->escape($_POST['name']));
        $phone = remove_junk($db->escape($_POST['phone']));
        $mail  = remove_junk($db->escape($_POST['mail']));
        $date  = make_date();

        $account = (int) $_SESSION['account'];

        /*--------------------------------------------------------------*/
        /*  Buscar empleado por DNI dentro de la misma sede (account)
        /*--------------------------------------------------------------*/
        $sql = "SELECT id, active
                FROM employees
                WHERE dni = '{$dni}'
                AND account = {$account}
                LIMIT 1";

        $result = $db->query($sql);

        if ($db->num_rows($result) > 0) {

            $employee = $db->fetch_assoc($result);

            /*--------------------------------------------------------------*/
            /*  Si el empleado ya existe y está activo
            /*--------------------------------------------------------------*/
            if ($employee['active'] == 1) {

                $session->msg('d', "El empleado ya existe.");
                redirect('employee.php', false);
            }

            /*--------------------------------------------------------------*/
            /*  Si el empleado existe pero está inactivo → reactivar
            /*--------------------------------------------------------------*/
            $update = "UPDATE employees SET
                        name = '{$name}',
                        phone = '{$phone}',
                        mail = '{$mail}',
                        account = '{$account}',
                        active = 1
                       WHERE id = {$employee['id']}";

            if ($db->query($update)) {

                $session->msg('s', "Empleado creado satisfactoriamente.");
                redirect('employee.php', false);

            } else {

                $session->msg('d', "No se pudo crear el empleado.");
                redirect('employee.php', false);
            }

        } else {

            /*--------------------------------------------------------------*/
            /*  Insertar nuevo empleado
            /*--------------------------------------------------------------*/
            $query = "INSERT INTO employees (
                        dni,
                        name,
                        phone,
                        mail,
                        date,
                        account,
                        active
                      ) VALUES (
                        '{$dni}',
                        '{$name}',
                        '{$phone}',
                        '{$mail}',
                        '{$date}',
                        '{$account}',
                        1
                      )";

            if ($db->query($query)) {

                $session->msg('s', "Empleado creado satisfactoriamente.");
                redirect('employee.php', false);

            } else {

                $session->msg('d', 'No se pudo crear el empleado.');
                redirect('employee.php', false);
            }
        }

    } else {

        $session->msg("d", $errors);
        redirect('employee.php', false);
    }
}
?>