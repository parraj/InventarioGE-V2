<?php
$page_title = 'Emitir Venta';
require_once('includes/load.php');
page_require_level(2);

if (!isset($_GET['id'])) {
  $session->msg('d', 'Venta no especificada.');
  redirect('sales.php', false);
}

$sale_id = (int)$_GET['id'];
$account = (int)$_SESSION['account'];

if ($sale_id <= 0) {
  $session->msg('d', 'Venta inválida.');
  redirect('sales.php', false);
}

$sale = find_sale_for_emit($sale_id, $account);

if (!$sale) {
  $session->msg('d', 'La venta no existe.');
  redirect('sales.php', false);
}

if ($sale['status'] !== 'En Validación') {
  $session->msg('d', 'Solo se pueden emitir ventas en validación.');
  redirect('sales.php', false);
}

$account_sender_id = (int)$sale['account_sender'];

$financial_accounts = find_all_for_account(
  'financial_accounts',
  $_SESSION['account']
);

$products = find_all_for_account(
  'products',
  $account_sender_id
);

$account_sender = find_by_id(
  'accounts',
  $account_sender_id
);

if (!$account_sender) {
  $session->msg('d', 'No se pudo cargar la cuenta emisora.');
  redirect('sales.php', false);
}

$db_products = find_sale_products_for_emit(
  $sale_id,
  $account
);

$db_payments = find_sale_payments_for_emit(
  $sale_id,
  $account
);


/*
|--------------------------------------------------------------------------
| PORCENTAJE DE DESCUENTO DE LA VENTA
|--------------------------------------------------------------------------
|
| El descuento se maneja mediante:
|
|   1. Un porcentaje global.
|   2. Selección individual de productos.
|
| Tomamos el primer porcentaje mayor a cero encontrado.
|
*/

$sale_discount_percent = 0;


/*
|--------------------------------------------------------------------------
| PRODUCTOS DE LA VENTA
|--------------------------------------------------------------------------
|
| IMPORTANTE:
|
| Cada producto permanece como una fila independiente.
| NO se agrupan productos repetidos.
|
*/

$sale_products = [];

foreach ($db_products as $row) {

  $qty = (float)($row['qty'] ?? 0);

  $price = (float)($row['price'] ?? 0);


  /*
  |--------------------------------------------------------------------------
  | DESCUENTO
  |--------------------------------------------------------------------------
  */

  $discount_percent = isset($row['discount_percent'])
    ? (float)$row['discount_percent']
    : 0;


  /*
   * Validar porcentaje.
   */

  if ($discount_percent < 0) {
    $discount_percent = 0;
  }

  if ($discount_percent > 100) {
    $discount_percent = 100;
  }


  /*
  |--------------------------------------------------------------------------
  | PORCENTAJE GLOBAL
  |--------------------------------------------------------------------------
  */

  if (
    $sale_discount_percent <= 0 &&
    $discount_percent > 0
  ) {
    $sale_discount_percent = $discount_percent;
  }


  /*
  |--------------------------------------------------------------------------
  | PRECIO DESPUÉS DEL DESCUENTO
  |--------------------------------------------------------------------------
  */

  if (
    isset($row['discounted_price']) &&
    $row['discounted_price'] !== null &&
    $row['discounted_price'] !== ''
  ) {

    $discounted_price = (float)$row['discounted_price'];

  } else {

    $discounted_price = $price * (
      1 - ($discount_percent / 100)
    );
  }


  /*
   * Evitar valores negativos.
   */

  if ($discounted_price < 0) {
    $discounted_price = 0;
  }


  /*
  |--------------------------------------------------------------------------
  | SUBTOTAL ORIGINAL
  |--------------------------------------------------------------------------
  */

  $subtotal = round(
    $qty * $price,
    2
  );


  /*
  |--------------------------------------------------------------------------
  | DESCUENTO DE LA FILA
  |--------------------------------------------------------------------------
  */

  $discount_amount = round(
    $subtotal - ($qty * $discounted_price),
    2
  );

  if ($discount_amount < 0) {
    $discount_amount = 0;
  }


  /*
  |--------------------------------------------------------------------------
  | SUBTOTAL FINAL
  |--------------------------------------------------------------------------
  */

  $final_subtotal = round(
    $qty * $discounted_price,
    2
  );


  /*
  |--------------------------------------------------------------------------
  | PRODUCTO SELECCIONADO PARA DESCUENTO
  |--------------------------------------------------------------------------
  */

  $discount_selected = (
    $discount_percent > 0
  );


  /*
  |--------------------------------------------------------------------------
  | PRODUCTO
  |--------------------------------------------------------------------------
  */

  $sale_products[] = [

    'product_id' => (int)$row['product_id'],

    'name' => $row['product_name'] ?? '',

    'location_product_id' => (int)(
      $row['location_product_id'] ?? 0
    ),

    'location_id' => (int)(
      $row['location_id'] ?? 0
    ),

    'location_name' => $row['location_name'] ?? '',

    'location_type' => $row['location_type'] ?? 'Interna',

    'dispatch_type' => $row['dispatch_type'] ?? 'Interno',

    'stock_available' => (float)(
      $row['current_stock'] ?? 0
    ),

    'quantity' => $qty,

    /*
     * Precio original.
     */
    'unitPrice' => $price,

    'note' => $row['note'] ?? '',

    /*
     * Descuento.
     */
    'discount_selected' => $discount_selected,

    'discount_percent' => $discount_percent,

    'discount_amount' => $discount_amount,

    /*
     * Subtotales.
     */
    'subtotal' => $subtotal,

    'final_subtotal' => $final_subtotal
  ];
}


/*
|--------------------------------------------------------------------------
| PAGOS DE LA VENTA
|--------------------------------------------------------------------------
*/

$sale_payments = [];

foreach ($db_payments as $row) {

  $sale_payments[] = [

    'account_id' => (int)(
      $row['account_id'] ?? 0
    ),

    'account_name' => $row['account_name'] ?? '',

    'amount' => (float)(
      $row['amount'] ?? 0
    ),

    'reference' => $row['reference'] ?? ''

  ];
}

?>


<?php include_once('layouts/header.php'); ?>


<form
  method="post"
  id="saleForm"
  action="process_emit_sale.php"
  autocomplete="off"
>


  <!-- =========================================================
       DATOS OCULTOS
  ========================================================== -->

  <input
    type="hidden"
    name="sale_id"
    value="<?php echo (int)$sale['id']; ?>"
  >

  <input
    type="hidden"
    name="products"
    id="productsInput"
  >

  <input
    type="hidden"
    name="payments"
    id="paymentsInput"
  >

  <input
    type="hidden"
    name="id_client"
    value="<?php echo (int)$sale['client_id']; ?>"
  >

  <input
    type="hidden"
    name="account_sender"
    value="<?php echo (int)$account_sender_id; ?>"
  >

  <input
    type="hidden"
    id="sale_type"
    name="sale_type"
    value="<?php echo remove_junk($sale['sale_type']); ?>"
  >


  <div class="row">


    <!-- =========================================================
         MENSAJES
    ========================================================== -->

    <div class="col-md-12">
      <?php echo display_msg($msg); ?>
    </div>


    <!-- =========================================================
         COLUMNA PRINCIPAL
    ========================================================== -->

    <div class="col-lg-8">


      <!-- =======================================================
           CLIENTE
      ======================================================== -->

      <div class="card mb-4">

        <div class="card-body">

          <h5 class="card-title">
            Datos del Cliente
          </h5>


          <div class="row g-3">


            <div class="col-md-3">

              <label class="form-label">
                Cuenta
              </label>

              <input
                class="form-control"
                disabled
                value="<?php echo remove_junk(
                  $account_sender['name']
                ); ?>"
              >

            </div>


            <div class="col-md-3">

              <label class="form-label">
                DNI
              </label>

              <input
                class="form-control"
                disabled
                value="<?php echo remove_junk(
                  $sale['client_dni']
                ); ?>"
              >

            </div>


            <div class="col-md-3">

              <label class="form-label">
                Nombre
              </label>

              <input
                type="hidden"
                id="client_name"
                value="<?php echo remove_junk(
                  $sale['client_name']
                ); ?>"
              >

              <input
                class="form-control"
                disabled
                value="<?php echo remove_junk(
                  $sale['client_name']
                ); ?>"
              >

            </div>


            <div class="col-md-3">

              <label class="form-label">
                Tipo de Venta
              </label>

              <input
                type="text"
                class="form-control"
                disabled
                value="<?php echo remove_junk(
                  $sale['sale_type']
                ); ?>"
              >

            </div>


            <div class="col-md-4">

              <label class="form-label">
                Teléfono
              </label>

              <input
                id="client_phone"
                name="client_phone"
                class="form-control"
                value="<?php echo remove_junk(
                  $sale['phone'] ??
                  $sale['client_phone'] ??
                  ''
                ); ?>"
              >

            </div>


            <div class="col-md-4">

              <label class="form-label">
                Email
              </label>

              <input
                id="client_mail"
                name="client_mail"
                class="form-control"
                value="<?php echo remove_junk(
                  $sale['client_mail'] ?? ''
                ); ?>"
              >

            </div>


            <div class="col-md-4">

              <label class="form-label">
                Dirección
              </label>

              <input
                id="client_address"
                name="client_address"
                class="form-control"
                value="<?php echo remove_junk(
                  $sale['address'] ??
                  $sale['client_address'] ??
                  ''
                ); ?>"
              >

            </div>


            <div class="col-12">

              <label class="form-label">
                Notas
              </label>

              <input
                id="client_note"
                name="client_note"
                class="form-control"
                value="<?php echo remove_junk(
                  $sale['note'] ??
                  $sale['client_note'] ??
                  ''
                ); ?>"
              >

            </div>


          </div>

        </div>

      </div>


      <!-- =======================================================
           PRODUCTOS
      ======================================================== -->

      <div class="card mb-4">

        <div class="card-body">


          <!-- ENCABEZADO PRODUCTOS -->

          <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="card-title mb-0">
              Productos
            </h5>


            <button
              type="button"
              class="btn btn-primary"
              data-bs-toggle="modal"
              data-bs-target="#addProductModal"
              onclick="prepareProductModal()"
            >

              <i class="bi bi-plus-circle"></i>

            </button>

          </div>


          <!-- =====================================================
               TABLA PRODUCTOS
          ====================================================== -->

          <div class="table-responsive">

            <table
              id="productosSeleccionados"
              class="table table-sm align-middle"
            >

              <thead>

                <tr>

                  <th style="width:70px;"></th>

                  <th>
                    Producto
                  </th>

                  <th>
                    Ubicación
                  </th>

                  <th>
                    Despacho
                  </th>

                  <th style="width:130px;">
                    Cantidad
                  </th>

                  <th style="width:140px;">
                    Precio
                  </th>

                  <th>
                    Nota
                  </th>

                  <th style="width:140px;">
                    Subtotal
                  </th>

                </tr>

              </thead>


              <tbody></tbody>


              <tfoot></tfoot>

            </table>

          </div>


          <!-- =====================================================
               DESCUENTO GLOBAL
               
               IGUAL QUE EN ADD_SALE:
               queda debajo de la tabla de productos.
          ====================================================== -->

          <div class="row mb-3">

            <div class="col-md-4">

              <label
                for="saleDiscountPercent"
                class="form-label"
              >
                Descuento %
              </label>

              <input
                type="number"
                id="saleDiscountPercent"
                name="discount_percent"
                class="form-control"
                min="0"
                max="100"
                step="0.01"
                value="<?php echo htmlspecialchars(
                  (string)$sale_discount_percent,
                  ENT_QUOTES,
                  'UTF-8'
                ); ?>"
                placeholder="Ej: 10"
                oninput="updateDiscountPercent()"
              >

              <small class="text-muted">
                Marca los productos a los que deseas aplicar el descuento.
              </small>

            </div>

          </div>


        </div>

      </div>


      <!-- =======================================================
           PAGOS
      ======================================================== -->

      <div class="card mb-4">

        <div class="card-body">


          <div class="d-flex justify-content-between align-items-center mb-2">

            <h5 class="card-title mb-0">
              Pagos
            </h5>

            <small
              class="text-muted"
              id="paymentsHelpText"
            >
              La venta debe quedar totalmente pagada para emitirse.
            </small>

          </div>


          <div class="row g-3 mb-3">


            <div class="col-md-4">

              <select
                id="paymentAccount"
                class="form-select"
              >

                <option value="">
                  Cuenta
                </option>


                <?php foreach ($financial_accounts as $fa): ?>

                  <option
                    value="<?php echo (int)$fa['id']; ?>"
                  >

                    <?php echo remove_junk(
                      $fa['name']
                    ); ?>

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
                min="0"
              >

            </div>


            <div class="col-md-4">

              <input
                type="text"
                id="paymentReference"
                class="form-control"
                placeholder="Referencia"
              >

            </div>


            <div class="col-md-1">

              <button
                type="button"
                class="btn btn-primary w-100"
                onclick="addPayment()"
              >

                <i class="bi bi-plus-circle"></i>

              </button>

            </div>


          </div>


          <div class="table-responsive">

            <table class="table table-sm align-middle">

              <thead>

                <tr>

                  <th style="width:50px;"></th>

                  <th>
                    Cuenta
                  </th>

                  <th>
                    Monto
                  </th>

                  <th>
                    Referencia
                  </th>

                </tr>

              </thead>


              <tbody id="paymentsTable"></tbody>

            </table>

          </div>


          <!-- BOTÓN ACTUALIZAR -->

          <div class="d-flex justify-content-center mt-3">

            <button
              id="btnActualizar"
              type="submit"
              name="action"
              value="update"
              class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow"
              style="width:60px;height:60px;"
              title="Actualizar venta"
            >

              <i class="bi bi-arrow-clockwise fs-3"></i>

            </button>

          </div>


        </div>

      </div>


    </div>


    <!-- =========================================================
         COLUMNA RESUMEN
    ========================================================== -->

    <div class="col-lg-4">


      <div
        class="card sticky-top"
        style="top:90px"
      >

        <div class="card-body">


          <h5 class="card-title">
            Resumen
          </h5>


          <div
            id="saleSummary"
            class="mb-3"
          ></div>


          <button
            type="submit"
            name="action"
            value="emit"
            id="confirmSaleBtn"
            class="btn btn-primary w-100"
          >
            Emitir Venta
          </button>


          <button
            type="button"
            class="btn btn-danger w-100 mt-2"
            onclick="delet(<?php echo (int)$sale['id']; ?>)"
          >
            Cancelar Venta
          </button>


        </div>

      </div>


    </div>


  </div>

</form>


<!-- =============================================================
     MODAL AGREGAR PRODUCTO
============================================================== -->

<div
  class="modal fade"
  id="addProductModal"
  tabindex="-1"
  aria-hidden="true"
>

  <div class="modal-dialog modal-lg">

    <div class="modal-content">


      <div class="modal-header">

        <h5 class="modal-title">
          Agregar Producto
        </h5>


        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
        ></button>

      </div>


      <div class="modal-body">


        <!-- PRODUCTO -->

        <div class="mb-3">

          <label class="form-label">
            Producto
          </label>


          <select
            id="productSelect"
            class="form-select"
            onchange="loadProductLocations()"
          >

            <option value="">
              Seleccione un producto
            </option>


            <?php foreach ($products as $p): ?>

              <option
                value="<?php echo (int)$p['id']; ?>"
                data-price="<?php echo (float)$p['sale_price']; ?>"
                data-stock="<?php echo (float)$p['qty']; ?>"
              >

                <?php echo remove_junk(
                  $p['name']
                ); ?>

                -

                $<?php echo number_format(
                  (float)$p['sale_price'],
                  2
                ); ?>

              </option>

            <?php endforeach; ?>


          </select>

        </div>


        <!-- UBICACIÓN -->

        <div class="mb-3">

          <label class="form-label">
            Ubicación con stock
          </label>


          <select
            id="locationProductSelect"
            class="form-select"
          >

            <option value="">
              Seleccione una ubicación
            </option>

          </select>


          <small
            id="locationDispatchInfo"
            class="text-muted d-block mt-1"
          ></small>

        </div>


        <!-- CANTIDAD / PRECIO / STOCK -->

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
              min="1"
            >

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
              step="0.01"
            >

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
              value="-"
            >

          </div>


        </div>


        <!-- NOTA -->

        <div class="mt-3">

          <label class="form-label">
            Nota
          </label>


          <input
            type="text"
            id="productNote"
            class="form-control"
            placeholder="Nota del producto"
          >

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
          onclick="saleAddProductFromModal()"
        >
          Agregar
        </button>

      </div>


    </div>

  </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| PRODUCTOS PRECARGADOS
|--------------------------------------------------------------------------
|
| Cada producto conserva su propia fila.
|
| NO se agrupan productos repetidos.
|
*/

window.preloadedSaleProducts = <?php
  echo json_encode(
    $sale_products,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
  );
?>;


/*
|--------------------------------------------------------------------------
| PAGOS PRECARGADOS
|--------------------------------------------------------------------------
*/

window.preloadedSalePayments = <?php
  echo json_encode(
    $sale_payments,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
  );
?>;


/*
|--------------------------------------------------------------------------
| PORCENTAJE GLOBAL PRECARGADO
|--------------------------------------------------------------------------
|
| Este valor es utilizado por el JS compartido con add_sale.
|
| Ejemplo:
|
| Venta:
|   Producto A -> 10%
|   Producto B -> 0%
|   Producto A -> 10%
|
| Resultado:
|
|   saleDiscountPercent = 10
|
| Los checkboxes de A quedan seleccionados.
| B queda sin seleccionar.
|
*/

window.preloadedSaleDiscountPercent = <?php
  echo json_encode(
    (float)$sale_discount_percent
  );
?>;


/*
|--------------------------------------------------------------------------
| CANCELAR VENTA
|--------------------------------------------------------------------------
*/

function delet(id) {

  Swal.fire({

    icon: "warning",

    title: "¿Estás seguro de cancelar esta venta?",

    showCancelButton: true,

    confirmButtonText: "Sí, cancelar",

    cancelButtonText: "Mejor no",

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

      window.location =
        'process_cancel_sale.php?id=' + id;

    }

  });

}

</script>


<?php include_once('layouts/footer.php'); ?>