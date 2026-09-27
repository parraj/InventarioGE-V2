<?php
/**
 * ============================
 * ADD FINANCIAL ACCOUNT
 * ============================
 */
$page_title = 'Agregar Cuenta Financiera';
require_once('includes/load.php');

page_require_level(1);

if (isset($_POST['add_financial'])) {

    $req_fields = ['name', 'type'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $name        = remove_junk($db->escape($_POST['name']));
        $type        = remove_junk($db->escape($_POST['type']));
        $description = remove_junk($db->escape($_POST['description']));
        $account     = (int)$_SESSION['account'];
        $date        = make_date();

        $sql = "INSERT INTO financial_accounts   (
                    name,
                    type,
                    description,
                    balance,
                    status,
                    account,
                    date
                ) VALUES (
                    '{$name}',
                    '{$type}',
                    '{$description}',
                    0.00,
                    1,
                    '{$account}',
                    '{$date}'
                )";

        if ($db->query($sql)) {
            $session->msg('s', ' Cuenta financiera creada Satisfactoriamente.');
        } else {
            $session->msg('d', ' Error al crear la cuenta financiera.');
        }

    } else {
        $session->msg('d', $errors);
    }
}

redirect('financial_accounts.php', false);
