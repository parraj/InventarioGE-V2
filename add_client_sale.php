<?php
$page_title = 'Agregar clientes';
require_once('includes/load.php');
page_require_level(2);

$dni = $_GET['dni'] ?? '';
$account_sender = $_GET['account_sender'] ?? '';

if (isset($_POST['add_client'])) {
    $dni = remove_junk($db->escape($_POST['dni'] ?? ''));
    $name = remove_junk($db->escape($_POST['name'] ?? ''));
    $phone = remove_junk($db->escape($_POST['phone'] ?? ''));
    $mail = remove_junk($db->escape($_POST['mail'] ?? ''));
    $address = remove_junk($db->escape($_POST['address'] ?? ''));
    $account_sender = (int)($_POST['account_sender'] ?? 0);
    $account = (int)$_SESSION['account'];

    if ($name === '') {
        $session->msg('d', 'El nombre es obligatorio.');
        redirect('add_client_sale.php?dni=' . urlencode($dni) . '&account_sender=' . $account_sender, false);
    }

    $check_query  = "SELECT id, active ";
    $check_query .= "FROM clients ";
    $check_query .= "WHERE dni = '{$dni}' ";
    $check_query .= "AND account = '{$account}' ";
    $check_query .= "LIMIT 1";

    $existing_client = find_by_sql($check_query);

    if (!empty($existing_client)) {
        $existing_client = $existing_client[0];

        if ((int)$existing_client['active'] === 1) {
            $session->msg('d', 'Ya existe un cliente activo con ese DNI.');
            redirect('add_client_sale.php?dni=' . urlencode($dni) . '&account_sender=' . $account_sender, false);
        } else {
            $client_id = (int)$existing_client['id'];

            $update_query  = "UPDATE clients SET ";
            $update_query .= "name = '{$name}', ";
            $update_query .= "phone = '{$phone}', ";
            $update_query .= "mail = '{$mail}', ";
            $update_query .= "address = '{$address}', ";
            $update_query .= "active = 1 ";
            $update_query .= "WHERE id = {$client_id} ";
            $update_query .= "AND account = '{$account}'";

            if ($db->query($update_query)) {
                ?>
                <form id="backToSale" method="post" action="add_sale.php">
                    <input type="hidden" name="account_sender" value="<?php echo $account_sender; ?>">
                    <input type="hidden" name="dni" value="<?php echo $dni; ?>">
                </form>
                <script>
                    document.getElementById('backToSale').submit();
                </script>
                <?php
                exit;
            } else {
                $session->msg('d', 'No se pudo reactivar el cliente.');
                redirect('add_client_sale.php?dni=' . urlencode($dni) . '&account_sender=' . $account_sender, false);
            }
        }
    } else {
        $query  = "INSERT INTO clients (dni, name, phone, mail, address, account, active) VALUES (";
        $query .= "'{$dni}', '{$name}', '{$phone}', '{$mail}', '{$address}', '{$account}', 1)";

        if ($db->query($query)) {
            ?>
            <form id="backToSale" method="post" action="add_sale.php">
                <input type="hidden" name="account_sender" value="<?php echo $account_sender; ?>">
                <input type="hidden" name="dni" value="<?php echo $dni; ?>">
            </form>
            <script>
                document.getElementById('backToSale').submit();
            </script>
            <?php
            exit;
        } else {
            $session->msg('d', 'No se pudo crear el cliente.');
            redirect('add_client_sale.php?dni=' . urlencode($dni) . '&account_sender=' . $account_sender, false);
        }
    }
}
?>
<?php include_once('layouts/header.php'); ?>
<?php echo display_msg($msg); ?>

<div class="row">
    <div class="col-md-12">

        <h5 class="mb-4">
            <i class="bi bi-person-plus me-2"></i>Agregar cliente
        </h5>

        <div class="row">
            <div class="col-md-6">
                <form method="post" action="add_client_sale.php" autocomplete="off">

                    <input type="hidden" name="account_sender" value="<?php echo (int)$account_sender; ?>">

                    <div class="mb-3">
                        <label class="form-label">DNI</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                            <input type="text"
                                   class="form-control"
                                   name="dni"
                                   value="<?php echo $dni; ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text"
                                   class="form-control"
                                   name="name"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text"
                                   class="form-control"
                                   name="phone">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email"
                                   class="form-control"
                                   name="mail">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <div class="input-group">
                            <span class="input-group-text align-items-start pt-2">
                                <i class="bi bi-geo-alt"></i>
                            </span>
                            <textarea
                                class="form-control"
                                name="address"
                                id="address"
                                rows="4"
                                style="resize: none; white-space: pre-wrap;"
                            ></textarea>
                        </div>
                    </div>

                    <button type="submit" name="add_client" class="btn btn-primary">Guardar</button>
                    <a href="sales.php" class="btn btn-danger">Cancelar</a>

                </form>
            </div>
        </div>

    </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const address = document.getElementById('address');
    if (address && address.value.trim() === '') {
        address.value = "Dirección:\nLocalidad:\nCódigo Postal:";
    }
});
</script>