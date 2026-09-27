<?php
/**
 * Editar Empleado
 * - Permite actualizar la información de un empleado existente
 */

$page_title = 'Editar Empleado';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

/* =====================================================
 * OBTENER EMPLEADO POR ID
 * ===================================================== */
$employee = find_by_id('employees', (int)$_GET['id']);

if (!$employee) {
    $session->msg('d', 'ID del empleado no encontrado.');
    redirect('employee.php', false);
}

/* =====================================================
 * PROCESAR ACTUALIZACIÓN
 * ===================================================== */
if (isset($_POST['employee'])) {

    $req_fields = ['dni','name'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $dni   = remove_junk($db->escape($_POST['dni']));
        $name  = remove_junk($db->escape($_POST['name']));
        $phone = remove_junk($db->escape($_POST['phone']));
        $mail  = remove_junk($db->escape($_POST['mail']));

        $sql = "UPDATE employees SET
                    dni   = '{$dni}',
                    name  = '{$name}',
                    phone = '{$phone}',
                    mail  = '{$mail}'
                WHERE id = '{$employee['id']}'";

        $result = $db->query($sql);

        if ($result) {
            $session->msg('s', 'Empleado modificado satisfactoriamente.');
        } else {
            $session->msg('d', 'Ocurrió un error, no se pudo modificar el empleado.');
        }

        redirect('employee.php', false);

    } else {
        $session->msg('d', $errors);
        redirect('edit_employee.php?id='.(int)$employee['id'], false);
    }
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">

        <!-- Título -->
        <h4 class="mb-4">
            <i class="bi bi-person-badge-fill me-2"></i>
            Editar Empleado
        </h4>

        <!-- Formulario -->
        <form method="POST"
              action="edit_employee.php?id=<?php echo (int)$employee['id']; ?>"
              autocomplete="off">

            <!-- DNI -->
            <div class="mb-3">
                <label class="form-label">DNI</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-upc-scan"></i>
                    </span>
                    <input
                        type="text"
                        name="dni"
                        class="form-control"
                        value="<?php echo remove_junk($employee['dni']); ?>"
                        required>
                </div>
            </div>

            <!-- Nombre -->
            <div class="mb-3">
                <label class="form-label">Nombre Completo</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person-fill"></i>
                    </span>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?php echo remove_junk($employee['name']); ?>"
                        required>
                </div>
            </div>

            <!-- Teléfono -->
            <div class="mb-3">
                <label class="form-label">Teléfono</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-telephone-fill"></i>
                    </span>
                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?php echo remove_junk($employee['phone']); ?>">
                </div>
            </div>

            <!-- Correo -->
            <div class="mb-3">
                <label class="form-label">Correo</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope-fill"></i>
                    </span>
                    <input
                        type="email"
                        name="mail"
                        class="form-control"
                        value="<?php echo remove_junk($employee['mail']); ?>">
                </div>
            </div>

            <!-- Botones -->
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="employee" class="btn btn-primary me-2">
                    Actualizar
                </button>
                <a href="employee.php" class="btn btn-danger">
                    Cancelar
                </a>
            </div>

        </form>

    </div>
</div>

<?php include_once('layouts/footer.php'); ?>