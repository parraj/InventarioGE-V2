<?php
/**
 * ============================
 * ADD MANUAL MOVEMENT
 * ============================
 */
$page_title = 'Agregar Movimiento';
require_once('includes/load.php');

page_require_level(1);

if (isset($_POST['add_movement'])) {

    $req_fields = ['financial_account_id', 'movement_type', 'amount'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $financial_account_id = (int)$_POST['financial_account_id'];
        $movement_type        = remove_junk($db->escape($_POST['movement_type'])); // Débito / Crédito
        $amount               = (float)$_POST['amount'];
        $note                 = remove_junk(str: $db->escape($_POST['note']));
        $reference                 = remove_junk(str: $db->escape($_POST['reference']));
        $account              = (int)$_SESSION['account'];
        $date                 = make_date();

        $sql = "INSERT INTO account_movements (
                    financial_account_id,
                    related_table,
                    related_id,
                    movement_type,
                    amount,
                    note,
                    account,
                    reference,
                    date
                ) VALUES (
                    '{$financial_account_id}',
                    'Ajuste Manual',
                    NULL,
                    '{$movement_type}',
                    '{$amount}',
                    '{$note}',
                    '{$account}',
                    '{$reference}',
                    '{$date}'
                )";

        if ($db->query($sql)) {
            $session->msg('s', ' Movimiento registrado Satisfactoriamente.');
        } else {
            $session->msg('d', ' Error al registrar el movimiento.');
        }

    } else {
        $session->msg('d', $errors);
    }
}

redirect('account_movements.php', false);