<?php
require_once('includes/load.php');

header('Content-Type: application/json');

$current_account = (int)$_SESSION['account'];

if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    echo json_encode([]);
    exit;
}

$product_id = (int)$db->escape($_GET['product_id']);

$sql = "SELECT 
            l.id,
            l.name,
            pl.qty
        FROM product_locations AS pl
        INNER JOIN locations AS l 
            ON l.id = pl.location_id
        WHERE pl.product_id = '{$product_id}'
          AND pl.account = '{$current_account}'
          AND pl.active = 1
          AND l.active = 1
          AND pl.qty > 0
        ORDER BY l.name ASC";

$result = $db->query($sql);

$data = array();

while($row = $db->fetch_assoc($result)){
    $data[] = $row;
}

echo json_encode($data);
exit;
?>