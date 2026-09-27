<?php
/**
 * Editar Usuario
 * - Actualiza información básica del usuario
 * - Permite cambiar contraseña y añadir cuentas usando modales
 */

$page_title = 'Editar Usuario';
require_once('includes/load.php');
page_require_level(1);

/* =====================================================
 * OBTENER USUARIO, ROLES Y CUENTAS
 * ===================================================== */
$e_user = find_by_id('users', (int)$_GET['id']);
$groups = find_all('user_groups');
$all_accounts = find_all('accounts');
$user_accounts = find_user_accounts($_GET['id']);

if (!$e_user) {
    $session->msg('d', 'ID de usuario no encontrado.');
    redirect('users.php', false);
}

/* =====================================================
 * ACTUALIZAR INFORMACIÓN BÁSICA
 * ===================================================== */
if (isset($_POST['update'])) {
    $req_fields = ['name', 'username', 'level'];
    validate_fields($req_fields);

    if (empty($errors)) {
        $id       = (int)$e_user['id'];
        $name     = remove_junk($db->escape($_POST['name']));
        $username = remove_junk($db->escape($_POST['username']));
        $level    = (int)$db->escape($_POST['level']);
        $status   = remove_junk($db->escape($_POST['status']));

        $sql = "UPDATE users SET
                    name       = '{$name}',
                    username   = '{$username}',
                    user_level = '{$level}',
                    status     = '{$status}'
                WHERE id = '{$id}'";
        $result = $db->query($sql);

        if ($result && $db->affected_rows() === 1) {
            $session->msg('s', 'Usuario modificado satisfactoriamente.');
        } else {
            $session->msg('d', 'Ocurrió un error, no se pudo modificar el usuario.');
        }

        redirect('edit_user.php?id=' . $id, false);
    } else {
        $session->msg('d', $errors);
        redirect('edit_user.php?id=' . (int)$e_user['id'], false);
    }
}

/* =====================================================
 * ACTUALIZAR CONTRASEÑA
 * ===================================================== */
if (isset($_POST['update-pass'])) {
    $req_fields = ['password'];
    validate_fields($req_fields);

    if (empty($errors)) {
        $id       = (int)$e_user['id'];
        $password = remove_junk($db->escape($_POST['password']));
        $h_pass   = sha1($password);

        $sql = "UPDATE users SET password = '{$h_pass}' WHERE id = '{$id}'";
        $result = $db->query($sql);

        if ($result && $db->affected_rows() === 1) {
            $session->msg('s', 'Contraseña actualizada correctamente.');
        } else {
            $session->msg('d', 'No se pudo actualizar la contraseña.');
        }

        redirect('edit_user.php?id=' . $id, false);
    } else {
        $session->msg('d', $errors);
        redirect('edit_user.php?id=' . (int)$e_user['id'], false);
    }
}

/* =====================================================
 * AÑADIR CUENTA AL USUARIO
 * ===================================================== */
if (isset($_POST['update-account'])) {
    $user_account = find_user_accounts_for_add((int)$_GET['id'], $_POST['account']);

    if (!$user_account) {
        $id      = (int)$_GET['id'];
        $account = remove_junk($db->escape($_POST['account']));
        $s_date  = make_date();

        $sql = "INSERT INTO user_accounts (user_id, account_id, date) VALUES ('{$id}','{$account}','{$s_date}')";
        $result = $db->query($sql);

        if ($result && $db->affected_rows() === 1) {
            $session->msg('s', ' Se añadió la nueva cuenta al usuario.');
        } else {
            $session->msg('d', ' No se pudo añadir la nueva cuenta.');
        }

        redirect('edit_user.php?id=' . $id, false);
    } else {
        $session->msg('d', 'El usuario ya tiene asociada la cuenta ' . $_POST['account']);
        redirect('edit_user.php?id=' . (int)$e_user['id'], false);
    }
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <?php echo display_msg($msg); ?>

        <!-- FORMULARIO DE INFORMACIÓN BÁSICA -->
        <h4 class="mb-4"><i class="bi bi-person-circle me-2"></i>Editar Usuario</h4>
        <form method="POST" action="edit_user.php?id=<?php echo (int)$e_user['id']; ?>" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" name="name" value="<?php echo remove_junk(ucwords($e_user['name'])); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                    <input type="text" class="form-control" name="username" value="<?php echo remove_junk(ucwords($e_user['username'])); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Rol de usuario</label>
                <select class="form-select" name="level" required>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo $group['group_level']; ?>" <?php echo ($group['group_level'] === $e_user['user_level']) ? 'selected' : ''; ?>>
                            <?php echo ucwords($group['group_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Estado</label>
                <select class="form-select" name="status" required>
                    <option value="1" <?php echo ($e_user['status'] === '1') ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo ($e_user['status'] === '0') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" name="update" class="btn btn-primary me-2">Actualizar</button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Cambiar Contraseña</button>

            </div>
        </form>
    </div>

    <!-- TABLA DE CUENTAS ASOCIADAS -->
    <div class="col-md-12 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5>Cuentas asociadas</h5>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">Añadir Cuenta</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table text-nowrap mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID Cuenta</th>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Logo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_accounts as $ua): ?>
                        <tr>
                            <td><?php echo remove_junk($ua['id_account']); ?></td>
                            <td><?php echo remove_junk($ua['name']); ?></td>
                            <td><?php echo remove_junk($ua['address']); ?></td>
                            <td><?php echo remove_junk($ua['phone']); ?></td>
                            <td>
                                <?php if(!empty($ua['image']) && file_exists($ua['image'])): ?>
                                    <img src="<?php echo $ua['image']; ?>"
                                        class="rounded-circle"
                                        style="width:45px;height:45px;object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:45px;height:45px;">
                                        <i class="ti ti-building text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="delete_user_account.php?id=<?php echo (int)$ua['id_ua']; ?>" class="btn btn-danger btn-sm" title="Eliminar"><i class="bi bi-trash-fill"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CAMBIAR CONTRASEÑA -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="edit_user.php?id=<?php echo (int)$e_user['id']; ?>" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordLabel">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="password" class="form-control" placeholder="Ingresa la nueva contraseña" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update-pass" class="btn btn-primary">Actualizar</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL AÑADIR CUENTA -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="edit_user.php?id=<?php echo (int)$e_user['id']; ?>" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAccountLabel">Añadir Cuenta al Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cuenta</label>
                        <select name="account" class="form-select">
                            <?php foreach ($all_accounts as $acc): ?>
                                <option value="<?php echo (int)$acc['id']; ?>"><?php echo $acc['id'] . ' - ' . $acc['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update-account" class="btn btn-primary">Añadir</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>