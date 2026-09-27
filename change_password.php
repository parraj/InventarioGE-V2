<?php
$page_title = 'Cambiar contraseña';
require_once('includes/load.php');
page_require_level(2);
$user = current_user();
?>

<?php
if(isset($_POST['update'])){
    $req_fields = array('new-password','old-password','id');
    validate_fields($req_fields);

    if(empty($errors)){
        if(sha1($_POST['old-password']) !== $user['password']){
            $session->msg('d', "Tu antigua contraseña no coincide");
            redirect('change_password.php', false);
        }

        $id = (int)$_POST['id'];
        $new = remove_junk($db->escape(sha1($_POST['new-password'])));
        $sql = "UPDATE users SET password ='{$new}' WHERE id='{$db->escape($id)}'";
        $result = $db->query($sql);

        if($result && $db->affected_rows() === 1){
            $session->logout();
            $session->msg('s',"Inicia sesión con tu nueva contraseña.");
            redirect('index.php', false);
        } else {
            $session->msg('d','Ocurrió un error, no se pudo actualizar la contraseña.');
            redirect('change_password.php', false);
        }
    } else {
        $session->msg("d", $errors);
        redirect('change_password.php', false);
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
    <div class="col-md-6">

        <h4 class="mb-4"><i class="bi bi-key-fill me-2"></i>Cambiar contraseña</h4>

        <form method="post" action="change_password.php" autocomplete="off">

            <div class="mb-3">
                <label for="old-password" class="form-label">Antigua contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" name="old-password" placeholder="Antigua contraseña" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="new-password" class="form-label">Nueva contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" name="new-password" placeholder="Nueva contraseña" required>
                </div>
            </div>

            <input type="hidden" name="id" value="<?php echo (int)$user['id'];?>">

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="update" class="btn btn-primary me-2">Actualizar</button>
                <a href="home.php" class="btn btn-danger">Cancelar</a>
            </div>

        </form>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>