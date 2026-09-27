<?php
$page_title = 'Stock por Ubicación';
require_once('includes/load.php');
page_require_level(2);

/* =====================================================
   OBTENER DATOS
===================================================== */

$products = find_all_with_account('products');
$locations = find_all_with_account('locations');
$product_locations = join_product_location_table();
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="table-responsive">

        <h5 class="mb-3">
            <i class="bi bi-geo-alt-fill me-2"></i>Stock por Ubicación
        </h5>

        <table id="datatable" class="table text-nowrap mb-0 align-middle">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Ubicación</th>
                    <th>Cantidad</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($product_locations as $pl): ?>

                    <tr>

                        <td>
                            <?php echo remove_junk($pl['id']); ?>
                        </td>

                        <td>
                            <?php echo remove_junk($pl['product']); ?>
                        </td>

                        <td>
                            <?php echo remove_junk($pl['location']); ?>
                        </td>

                        <td>
                            <?php echo remove_junk($pl['qty']); ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>
    </div>

</div>

<?php include_once('layouts/footer.php'); ?>