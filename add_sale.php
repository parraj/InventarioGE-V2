<?php
$page_title = 'Nueva Venta';
require_once('includes/load.php');
page_require_level(2);

if (!isset($_POST['dni']) || !isset($_POST['account_sender'])) {
    redirect('home.php', false);
}

$dni = (int) $_POST['dni'];
$account_sender_id = (int) $_POST['account_sender'];

$e_client = find_by_dni('clients', $dni, 1);
if (!$e_client) {
    redirect('add_client_sale.php?dni=' . urlencode($dni) . '&account_sender=' . $account_sender_id, false);
}

$financial_accounts = find_all_for_account('financial_accounts', $_SESSION['account']);
$products = find_all_for_account('products', $account_sender_id);
$account_sender = find_by_id('accounts', $account_sender_id);

if (!$account_sender) {
    redirect('home.php', false);
}
?>

<?php include_once('layouts/header.php'); ?>

<form method="post" id="saleForm" action="insert_sale.php" autocomplete="off">

    <!-- DATA -->
    <input type="hidden" name="products" id="productsInput">
    <input type="hidden" name="payments" id="paymentsInput">
    <input type="hidden" name="id_client" value="<?php echo (int) $e_client['id']; ?>">
    <input type="hidden" name="account_sender" value="<?php echo (int) $account_sender_id; ?>">

    <div class="row">

        <!-- ================= LEFT ================= -->
        <div class="col-lg-8">

            <!-- CLIENT -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Datos del Cliente</h5>

                    <div class="row g-3">

                        <div class="col-md-3">
                            <label class="form-label">Cuenta</label>
                            <input class="form-control" disabled
                                value="<?php echo remove_junk($account_sender['name']); ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">DNI</label>
                            <input class="form-control" disabled
                                value="<?php echo remove_junk($e_client['dni']); ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Nombre</label>
                            <input type="hidden" id="client_name"
                                value="<?php echo remove_junk($e_client['name']); ?>">
                            <input class="form-control" disabled
                                value="<?php echo remove_junk($e_client['name']); ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tipo de Venta</label>
                            <select name="sale_type" id="sale_type" class="form-select" onchange="updateUI()">
                                <option value="Diaria" selected>Diaria</option>
                                <option value="Mayorista">Mayorista</option>
                                <option value="Mercado Libre">Mercado Libre</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input id="client_phone" name="client_phone" class="form-control"
                                value="<?php echo remove_junk($e_client['phone']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input id="client_mail" name="client_mail" class="form-control"
                                value="<?php echo remove_junk($e_client['mail']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Dirección</label>
                            <textarea id="client_address" name="client_address" class="form-control" rows="3"
                                style="resize: none; white-space: pre-wrap;"><?php echo remove_junk($e_client['address']); ?></textarea>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Notas</label>
                            <textarea id="client_note" name="client_note" class="form-control" rows="3"
                                style="resize: none;"><?php echo remove_junk($e_client['note']); ?></textarea>
                        </div>

                    </div>
                </div>
            </div>

            <!-- PRODUCTS -->
            <div class="card mb-4">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Productos</h5>

                        <button type="button"
                            class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#addProductModal"
                            onclick="prepareProductModal()">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                    </div>

                    <div class="table-responsive">

                        <table id="productosSeleccionados" class="table table-sm align-middle">

                            <thead>
                                <tr>
                                    <th style="width:50px;"></th>
                                    <th>Producto</th>
                                    <th>Ubicación</th>
                                    <th>Despacho</th>
                                    <th style="width:130px;">Cantidad</th>
                                    <th style="width:140px;">Precio</th>
                                    <th>Nota</th>
                                    <th style="width:120px;">Subtotal</th>
                                </tr>
                            </thead>

                            <tbody></tbody>

                            <tfoot></tfoot>

                        </table>

                    </div>

                    <!-- ================= DESCUENTO ================= -->

                    <hr class="my-4">

                    <div class="row g-3 align-items-end">

                        <div class="col-md-4">

                            <label for="saleDiscountPercent" class="form-label">
                                Descuento (%)
                            </label>

                            <input
                            type="number"
                            id="saleDiscountPercent"
                            name="discount_percent"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                            value="0"
                            placeholder="Ej: 10"
                            oninput="updateDiscountPercent()">

                            <small class="text-muted">
                                Ingrese el porcentaje de descuento.
                            </small>

                        </div>

                    </div>

                </div>
            </div>

            <!-- PAYMENTS -->
            <div class="card mb-4">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <h5 class="card-title mb-0">
                            Pagos
                        </h5>

                        <small class="text-muted" id="paymentsHelpText">
                            Venta Diaria y Mercado Libre requieren pago completo.
                            Mayorista puede quedar pendiente.
                        </small>

                    </div>

                    <div class="row g-3 mb-3">

                        <div class="col-md-4">

                            <select id="paymentAccount" class="form-select">

                                <option value="">
                                    Cuenta
                                </option>

                                <?php foreach ($financial_accounts as $fa): ?>

                                    <option value="<?php echo (int) $fa['id']; ?>">
                                        <?php echo remove_junk($fa['name']); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-3">

                            <input
                                type="number"
                                id="paymentAmount"
                                class="form-control"
                                placeholder="Monto"
                                step="0.01"
                                min="0">

                        </div>

                        <div class="col-md-4">

                            <input
                                type="text"
                                id="paymentReference"
                                class="form-control"
                                placeholder="Referencia">

                        </div>

                        <div class="col-md-1">

                            <button
                                type="button"
                                class="btn btn-primary w-100"
                                onclick="addPayment()">

                                <i class="bi bi-plus-circle"></i>

                            </button>

                        </div>

                    </div>

                    <div class="table-responsive">

                        <table class="table table-sm align-middle">

                            <thead>

                                <tr>
                                    <th style="width:50px;"></th>
                                    <th>Cuenta</th>
                                    <th>Monto</th>
                                    <th>Referencia</th>
                                </tr>

                            </thead>

                            <tbody id="paymentsTable"></tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

        <!-- ================= RIGHT ================= -->

        <div class="col-lg-4">

            <div class="card sticky-top" style="top:90px">

                <div class="card-body">

                    <h5 class="card-title">
                        Resumen
                    </h5>

                    <div id="saleSummary" class="mb-3"></div>

                    <button
                        type="submit"
                        name="action"
                        value="emit"
                        id="confirmSaleBtn"
                        class="btn btn-primary w-100">

                        Emitir Venta

                    </button>

                </div>

            </div>

        </div>

    </div>

</form>


<!-- ================= MODAL PRODUCTS ================= -->

<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Agregar Producto
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="mb-3">

                    <label class="form-label">
                        Producto
                    </label>

                    <select
                        id="productSelect"
                        class="selectpicker w-100"
                        data-live-search="true"
                        onchange="loadProductLocations()">

                        <option value="">
                            Seleccione un producto
                        </option>

                        <?php foreach ($products as $p): ?>

                            <?php

                            $images = find_product_img($p['id']);
                            $img = !empty($images) ? $images[0]['image'] : '';
                            $thumb = getThumbnail($img);

                            if ($thumb) {

                                $content = '
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <img src="' . $thumb . '"
                                            style="width:45px;height:45px;object-fit:contain;border-radius:50%;">
                                        <span>'
                                            . remove_junk($p['name']) .
                                            ' - $' .
                                            number_format((float) $p['sale_price'], 2) .
                                        '</span>
                                    </div>';

                            } else {

                                $content = '
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="width:45px;height:45px;border-radius:50%;background:#f1f5f9;
                                            display:flex;align-items:center;justify-content:center;">
                                            <i class="ti ti-building text-muted"></i>
                                        </div>
                                        <span>'
                                            . remove_junk($p['name']) .
                                            ' - $' .
                                            number_format((float) $p['sale_price'], 2) .
                                        '</span>
                                    </div>';

                            }

                            ?>

                            <option
                                value="<?php echo (int) $p['id']; ?>"
                                data-price="<?php echo (float) $p['sale_price']; ?>"
                                data-stock="<?php echo (float) $p['qty']; ?>"
                                data-content='<?php echo $content; ?>'>

                                <?php echo remove_junk($p['name']); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Ubicación con stock
                    </label>

                    <select
                        id="locationProductSelect"
                        class="form-select">

                        <option value="">
                            Seleccione una ubicación
                        </option>

                    </select>

                    <small
                        id="locationDispatchInfo"
                        class="text-muted d-block mt-1">
                    </small>

                </div>


                <div class="row g-3">

                    <div class="col-md-4">

                        <label class="form-label">
                            Cantidad
                        </label>

                        <input
                            type="number"
                            id="productQty"
                            class="form-control"
                            placeholder="Cantidad"
                            min="1">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Precio
                        </label>

                        <input
                            type="number"
                            id="productPrice"
                            class="form-control"
                            placeholder="Precio"
                            min="0"
                            step="0.01">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Stock disponible
                        </label>

                        <input
                            type="text"
                            id="productStockInfo"
                            class="form-control"
                            disabled
                            value="-">

                    </div>

                </div>


                <div class="mt-3">

                    <label class="form-label">
                        Nota
                    </label>

                    <input
                        type="text"
                        id="productNote"
                        class="form-control"
                        placeholder="Nota del producto">

                </div>


                <div class="alert alert-info mt-3 mb-0">

                    El responsable del despacho se define automáticamente según la ubicación:

                    <br>

                    <strong>
                        Interna → El despacho lo realizas tú
                    </strong>

                    <br>

                    <strong>
                        Externa → El despacho es realizado por un tercero
                    </strong>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="saleAddProductFromModal()">

                    Agregar

                </button>

            </div>

        </div>

    </div>

</div>


<?php include_once('layouts/footer.php'); ?>

<script>

</script>