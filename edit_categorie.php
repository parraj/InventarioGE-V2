<?php
/**
 * Editar Categoría
 * - Permite actualizar el nombre de una categoría existente
 */

$page_title = 'Editar categoría';
require_once('includes/load.php');

// Verificar nivel de acceso (Administrador)
page_require_level(2);

/* =====================================================
 * OBTENER CATEGORÍA POR ID
 * ===================================================== */
$categorie = find_by_id('categories', (int)$_GET['id']);

if (!$categorie) {
    $session->msg('d', 'ID de la categoría no encontrado.');
    redirect('categorie.php', false);
}

/* =====================================================
 * PROCESAR ACTUALIZACIÓN
 * ===================================================== */
if (isset($_POST['edit_cat'])) {

    $req_fields = ['categorie-name'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $cat_name = remove_junk($db->escape($_POST['categorie-name']));

        $sql = "UPDATE categories 
                SET name = '{$cat_name}' 
                WHERE id = '{$categorie['id']}'";

        $result = $db->query($sql);

        if ($result && $db->affected_rows() === 1) {
            $session->msg('s', 'Categoría modificada satisfactoriamente.');
        } else {
            $session->msg('d', 'Ocurrió un error, no se pudo modificar la categoría.');
        }

        redirect('categorie.php', false);

    } else {
        $session->msg('d', $errors);
        redirect('categorie.php', false);
    }
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row justify-content-center">
    <div class="col-md-5">

        <!-- Mensajes del sistema -->
        <?php echo display_msg($msg); ?>

        <!-- Título -->
        <h4 class="mb-4">
            <i class="bi bi-tags me-2"></i>
            Editar Categoría
        </h4>

        <!-- Formulario -->
        <form method="POST"
              action="edit_categorie.php?id=<?php echo (int)$categorie['id']; ?>"
              autocomplete="off">

            <div class="mb-3">
                <label class="form-label">Nombre de la categoría</label>
                <input
                    type="text"
                    name="categorie-name"
                    class="form-control"
                    value="<?php echo remove_junk(ucfirst($categorie['name'])); ?>"
                    required>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="edit_cat" class="btn btn-primary me-2">
                    Actualizar
                </button>

                <a href="categorie.php" class="btn btn-danger">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>