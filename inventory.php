<?php
$page_title = 'Inventario';
require_once('includes/load.php');
page_require_level(2);

$start = date("Y-m-01") . " 00:00:00";
$end = date("Y-m-t") . " 23:59:59";

if (isset($_POST['submit'])) {
    $inventorys = join_inventory_table($_POST['start-date'] . " 00:00:00", $_POST['end-date'] . " 23:59:59");
} else {
    $inventorys = join_inventory_table($start, $end);
}

$all_products = find_all_with_account('products');
$all_locations = find_all_with_account('locations');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">

    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <!-- Filtro de fechas -->
    <div class="col-md-12 mb-3">
        <form method="post" action="inventory.php" class="row g-2 align-items-center">

            <div class="col-auto">
                <input type="date" class="form-control" name="start-date" required>
            </div>

            <div class="col-auto">
                <input type="date" class="form-control" name="end-date" required>
            </div>

            <div class="col-auto">
                <button type="submit" name="submit" class="btn btn-primary">Consultar</button>
            </div>

        </form>
    </div>


    <!-- Tabla de inventario -->
    <div class="col-md-12 table-responsive">

        <h5 class="mb-3">
            <i class="ti ti-archive me-2"></i>Entradas de Inventario
        </h5>

        <table id="datatable" class="table text-nowrap align-middle mb-0">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Ubicación</th>
                    <th>Precio Compra</th>
                    <th>Cantidad</th>
                    <th>Total</th>
                    <th>Tipo de Movimiento</th>
                    <th>Creado</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($inventorys as $inventory): ?>

                    <tr>

                        <td>
                            <?php echo remove_junk($inventory['id']); ?>
                        </td>

                        <td>
                            <?php echo remove_junk($inventory['name']); ?>
                        </td>

                        <td>
                            <?php echo remove_junk($inventory['location_name']); ?>
                        </td>

                        <td>
                            <?php echo number_format(remove_junk($inventory['buy_price']), 2, ',', '.'); ?>
                        </td>

                        <td>
                            <?php echo remove_junk($inventory['qty']); ?>
                        </td>

                        <td>
                            <?php echo number_format(remove_junk($inventory['total']), 2, ',', '.'); ?>
                        </td>

                          <td>
                            <?php echo remove_junk($inventory['movement_type']); ?>
                        </td>

                        <td>
                            <?php echo read_date($inventory['date']); ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>


    <!-- Botón flotante -->

    <button id="btnFlotante"
        class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 d-flex align-items-center justify-content-center shadow"
        style="width:60px;height:60px;" data-bs-toggle="modal" data-bs-target="#addinventory">

        <i class="bi bi-plus fs-3"></i>

    </button>

</div>


<?php include_once('layouts/footer.php'); ?>


<!-- MODAL CREAR INVENTARIO -->

<div class="modal fade" id="addinventory" tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form method="post" action="add_inventory.php" autocomplete="off">

                <div class="modal-header">
                    <h5 class="modal-title">Crear Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row">

                        <!-- PRODUCTO -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Producto</label>
                            <select id="product" class="selectpicker w-100" data-live-search="true">
                                <option value="">Selecciona el producto</option>
                                <?php foreach ($all_products as $product): ?>
                                    <option value="<?php echo (int)$product['id']; ?>">
                                        <?php echo remove_junk($product['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- UBICACIÓN -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ubicación</label>
                            <select id="location" class="selectpicker w-100" data-live-search="true">
                                <option value="">Selecciona la ubicación</option>
                                <?php foreach ($all_locations as $location): ?>
                                    <option value="<?php echo (int)$location['id']; ?>">
                                        <?php echo remove_junk($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- PRECIO -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio de Compra</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                <input type="number" step="0.01" min="0" id="buy" class="form-control">
                            </div>
                        </div>

                        <!-- CANTIDAD -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cantidad</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-cart-plus"></i></span>
                                <input type="number" id="qty" class="form-control">
                            </div>
                        </div>

                    </div>

                    <!-- BOTÓN AGREGAR -->
                    <button type="button" id="addItem" class="btn btn-success mb-3">
                        Agregar a la lista
                    </button>

                    <!-- TABLA -->
                    <div class="table-responsive">
                        <table class="table text-nowrap align-middle mb-0" id="tempTable">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Producto</th>
                                    <th>Ubicación</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <!-- INPUT OCULTO -->
                    <input type="hidden" name="items" id="itemsInput">

                </div>

                <div class="modal-footer">

                    <button type="submit" name="save_all" class="btn btn-primary">
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