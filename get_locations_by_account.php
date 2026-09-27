<?php
require_once('includes/load.php');

$account_id = (int)$_GET['account_id'];

$sql = "SELECT id, name
        FROM locations
        WHERE account = '{$account_id}'
        ORDER BY name ASC";

$locations = find_by_sql($sql);

header('Content-Type: application/json');
echo json_encode($locations);