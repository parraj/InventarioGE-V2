<?php
require_once('includes/load.php');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([]);
    exit;
}

if (!isset($_GET['product_id']) || !isset($_GET['account'])) {
    echo json_encode([]);
    exit;
}

$product_id = (int)$_GET['product_id'];
$account    = (int)$_GET['account'];

if ($product_id <= 0 || $account <= 0) {
    echo json_encode([]);
    exit;
}

if (!isset($_SESSION['account'])) {
    echo json_encode([]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Validar que el producto exista y pertenezca a la cuenta enviada
|--------------------------------------------------------------------------
*/
$sql_product = "SELECT id
                FROM products
                WHERE id = '{$product_id}'
                  AND account = '{$account}'
                  AND active = 1
                LIMIT 1";

$result_product = $db->query($sql_product);

if (!$result_product || $db->num_rows($result_product) === 0) {
    echo json_encode([]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Traer ubicaciones con stock real disponible
|--------------------------------------------------------------------------
*/
$sql = "SELECT 
            pl.id,
            pl.product_id,
            pl.location_id,
            pl.qty,
            l.name AS location_name,
            l.location_type
        FROM product_locations pl
        INNER JOIN locations l ON l.id = pl.location_id
        WHERE pl.product_id = '{$product_id}'
          AND pl.account = '{$account}'
          AND pl.active = 1
          AND l.account = '{$account}'
          AND l.active = 1
          AND pl.qty > 0
        ORDER BY l.name ASC";

$result = $db->query($sql);

$data = [];

if ($result) {
    while ($row = $db->fetch_assoc($result)) {
        $location_type = ($row['location_type'] === 'Externa') ? 'Externa' : 'Interna';

        $data[] = [
            'id'            => (int)$row['id'], // product_locations.id
            'product_id'    => (int)$row['product_id'],
            'location_id'   => (int)$row['location_id'],
            'location_name' => $row['location_name'],
            'location_type' => $location_type,
            'qty'           => (float)$row['qty']
        ];
    }
}

echo json_encode($data);
exit;
?>