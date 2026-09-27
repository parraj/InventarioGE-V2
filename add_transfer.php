<?php
/*
|--------------------------------------------------------------------------
| add_transfer.php (MULTI TRANSFER)
|--------------------------------------------------------------------------
| Maneja la inserción de múltiples transferencias de inventario.
| Recibe un array JSON desde la modal:
|   $_POST['items']
|
| Cada item contiene:
| - product_id
| - from_location_id
| - to_location_id
| - account_id
| - qty
|
| Se ejecuta todo dentro de una transacción.
|--------------------------------------------------------------------------
*/

require_once('includes/load.php');
page_require_level(2);

/*
|--------------------------------------------------------------------------
| Usuario actual
|--------------------------------------------------------------------------
*/
$current_user = current_user();

/*
|--------------------------------------------------------------------------
| VALIDACIÓN PRINCIPAL
|--------------------------------------------------------------------------
*/
if(isset($_POST['add_transfer'])){

    $items = json_decode($_POST['items'], true);

    if(!$items || count($items) === 0){
        $session->msg('d',"No hay traslados para procesar");
        redirect('inventory_transfer.php', false);
    }

    /*
    |--------------------------------------------------------------------------
    | INICIO DE TRANSACCIÓN
    |--------------------------------------------------------------------------
    */
    $db->query("START TRANSACTION");

    try {

        /*
        |--------------------------------------------------------------------------
        | PROCESAR CADA TRANSFERENCIA
        |--------------------------------------------------------------------------
        */
        foreach($items as $row){

            /*
            |--------------------------------------------------------------------------
            | SANITIZACIÓN DE DATOS
            |--------------------------------------------------------------------------
            */
            $p_id          = (int)$db->escape($row['product_id']);
            $from_location = (int)$db->escape($row['from_location_id']);
            $to_location   = (int)$db->escape($row['to_location_id']);
            $p_acc         = (int)$db->escape($row['account_id']);
            $p_qty         = (int)$db->escape($row['qty']);

            $date = make_date();
            $user = $current_user['name'];
            $current_account = (int)$_SESSION['account'];

            /*
            |--------------------------------------------------------------------------
            | VALIDACIÓN 1: CANTIDAD
            |--------------------------------------------------------------------------
            */
            if($p_qty <= 0){
                throw new Exception("Cantidad inválida en uno de los items");
            }

            /*
            |--------------------------------------------------------------------------
            | TIPO DE TRANSFERENCIA
            |--------------------------------------------------------------------------
            */
            if($p_acc == $current_account){
                $transfer_type = 'Traslados Entre Ubicaciones';
                $movement_type = 'Traslados entre Ubicaciones';
            } else {
                $transfer_type = 'Traslados Entre Cuentas';
                $movement_type = 'Traslados entre Cuentas';
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDACIÓN 2: PRODUCTO ORIGEN
            |--------------------------------------------------------------------------
            */
            $product_origen = find_by_id_account('products',$p_id,$current_account);

            if(!$product_origen){
                throw new Exception("Producto no encontrado en cuenta origen");
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDACIÓN 3: STOCK ORIGEN
            |--------------------------------------------------------------------------
            */
            $query_stock_origen = "
                SELECT *
                FROM product_locations
                WHERE product_id = '{$p_id}'
                AND location_id = '{$from_location}'
                AND account = '{$current_account}'
                AND active = 1
                LIMIT 1
            ";

            $result_stock_origen = $db->query($query_stock_origen);
            $stock_origen = $db->fetch_assoc($result_stock_origen);

            if(!$stock_origen){
                throw new Exception("No existe stock en ubicación origen");
            }

            if($stock_origen['qty'] < $p_qty){
                throw new Exception("Stock insuficiente en origen");
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDACIÓN 4: STOCK DESTINO
            |--------------------------------------------------------------------------
            */
            $query_stock_destino = "
                SELECT *
                FROM product_locations
                WHERE product_id = '{$p_id}'
                AND location_id = '{$to_location}'
                AND account = '{$p_acc}'
                AND active = 1
                LIMIT 1
            ";

            $result_stock_destino = $db->query($query_stock_destino);
            $stock_destino = $db->fetch_assoc($result_stock_destino);

            /*
            |--------------------------------------------------------------------------
            | VALIDACIÓN 5: PRECIO COMPRA
            |--------------------------------------------------------------------------
            */
            $query_price = "
                SELECT buy_price
                FROM inventory
                WHERE product_id = '{$p_id}'
                AND account = '{$current_account}'
                and buy_price <> 0
                ORDER BY date DESC
                LIMIT 1
            ";

            $result_price = $db->query($query_price);
            $price = $db->fetch_assoc($result_price);

            if(!$price){
                throw new Exception("No se encontró precio de compra");
            }

            $buy_price = $price['buy_price'];

            $cant_neg = $p_qty * -1;

            /*
            |--------------------------------------------------------------------------
            | MOVIMIENTO 1: DESCONTAR ORIGEN
            |--------------------------------------------------------------------------
            */
            $db->query("
                UPDATE product_locations
                SET qty = qty - {$p_qty}
                WHERE id = '{$stock_origen['id']}'
            ");

            /*
            |--------------------------------------------------------------------------
            | MOVIMIENTO 2: INVENTORY ORIGEN
            |--------------------------------------------------------------------------
            */
            $db->query("
                INSERT INTO inventory
                (product_id,location_id,qty,buy_price,total,user,date,account,movement_type)
                VALUES
                ('$p_id','$from_location','$cant_neg','$buy_price',".($cant_neg*$buy_price).",'$user','$date','$current_account','$movement_type')
            ");

            /*
            |--------------------------------------------------------------------------
            | MOVIMIENTO 3: AUMENTAR O CREAR DESTINO
            |--------------------------------------------------------------------------
            */
            if($stock_destino){

                $db->query("
                    UPDATE product_locations
                    SET qty = qty + {$p_qty}
                    WHERE id = '{$stock_destino['id']}'
                ");

            } else {

                $db->query("
                    INSERT INTO product_locations
                    (product_id,location_id,qty,active,account)
                    VALUES
                    ('$p_id','$to_location','$p_qty',1,'$p_acc')
                ");
            }

            /*
            |--------------------------------------------------------------------------
            | MOVIMIENTO 4: INVENTORY DESTINO
            |--------------------------------------------------------------------------
            */
            $db->query("
                INSERT INTO inventory
                (product_id,location_id,qty,buy_price,total,user,date,account,movement_type)
                VALUES
                ('$p_id','$to_location','$p_qty','$buy_price',".($p_qty*$buy_price).",'$user','$date','$p_acc','$movement_type')
            ");

            /*
            |--------------------------------------------------------------------------
            | MOVIMIENTO 5: REGISTRO TRANSFERENCIA
            |--------------------------------------------------------------------------
            */
            $db->query("
                INSERT INTO inventory_transfer
                (user,from_location_id,to_location_id,transfer_type,product_id_origin,product_id_destination,receiving_account_id,qty,account,date)
                VALUES
                ('$user','$from_location','$to_location','$transfer_type','$p_id','$p_id','$p_acc','$p_qty','$current_account','$date')
            ");
        }

        /*
        |--------------------------------------------------------------------------
        | CONFIRMAR TRANSACCIÓN
        |--------------------------------------------------------------------------
        */
        $db->query("COMMIT");

        $session->msg('s',"Traslados realizados satisfactoriamente");
        redirect('inventory_transfer.php', false);

    } catch(Exception $e) {

        /*
        |--------------------------------------------------------------------------
        | ROLLBACK EN CASO DE ERROR
        |--------------------------------------------------------------------------
        */
        $db->query("ROLLBACK");

        $session->msg('d',$e->getMessage());
        redirect('inventory_transfer.php', false);
    }
}
?>