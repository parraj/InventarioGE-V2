<?php
/**
 * Editar Cuenta de Usuario
 * - Permite actualizar la foto del usuario
 * - Permite actualizar nombre y username
 */

$page_title = 'Editar Cuenta';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(2);


/* =====================================================
 * ACTUALIZAR INFORMACIÓN DEL USUARIO
 * ===================================================== */
if (isset($_POST['update'])) {

    $req_fields = ['name', 'username'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $id = (int) $_SESSION['user_id'];
        $name = remove_junk($db->escape($_POST['name']));
        $username = remove_junk($db->escape($_POST['username']));

        $sql = "UPDATE users 
                SET name = '{$name}', username = '{$username}' 
                WHERE id = '{$id}'";

        $result = $db->query($sql);

        if ($result && $db->affected_rows() === 1) {
            $session->msg('s', 'Cuenta actualizada.');
        } else {
            $session->msg('d', 'Lo siento, la actualización falló.');
        }

        redirect('edit_account.php', false);

    } else {
        $session->msg('d', $errors);
        redirect('edit_account.php', false);
    }
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row justify-content-center">
    <div class="col-md-12 mb-3">
        <?php echo display_msg($msg); ?>
    </div>

    <!-- ================================
         EDITAR DATOS DE LA CUENTA
         ================================ -->
    <div class="col-md-6">
        <h5 class="mb-3">
            <i class="bi bi-person-badge me-2"></i>
            Editar mi cuenta
        </h5>

        <form method="POST" action="edit_account.php?id=<?php echo (int) $user['id']; ?>" class="mb-3">

            <div class="mb-3">
                <label class="form-label">Nombres</label>
                <input type="text" name="name" class="form-control"
                    value="<?php echo remove_junk(ucwords($user['name'])); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="username" class="form-control"
                    value="<?php echo remove_junk(ucwords($user['username'])); ?>" required>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" name="update" class="btn btn-primary">
                    Actualizar
                </button>

                <a href="change_password.php" class="btn btn-success">
                    Cambiar contraseña
                </a>
            </div>

        </form>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>