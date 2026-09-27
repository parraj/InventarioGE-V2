<?php
/**
 * Editar Cliente
 * - Permite actualizar la información de un cliente existente
 */

$page_title = 'Editar Cliente';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(2);

/* =====================================================
 * OBTENER CLIENTE POR ID
 * ===================================================== */
$client = find_by_id('clients', (int)$_GET['id']);

if (!$client) {
    $session->msg('d', 'ID del cliente no encontrado.');
    redirect('client.php', false);
}

/* =====================================================
 * PROCESAR ACTUALIZACIÓN
 * ===================================================== */
if (isset($_POST['client'])) {

    $req_fields = ['dni', 'name'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $dni     = remove_junk($db->escape($_POST['dni']));
        $name    = remove_junk($db->escape($_POST['name']));
        $phone   = remove_junk($db->escape($_POST['phone']));
        $mail    = remove_junk($db->escape($_POST['mail']));
        $address = remove_junk($db->escape($_POST['address']));

        $sql = "UPDATE clients SET
                    dni     = '{$dni}',
                    name    = '{$name}',
                    phone   = '{$phone}',
                    mail    = '{$mail}',
                    address = '{$address}'
                WHERE id = '{$client['id']}'";

        $result = $db->query($sql);

        if ($result && $db->affected_rows() === 1) {
            $session->msg('s', 'Cliente modificado satisfactoriamente.');
        } else {
            $session->msg('d', 'Ocurrió un error, no se pudo modificar el cliente.');
        }

        redirect('client.php', false);

    } else {
        $session->msg('d', $errors);
        redirect('client.php', false);
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
            <i class="bi bi-person-fill me-2"></i>
            Editar Cliente
        </h4>

        <!-- Formulario -->
        <form method="POST"
              action="edit_client.php?id=<?php echo (int)$client['id']; ?>"
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
                        value="<?php echo remove_junk($client['dni']); ?>"
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
                        value="<?php echo remove_junk($client['name']); ?>"
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
                        value="<?php echo remove_junk($client['phone']); ?>">
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
                        value="<?php echo remove_junk($client['mail']); ?>">
                </div>
            </div>

            <!-- Dirección -->
<div class="mb-3">
    <label class="form-label">Dirección</label>
    <div class="input-group">
        <span class="input-group-text align-items-start pt-2">
            <i class="bi bi-geo-alt"></i>
        </span>
        <textarea
            name="address"
            id="address"
            class="form-control"
            rows="4"
            style="resize: none; white-space: pre-wrap;"
        ><?php echo remove_junk($client['address']); ?></textarea>
    </div>
</div>

            <!-- Botones -->
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="client" class="btn btn-primary me-2">
                    Actualizar
                </button>
                <a href="client.php" class="btn btn-danger">
                    Cancelar
                </a>
            </div>

        </form>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>