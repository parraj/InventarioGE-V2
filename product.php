<?php
$page_title = 'Productos';
require_once('includes/load.php');
page_require_level(2);

$products = join_product_table();
$all_categories = find_all_with_account('categories');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="table-responsive">

        <h5 class="mb-3">
            <i class="bi bi-box-seam me-2"></i>Listado de Productos
        </h5>

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
                        <?php $locations = find_product_locations($product['id']);
                         $images = find_product_img($product['id']);

                        $first_image = !empty($images)
                            ? $images[0]['image']
                            : '';

                        $thumb = getThumbnail($first_image);
                        ?>
                        
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" value="<?php echo (int) $product['id']; ?>"
                                    class="form-check-input product-check" data-id="<?php echo (int) $product['id']; ?>">
                            </td>

                            <td><?php echo remove_junk($product['id']); ?></td>
                            <td><?php echo remove_junk($product['name']); ?></td>
                            <td><?php echo remove_junk($product['categorie']); ?></td>

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

                                <?php if (!empty($thumb)): ?>

                                    <img src="<?php echo $thumb; ?>" class="rounded-circle"
                                        style="width:45px;height:45px;object-fit:contain;cursor:pointer" data-bs-toggle="modal"
                                        data-bs-target="#modalImages<?php echo (int) $product['id']; ?>">

                                <?php else: ?>

                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:45px;height:45px;">
                                        <i class="ti ti-building text-muted"></i>
                                    </div>

                                <?php endif; ?>

                            </td>

                            <td><?php echo read_date($product['date']); ?></td>

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

    <!-- Botones flotantes -->
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

<!-- MODALES DE STOCK -->
<?php foreach ($products as $product): ?>
    <?php $locations = find_product_locations($product['id']); ?>
    <?php if (!empty($locations)): ?>
        <div class="modal fade" id="stockModal<?php echo (int) $product['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Stock por Ubicación - <?php echo remove_junk($product['name']); ?>
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
                                        <td><?php echo remove_junk($loc['location']); ?></td>
                                        <td><?php echo remove_junk($loc['qty']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<!-- MODALES DE IMAGENES CARGADAS VÍA AJAX -->
<?php foreach ($products as $product): ?>
    <div class="modal fade" id="modalImages<?php echo (int) $product['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" id="modal-body-<?php echo (int) $product['id']; ?>">
                <div class="text-center p-5">
                    <span>Cargando imágenes...</span>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include_once('layouts/footer.php'); ?>

<!-- Modal Add Product -->
<div class="modal fade" id="addproduct" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="add_product.php" autocomplete="off" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Producto</h5>
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
                                placeholder="Nombre del Producto" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select class="selectpicker w-100" data-live-search="true" name="product-categorie"
                            title="Selecciona una categoría">
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo (int) $cat['id'] ?>">
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
                                placeholder="Precio de venta" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Imágenes del Producto</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                    </div>

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
            window.location = 'delete_product.php?id=' + id;
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

    if (selectedProducts.size > 0) {
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('product-check')) {
            const id = e.target.getAttribute('data-id');
            if (e.target.checked) selectedProducts.add(id);
            else selectedProducts.delete(id);
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

    // AJAX para cargar imágenes solo al abrir la modal
    <?php foreach ($products as $product): ?>
    $('#modalImages<?php echo (int)$product['id']; ?>').on('show.bs.modal', function () {
        const container = $('#modal-body-<?php echo (int)$product['id']; ?>');
        if (!container.data('loaded')) {
            $.get('get_product_images.php?id=<?php echo (int)$product['id']; ?>', function(data) {
                container.html(data);
                container.data('loaded', true);
            });
        }
    });
    <?php endforeach; ?>
});
</script>