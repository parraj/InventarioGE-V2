<?php

require_once __DIR__ . '/includes/load.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

page_require_level(2);


/* =========================================================
   DATOS GENERALES
========================================================= */

$account_id = (int) $_SESSION['account'];

$sale_id = (int) ($_GET['id'] ?? 0);


if ($sale_id <= 0) {
    die("Venta inválida");
}


/* =========================================================
   HELPERS
========================================================= */

if (!function_exists('money')) {

    function money($v)
    {
        /*
         * Todos los valores monetarios se muestran
         * siempre con 2 decimales.
         */
        return '$' . number_format(
            round((float) $v, 2),
            2,
            ',',
            '.'
        );
    }
}


if (!function_exists('e')) {

    function e($v)
    {
        return htmlspecialchars(
            (string) $v,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('image_to_base64')) {

    function image_to_base64($path)
    {
        if (empty($path)) {
            return null;
        }

        $clean = ltrim(
            str_replace(
                ['../', '..\\'],
                '',
                $path
            ),
            '/\\'
        );

        $full = __DIR__ . '/' . $clean;

        if (!file_exists($full)) {
            return null;
        }

        $type = pathinfo(
            $full,
            PATHINFO_EXTENSION
        );

        $data = file_get_contents($full);

        return 'data:image/' .
            $type .
            ';base64,' .
            base64_encode($data);
    }
}


/*
 * Redondeo monetario centralizado.
 */
if (!function_exists('round_money')) {

    function round_money($value)
    {
        return round(
            (float) $value,
            2
        );
    }
}


/* =========================================================
   EMPRESA
========================================================= */

$account = find_by_id(
    'accounts',
    $account_id
);


$logo_src = image_to_base64(
    $account['image'] ?? ''
);


/* =========================================================
   VENTA
========================================================= */

$sql_sale = "SELECT
                s.*,
                c.name AS client_name
             FROM sales s
             LEFT JOIN clients c
                ON c.id = s.client_id
             WHERE s.id = {$sale_id}
               AND s.account = {$account_id}
             LIMIT 1";


$res_sale = $db->query(
    $sql_sale
);


$sale = $res_sale
    ? $res_sale->fetch_assoc()
    : null;


if (!$sale) {
    die("Venta no encontrada");
}


/* =========================================================
   DIRECCIÓN
========================================================= */

$addr = $sale['address'] ?? '';


/*
 * Limpiar etiquetas previas.
 */
$addr = strip_tags($addr);


/*
 * Dirección.
 */
$addr = preg_replace(
    '/Dirección:\s*/i',
    '<strong>Dirección:</strong> ',
    $addr
);


/*
 * Localidad.
 */
$addr = preg_replace(
    '/Localidad:\s*/i',
    '<br><strong>Localidad:</strong> ',
    $addr
);


/*
 * Código postal.
 */
$addr = preg_replace(
    '/Código Postal:\s*/i',
    '<br><strong>Código Postal:</strong> ',
    $addr
);


/*
 * Evitar que comience con <br>.
 */
$addr = preg_replace(
    '/^<br>/',
    '',
    $addr
);


/* =========================================================
   PRODUCTOS
   + DEPÓSITO
   + DESPACHO
   + DESCUENTO
========================================================= */

$sql_products = "SELECT
                    sd.*,
                    p.name AS product_name,
                    l.name AS location_name,
                    l.location_type AS dispatch_type
                 FROM sales_detail sd
                 INNER JOIN products p
                    ON p.id = sd.product_id
                 LEFT JOIN product_locations pl
                    ON pl.id = sd.location_product_id
                 LEFT JOIN locations l
                    ON l.id = pl.location_id
                 WHERE sd.sale_id = {$sale_id}
                 ORDER BY sd.id ASC";


$res = $db->query(
    $sql_products
);


$products = [];


$total = 0;

$total_discount = 0;

$total_items = 0;

$total_qty = 0;

$dispatch_label = '-';


while ($row = $db->fetch_assoc($res)) {

    /*
     * =====================================================
     * CANTIDAD
     * =====================================================
     */

    $qty = round_money(
        $row['qty'] ?? 0
    );


    /*
     * =====================================================
     * PRECIO ORIGINAL
     * =====================================================
     */

    $price = round_money(
        $row['price'] ?? 0
    );


    /*
     * =====================================================
     * PORCENTAJE DE DESCUENTO
     * =====================================================
     */

    $discount_percent =
        isset($row['discount_percent'])
            ? (float) $row['discount_percent']
            : 0;


    $discount_percent =
        round_money(
            $discount_percent
        );


    /*
     * Seguridad.
     */
    if ($discount_percent < 0) {
        $discount_percent = 0;
    }


    if ($discount_percent > 100) {
        $discount_percent = 100;
    }


    /*
     * =====================================================
     * SUBTOTAL ORIGINAL
     * =====================================================
     *
     * Siempre se calcula con el precio original.
     */

    $original_sub = round_money(
        $qty * $price
    );


    /*
     * =====================================================
     * PRECIO FINAL
     * =====================================================
     *
     * MUY IMPORTANTE:
     *
     * Si el porcentaje es 0:
     *
     *     discounted_price = price
     *
     * NO importa si la BD tiene discounted_price = 0.
     *
     * Esto evita que una factura sin descuento
     * quede con total incorrecto o saldo negativo.
     */

    if ($discount_percent <= 0) {

        /*
         * SIN DESCUENTO
         */
        $discounted_price =
            $price;

        $discount_percent = 0;

    } else {

        /*
         * CON DESCUENTO
         */

        if (
            isset($row['discounted_price']) &&
            $row['discounted_price'] !== null &&
            $row['discounted_price'] !== ''
        ) {

            $discounted_price =
                (float) $row['discounted_price'];

        } else {

            /*
             * Si no existe precio descontado,
             * lo calculamos.
             */
            $discounted_price =
                $price * (
                    1 -
                    (
                        $discount_percent / 100
                    )
                );
        }
    }


    /*
     * Redondear precio final.
     */
    $discounted_price =
        round_money(
            $discounted_price
        );


    /*
     * Nunca permitir precio negativo.
     */
    if ($discounted_price < 0) {
        $discounted_price = 0;
    }


    /*
     * =====================================================
     * SUBTOTAL FINAL
     * =====================================================
     */

    if ($discount_percent <= 0) {

        /*
         * Sin descuento:
         * subtotal final = subtotal original.
         */
        $sub =
            $original_sub;

    } else {

        /*
         * Con descuento.
         */
        $sub = round_money(
            $qty * $discounted_price
        );
    }


    /*
     * =====================================================
     * DESCUENTO DE LA LÍNEA
     * =====================================================
     */

    if ($discount_percent <= 0) {

        /*
         * Sin descuento:
         * descuento = 0.
         */
        $discount_amount = 0;

    } else {

        $discount_amount =
            round_money(
                $original_sub - $sub
            );
    }


    /*
     * Seguridad contra negativos.
     */
    if ($discount_amount < 0) {
        $discount_amount = 0;
    }


    /*
     * =====================================================
     * TOTALIZADORES
     * =====================================================
     */

    $total =
        round_money(
            $total + $sub
        );


    $total_discount =
        round_money(
            $total_discount +
            $discount_amount
        );


    $total_items++;


    $total_qty =
        round_money(
            $total_qty + $qty
        );


    /*
     * =====================================================
     * DESPACHO
     * =====================================================
     */

    if (
        isset($row['dispatch_type']) &&
        $row['dispatch_type'] === 'Interna'
    ) {

        $dispatch_label =
            'Interno';

    } else {

        $dispatch_label =
            'Externo';
    }


    /*
     * =====================================================
     * PRODUCTO PARA LA FACTURA
     * =====================================================
     */

    $products[] = [

        'name' =>
            $row['product_name'],

        'qty' =>
            $qty,

        'price' =>
            $price,

        'discount_percent' =>
            $discount_percent,

        'discount_amount' =>
            $discount_amount,

        'discounted_price' =>
            $discounted_price,

        'original_sub' =>
            $original_sub,

        'sub' =>
            $sub,

        'location' =>
            $row['location_name']
    ];
}


/* =========================================================
   REDONDEAR TOTALES DE PRODUCTOS
========================================================= */

$total =
    round_money(
        $total
    );


$total_discount =
    round_money(
        $total_discount
    );


$total_qty =
    round_money(
        $total_qty
    );


/* =========================================================
   PAGOS
========================================================= */

$sql_pay = "SELECT
                amount
            FROM account_movements
            WHERE related_id = {$sale_id}
              AND related_table = 'Ventas'";


$r = $db->query(
    $sql_pay
);


$total_paid = 0;


while ($p = $db->fetch_assoc($r)) {

    $payment_amount =
        round_money(
            $p['amount']
        );

    $total_paid =
        round_money(
            $total_paid +
            $payment_amount
        );
}


/*
 * Asegurar 2 decimales.
 */
$total_paid =
    round_money(
        $total_paid
    );


/* =========================================================
   SALDO
========================================================= */

$balance =
    round_money(
        $total -
        $total_paid
    );


/*
 * =========================================================
 * CORRECCIÓN DE PRECISIÓN
 * =========================================================
 *
 * Si la diferencia es prácticamente cero,
 * mostramos exactamente 0.
 *
 * Esto evita cosas como:
 *
 * -0,01
 * 0,01
 *
 * provocadas por operaciones con decimales.
 */

if (abs($balance) < 0.01) {
    $balance = 0;
}


/*
 * Si por alguna razón el saldo queda negativo
 * solamente por unos pocos centavos debido a precisión,
 * también lo normalizamos.
 */
if (
    $balance < 0 &&
    abs($balance) < 0.01
) {
    $balance = 0;
}


/* =========================================================
   LABELS
========================================================= */

$sale_type_label =
    $sale['sale_type'] ?? '-';


/* =========================================================
   MARCA DE AGUA
========================================================= */

$status =
    strtoupper(
        $sale['status']
    );


$watermark =
    $status;


/* =========================================================
   DOMPDF
========================================================= */

$options =
    new Options();


$options->set(
    'isHtml5ParserEnabled',
    true
);


$options->set(
    'defaultFont',
    'DejaVu Sans'
);


$dompdf =
    new Dompdf(
        $options
    );


/* =========================================================
   HTML
========================================================= */

ob_start();

?>

<style>

    body {
        font-family: DejaVu Sans;
        font-size: 12px;
        color: #1e293b;
    }


    /* WATERMARK */

    .watermark {
        position: fixed;
        top: 40%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 80px;
        color: rgba(200, 200, 200, 0.25);
        z-index: -1;
    }


    /* HEADER */

    .header {
        text-align: center;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }


    .logo {
        width: 80px;
        margin-bottom: 5px;
    }


    .company {
        font-size: 20px;
        font-weight: bold;
    }


    .title {
        margin-top: 5px;
        font-size: 13px;
        background: #dbeafe;
        color: #1d4ed8;
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
    }


    /* INFO */

    .info {
        width: 100%;
        margin-top: 10px;
    }


    .info td {
        vertical-align: top;
        padding: 5px;
    }


    /* TABLE */

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }


    .table th {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 6px;
    }


    .table td {
        border: 1px solid #e2e8f0;
        padding: 6px;
    }


    .text-right {
        text-align: right;
    }


    /* DISCOUNT */

    .old-price {
        text-decoration: line-through;
        color: #94a3b8;
        font-size: 10px;
    }


    .final-price {
        font-weight: bold;
    }


    .discount {
        color: #16a34a;
        font-weight: bold;
    }


    /* TOTAL */

    .total-box {
        margin-top: 15px;
        width: 40%;
        float: right;
    }


    .total-box td {
        padding: 6px;
    }


    .total-final {
        font-size: 15px;
        font-weight: bold;
    }


    /* NOTE */

    .note {
        margin-top: 20px;
        font-size: 10px;
        color: #64748b;
    }

</style>


<div class="watermark">

    <?php echo e($watermark); ?>

</div>


<div class="header">


    <?php if ($logo_src): ?>

        <img
            src="<?php echo $logo_src; ?>"
            class="logo"
        >

    <?php endif; ?>


    <div class="company">

        <?php echo e(
            $account['name']
        ); ?>

    </div>


    <div>

        <?php echo e(
            $account['address']
        ); ?>

    </div>


    <div>

        <?php echo e(
            $account['phone']
        ); ?>

    </div>


    <div class="title">

        COMPROBANTE DE VENTA

    </div>


</div>


<table class="info">

    <tr>


        <td>

            <strong>Cliente:</strong>

            <?php echo e(
                $sale['client_name']
            ); ?>

            <br>


            <strong>Tel:</strong>

            <?php echo e(
                $sale['phone']
            ); ?>

            <br>


            <?php echo $addr; ?>

            <br>

        </td>


        <td class="text-right">


            <strong>Comprobante #:</strong>

            <?php echo e(
                $sale['id']
            ); ?>

            <br>


            <strong>Fecha:</strong>

            <?php echo date(
                'd/m/Y H:i',
                strtotime($sale['date'])
            ); ?>

            <br>


            <strong>Estado:</strong>

            <?php echo e(
                $sale['status']
            ); ?>

            <br>


            <strong>Tipo de venta:</strong>

            <?php echo e(
                $sale_type_label
            ); ?>

            <br>


            <strong>Despacho:</strong>

            <?php echo e(
                $dispatch_label
            ); ?>


        </td>


    </tr>

</table>


<table class="table">


    <thead>

        <tr>

            <th>
                Producto
            </th>

            <th>
                Depósito
            </th>

            <th>
                Cant
            </th>

            <th>
                Precio
            </th>

            <th>
                Descuento
            </th>

            <th>
                Subtotal
            </th>

        </tr>

    </thead>


    <tbody>


        <?php foreach ($products as $p): ?>


            <tr>


                <td>

                    <?php echo e(
                        $p['name']
                    ); ?>

                </td>


                <td>

                    <?php echo e(
                        $p['location']
                    ); ?>

                </td>


                <td class="text-right">

                    <?php echo e(
                        $p['qty']
                    ); ?>

                </td>


                <td class="text-right">


                    <?php if (
                        $p['discount_percent'] > 0
                    ): ?>


                        <span class="old-price">

                            <?php echo money(
                                $p['price']
                            ); ?>

                        </span>


                        <br>


                        <span class="final-price">

                            <?php echo money(
                                $p['discounted_price']
                            ); ?>

                        </span>


                    <?php else: ?>


                        <?php echo money(
                            $p['price']
                        ); ?>


                    <?php endif; ?>


                </td>


                <td class="text-right">


                    <?php if (
                        $p['discount_percent'] > 0
                    ): ?>


                        <span class="discount">

                            <?php echo number_format(
                                $p['discount_percent'],
                                2,
                                ',',
                                '.'
                            ); ?>%

                        </span>


                        <br>


                        <span class="discount">

                            -<?php echo money(
                                $p['discount_amount']
                            ); ?>

                        </span>


                    <?php else: ?>


                        -


                    <?php endif; ?>


                </td>


                <td class="text-right">

                    <?php echo money(
                        $p['sub']
                    ); ?>

                </td>


            </tr>


        <?php endforeach; ?>


    </tbody>


</table>


<table class="total-box">


    <tr>

        <td>
            Productos:
        </td>

        <td class="text-right">

            <?php echo e(
                $total_items
            ); ?>

        </td>

    </tr>


    <tr>

        <td>
            Unidades:
        </td>

        <td class="text-right">

            <?php echo e(
                $total_qty
            ); ?>

        </td>

    </tr>


    <?php if (
        $total_discount > 0
    ): ?>


        <tr>

            <td>
                Descuento:
            </td>

            <td class="text-right">

                -<?php echo money(
                    $total_discount
                ); ?>

            </td>

        </tr>


    <?php endif; ?>


    <tr>

        <td>
            Total:
        </td>

        <td class="text-right">

            <?php echo money(
                $total
            ); ?>

        </td>

    </tr>


    <tr>

        <td>
            Pagado:
        </td>

        <td class="text-right">

            <?php echo money(
                $total_paid
            ); ?>

        </td>

    </tr>


    <tr>

        <td class="total-final">

            Saldo:

        </td>


        <td class="text-right total-final">

            <?php echo money(
                $balance
            ); ?>

        </td>

    </tr>


</table>


<div style="clear:both;"></div>


<div class="note">


    <strong>Nota:</strong>

    <br>


    <?php echo e(
        $sale['note']
    ); ?>


</div>


<?php


$html =
    ob_get_clean();


/* =========================================================
   GENERAR PDF
========================================================= */

$dompdf->loadHtml(
    $html
);


$dompdf->setPaper(
    'A4',
    'portrait'
);


$dompdf->render();


/* =========================================================
   DESCARGA / RAW
========================================================= */

if (isset($_GET['download'])) {

    $dompdf->stream(
        "comprobante_{$sale_id}.pdf",
        [
            "Attachment" => false
        ]
    );

    exit;
}


if (isset($_GET['raw'])) {

    echo $dompdf->output();

    return;
}

?>