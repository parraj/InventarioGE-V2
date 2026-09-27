<?php
$page_title = 'Reporte de Ventas';
require_once('includes/load.php');
page_require_level(1);

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$account_id = (int)($_SESSION['account'] ?? 0);

// ==============================
// HELPERS
// ==============================
function money($value) {
    return '$' . number_format((float)$value, 2, ',', '.');
}

function number_qty($value) {
    return number_format((float)$value, 0, ',', '.');
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function report_label($report_type) {
    return $report_type === 'consolidated' ? 'Consolidado por producto' : 'Detallado por movimientos';
}

function sale_type_label($sale_type) {
    return $sale_type === 'all' ? 'Todos' : $sale_type;
}

function image_to_base64($relative_path) {
    if (empty($relative_path)) {
        return null;
    }

    $clean_path = ltrim(str_replace(['../', '..\\'], '', $relative_path), '/\\');
    $full_path  = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $clean_path);

    if (!file_exists($full_path) || !is_file($full_path)) {
        return null;
    }

    $extension = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
    $mime_map = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
    ];

    if (!isset($mime_map[$extension])) {
        return null;
    }

    $content = @file_get_contents($full_path);
    if ($content === false) {
        return null;
    }

    return 'data:' . $mime_map[$extension] . ';base64,' . base64_encode($content);
}

// ==============================
// CONFIG DOMPDF
// ==============================
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

// ==============================
// DATOS DE LA EMPRESA
// ==============================
$account_info = find_by_id('accounts', $account_id);
$company_name    = $account_info['name'] ?? 'JP-System';
$company_address = $account_info['address'] ?? 'Medellín, Antioquia';
$company_phone   = $account_info['phone'] ?? '+57 000 000 00 00';
$company_logo    = !empty($account_info['image']) ? $account_info['image'] : 'assets/images/logos/logo.png';

$logo_src = image_to_base64($company_logo);

// ==============================
// FILTROS
// ==============================
$start_date_input = $_GET['start_date'] ?? date('Y-m-01');
$end_date_input   = $_GET['end_date'] ?? date('Y-m-d');
$report_type      = $_GET['report_type'] ?? 'detailed';
$sale_type        = $_GET['sale_type'] ?? 'all';

$start_date = $start_date_input . ' 00:00:00';
$end_date   = $end_date_input . ' 23:59:59';

// ==============================
// WHERE
// ==============================
$where = [];
$where[] = "s.account = {$account_id}";
$where[] = "s.status = 'Emitida'";
$where[] = "s.date >= '{$db->escape($start_date)}'";
$where[] = "s.date <= '{$db->escape($end_date)}'";

if ($sale_type !== 'all') {
    $where[] = "s.sale_type = '{$db->escape($sale_type)}'";
}

$where_sql = implode(' AND ', $where);

// ==============================
// RESUMEN GENERAL
// ==============================
$sql_summary = "
    SELECT
        COUNT(DISTINCT s.id) AS total_sales,
        COALESCE(SUM(sd.qty), 0) AS total_units,
        COALESCE(SUM(sd.qty * sd.price), 0) AS gross_sales,
        COALESCE(SUM(sd.qty * COALESCE(sd.cost_price, 0)), 0) AS total_cost
    FROM sales s
    INNER JOIN sales_detail sd ON sd.sale_id = s.id
    INNER JOIN products p ON p.id = sd.product_id
    WHERE {$where_sql}
";

$summary_result = $db->query($sql_summary);
$summary = $db->fetch_assoc($summary_result);

$total_sales  = (int)($summary['total_sales'] ?? 0);
$total_units  = (float)($summary['total_units'] ?? 0);
$gross_sales  = (float)($summary['gross_sales'] ?? 0);
$total_cost   = (float)($summary['total_cost'] ?? 0);
$total_profit = $gross_sales - $total_cost;
$margin_pct   = $gross_sales > 0 ? (($total_profit / $gross_sales) * 100) : 0;

// ==============================
// CONSULTA SEGÚN TIPO DE REPORTE
// ==============================
if ($report_type === 'consolidated') {
    $sql = "
        SELECT
            p.id AS product_id,
            p.name AS product_name,
            s.sale_type,
            AVG(COALESCE(sd.cost_price, 0)) AS product_cost,
            AVG(sd.price) AS avg_sale_price,
            SUM(sd.qty) AS total_qty,
            SUM(sd.qty * sd.price) AS total_sale,
            SUM(sd.qty * COALESCE(sd.cost_price, 0)) AS total_cost,
            SUM((sd.price - COALESCE(sd.cost_price, 0)) * sd.qty) AS total_profit
        FROM sales s
        INNER JOIN sales_detail sd ON sd.sale_id = s.id
        INNER JOIN products p ON p.id = sd.product_id
        WHERE {$where_sql}
        GROUP BY p.id, p.name, s.sale_type
        ORDER BY total_sale DESC, p.name ASC
    ";
} else {
    $sql = "
        SELECT
            s.id AS sale_id,
            s.date,
            s.sale_type,
            sd.qty,
            sd.price AS price_sale,
            p.name AS product_name,
            COALESCE(sd.cost_price, 0) AS product_cost,
            (sd.qty * sd.price) AS line_total,
            ((sd.price - COALESCE(sd.cost_price, 0)) * sd.qty) AS line_profit
        FROM sales s
        INNER JOIN sales_detail sd ON sd.sale_id = s.id
        INNER JOIN products p ON p.id = sd.product_id
        WHERE {$where_sql}
        ORDER BY s.date ASC, s.id ASC, p.name ASC
    ";
}

$result = $db->query($sql);

$report_title = 'Reporte de Ventas';
$report_type_text = report_label($report_type);
$generated_at = date('d/m/Y H:i');

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo e($report_title); ?></title>
    <style>
        @page {
            margin: 18px 18px 18px 18px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #0f172a;
            background: #ffffff;
        }

        .page {
            border: 1px solid #e2e8f0;
        }

        .top-meta {
            width: 100%;
            border-collapse: collapse;
        }

        .top-meta td {
            padding: 10px 16px 0 16px;
            font-size: 10px;
            color: #64748b;
        }

        .top-meta .right {
            text-align: right;
        }

        .header {
            padding: 0 16px 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            background: #fbfdff;
        }

        .logo-wrap {
            margin: 4px 0 8px 0;
        }

        .logo {
            width: 74px;
            height: 74px;
            object-fit: contain;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #0f172a;
        }

        .company-meta {
            font-size: 10px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .report-badge {
            display: inline-block;
            padding: 5px 12px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: bold;
            border-radius: 14px;
            margin-bottom: 6px;
        }

        .section {
            padding: 14px 16px 0 16px;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            margin: 0 0 8px 0;
            color: #0f172a;
        }

        .params-table,
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
        }

        .box {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 10px 12px;
            vertical-align: top;
            border-radius: 8px;
        }

        .label {
            display: block;
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .value {
            display: block;
            font-size: 12px;
            color: #334155;
            font-weight: bold;
        }

        .value-big {
            display: block;
            font-size: 18px;
            color: #0f172a;
            font-weight: bold;
        }

        .success {
            color: #16a34a;
        }

        .danger {
            color: #dc2626;
        }

        .detail-page {
            page-break-before: always;
        }

        .table-section {
            padding: 14px 16px 16px 16px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .report-table thead th {
            background: #f1f5f9;
            border: 1px solid #dbe3ea;
            padding: 8px 6px;
            text-align: left;
            color: #0f172a;
        }

        .report-table tbody td {
            border: 1px solid #e5e7eb;
            padding: 7px 6px;
        }

        .report-table tfoot th,
        .report-table tfoot td {
            background: #eff6ff;
            border: 1px solid #cbd5e1;
            padding: 8px 6px;
            font-weight: bold;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer-note {
            padding: 0 16px 16px 16px;
            font-size: 9px;
            color: #64748b;
        }

        .empty-state {
            margin: 0 16px 16px 16px;
            border: 1px solid #e2e8f0;
            padding: 25px 10px;
            text-align: center;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="page">
        <table class="top-meta">
            <tr>
                <td></td>
                <td class="right">Generado: <?php echo e($generated_at); ?></td>
            </tr>
        </table>

        <div class="header">
            <?php if (!empty($logo_src)): ?>
                <div class="logo-wrap">
                    <img src="<?php echo $logo_src; ?>" class="logo" alt="Logo">
                </div>
            <?php endif; ?>

            <div class="company-name"><?php echo e($company_name); ?></div>

            <div class="company-meta">
                <div><?php echo e($company_address); ?></div>
                <div><?php echo e($company_phone); ?></div>
            </div>

            <div class="report-badge"><?php echo e($report_title); ?></div>
        </div>

        <div class="section">
            <div class="section-title">Parámetros del reporte</div>
            <table class="params-table">
                <tr>
                    <td class="box" width="25%">
                        <span class="label">Fecha inicio</span>
                        <span class="value"><?php echo date('d/m/Y', strtotime($start_date_input)); ?></span>
                    </td>
                    <td class="box" width="25%">
                        <span class="label">Fecha fin</span>
                        <span class="value"><?php echo date('d/m/Y', strtotime($end_date_input)); ?></span>
                    </td>
                    <td class="box" width="25%">
                        <span class="label">Tipo de venta</span>
                        <span class="value"><?php echo e(sale_type_label($sale_type)); ?></span>
                    </td>
                    <td class="box" width="25%">
                        <span class="label">Tipo de reporte</span>
                        <span class="value"><?php echo e($report_type_text); ?></span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Resumen ejecutivo</div>
            <table class="kpi-table">
                <tr>
                    <td class="box" width="33.33%">
                        <span class="label">Ventas registradas</span>
                        <span class="value-big"><?php echo number_qty($total_sales); ?></span>
                    </td>
                    <td class="box" width="33.33%">
                        <span class="label">Unidades vendidas</span>
                        <span class="value-big"><?php echo number_qty($total_units); ?></span>
                    </td>
                    <td class="box" width="33.33%">
                        <span class="label">Venta bruta</span>
                        <span class="value-big"><?php echo money($gross_sales); ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="box" width="33.33%">
                        <span class="label">Costo total</span>
                        <span class="value-big"><?php echo money($total_cost); ?></span>
                    </td>
                    <td class="box" width="33.33%">
                        <span class="label">Utilidad total</span>
                        <span class="value-big <?php echo $total_profit >= 0 ? 'success' : 'danger'; ?>">
                            <?php echo money($total_profit); ?>
                        </span>
                    </td>
                    <td class="box" width="33.33%">
                        <span class="label">Margen</span>
                        <span class="value-big <?php echo $margin_pct >= 0 ? 'success' : 'danger'; ?>">
                            <?php echo number_format($margin_pct, 2, ',', '.'); ?>%
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="detail-page">
        <div class="section">
            <div class="section-title">Detalle del reporte</div>
        </div>

        <div class="table-section">
            <?php if ($result && $db->num_rows($result) > 0): ?>

                <?php if ($report_type === 'consolidated'): ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Tipo de venta</th>
                                <th class="text-end">Costo unitario prom.</th>
                                <th class="text-end">Precio venta prom.</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Costo total</th>
                                <th class="text-end">Venta total</th>
                                <th class="text-end">Utilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sum_qty = 0;
                            $sum_cost = 0;
                            $sum_sale = 0;
                            $sum_profit = 0;

                            while ($row = $db->fetch_assoc($result)):
                                $sum_qty += (float)$row['total_qty'];
                                $sum_cost += (float)$row['total_cost'];
                                $sum_sale += (float)$row['total_sale'];
                                $sum_profit += (float)$row['total_profit'];
                            ?>
                                <tr>
                                    <td><?php echo e($row['product_name']); ?></td>
                                    <td><?php echo e($row['sale_type']); ?></td>
                                    <td class="text-end"><?php echo money($row['product_cost']); ?></td>
                                    <td class="text-end"><?php echo money($row['avg_sale_price']); ?></td>
                                    <td class="text-end"><?php echo number_qty($row['total_qty']); ?></td>
                                    <td class="text-end"><?php echo money($row['total_cost']); ?></td>
                                    <td class="text-end"><?php echo money($row['total_sale']); ?></td>
                                    <td class="text-end <?php echo ((float)$row['total_profit'] >= 0) ? 'success' : 'danger'; ?>">
                                        <?php echo money($row['total_profit']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Totales generales</th>
                                <th class="text-end"><?php echo number_qty($sum_qty); ?></th>
                                <th class="text-end"><?php echo money($sum_cost); ?></th>
                                <th class="text-end"><?php echo money($sum_sale); ?></th>
                                <th class="text-end"><?php echo money($sum_profit); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Venta #</th>
                                <th>Producto</th>
                                <th>Tipo de venta</th>
                                <th class="text-end">Costo unitario</th>
                                <th class="text-end">Precio venta</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">Utilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sum_qty = 0;
                            $sum_line_total = 0;
                            $sum_line_profit = 0;

                            while ($row = $db->fetch_assoc($result)):
                                $sum_qty += (float)$row['qty'];
                                $sum_line_total += (float)$row['line_total'];
                                $sum_line_profit += (float)$row['line_profit'];
                            ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['date'])); ?></td>
                                    <td><?php echo (int)$row['sale_id']; ?></td>
                                    <td><?php echo e($row['product_name']); ?></td>
                                    <td><?php echo e($row['sale_type']); ?></td>
                                    <td class="text-end"><?php echo money($row['product_cost']); ?></td>
                                    <td class="text-end"><?php echo money($row['price_sale']); ?></td>
                                    <td class="text-end"><?php echo number_qty($row['qty']); ?></td>
                                    <td class="text-end"><?php echo money($row['line_total']); ?></td>
                                    <td class="text-end <?php echo ((float)$row['line_profit'] >= 0) ? 'success' : 'danger'; ?>">
                                        <?php echo money($row['line_profit']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-end">Totales generales</th>
                                <th class="text-end"><?php echo number_qty($sum_qty); ?></th>
                                <th class="text-end"><?php echo money($sum_line_total); ?></th>
                                <th class="text-end"><?php echo money($sum_line_profit); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    No se encontraron registros para los filtros seleccionados.
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-note">
            Reporte generado automáticamente por el sistema. Los valores de utilidad se calculan con base en el costo de compra registrado en cada ítem de venta.
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream('reporte_ventas.pdf', [
    'Attachment' => false
]);
exit;