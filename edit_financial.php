<?php
/**
 * Editar Cuenta Financiera
 * - Permite actualizar nombre, tipo, descripción y estado
 */

$page_title = 'Editar Cuenta Financiera';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

/* =====================================================
 * OBTENER CUENTA FINANCIERA
 * ===================================================== */
$financial = find_by_id('financial_accounts', (int)$_GET['id']);

if (!$financial) {
    $session->msg('d', 'ID de la cuenta financiera no encontrado.');
    redirect('financial_accounts.php', false);
}

/* =====================================================
 * PROCESAR ACTUALIZACIÓN
 * ===================================================== */
if (isset($_POST['update_financial'])) {

    $req_fields = ['name', 'type'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $name        = remove_junk($db->escape($_POST['name']));
        $type        = remove_junk($db->escape($_POST['type']));
        $description = remove_junk($db->escape($_POST['description']));

        $sql = "UPDATE financial_accounts SET
                    name        = '{$name}',
                    type        = '{$type}',
                    description = '{$description}'
                WHERE id = '{$financial['id']}'";

        $result = $db->query($sql);

        if ($result && $db->affected_rows() === 1) {
            $session->msg('s', 'Cuenta financiera actualizada satisfactoriamente.');
        } else {
            $session->msg('d', 'Ocurrió un error, no se pudo modificar la cuenta financiera');
        }

        redirect('financial_accounts.php', false);

    } else {
        $session->msg('d', $errors);
        redirect('financial_accounts.php', false);
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

        <h4 class="mb-4">
            <i class="bi bi-wallet2 me-2"></i>
            Editar Cuenta Financiera
        </h4>

        <form method="post"
              action="edit_financial.php?id=<?php echo (int)$financial['id']; ?>"
              autocomplete="off">

            <!-- Nombre -->
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-card-text"></i>
                    </span>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?php echo remove_junk($financial['name']); ?>"
                        required>
                </div>
            </div>

            <!-- Tipo -->
            <div class="mb-3">
                <label class="form-label">Tipo</label>
                <select name="type" class="form-select" required>
                    <option value="Caja" <?php echo ($financial['type'] === 'Caja') ? 'selected' : ''; ?>>Caja</option>
                    <option value="Banco" <?php echo ($financial['type'] === 'Banco') ? 'selected' : ''; ?>>Banco</option>
                    <option value="Datáfono" <?php echo ($financial['type'] === 'Datáfono') ? 'selected' : ''; ?>>Datáfono</option>
                </select>
            </div>
            <!-- Descripción -->
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <input type="text" name="description" class="form-control" value="<?php echo remove_junk($financial['description']); ?>">
            </div>

            <!-- Botones -->
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="update_financial" class="btn btn-primary me-2">
                    Actualizar
                </button>
                <a href="financial_accounts.php" class="btn btn-danger">
                    Cancelar
                </a>
            </div>

        </form>

    </div>
</div>

<?php include_once('layouts/footer.php'); ?>