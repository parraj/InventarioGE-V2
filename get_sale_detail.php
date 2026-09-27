<?php
// get_sale_detail.php
require_once('includes/load.php');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  echo json_encode([
    'success' => false,
    'message' => 'Método no permitido'
  ]);
  exit;
}

if (!isset($_GET['id'])) {
  echo json_encode([
    'success' => false,
    'message' => 'ID inválido'
  ]);
  exit;
}

$sale_id = (int)$_GET['id'];
$account = (int)$_SESSION['account'];

if ($sale_id <= 0) {
  echo json_encode([
    'success' => false,
    'message' => 'ID inválido'
  ]);
  exit;
}

/**
 * Venta
 */
$sql_sale = "SELECT s.id, s.client_id, s.sale_type, s.total, s.address, s.note, s.phone, s.user, s.date, s.status, c.name AS client_name
             FROM sales s
             LEFT JOIN clients c ON c.id = s.client_id
             WHERE s.id = '{$sale_id}' AND s.account = '{$account}'
             LIMIT 1";

$result_sale = $db->query($sql_sale);

if (!$result_sale || $db->num_rows($result_sale) === 0) {
  echo json_encode([
    'success' => false,
    'message' => 'Venta no encontrada'
  ]);
  exit;
}

$sale = $db->fetch_assoc($result_sale);

/**
 * Productos
 */
$sql_products = "SELECT sd.id, sd.sale_id, sd.product_id, sd.location_product_id, sd.dispatch_type, sd.qty, sd.price,
                        sd.discount_percent, sd.discounted_price,
                        sd.cost_price, sd.note, sd.status,
                        p.name AS product_name, pl.location_id, l.name AS location_name
                 FROM sales_detail sd
                 INNER JOIN products p ON p.id = sd.product_id
                 LEFT JOIN product_locations pl ON pl.id = sd.location_product_id
                 LEFT JOIN locations l ON l.id = pl.location_id
                 WHERE sd.sale_id = '{$sale_id}' AND sd.account = '{$account}'
                 ORDER BY sd.id ASC";

$result_products = $db->query($sql_products);
$products = [];

if ($result_products) {
  while ($row = $db->fetch_assoc($result_products)) {
    $products[] = [
      'id' => (int)$row['id'],
      'product_name' => $row['product_name'],
      'location_name' => $row['location_name'],
      'dispatch_type' => $row['dispatch_type'],
      'qty' => (float)$row['qty'],
      'price' => (float)$row['price'],
      'discount_percent' => (float)$row['discount_percent'],
      'discounted_price' => (float)$row['discounted_price'],
      'cost_price' => (float)$row['cost_price'],
      'note' => $row['note'],
      'status' => $row['status']
    ];
  }
}

/**
 * Pagos
 */
$sql_payments = "SELECT am.id, am.financial_account_id, am.amount, am.reference, fa.name AS account_name
                 FROM account_movements am
                 LEFT JOIN financial_accounts fa ON fa.id = am.financial_account_id
                 WHERE am.related_id = '{$sale_id}' AND am.related_table = 'Ventas' AND am.account = '{$account}'
                 ORDER BY am.id ASC";

$result_payments = $db->query($sql_payments);
$payments = [];

if ($result_payments) {
  while ($row = $db->fetch_assoc($result_payments)) {
    $payments[] = [
      'id' => (int)$row['id'],
      'account_name' => $row['account_name'],
      'amount' => (float)$row['amount'],
      'reference' => $row['reference']
    ];
  }
}

echo json_encode([
  'success' => true,
  'sale' => $sale,
  'products' => $products,
  'payments' => $payments
]);

exit;
?>