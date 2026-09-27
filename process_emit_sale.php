<?php
$page_title = 'Procesar Emisión de Venta';

require_once('includes/load.php');

page_require_level(2);


/* =========================================================
   VALIDAR DATOS OBLIGATORIOS
========================================================= */

if (
  !isset($_POST['sale_id']) ||
  !isset($_POST['id_client']) ||
  !isset($_POST['account_sender']) ||
  !isset($_POST['sale_type']) ||
  !isset($_POST['products']) ||
  !isset($_POST['payments'])
) {
  $session->msg(
    'd',
    'Faltan datos para procesar la venta.'
  );

  redirect('sales.php', false);
}


$current_user = current_user();

if (!$current_user) {

  $session->msg(
    'd',
    'Usuario no válido.'
  );

  redirect('sales.php', false);
}


/* =========================================================
   HELPERS
========================================================= */

function sale_emit_error($message)
{
  global $session;

  $sale_id = isset($_POST['sale_id'])
    ? (int)$_POST['sale_id']
    : 0;

  $session->msg(
    'd',
    $message
  );

  if ($sale_id > 0) {

    redirect(
      'emit_sale.php?id=' . $sale_id,
      false
    );

  } else {

    redirect(
      'sales.php',
      false
    );
  }

  exit;
}


/*
|--------------------------------------------------------------------------
| OBTENER ÚLTIMO PRECIO DE COSTO
|--------------------------------------------------------------------------
*/

function get_last_cost_price_emit(
  $product_id,
  $account_sender
) {
  global $db;

  $product_id = (int)$product_id;
  $account_sender = (int)$account_sender;

  $sql = "SELECT buy_price
          FROM inventory
          WHERE product_id = '{$product_id}'
            AND account = '{$account_sender}'
            AND movement_type = 'Compra'
            AND active = 1
          ORDER BY id DESC
          LIMIT 1";

  $result = $db->query($sql);

  if (
    $result &&
    $db->num_rows($result) > 0
  ) {

    $row = $db->fetch_assoc($result);

    return (float)$row['buy_price'];
  }

  return 0;
}


/* =========================================================
   DATOS GENERALES
========================================================= */

$sale_id = (int)$_POST['sale_id'];

$id_client = (int)$_POST['id_client'];

$account_sender = (int)$_POST['account_sender'];

$sale_type = isset($_POST['sale_type'])
  ? $db->escape($_POST['sale_type'])
  : 'Mayorista';


$client_phone = isset($_POST['client_phone'])
  ? $db->escape($_POST['client_phone'])
  : '';


$client_mail = isset($_POST['client_mail'])
  ? $db->escape($_POST['client_mail'])
  : '';


$client_address = isset($_POST['client_address'])
  ? $db->escape($_POST['client_address'])
  : '';


$client_note = isset($_POST['client_note'])
  ? $db->escape($_POST['client_note'])
  : '';


$account = (int)$_SESSION['account'];

$user = $db->escape(
  $current_user['name'] ?? ''
);

$date = make_date();


/* =========================================================
   ACCIÓN
========================================================= */

$action = isset($_POST['action'])
  ? $db->escape($_POST['action'])
  : '';


$is_emit = (
  $action === 'emit'
);


/* =========================================================
   VALIDACIONES GENERALES
========================================================= */

if ($sale_id <= 0) {

  sale_emit_error(
    'Venta inválida.'
  );
}


if ($id_client <= 0) {

  sale_emit_error(
    'Cliente inválido.'
  );
}


if ($account_sender <= 0) {

  sale_emit_error(
    'Cuenta emisora inválida.'
  );
}


$valid_sale_types = [
  'Diaria',
  'Mayorista',
  'Mercado Libre'
];


if (!in_array(
  $sale_type,
  $valid_sale_types
)) {

  sale_emit_error(
    'Tipo de venta inválido.'
  );
}


$valid_actions = [
  'update',
  'emit'
];


if (!in_array(
  $action,
  $valid_actions
)) {

  sale_emit_error(
    'Acción inválida.'
  );
}


/* =========================================================
   VALIDAR PORCENTAJE GLOBAL DE DESCUENTO
========================================================= */

/*
|--------------------------------------------------------------------------
| El porcentaje global viene del input:
|
| saleDiscountPercent
|
| El servidor NO confía en discount_amount,
| subtotal o final_subtotal enviados por JS.
|
| PHP vuelve a calcular todo.
|--------------------------------------------------------------------------
*/

$sale_discount_percent = isset(
  $_POST['discount_percent']
)
  ? (float)$_POST['discount_percent']
  : 0;


if (is_nan($sale_discount_percent)) {
  $sale_discount_percent = 0;
}


if ($sale_discount_percent < 0) {
  $sale_discount_percent = 0;
}


if ($sale_discount_percent > 100) {
  $sale_discount_percent = 100;
}


$sale_discount_percent = round(
  $sale_discount_percent,
  2
);


/* =========================================================
   VALIDAR VENTA ORIGINAL
========================================================= */

$sale = find_sale_for_emit(
  $sale_id,
  $account
);


if (!$sale) {

  sale_emit_error(
    'La venta no existe.'
  );
}


if ($sale['status'] !== 'En Validación') {

  sale_emit_error(
    'La venta ya no está en validación.'
  );
}


/* =========================================================
   JSON DE PRODUCTOS Y PAGOS
========================================================= */

$products_json = $_POST['products'];

$payments_json = $_POST['payments'];


$products = json_decode(
  $products_json,
  true
);


$payments = json_decode(
  $payments_json,
  true
);


if (!is_array($products)) {
  $products = [];
}


if (!is_array($payments)) {
  $payments = [];
}


if (
  $is_emit &&
  empty($products)
) {

  sale_emit_error(
    'Debe agregar al menos un producto para emitir la venta.'
  );
}


/* =========================================================
   VALIDAR PRODUCTOS Y CALCULAR TOTAL
========================================================= */

$total = 0;

$total_discount = 0;

$locked_dispatch_type = null;


/*
|--------------------------------------------------------------------------
| Aquí NO usamos:
|
|   p['discount_amount']
|   p['subtotal']
|   p['final_subtotal']
|
| porque esos valores vienen del navegador.
|
| PHP calcula nuevamente el descuento.
|--------------------------------------------------------------------------
*/

foreach ($products as $p) {


  /* -------------------------------------------------------
     DATOS OBLIGATORIOS
  ------------------------------------------------------- */

  if (
    !isset($p['product_id']) ||
    !isset($p['location_product_id']) ||
    !isset($p['quantity']) ||
    !isset($p['unitPrice'])
  ) {

    sale_emit_error(
      'Hay productos con datos incompletos.'
    );
  }


  $product_id = (int)$p['product_id'];

  $location_product_id =
    (int)$p['location_product_id'];

  $qty = (int)$p['quantity'];

  $price = (float)$p['unitPrice'];


  /* -------------------------------------------------------
     VALIDAR DATOS
  ------------------------------------------------------- */

  if (
    $product_id <= 0 ||
    $location_product_id <= 0 ||
    $qty <= 0 ||
    $price < 0
  ) {

    sale_emit_error(
      'Hay productos con datos inválidos.'
    );
  }


  /* -------------------------------------------------------
     VALIDAR SELECCIÓN DE DESCUENTO
  ------------------------------------------------------- */

  $discount_selected = !empty(
    $p['discount_selected']
  );


  /*
  |--------------------------------------------------------------------------
  | Si el producto NO está seleccionado:
  |
  | descuento = 0
  |--------------------------------------------------------------------------
  */

  $discount_percent = 0;


  if (
    $discount_selected &&
    $sale_discount_percent > 0
  ) {

    $discount_percent =
      $sale_discount_percent;
  }


  /* -------------------------------------------------------
     SUBTOTAL ORIGINAL
  ------------------------------------------------------- */

  $subtotal = round(
    $qty * $price,
    2
  );


  /* -------------------------------------------------------
     PRECIO DESPUÉS DEL DESCUENTO
  ------------------------------------------------------- */

  $discounted_price = round(
    $price * (
      1 - (
        $discount_percent / 100
      )
    ),
    2
  );


  if ($discounted_price < 0) {
    $discounted_price = 0;
  }


  /* -------------------------------------------------------
     DESCUENTO DE LA FILA
  ------------------------------------------------------- */

  $discount_amount = round(
    $subtotal -
    ($qty * $discounted_price),
    2
  );


  if ($discount_amount < 0) {
    $discount_amount = 0;
  }


  /* -------------------------------------------------------
     SUBTOTAL FINAL
  ------------------------------------------------------- */

  $final_subtotal = round(
    $qty * $discounted_price,
    2
  );


  /* -------------------------------------------------------
     ACUMULAR TOTALES
  ------------------------------------------------------- */

  $total += $final_subtotal;

  $total_discount += $discount_amount;


  /* -------------------------------------------------------
     VALIDAR UBICACIÓN
  ------------------------------------------------------- */

  $row_check = find_sale_product_location(
    $location_product_id,
    $product_id,
    $account_sender
  );


  if (!$row_check) {

    sale_emit_error(
      'Una de las ubicaciones seleccionadas no es válida para la venta.'
    );
  }


  /* -------------------------------------------------------
     VALIDAR STOCK
  ------------------------------------------------------- */

  if (
    (float)$row_check['qty'] < $qty
  ) {

    sale_emit_error(
      'Stock insuficiente para uno de los productos seleccionados.'
    );
  }


  /* -------------------------------------------------------
     TIPO DE UBICACIÓN / DESPACHO
  ------------------------------------------------------- */

  $location_type =
    ($row_check['location_type'] === 'Externa')
      ? 'Externa'
      : 'Interna';


  $dispatch_type =
    ($location_type === 'Externa')
      ? 'Externo'
      : 'Interno';


  /* -------------------------------------------------------
     NO MEZCLAR DESPACHOS
  ------------------------------------------------------- */

  if ($locked_dispatch_type === null) {

    $locked_dispatch_type =
      $dispatch_type;

  } elseif (
    $locked_dispatch_type !== $dispatch_type
  ) {

    sale_emit_error(
      'No se pueden mezclar productos con despacho Interno y Externo en la misma venta.'
    );
  }
}


/* =========================================================
   REDONDEAR TOTALES
========================================================= */

$total = round(
  $total,
  2
);


$total_discount = round(
  $total_discount,
  2
);


/* =========================================================
   VALIDAR PAGOS
========================================================= */

$total_paid = 0;


foreach ($payments as $pay) {


  if (
    !isset($pay['account_id']) ||
    !isset($pay['amount'])
  ) {

    sale_emit_error(
      'Hay pagos con datos incompletos.'
    );
  }


  $payment_account_id =
    (int)$pay['account_id'];


  $payment_amount =
    (float)$pay['amount'];


  if (
    $payment_account_id <= 0 ||
    $payment_amount <= 0
  ) {

    sale_emit_error(
      'Hay pagos con datos inválidos.'
    );
  }


  $total_paid +=
    $payment_amount;
}


$total_paid = round(
  $total_paid,
  2
);


$remaining = round(
  $total - $total_paid,
  2
);


/* =========================================================
   VALIDAR QUE ESTÉ PAGADA PARA EMITIR
========================================================= */

if (
  $is_emit &&
  abs($remaining) > 0.01
) {

  sale_emit_error(
    'La venta debe quedar totalmente pagada para emitirse.'
  );
}


/* =========================================================
   TRANSACCIÓN
========================================================= */

$db->query(
  "START TRANSACTION"
);


/* =========================================================
   ESTADO DE LA VENTA
========================================================= */

$status_to_save =
  $is_emit
    ? 'Emitida'
    : 'En Validación';


/* =========================================================
   ACTUALIZAR CABECERA
========================================================= */

$sql_update_sale = "UPDATE sales
                    SET client_id = '{$id_client}',
                        sale_type = '{$sale_type}',
                        total = '{$total}',
                        address = '{$client_address}',
                        note = '{$client_note}',
                        user_status = '{$user}',
                        date = '{$date}',
                        account_sender = '{$account_sender}',
                        phone = '{$client_phone}',
                        status = '{$status_to_save}'
                    WHERE id = '{$sale_id}'
                      AND account = '{$account}'
                    LIMIT 1";


if (!$db->query(
  $sql_update_sale
)) {

  $db->query(
    "ROLLBACK"
  );

  sale_emit_error(
    'No se pudo actualizar la venta.'
  );
}


/* =========================================================
   ELIMINAR DETALLE ANTERIOR
========================================================= */

$sql_delete_details =
  "DELETE FROM sales_detail
   WHERE sale_id = '{$sale_id}'";


if (!$db->query(
  $sql_delete_details
)) {

  $db->query(
    "ROLLBACK"
  );

  sale_emit_error(
    'No se pudo limpiar el detalle anterior.'
  );
}


/* =========================================================
   ELIMINAR PAGOS ANTERIORES
========================================================= */

$sql_delete_payments =
  "DELETE FROM account_movements
   WHERE related_table = 'Ventas'
     AND related_id = '{$sale_id}'
     AND account = '{$account}'";


if (!$db->query(
  $sql_delete_payments
)) {

  $db->query(
    "ROLLBACK"
  );

  sale_emit_error(
    'No se pudieron limpiar los pagos anteriores.'
  );
}


/* =========================================================
   INSERTAR DETALLE NUEVO
========================================================= */

foreach ($products as $p) {


  /* -------------------------------------------------------
     DATOS BÁSICOS
  ------------------------------------------------------- */

  $product_id =
    (int)$p['product_id'];


  $location_product_id =
    (int)$p['location_product_id'];


  $qty =
    (int)$p['quantity'];


  $price =
    (float)$p['unitPrice'];


  $note =
    isset($p['note'])
      ? $db->escape($p['note'])
      : '';


  /* -------------------------------------------------------
     DESCUENTO
  ------------------------------------------------------- */

  $discount_selected =
    !empty($p['discount_selected']);


  $discount_percent = 0;


  if (
    $discount_selected &&
    $sale_discount_percent > 0
  ) {

    $discount_percent =
      $sale_discount_percent;
  }


  /* -------------------------------------------------------
     SUBTOTAL ORIGINAL
  ------------------------------------------------------- */

  $subtotal = round(
    $qty * $price,
    2
  );


  /* -------------------------------------------------------
     PRECIO FINAL UNITARIO
  ------------------------------------------------------- */

  $discounted_price = round(
    $price * (
      1 - (
        $discount_percent / 100
      )
    ),
    2
  );


  if ($discounted_price < 0) {
    $discounted_price = 0;
  }


  /* -------------------------------------------------------
     SUBTOTAL FINAL
  ------------------------------------------------------- */

  $final_subtotal = round(
    $qty * $discounted_price,
    2
  );


  /* -------------------------------------------------------
     VALIDAR UBICACIÓN NUEVAMENTE
  ------------------------------------------------------- */

  $row_detail =
    find_sale_product_location(
      $location_product_id,
      $product_id,
      $account_sender
    );


  if (!$row_detail) {

    $db->query(
      "ROLLBACK"
    );

    sale_emit_error(
      'No se pudo validar una ubicación del detalle.'
    );
  }


  /* -------------------------------------------------------
     VALIDAR STOCK
  ------------------------------------------------------- */

  $qty_before =
    (float)$row_detail['qty'];


  if ($qty_before < $qty) {

    $db->query(
      "ROLLBACK"
    );

    sale_emit_error(
      'Stock insuficiente al confirmar la venta.'
    );
  }


  /* -------------------------------------------------------
     TIPO DE UBICACIÓN
  ------------------------------------------------------- */

  $location_type =
    ($row_detail['location_type'] === 'Externa')
      ? 'Externa'
      : 'Interna';


  $dispatch_type =
    ($location_type === 'Externa')
      ? 'Externo'
      : 'Interno';


  /* -------------------------------------------------------
     VALIDAR TIPO DE DESPACHO
  ------------------------------------------------------- */

  if (
    $locked_dispatch_type !==
    $dispatch_type
  ) {

    $db->query(
      "ROLLBACK"
    );

    sale_emit_error(
      'La venta intenta mezclar tipos de despacho.'
    );
  }


  /* -------------------------------------------------------
     PRECIO DE COSTO
  ------------------------------------------------------- */

  $cost_price =
    get_last_cost_price_emit(
      $product_id,
      $account_sender
    );


  $detail_status =
    'N/A';


  /* =======================================================
     INSERTAR DETALLE
     
     AQUÍ SE GUARDAN LOS DATOS DEL DESCUENTO
  ======================================================= */

  $sql_detail = "INSERT INTO sales_detail (
                    sale_id,
                    product_id,
                    location_product_id,
                    dispatch_type,
                    qty,
                    price,
                    cost_price,
                    note,
                    account,
                    status,
                    discount_percent,
                    discounted_price
                 ) VALUES (
                    '{$sale_id}',
                    '{$product_id}',
                    '{$location_product_id}',
                    '{$dispatch_type}',
                    '{$qty}',
                    '{$price}',
                    '{$cost_price}',
                    '{$note}',
                    '{$account}',
                    '{$detail_status}',
                    '{$discount_percent}',
                    '{$discounted_price}'
                 )";


  if (!$db->query(
    $sql_detail
  )) {

    $db->query(
      "ROLLBACK"
    );

    sale_emit_error(
      'No se pudo registrar el detalle de la venta.'
    );
  }


  /* =======================================================
     DESCONTAR STOCK SOLAMENTE AL EMITIR
  ======================================================= */

  if ($is_emit) {


    $sql_stock =
      "UPDATE product_locations
       SET qty = qty - '{$qty}'
       WHERE id = '{$location_product_id}'
         AND product_id = '{$product_id}'
         AND account = '{$account_sender}'
         AND active = 1
         AND qty >= '{$qty}'
       LIMIT 1";


    if (!$db->query(
      $sql_stock
    )) {

      $db->query(
        "ROLLBACK"
      );

      sale_emit_error(
        'No se pudo descontar el stock de una ubicación.'
      );
    }


    /* -----------------------------------------------------
       VERIFICAR STOCK
    ----------------------------------------------------- */

    $sql_verify =
      "SELECT qty
       FROM product_locations
       WHERE id = '{$location_product_id}'
         AND product_id = '{$product_id}'
         AND account = '{$account_sender}'
         AND active = 1
       LIMIT 1";


    $result_verify =
      $db->query(
        $sql_verify
      );


    if (
      !$result_verify ||
      $db->num_rows($result_verify) === 0
    ) {

      $db->query(
        "ROLLBACK"
      );

      sale_emit_error(
        'No se pudo verificar el stock descontado.'
      );
    }


    $row_verify =
      $db->fetch_assoc(
        $result_verify
      );


    $expected_qty =
      $qty_before - $qty;


    $current_qty =
      (float)$row_verify['qty'];


    if (
      abs(
        $current_qty -
        $expected_qty
      ) > 0.01
    ) {

      $db->query(
        "ROLLBACK"
      );

      sale_emit_error(
        'No se pudo descontar correctamente el stock de una ubicación.'
      );
    }
  }
}


/* =========================================================
   INSERTAR PAGOS NUEVOS
========================================================= */

if (!empty($payments)) {

  foreach ($payments as $pay) {


    $payment_account_id =
      (int)$pay['account_id'];


    $payment_amount =
      (float)$pay['amount'];


    $payment_reference =
      isset($pay['reference'])
        ? $db->escape($pay['reference'])
        : '';


    $sql_pay =
      "INSERT INTO account_movements (
        related_id,
        financial_account_id,
        related_table,
        movement_type,
        amount,
        reference,
        account,
        date
      ) VALUES (
        '{$sale_id}',
        '{$payment_account_id}',
        'Ventas',
        'Crédito',
        '{$payment_amount}',
        '{$payment_reference}',
        '{$account}',
        '{$date}'
      )";


    if (!$db->query(
      $sql_pay
    )) {

      $db->query(
        "ROLLBACK"
      );

      sale_emit_error(
        'No se pudo registrar uno de los pagos.'
      );
    }
  }
}


/* =========================================================
   COMMIT
========================================================= */

if (!$db->query(
  "COMMIT"
)) {

  $db->query(
    "ROLLBACK"
  );

  sale_emit_error(
    'No se pudo finalizar el proceso de la venta.'
  );
}


/* =========================================================
   RESULTADO
========================================================= */

if ($is_emit) {

  send_sale_notification_email_intern(
    $sale_id,
    'emitida'
  );

  $session->msg(
    's',
    'Venta emitida satisfactoriamente.'
  );

} else {

  $session->msg(
    's',
    'Venta actualizada satisfactoriamente.'
  );

  redirect(
    'emit_sale.php?id=' . $sale_id,
    false
  );
}


redirect(
  'sales.php',
  false
);
?>