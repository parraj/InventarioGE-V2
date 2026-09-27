<?php
require_once('includes/load.php');

global $db;
//destruyo los datos guardados
unset($_SESSION['account']);
unset($_SESSION['image']);
unset($_SESSION['name']);
unset($_SESSION['account_name']);
unset($_SESSION['account_image']);
//Consulto por el id de la cuenta para tener los nuevos datos
$account = find_by_id('accounts',$_GET['id']);

//Asigno los nuevos valores de la cuenta
$_SESSION['account'] = $account['id'];
$_SESSION['image'] = $account['image'];
$_SESSION['name'] = $account['name'];
$_SESSION['account_name'] = $account['name'];
$_SESSION['account_image'] = $account['image'];
$session->msg("s", "Cambiaste correctamente a la cuenta ".$account['name']);
redirect('home.php');?> 