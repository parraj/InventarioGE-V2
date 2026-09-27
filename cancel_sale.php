<?php
require_once('includes/load.php');
page_require_level(2);

if (!isset($_GET['id'])) {
    $session->msg('d', 'No se recibió la venta.');
    redirect('sales.php', false);
}

$sale_id = (int)$_GET['id'];
$account = (int)$_SESSION['account'];

if ($sale_id <= 0) {
    $session->msg('d', 'La venta no es válida.');
    redirect('sales.php', false);
}

$sale_sql  = "SELECT id, status ";
$sale_sql .= "FROM sales ";
$sale_sql .= "WHERE id = {$sale_id} ";
$sale_sql .= "AND account = {$account} ";
$sale_sql .= "LIMIT 1";

$sale = find_by_sql($sale_sql);

if (empty($sale)) {
    $session->msg('d', 'La venta no existe o no pertenece a esta cuenta.');
    redirect('sales.php', false);
}

$sale = $sale[0];

if ($sale['status'] === 'Cancelada') {
    $session->msg('d', 'La venta ya está cancelada.');
    redirect('sales.php', false);
}
$current_user = current_user();
if (!$current_user) {
  $session->msg('d', 'Usuario no válido.');
  redirect('sales.php', false);
}
$user = $db->escape($current_user['name'] ?? '');

$db->query("START TRANSACTION");

$has_error = false;

/*
|--------------------------------------------------------------------------
| Si la venta estaba Emitida, devolver stock
|--------------------------------------------------------------------------
*/
if ($sale['status'] === 'Emitida') {
    $detail_sql  = "SELECT location_product_id, qty ";
    $detail_sql .= "FROM sales_detail ";
    $detail_sql .= "WHERE sale_id = {$sale_id} ";
    $detail_sql .= "AND account = {$account}";

    $sale_details = find_by_sql($detail_sql);

    foreach ($sale_details as $detail) {
        $location_product_id = (int)$detail['location_product_id'];
        $qty = (int)$detail['qty'];

        if ($qty <= 0) {
            continue;
        }

        if ($location_product_id <= 0) {
            $has_error = true;
            break;
        }

        $restore_sql  = "UPDATE product_locations SET ";
        $restore_sql .= "qty = qty + {$qty} ";
        $restore_sql .= "WHERE id = {$location_product_id} ";
        $restore_sql .= "AND account = {$account}";

        $restore_result = $db->query($restore_sql);

        if (!$restore_result || $db->affected_rows() < 1) {
            $has_error = true;
            break;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Eliminar movimientos de cuenta relacionados con la venta
|--------------------------------------------------------------------------
*/
if (!$has_error) {
    $movement_sql  = "DELETE FROM account_movements ";
    $movement_sql .= "WHERE related_table = 'Ventas' ";
    $movement_sql .= "AND related_id = {$sale_id} ";
    $movement_sql .= "AND account = {$account}";

    if (!$db->query($movement_sql)) {
        $has_error = true;
    }
}

/*
|--------------------------------------------------------------------------
| Cancelar venta
|--------------------------------------------------------------------------
*/
if (!$has_error) {
    $cancel_sql  = "UPDATE sales SET ";
    $cancel_sql .= "status = 'Cancelada', user_status = '{$user}' ";
    $cancel_sql .= "WHERE id = {$sale_id} ";
    $cancel_sql .= "AND account = {$account} ";
    $cancel_sql .= "AND status <> 'Cancelada' ";
    $cancel_sql .= "LIMIT 1";

    $cancel_result = $db->query($cancel_sql);

    if (!$cancel_result || $db->affected_rows() < 1) {
        $has_error = true;
    }
}

if ($has_error) {
    $db->query("ROLLBACK");
    $session->msg('d', 'No se pudo cancelar la venta.');
    redirect('sales.php', false);
} else {
    $db->query("COMMIT");
    send_sale_notification_email_intern($sale_id,'cancelada');
    $session->msg('s', 'Venta cancelada satisfactoriamente.');
    redirect('sales.php', false);
}
?>