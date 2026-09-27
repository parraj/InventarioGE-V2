<?php
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

// ========================
// Validar contraseña
// ========================
$user_id = authenticate(
    $_SESSION['username'],
    $_GET['pass'],
    $_SESSION['account']
);

if (!$user_id) {
    $session->msg('d', 'La contraseña ingresada es incorrecta.');
    redirect('accounts.php', false);
}

// ========================
// Validar cuenta
// ========================
$id = (int) $_GET['id'];
$account = find_by_id('accounts', $id);

if (!$account) {
    $session->msg('d', 'ID de cuenta no válido.');
    redirect('accounts.php', false);
}

// ========================
// ELIMINACIÓN EN CASCADA (MANUAL)
// ========================
$id = $db->escape($id);

// Inventario
$db->query("DELETE FROM inventory WHERE account = {$id}");

// Categorías
$db->query("DELETE FROM categories WHERE account = {$id}");

// Ventas
//$db->query("DELETE FROM sales WHERE account = {$id}");

// Productos
$db->query("DELETE FROM products WHERE account = {$id}");

// Relación usuario-cuenta
$db->query("DELETE FROM user_accounts WHERE account_id = {$id}");

// Cuenta
$db->query("DELETE FROM accounts WHERE id = {$id}");

// ========================
// Cerrar sesión
// ========================
redirect('logout.php', false);