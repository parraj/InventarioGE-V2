<?php
$page_title = 'Productos por categoría';
require_once('includes/load.php');
page_require_level(2);

if (!isset($_GET['id']) || empty((int) $_GET['id'])) {
    $session->msg("d", "Categoría no válida.");
    redirect('categorie.php', false);
}

$category_id = (int) $_GET['id'];
$account = $db->escape($_SESSION['account']);

$category = find_by_id('categories', $category_id);

if (!$category || $category['account'] != $_SESSION['account']) {
    $session->msg("d", "La categoría no existe o no pertenece a tu cuenta.");
    redirect('categorie.php', false);
}

$all_categories = find_all_with_account('categories');


$products = join_product_table_by_category($category_id);
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="col-md-12 mb-3 d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0">
            <i class="bi bi-box-seam me-2"></i>
            Productos de la categoría: <span class="text-primary"><?php echo remove_junk(ucfirst($category['name'])); ?></span>
        </h5>

        <a href="categorie.php" class="btn btn-primary btn-sm mt-2 mt-md-0">
            <i class="bi bi-arrow-left me-1"></i>Volver a categorías
        </a>
    </div>

    <div class="table-responsive">

        <form method="post" action="edit_products.php" autocomplete="off" id="bulkProductsForm">

            <table id="datatable" class="table text-nowrap mb-0 align-middle">

                <thead>
                    <tr>
                        <th>Multiple</th>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Precio de venta</th>
                        <th>Imagenes</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($products as $product): ?>

                        <?php
                        $locations = find_product_locations($product['id']);
                        $images = find_product_img($product['id']);

                        $first_image = !empty($images)
                            ? $images[0]['image']
                            : 'uploads/no_image.png';
                        ?>

                        <tr>

                            <td class="text-center">
                                <input type="checkbox" value="<?php echo (int) $product['id']; ?>"
                                    class="form-check-input product-check" data-id="<?php echo (int) $product['id']; ?>">
                            </td>

                            <td>
                                <?php echo remove_junk($product['id']); ?>
                            </td>

                            <td>
                                <?php echo remove_junk($product['name']); ?>
                            </td>

                            <td>
                                <?php echo remove_junk($product['categorie']); ?>
                            </td>

                            <td>
                                <span style="cursor:pointer;font-weight:600" data-bs-toggle="modal"
                                    data-bs-target="#stockModal<?php echo (int) $product['id']; ?>">
                                    <?php echo remove_junk($product['qty']); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo number_format(remove_junk($product['sale_price']), 2, ',', '.'); ?>
                            </td>

                            <td>

                                <?php if (!empty($images)): ?>

                                    <img src="<?php echo $first_image; ?>" class="rounded-circle"
                                        style="width:45px;height:45px;object-fit:contain;cursor:pointer" data-bs-toggle="modal"
                                        data-bs-target="#modalImages<?php echo (int) $product['id']; ?>">

                                <?php else: ?>

                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:45px;height:45px;">
                                        <i class="ti ti-building text-muted"></i>
                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?php echo read_date($product['date']); ?>
                            </td>

                            <td>

                                <div class="btn-group">

                                    <a href="edit_product.php?id=<?php echo (int) $product['id']; ?>"
                                        class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <button type="button" onclick="delet(<?php echo (int) $product['id']; ?>)"
                                        class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </form>
    </div>

    <button id="btnFlotante"
        class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 d-flex align-items-center justify-content-center shadow"
        style="width:60px;height:60px;z-index:1050;" data-bs-toggle="modal" data-bs-target="#addproduct">

        <i class="bi bi-plus fs-3"></i>

    </button>

    <button id="btnBulkEdit" type="button"
        class="btn btn-success rounded-circle position-fixed align-items-center justify-content-center shadow"
        style="width:60px;height:60px;display:none;bottom:92px;right:16px;z-index:1051;"
        onclick="submitBulkProducts();">

        <i class="bi bi-check2-square fs-4"></i>

    </button>

</div>


<!-- MODALES -->

<?php foreach ($products as $product): ?>

    <?php
    $locations = find_product_locations($product['id']);
    $images = find_product_img($product['id']);
    ?>

    <?php if (!empty($locations)): ?>

        <div class="modal fade" id="stockModal<?php echo (int) $product['id']; ?>" tabindex="-1">

            <div class="modal-dialog modal-lg">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Stock por Ubicación -
                            <?php echo remove_junk($product['name']); ?>
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                    </div>

                    <div class="modal-body">

                        <table class="table table-bordered text-center">

                            <thead>
                                <tr>
                                    <th>Ubicación</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($locations as $loc): ?>

                                    <tr>

                                        <td>
                                            <?php echo remove_junk($loc['location']); ?>
                                        </td>

                                        <td>
                                            <?php echo remove_junk($loc['qty']); ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>
            </div>
        </div>

    <?php endif; ?>


    <?php if (!empty($images)): ?>

        <div class="modal fade" id="modalImages<?php echo (int) $product['id']; ?>" tabindex="-1">

            <div class="modal-dialog modal-lg">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Imágenes de
                            <?php echo remove_junk($product['name']); ?>
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                    </div>

                    <div class="modal-body">

                        <div class="row">

                            <?php foreach ($images as $img): ?>

                                <div class="col-md-4 mb-3">

                                    <img src="<?php echo $img['image']; ?>" class="img-fluid rounded"
                                        style="width:100%;height:200px;object-fit:contain;background:#f8f9fa;">

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>
            </div>
        </div>

    <?php endif; ?>

<?php endforeach; ?>

<?php include_once('layouts/footer.php'); ?>

<!-- Modal Add Product -->
<div class="modal fade" id="addproduct" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="add_product.php" autocomplete="off" enctype="multipart/form-data">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Crear Producto en <?php echo remove_junk(ucfirst($category['name'])); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Nombre del Producto</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-box-seam"></i>
                            </span>
                            <input type="text" class="form-control" name="product-title"
                                placeholder="Nombre del Producto">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select class="selectpicker w-100" data-live-search="true" name="product-categorie"
                            title="Selecciona una categoría">

                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo (int) $cat['id'] ?>"
                                    <?php echo ($cat['id'] == $category_id) ? 'selected' : ''; ?>>
                                    <?php echo $cat['name'] ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Precio</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-currency-dollar"></i>
                            </span>
                            <input type="number" class="form-control" name="saleing-price"
                                placeholder="Precio de venta">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Imágenes del Producto</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                    </div>

                    <input type="hidden" name="redirect_category_id" value="<?php echo (int) $category_id; ?>">

                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_product" class="btn btn-primary">
                        Guardar
                    </button>

                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    function delet(id) {
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
                window.location = 'delete_product.php?id=' + id + '&category_id=<?php echo (int) $category_id; ?>';
            }
        });
    }

    const selectedProducts = new Set();

    function toggleBulkButton() {
        const bulkBtn = document.getElementById('btnBulkEdit');
        bulkBtn.style.display = selectedProducts.size > 0 ? 'flex' : 'none';
    }

    function syncVisibleCheckboxes() {
        document.querySelectorAll('.product-check').forEach(function (checkbox) {
            const id = checkbox.getAttribute('data-id');
            checkbox.checked = selectedProducts.has(id);
        });
    }

    function submitBulkProducts() {
        const form = document.getElementById('bulkProductsForm');

        form.querySelectorAll('input[name="product[]"]').forEach(function (input) {
            input.remove();
        });

        selectedProducts.forEach(function (id) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product[]';
            input.value = id;
            form.appendChild(input);
        });

        const catInput = document.createElement('input');
        catInput.type = 'hidden';
        catInput.name = 'category_id';
        catInput.value = '<?php echo (int) $category_id; ?>';
        form.appendChild(catInput);

        if (selectedProducts.size > 0) {
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('product-check')) {
                const id = e.target.getAttribute('data-id');

                if (e.target.checked) {
                    selectedProducts.add(id);
                } else {
                    selectedProducts.delete(id);
                }

                toggleBulkButton();
            }
        });

        if (window.jQuery && $.fn.DataTable) {
            $('#datatable').on('draw.dt', function () {
                syncVisibleCheckboxes();
                toggleBulkButton();
            });
        }

        syncVisibleCheckboxes();
        toggleBulkButton();
    });

    console.log($.fn.selectpicker);
</script>