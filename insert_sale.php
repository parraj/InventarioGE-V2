<?php
$page_title = 'Procesar Venta';
require_once('includes/load.php');
page_require_level(2);

if (
    !isset($_POST['id_client']) ||
    !isset($_POST['account_sender']) ||
    !isset($_POST['products']) ||
    !isset($_POST['payments'])
) {
    $session->msg('d', 'Faltan datos para procesar la venta.');
    redirect('home.php', false);
}

$current_user = current_user();
if (!$current_user) {
    $session->msg('d', 'Usuario no válido.');
    redirect('home.php', false);
}

/* =========================================================
   HELPERS
========================================================= */
function sale_error($message) {
    global $session;
    $session->msg('d', $message);
    redirect('sales.php', false);
    exit;
}

function get_last_cost_price($product_id, $account_sender) {
    global $db;

    $product_id     = (int)$product_id;
    $account_sender = (int)$account_sender;

    $sql = "SELECT buy_price
            FROM inventory
            WHERE product_id = '{$product_id}'
              AND account = '{$account_sender}'
              AND movement_type = 'Compra'
              AND active = 1
              and buy_price <> 0
            ORDER BY id DESC
            LIMIT 1";

    $result = $db->query($sql);

    if ($result && $db->num_rows($result) > 0) {
        $row = $db->fetch_assoc($result);
        return (float)$row['buy_price'];
    }

    return 0;
}

/* =========================================================
   DATOS GENERALES
========================================================= */
$id_client      = (int)$_POST['id_client'];
$account_sender = (int)$_POST['account_sender'];
$sale_type      = isset($_POST['sale_type']) ? $db->escape($_POST['sale_type']) : 'Diaria';
$client_phone   = isset($_POST['client_phone']) ? $db->escape($_POST['client_phone']) : '';
$client_mail    = isset($_POST['client_mail']) ? $db->escape($_POST['client_mail']) : '';
$client_address = isset($_POST['client_address']) ? $db->escape($_POST['client_address']) : '';
$client_note    = isset($_POST['client_note']) ? $db->escape($_POST['client_note']) : '';
$account        = (int)$_SESSION['account'];
$user           = $db->escape($current_user['name'] ?? '');
$date           = make_date();

if ($id_client <= 0) {
    sale_error('Cliente inválido.');
}

if ($account_sender <= 0) {
    sale_error('Cuenta emisora inválida.');
}

$valid_sale_types = ['Diaria', 'Mayorista', 'Mercado Libre'];
if (!in_array($sale_type, $valid_sale_types)) {
    sale_error('Tipo de venta inválido.');
}

/* =========================================================
   STATUS DE LA VENTA
========================================================= */
$status = ($sale_type === 'Mayorista') ? 'En Validación' : 'Emitida';
$user_status = ($status === 'Emitida') ? $user : '';

/* =========================================================
   JSON DE PRODUCTOS Y PAGOS
========================================================= */
$products_json = $_POST['products'];
$payments_json = $_POST['payments'];

$products = json_decode($products_json, true);
$payments = json_decode($payments_json, true);

if (!is_array($products) || empty($products)) {
    sale_error('Debe agregar al menos un producto.');
}

if (!is_array($payments)) {
    $payments = [];
}

/* =========================================================
   VALIDAR PRODUCTOS Y TOTAL
========================================================= */
$total = 0;
$locked_dispatch_type = null;

foreach ($products as $p) {
    if (
        !isset($p['product_id']) ||
        !isset($p['location_product_id']) ||
        !isset($p['quantity']) ||
        !isset($p['unitPrice'])
    ) {
        sale_error('Hay productos con datos incompletos.');
    }

    $product_id          = (int)$p['product_id'];
    $location_product_id = (int)$p['location_product_id'];
    $qty                 = (int)$p['quantity'];
    $price               = (float)$p['unitPrice'];

    /* =====================================================
       DESCUENTO POR PRODUCTO
    ===================================================== */
    $discount_selected = !empty($p['discount_selected']);

    $discount_percent = isset($p['discount_percent'])
        ? (float)$p['discount_percent']
        : 0;

    if ($product_id <= 0 || $location_product_id <= 0 || $qty <= 0 || $price < 0) {
        sale_error('Hay productos con datos inválidos.');
    }

    /*
     * Si la fila no está seleccionada para descuento,
     * el porcentaje debe ser 0.
     */
    if (!$discount_selected) {
        $discount_percent = 0;
    }

    /*
     * Validar el porcentaje antes de utilizarlo.
     */
    if ($discount_percent < 0 || $discount_percent > 100) {
        sale_error('El porcentaje de descuento de uno de los productos no es válido.');
    }

    /*
     * Calcular nuevamente el total en PHP.
     * No se utilizan discount_amount ni final_subtotal
     * enviados desde JavaScript.
     */
    $subtotal = round($qty * $price, 2);

    $discount_amount = round(
        $subtotal * ($discount_percent / 100),
        2
    );

    $final_subtotal = round(
        $subtotal - $discount_amount,
        2
    );

    $total += $final_subtotal;

    $sql_check = "SELECT 
                    pl.id,
                    pl.product_id,
                    pl.location_id,
                    pl.qty,
                    pl.account,
                    l.name AS location_name,
                    l.location_type
                  FROM product_locations pl
                  INNER JOIN locations l ON l.id = pl.location_id
                  WHERE pl.id = '{$location_product_id}'
                    AND pl.product_id = '{$product_id}'
                    AND pl.account = '{$account_sender}'
                    AND pl.active = 1
                    AND l.account = '{$account_sender}'
                    AND l.active = 1
                  LIMIT 1";

    $result_check = $db->query($sql_check);

    if (!$result_check || $db->num_rows($result_check) === 0) {
        sale_error('Una de las ubicaciones seleccionadas no es válida para la venta.');
    }

    $row_check = $db->fetch_assoc($result_check);

    if ($status === 'Emitida' && (float)$row_check['qty'] < $qty) {
        sale_error('Stock insuficiente para uno de los productos seleccionados.');
    }

    $location_type = ($row_check['location_type'] === 'Externa') ? 'Externa' : 'Interna';
    $dispatch_type = ($location_type === 'Externa') ? 'Externo' : 'Interno';

    if ($locked_dispatch_type === null) {
        $locked_dispatch_type = $dispatch_type;
    } elseif ($locked_dispatch_type !== $dispatch_type) {
        sale_error('No se pueden mezclar productos con despacho Interno y Externo en la misma venta.');
    }
}

/* =========================================================
   VALIDAR PAGOS
========================================================= */
$total_paid = 0;

foreach ($payments as $pay) {
    if (!isset($pay['account_id']) || !isset($pay['amount'])) {
        sale_error('Hay pagos con datos incompletos.');
    }

    $payment_account_id = (int)$pay['account_id'];
    $payment_amount     = (float)$pay['amount'];

    if ($payment_account_id <= 0 || $payment_amount <= 0) {
        sale_error('Hay pagos con datos inválidos.');
    }

    $total_paid += $payment_amount;
}

$total      = round($total, 2);
$total_paid = round($total_paid, 2);
$remaining  = round($total - $total_paid, 2);

if ($sale_type !== 'Mayorista' && $remaining != 0) {
    sale_error('La venta debe quedar totalmente pagada.');
}

/* =========================================================
   TRANSACCIÓN
========================================================= */
$db->query("START TRANSACTION");

/* =========================================================
   INSERTAR SALE
========================================================= */
$sql_sale = "INSERT INTO sales 
    (
        client_id,
        sale_type,
        total,
        address,
        note,
        user,
        user_status,
        account,
        date,
        account_sender,
        phone,
        status
    ) VALUES (
        '{$id_client}',
        '{$sale_type}',
        '{$total}',
        '{$client_address}',
        '{$client_note}',
        '{$user}',
        '{$user_status}',
        '{$account}',
        '{$date}',
        '{$account_sender}',
        '{$client_phone}',
        '{$status}'
    )";

if (!$db->query($sql_sale)) {
    $db->query("ROLLBACK");
    sale_error('No se pudo registrar la venta.');
}

$sale_id = $db->insert_id();

/* =========================================================
   INSERTAR DETALLE + DESCONTAR STOCK SOLO SI ESTÁ EMITIDA
========================================================= */
foreach ($products as $p) {
    $product_id          = (int)$p['product_id'];
    $location_product_id = (int)$p['location_product_id'];
    $qty                 = (int)$p['quantity'];
    $price               = (float)$p['unitPrice'];
    $note                = isset($p['note']) ? $db->escape($p['note']) : '';

    /* =====================================================
       DESCUENTO POR PRODUCTO
    ===================================================== */
    $discount_selected = !empty($p['discount_selected']);

    $discount_percent = isset($p['discount_percent'])
        ? (float)$p['discount_percent']
        : 0;

    /*
     * Si no está seleccionado el descuento,
     * se guarda 0 aunque JS haya enviado otro valor.
     */
    if (!$discount_selected) {
        $discount_percent = 0;
    }

    if ($discount_percent < 0 || $discount_percent > 100) {
        $db->query("ROLLBACK");
        sale_error('El porcentaje de descuento de uno de los productos no es válido.');
    }

    /*
     * discounted_price es el precio UNITARIO final
     * después del descuento.
     */
    $discounted_price = round(
        $price * (1 - ($discount_percent / 100)),
        2
    );

    $sql_detail_check = "SELECT 
                            pl.id,
                            pl.product_id,
                            pl.location_id,
                            pl.qty,
                            pl.account,
                            l.location_type
                         FROM product_locations pl
                         INNER JOIN locations l ON l.id = pl.location_id
                         WHERE pl.id = '{$location_product_id}'
                           AND pl.product_id = '{$product_id}'
                           AND pl.account = '{$account_sender}'
                           AND pl.active = 1
                           AND l.account = '{$account_sender}'
                           AND l.active = 1
                         LIMIT 1";

    $result_detail_check = $db->query($sql_detail_check);

    if (!$result_detail_check || $db->num_rows($result_detail_check) === 0) {
        $db->query("ROLLBACK");
        sale_error('No se pudo validar una ubicación del detalle.');
    }

    $row_detail = $db->fetch_assoc($result_detail_check);
    $qty_before = (float)$row_detail['qty'];

    if ($qty_before < $qty) {
        $db->query("ROLLBACK");
        sale_error('Stock insuficiente al confirmar la venta.');
    }

    $location_type = ($row_detail['location_type'] === 'Externa') ? 'Externa' : 'Interna';
    $dispatch_type = ($location_type === 'Externa') ? 'Externo' : 'Interno';

    if ($locked_dispatch_type !== $dispatch_type) {
        $db->query("ROLLBACK");
        sale_error('La venta intenta mezclar tipos de despacho.');
    }

    $cost_price = get_last_cost_price($product_id, $account_sender);
    $detail_status = ($sale_type === 'Mayorista') ? 'not_delivered' : 'N/A';

    $sql_detail = "INSERT INTO sales_detail
        (
            sale_id,
            product_id,
            location_product_id,
            dispatch_type,
            qty,
            price,
            discount_percent,
            discounted_price,
            cost_price,
            note,
            account,
            status
        ) VALUES (
            '{$sale_id}',
            '{$product_id}',
            '{$location_product_id}',
            '{$dispatch_type}',
            '{$qty}',
            '{$price}',
            '{$discount_percent}',
            '{$discounted_price}',
            '{$cost_price}',
            '{$note}',
            '{$account}',
            '{$detail_status}'
        )";

    if (!$db->query($sql_detail)) {
        $db->query("ROLLBACK");
        sale_error('No se pudo registrar el detalle de la venta.');
    }

    if ($status === 'Emitida') {
        $sql_discount = "UPDATE product_locations
                         SET qty = qty - '{$qty}'
                         WHERE id = '{$location_product_id}'
                           AND product_id = '{$product_id}'
                           AND account = '{$account_sender}'
                           AND active = 1
                           AND qty >= '{$qty}'
                         LIMIT 1";

        if (!$db->query($sql_discount)) {
            $db->query("ROLLBACK");
            sale_error('No se pudo descontar el stock de una ubicación.');
        }

        $sql_verify = "SELECT qty
                       FROM product_locations
                       WHERE id = '{$location_product_id}'
                         AND product_id = '{$product_id}'
                         AND account = '{$account_sender}'
                         AND active = 1
                       LIMIT 1";

        $result_verify = $db->query($sql_verify);

        if (!$result_verify || $db->num_rows($result_verify) === 0) {
            $db->query("ROLLBACK");
            sale_error('No se pudo verificar el stock descontado.');
        }

        $row_verify = $db->fetch_assoc($result_verify);
        $expected_qty = $qty_before - $qty;
        $current_qty  = (float)$row_verify['qty'];

        if ($current_qty != $expected_qty) {
            $db->query("ROLLBACK");
            sale_error('No se pudo descontar correctamente el stock de una ubicación.');
        }
    }
}

/* =========================================================
   INSERTAR PAGOS
========================================================= */
if (!empty($payments)) {
    foreach ($payments as $pay) {
        $payment_account_id = (int)$pay['account_id'];
        $payment_amount     = (float)$pay['amount'];
        $payment_reference  = isset($pay['reference']) ? $db->escape($pay['reference']) : '';

        $sql_pay = "INSERT INTO account_movements
            (
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

        if (!$db->query($sql_pay)) {
            $db->query("ROLLBACK");
            sale_error('No se pudo registrar uno de los pagos.');
        }
    }
}

/* =========================================================
   COMMIT
========================================================= */
if (!$db->query("COMMIT")) {
    $db->query("ROLLBACK");
    sale_error('No se pudo finalizar la venta.');
}

if ($status === 'Emitida') {
    send_sale_notification_email_intern($sale_id,'emitida');
}

$session->msg('s', 'Venta registrada satisfactoriamente.');
redirect('sales.php', false);
?>