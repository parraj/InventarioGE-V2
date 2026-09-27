<?php
require_once('includes/load.php');

/**
 * Elimina una cuenta asociada a un usuario
 * - Verifica que el usuario tenga nivel 1
 * - Valida que el ID exista
 * - Intenta eliminar la cuenta directamente sin función externa
 */
page_require_level(1);

// Obtener ID de la cuenta desde GET
$account_id = (int)$_GET['id'];

// Verificar que la cuenta exista
$acc = find_by_id('user_accounts', $account_id);
if (!$acc) {
    $session->msg("d", "ID vacío o no encontrado");
    redirect('users.php');
}

// Eliminar la cuenta asociada directamente
global $db;
$sql = "DELETE FROM user_accounts WHERE id=" . $db->escape($account_id);
$db->query($sql);

// Mostrar mensaje según resultado
if ($db->affected_rows() === 1) {
    $session->msg("s", "Cuenta eliminada del usuario");
} else {
    $session->msg("d", "No se pudo eliminar la cuenta");
}

redirect('users.php');