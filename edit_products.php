<?php
$page_title = 'Editar Multiples productos';
require_once('includes/load.php');
page_require_level(2);

$products = [];
$selected_products = [];

if (!empty($_POST['product']) && is_array($_POST['product'])) {
    $selected_products = array_map('intval', $_POST['product']);
    $products = find_product_in(implode(',', $selected_products));
} else {
    redirect("product.php", false);
}

$all_categories = find_all_with_account('categories');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="col-md-12">
        <div class="alert alert-danger">
            Tenga en cuenta que la acción que tome se aplicará para todos los productos si usa la edición global.
            <br>Si los deja vacíos, se usarán los valores individuales de la tabla.
        </div>
    </div>

    <form method="post" action="edit_multi_product.php" autocomplete="off" enctype="multipart/form-data">

        <input type="hidden" name="products" value="<?php echo implode(',', $selected_products); ?>">

        <!--  EDICIÓN GLOBAL -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-body">

                    <h5 class="mb-3">
                        <i class="bi bi-pencil-square me-2"></i>Edición global
                    </h5>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="global_category">
                                <option value="">Selecciona una categoría</option>

                                <?php foreach ($all_categories as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>">
                                        <?php echo $cat['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Precio de venta</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-currency-dollar"></i>
                                </span>
                                <input type="number"
                                       step="0.01"
                                       class="form-control"
                                       name="global_price">
                            </div>
                        </div>
                         <div class="col-md-4 mb-3">
                            <label class="form-label">Imagenes</label>
                            <div class="input-group">
                                <input type="file"
                                name="images[]"
                                class="form-control"
                                multiple
                                accept="image/*">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!--  TABLA EDITABLE -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">

                    <h5 class="mb-3">
                        <i class="bi bi-box-seam me-2"></i>Productos seleccionados
                    </h5>

                    <div class="table-responsive">
                        <table id="datatable" class="table text-nowrap mb-0 align-middle">

                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Nombre</th>
                                    <th class="text-center">Categoría</th>
                                    <th class="text-center">Precio de venta</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo remove_junk($product['id']); ?></td>

                                        <!--  NOMBRE -->
                                        <td>
                                            <input type="text"
                                                   class="form-control"
                                                   name="items[<?php echo $product['id']; ?>][name]"
                                                   value="<?php echo remove_junk($product['name']); ?>">
                                        </td>

                                        <!--  CATEGORÍA -->
                                        <td class="text-center">
                                            <select class="form-select" data-live-search="true"
                                                    name="items[<?php echo $product['id']; ?>][category]">

                                                <option value="">-- Sin cambio --</option>

                                                <?php foreach ($all_categories as $cat): ?>
                                                    <option value="<?php echo (int)$cat['id']; ?>"
                                                        <?php if ($cat['id'] == $product['categorie_id']) echo 'selected'; ?>>
                                                        <?php echo $cat['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>

                                        <!--  PRECIO -->
                                        <td class="text-center">
                                            <input type="number"
                                                   step="0.01"
                                                   class="form-control"
                                                   name="items[<?php echo $product['id']; ?>][price]"
                                                   value="<?php echo remove_junk($product['sale_price']); ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>

                    <!--  BOTONES ABAJO -->
                    <div class="d-flex justify-content-between mt-3 me-2">
                        <div>
                            <small class="text-muted">
                                Los cambios se aplicarán a los productos seleccionados
                            </small>
                        </div>

                        <div>
                            <button type="submit" name="update_products" class="btn btn-primary">
                                Guardar cambios
                            </button>

                            <button type="button"
                                    class="btn btn-danger"
                                    onclick="deleteMultipleProducts('<?php echo implode(',', $selected_products); ?>')">
                                Eliminar
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </form>
</div>

<script>
function deleteMultipleProducts(ids) {
    Swal.fire({
        icon: "warning",
        title: "¿Estás seguro de eliminar este producto?",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#0d6efd",
        cancelButtonColor: "#dc3545",
        width: '350px',
        customClass: {
            title: 'swal-title-sm',
            content: 'swal-content-sm',
            confirmButton: 'swal-btn-sm',
            cancelButton: 'swal-btn-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = 'edit_multi_product.php?delete=' + ids;
        }
    });
}
</script>

<?php include_once('layouts/footer.php'); ?>