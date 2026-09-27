<?php
require_once('includes/load.php');
page_require_level(2);

if (!isset($_POST['items'])) {
    $session->msg('d', 'Sin datos');
    redirect('inventory.php');
}

$data = json_decode($_POST['items'], true);

if (!$data || count($data) === 0) {
    $session->msg('d', 'Datos inválidos');
    redirect('inventory.php');
}

$current_user = current_user();
$account = $_SESSION['account'];
$date = make_date();
$user = $current_user['name'];

$db->query("START TRANSACTION");

foreach ($data as $item) {

    $pid = (int)$item['product_id'];
    $lid = (int)$item['location_id'];
    $qty = (float)$item['qty'];
    $buy = (float)$item['buy'];
    $total = $qty * $buy;

    $type = ($qty < 0) ? 'Retiro' : 'Compra';

    // Validar stock
    if ($qty < 0) {

        $res = $db->query("
            SELECT COALESCE(SUM(qty),0) stock
            FROM product_locations
            WHERE product_id='{$pid}'
            AND location_id='{$lid}'
            AND account='{$account}'
        ");

        $stock = $db->fetch_assoc($res)['stock'];

        if (abs($qty) > $stock) {
            $db->query("ROLLBACK");
            $session->msg('d', 'Alguno de los productos tiene una cantidad a retirar mayor al stock disponible.');
            redirect('inventory.php');
        }
    }

    // Insert inventario
    $sql = "INSERT INTO inventory
    (product_id, location_id, qty, date, user, buy_price, total, movement_type, account)
    VALUES
    ('{$pid}','{$lid}','{$qty}','{$date}','{$user}','{$buy}','{$total}','{$type}','{$account}')";

    if (!$db->query($sql)) {
        $db->query("ROLLBACK");
        $session->msg('d', 'Ocurrió un error, no se pudo crear la entrada de inventario.');
        redirect('inventory.php');
    }

    // Update stock
    $sql = "INSERT INTO product_locations (product_id, location_id, qty, account)
            VALUES ('{$pid}','{$lid}','{$qty}','{$account}')
            ON DUPLICATE KEY UPDATE qty = qty + {$qty}";

    if (!$db->query($sql)) {
        $db->query("ROLLBACK");
        $session->msg('d', 'Ocurrió un error, no se pudo crear la entrada de inventario.');
        redirect('inventory.php');
    }
}

$db->query("COMMIT");

$session->msg('s', 'Entrada de inventario creada satisfactoriamente.');
redirect('inventory.php');