<?php
/**
 * Editar Ubicación
 * - Permite actualizar la información de una ubicación existente
 */

$page_title = 'Editar Ubicación';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(2);

/* =====================================================
 * OBTENER UBICACIÓN POR ID
 * ===================================================== */
$location = find_by_id('locations', (int)$_GET['id']);

if (!$location) {
    $session->msg('d', 'ID de la ubicación no encontrado.');
    redirect('location.php', false);
}

/* =====================================================
 * PROCESAR ACTUALIZACIÓN
 * ===================================================== */
if (isset($_POST['location'])) {

    $req_fields = ['name', 'address', 'location_type'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $name = remove_junk($db->escape($_POST['name']));
        $address = remove_junk($db->escape($_POST['address']));
        $location_type = remove_junk($db->escape($_POST['location_type']));

        if ($location_type !== 'Interna' && $location_type !== 'Externa') {
            $session->msg('d', 'El tipo de ubicación no es válido.');
            redirect('edit_location.php?id=' . (int)$location['id'], false);
        }

        $sql = "UPDATE locations SET
                    name = '{$name}',
                    address = '{$address}',
                    location_type = '{$location_type}'
                WHERE id = '{$location['id']}'";

        $result = $db->query($sql);

        if ($result) {
            $session->msg('s', 'Ubicación modificada satisfactoriamente.');
        } else {
            $session->msg('d', 'Ocurrió un error, no se pudo modificar la ubicación.');
        }

        redirect('location.php', false);

    } else {
        $session->msg('d', $errors);
        redirect('edit_location.php?id=' . (int)$location['id'], false);
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
            <i class="bi bi-geo-alt-fill me-2"></i>
            Editar Ubicación
        </h4>

        <!-- Formulario -->
        <form method="POST"
              action="edit_location.php?id=<?php echo (int)$location['id']; ?>"
              autocomplete="off">

            <!-- Nombre -->
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-building"></i>
                    </span>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?php echo remove_junk($location['name']); ?>"
                        required>
                </div>
            </div>

            <!-- Dirección -->
            <div class="mb-3">
                <label class="form-label">Dirección</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-geo-alt"></i>
                    </span>
                    <input
                        type="text"
                        name="address"
                        class="form-control"
                        value="<?php echo remove_junk($location['address']); ?>"
                        required>
                </div>
            </div>

            <!-- Tipo de ubicación -->
            <div class="mb-3">
                <label class="form-label">Tipo de ubicación</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-diagram-3"></i>
                    </span>
                    <select name="location_type" class="form-select" required>
                        <option value="Interna" <?php echo ($location['location_type'] === 'Interna') ? 'selected' : ''; ?>>
                            Interna
                        </option>
                        <option value="Externa" <?php echo ($location['location_type'] === 'Externa') ? 'selected' : ''; ?>>
                            Externa
                        </option>
                    </select>
                </div>
            </div>

            <!-- Botones -->
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="location" class="btn btn-primary me-2">
                    Actualizar
                </button>
                <a href="location.php" class="btn btn-danger">
                    Cancelar
                </a>
            </div>

        </form>

    </div>
</div>

<?php include_once('layouts/footer.php'); ?>