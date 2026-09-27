<?php
$page_title = 'Cancelar Venta';
require_once('includes/load.php');
page_require_level(2);

if (!isset($_GET['id'])) {
    $session->msg('d', 'Venta no especificada.');
    redirect('sales.php', false);
}

$sale_id = (int) $_GET['id'];
$account = (int) $_SESSION['account'];

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
    $session->msg('d', 'Solo se pueden cancelar ventas en validación.');
    redirect('sales.php', false);
}

$current_user = current_user();
if (!$current_user) {
  $session->msg('d', 'Usuario no válido.');
  redirect('sales.php', false);
}
$user = $db->escape($current_user['name'] ?? '');

$db->query("START TRANSACTION");

/* borrar pagos */
$sql_payments = "
DELETE FROM account_movements
WHERE related_table = 'Ventas'
AND related_id = '{$sale_id}'
AND account = '{$account}'
";

if (!$db->query($sql_payments)) {
    $db->query("ROLLBACK");
    $session->msg('d', 'No se pudieron eliminar los pagos.');
    redirect('sales.php', false);
}

/* cambiar estado */
$sql_sale = "
UPDATE sales
SET status = 'Cancelada', user_status = '{$user}'
WHERE id = '{$sale_id}'
AND account = '{$account}'
LIMIT 1
";

if (!$db->query($sql_sale)) {
    $db->query("ROLLBACK");
    $session->msg('d', 'No se pudo cancelar la venta.');
    redirect('sales.php', false);
}

$db->query("COMMIT");

send_sale_notification_email_intern($sale_id,'cancelada');
$session->msg('s', 'Venta cancelada satisfactoriamente.');
redirect('sales.php', false);
?>