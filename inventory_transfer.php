<?php
$page_title = 'Traslados de Inventario';
require_once('includes/load.php');
page_require_level(2);

$start = date("Y-m-01") . " 00:00:00";
$end = date("Y-m-t") . " 23:59:59";

$current_account = (int)$_SESSION['account'];

if (isset($_POST['submit'])) {
    $outs = join_inventory_transfer_out_table($_POST['start-date'] . " 00:00:00", $_POST['end-date'] . " 23:59:59");
    $ins = join_inventory_transfer_in_table($_POST['start-date'] . " 00:00:00", $_POST['end-date'] . " 23:59:59");
} else {
    $outs = join_inventory_transfer_out_table($start, $end);
    $ins = join_inventory_transfer_in_table($start, $end);
}

$all_products = find_all_with_account('products');
$all_accounts = find_all('accounts');
$locations_origin = find_all_with_account('locations');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <!-- FILTRO -->
    <div class="col-md-12 mb-3">
        <form method="post" action="inventory_transfer.php" class="row g-2 align-items-center">

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


    <!-- TABS -->
    <div class="col-md-12">

        <ul class="nav nav-tabs mb-3">

            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#outs">
                    Traslados Realizados
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ins">
                    Traslados Recibidos
                </button>
            </li>

        </ul>


        <div class="tab-content">

            <!-- REALIZADAS -->
            <div class="tab-pane fade show active table-responsive" id="outs">

                <table id="datatable" class="table text-nowrap align-middle mb-0">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Cuenta Origen</th>
                            <th>Ubicación Origen</th>
                            <th>Cuenta Destino</th>
                            <th>Ubicación Destino</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($outs as $out): ?>
                            <tr>

                                <td><?php echo remove_junk($out['id']); ?></td>
                                <td><?php echo remove_junk($out['transfer_type']); ?></td>
                                <td><?php echo remove_junk($out['name']); ?></td>
                                <td><?php echo remove_junk($out['qty']); ?></td>
                                <td><?php echo remove_junk($out['origin_account']); ?></td>
                                <td><?php echo remove_junk($out['from_location']); ?></td>
                                <td><?php echo remove_junk($out['receiving_account']); ?></td>
                                <td><?php echo remove_junk($out['to_location']); ?></td>
                                <td><?php echo remove_junk($out['user']); ?></td>
                                <td><?php echo read_date($out['date']); ?></td>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>


            <!-- RECIBIDAS -->
            <div class="tab-pane fade table-responsive" id="ins">

                <table id="datatable2" class="table text-nowrap align-middle mb-0">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tipo de Traslado</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Cuenta Origen</th>
                            <th>Ubicación Origen</th>
                            <th>Cuenta Destino</th>
                            <th>Ubicación Destino</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($ins as $in): ?>
                            <tr>

                                <td><?php echo remove_junk($in['id']); ?></td>
                                <td><?php echo remove_junk($in['transfer_type']); ?></td>
                                <td><?php echo remove_junk($in['name']); ?></td>
                                <td><?php echo remove_junk($in['qty']); ?></td>
                                <td><?php echo remove_junk($in['origin_account']); ?></td>
                                <td><?php echo remove_junk($in['from_location']); ?></td>
                                <td><?php echo remove_junk($in['receiving_account']); ?></td>
                                <td><?php echo remove_junk($in['to_location']); ?></td>
                                <td><?php echo remove_junk($in['user']); ?></td>
                                <td><?php echo read_date($in['date']); ?></td>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        </div>
    </div>


    <!-- BOTON FLOTANTE -->
    <button
        class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 d-flex align-items-center justify-content-center shadow"
        style="width:60px;height:60px;" data-bs-toggle="modal" data-bs-target="#addTransfer">

        <i class="bi bi-plus fs-3"></i>

    </button>

</div>

<?php include_once('layouts/footer.php'); ?>


<!--
|--------------------------------------------------------------------------
| MODAL: TRANSFERENCIA DE INVENTARIO MULTIPLE
|--------------------------------------------------------------------------
| Permite agregar múltiples traslados antes de enviarlos al backend.
| Cada item incluye:
| - Producto
| - Ubicación origen
| - Cuenta destino
| - Ubicación destino
| - Cantidad
|--------------------------------------------------------------------------
-->
<div class="modal fade" id="addTransfer" tabindex="-1">

    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="post" action="add_transfer.php" autocomplete="off">

                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title">Realizar Traslado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">

                    <!-- ALERTA INFORMATIVA -->
                     <div class="alert alert-info py-2" role="alert">
                        Puedes trasladar entre ubicaciones de la misma cuenta o hacia otra cuenta.
                    </div>  

                    <!--
                    |--------------------------------------------------------------------------
                    | PRODUCTO
                    |--------------------------------------------------------------------------
                    | Producto base del traslado
                    | Se usa selectpicker con búsqueda
                    |--------------------------------------------------------------------------
                    -->
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <select id="product_select"
                                class="selectpicker w-100"
                                data-live-search="true">

                            <option value="">Selecciona el producto</option>

                            <?php foreach ($all_products as $product): ?>
                                <option value="<?php echo (int)$product['id']; ?>">
                                    <?php echo remove_junk($product['name']); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <!--
                    |--------------------------------------------------------------------------
                    | UBICACIÓN ORIGEN
                    |--------------------------------------------------------------------------
                    | Se carga dinámicamente según el producto seleccionado
                    |--------------------------------------------------------------------------
                    -->
                    <div class="mb-3">
                        <label class="form-label">Ubicación Origen</label>
                        <select id="from_location" class="form-select">
                            <option value="">Selecciona primero el producto</option>
                        </select>
                    </div>

                    <!--
                    |--------------------------------------------------------------------------
                    | CUENTA DESTINO
                    |--------------------------------------------------------------------------
                    | Define la cuenta receptora del inventario
                    |--------------------------------------------------------------------------
                    -->
                    <div class="mb-3">
                        <label class="form-label">Cuenta Destino</label>
                        <select id="account_select" class="form-select">

                            <option value="">Selecciona la cuenta</option>

                            <?php foreach ($all_accounts as $account): ?>
                                <option value="<?php echo (int)$account['id']; ?>"
                                    <?php echo ((int)$account['id'] === $current_account) ? 'selected' : ''; ?>>
                                    <?php echo remove_junk($account['name']); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <!--
                    |--------------------------------------------------------------------------
                    | UBICACIÓN DESTINO
                    |--------------------------------------------------------------------------
                    | Se carga dinámicamente según la cuenta seleccionada
                    |--------------------------------------------------------------------------
                    -->
                    <div class="mb-3">
                        <label class="form-label">Ubicación Destino</label>
                        <select id="location_destino" class="form-select">
                            <option value="">Selecciona la cuenta primero</option>
                        </select>
                    </div>

                    <!--
                    |--------------------------------------------------------------------------
                    | CANTIDAD
                    |--------------------------------------------------------------------------
                    | Cantidad a transferir del producto seleccionado
                    |--------------------------------------------------------------------------
                    -->
                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" name="qty" min="1">
                    </div>

                    <!-- MENSAJE DINÁMICO -->
                    <div id="transferHelp" class="small text-muted mb-2"></div>

                    <!-- BOTÓN AGREGAR -->
                    <button type="button" id="addTransferItem" class="btn btn-success mb-3">
                        Agregar a la lista
                    </button>

                    <!--
                    |--------------------------------------------------------------------------
                    | TABLA TEMPORAL
                    |--------------------------------------------------------------------------
                    | Muestra los traslados agregados antes de enviar
                    |--------------------------------------------------------------------------
                    -->
                    <div class="table-responsive">
                        <table class="table text-nowrap align-middle mb-0" id="tempTransferTable">

                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Producto</th>
                                    <th>Origen</th>
                                    <th>Destino</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>

                            <tbody></tbody>

                        </table>
                    </div>

                    <!-- INPUT OCULTO PARA BACKEND -->
                    <input type="hidden" name="items" id="transferItemsInput">

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">

                    <button type="submit" name="add_transfer" class="btn btn-primary">
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
    const productSelect = document.getElementById("product_select");
    const fromLocationSelect = document.getElementById("from_location");
    const accountSelect = document.getElementById("account_select");
    const toLocationSelect = document.getElementById("location_destino");
    const transferHelp = document.getElementById("transferHelp");
    const currentAccount = <?php echo (int)$current_account; ?>;

    function updateTransferHelp() {
        const selectedAccount = parseInt(accountSelect.value || 0);

        if (selectedAccount === currentAccount) {
            transferHelp.innerHTML = "Esta operación será un traslado entre ubicaciones de la misma cuenta.";
        } else {
            transferHelp.innerHTML = "Esta operación será un traslado entre cuentas diferentes.";
        }
    }

    function loadOriginLocationsByProduct(selectedLocationId = "") {
        let product_id = productSelect.value;

        if (!product_id) {
            fromLocationSelect.innerHTML = '<option value="">Selecciona primero el producto</option>';
            return;
        }

        fetch("get_product_locations_stock.php?product_id=" + product_id)
            .then(response => response.json())
            .then(data => {
                fromLocationSelect.innerHTML = '<option value="">Selecciona la ubicación origen</option>';

                if (data.length === 0) {
                    fromLocationSelect.innerHTML = '<option value="">No hay ubicaciones con stock disponible</option>';
                    return;
                }

                data.forEach(function (loc) {
                    let selected = (selectedLocationId && selectedLocationId == loc.id) ? 'selected' : '';
                    fromLocationSelect.innerHTML += `<option value="${loc.id}" ${selected}>${loc.name} (${loc.qty})</option>`;
                });
            })
            .catch(() => {
                fromLocationSelect.innerHTML = '<option value="">No fue posible cargar las ubicaciones</option>';
            });
    }

    function loadDestinationLocations(selectedLocationId = "") {
        let account_id = accountSelect.value;

        if (!account_id) {
            toLocationSelect.innerHTML = '<option value="">Selecciona primero la cuenta destino</option>';
            updateTransferHelp();
            return;
        }

        fetch("get_locations_by_account.php?account_id=" + account_id)
            .then(response => response.json())
            .then(data => {
                toLocationSelect.innerHTML = '<option value="">Selecciona la ubicación destino</option>';

                data.forEach(function (loc) {
                    let selected = (selectedLocationId && selectedLocationId == loc.id) ? 'selected' : '';
                    toLocationSelect.innerHTML += `<option value="${loc.id}" ${selected}>${loc.name}</option>`;
                });

                updateTransferHelp();
            })
            .catch(() => {
                toLocationSelect.innerHTML = '<option value="">No fue posible cargar las ubicaciones</option>';
            });
    }

    productSelect.addEventListener("change", function () {
        loadOriginLocationsByProduct();
    });

    accountSelect.addEventListener("change", function () {
        loadDestinationLocations();
    });

    document.addEventListener("DOMContentLoaded", function () {
        loadDestinationLocations();
        updateTransferHelp();
    });
</script>