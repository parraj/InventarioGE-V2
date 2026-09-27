<?php
/**
 * ============================================================================
 * DASHBOARD PRINCIPAL
 * ============================================================================
 * Dashboard ejecutivo con variedad de gráficos
 *
 * ESTÁNDAR DE COLORES DE GRÁFICOS:
 * - success  : positivo / emitido / ventas buenas / utilidad
 * - info     : principal neutro / comparativos / canal base
 * - warning  : inventario / atención / en validación
 * - danger   : cancelaciones / riesgo / stock bajo
 *
 * IMPORTANTE:
 * - No usa primary en gráficos
 * - Usa solo Bootstrap 5 / Modernize / ApexCharts
 * - Se eliminaron:
 *   - Peso comisión ML
 *   - Resumen económico ML
 *   - Ventas por usuario
 * ============================================================================
 */

$page_title = 'Dashboard';
require_once('includes/load.php');
page_require_level(1);

/* ============================================================================
 * RANGO DE FECHAS
 * ============================================================================
 */
$startDate = date("Y-m-01");
$endDate = date("Y-m-t");

if (isset($_POST['submit'])) {
    $startDate = remove_junk($db->escape($_POST['start-date']));
    $endDate = remove_junk($db->escape($_POST['end-date']));
}

$start = $startDate . " 00:00:00";
$end = $endDate . " 23:59:59";

/* ============================================================================
 * KPIs
 * ============================================================================
 */
$totalSalesAmount = total_sales_amount($start, $end);
$totalProfit = product_profit($start, $end);
$totalProductsSold = products_sold($start, $end);
$totalPurchasedItems = inventory_items_purchased($start, $end);
$totalInventoryInvestment = inventory_total_investment($start, $end);
$averageTicket = average_ticket($start, $end);
$totalClientsCount = distinct_clients_count($start, $end);
$totalStockUnits = total_stock_units();
$totalFinancialBalance = total_financial_balance();

/* ============================================================================
 * GRÁFICOS EXISTENTES
 * ============================================================================
 */
$salesByCategory = get_sales_by_category($start, $end);
$topSellingProducts = get_top_selling_products($start, $end);
$salesByType = get_delivery_vs_retail_sales($start, $end);
$topProfitProducts = get_product_profitability($start, $end);
$inventoryValued = get_valued_inventory($start, $end);
$stockComparison = get_stock_comparison_products($start, $end);
$topClients = get_top_clients($start, $end);
$financialAccounts = get_financial_accounts($start, $end);
$salesStatusSummary = get_sales_status_summary($start, $end);
$stockByLocation = get_stock_by_location();

/* ============================================================================
 * GRÁFICOS NUEVOS
 * ============================================================================
 */
$marginByChannel = get_margin_by_sale_type($start, $end);
$investmentByCategory = get_inventory_investment_by_category($start, $end);
$stockVsSales = get_stock_vs_sales_products($start, $end);
$financialByType = get_financial_balance_by_type();
$mlVsNonMlSales = get_ml_vs_non_ml_sales($start, $end);
$productsWithoutMovement = get_products_without_movement($start, $end);

$profitByCategory = get_profit_by_category($start, $end);
$inventoryRotation = get_inventory_rotation_products($start, $end);
$clientsConcentration = get_clients_concentration($start, $end);
$topMarginProducts = get_top_margin_products($start, $end);
$stockDistributionByCategory = get_stock_distribution_by_category();
$salesVsPurchases = get_sales_vs_purchases_summary($start, $end);
$inventoryAgeBuckets = get_inventory_age_buckets();

/* ============================================================================
 * DATOS DERIVADOS PARA FRONT
 * ============================================================================
 */
$statusMap = [
    'Emitida' => 0,
    'En Validación' => 0,
    'Cancelada' => 0
];

foreach ($salesStatusSummary as $row) {
    if (isset($statusMap[$row['status']])) {
        $statusMap[$row['status']] = (int) $row['total'];
    }
}

$totalStatusSales = array_sum($statusMap);
$issuedSalesCount = $statusMap['Emitida'];
$issuedPercentage = $totalStatusSales > 0 ? round(($issuedSalesCount / $totalStatusSales) * 100, 2) : 0;

/* Pareto clientes */
$clientsParetoLabels = [];
$clientsParetoSales = [];
$clientsParetoAccum = [];
$clientsParetoTotal = array_sum(array_map(fn($r) => (float) $r['total_purchases'], $clientsConcentration));
$runningClients = 0;

foreach ($clientsConcentration as $row) {
    $value = (float) $row['total_purchases'];
    $runningClients += $value;
    $clientsParetoLabels[] = $row['client'];
    $clientsParetoSales[] = round($value, 2);
    $clientsParetoAccum[] = $clientsParetoTotal > 0 ? round(($runningClients / $clientsParetoTotal) * 100, 2) : 0;
}
?>

<?php include_once('layouts/header.php'); ?>

<style>
    .dashboard-section-title {
        letter-spacing: .2px;
    }

    /* ===============================
       CARDS
    =============================== */

    .dashboard-soft-card {
        border: 1px solid rgba(0, 0, 0, .06) !important;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .04) !important;
        border-radius: 1rem !important;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        background-color: var(--bs-card-bg);
    }

    .dashboard-soft-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 36px rgba(15, 23, 42, .07) !important;
        border-color: rgba(0, 0, 0, .08) !important;
    }

    .dashboard-soft-card .card-body {
        padding: 1.35rem 1.35rem 1.15rem;
    }

    /* ===============================
       HERO SUPERIOR
    =============================== */

    .dashboard-muted-hero {
        border-radius: 1.25rem !important;
        border: 1px solid rgba(0, 0, 0, .06) !important;
        background:
            radial-gradient(circle at top right, rgba(13, 202, 240, .08), transparent 28%),
            linear-gradient(180deg, rgba(248, 249, 250, 1) 0%, rgba(248, 249, 250, .92) 100%);
    }

    /* ===============================
       KPI CARDS
    =============================== */

    .dashboard-kpi-subtle {
        border-radius: 1rem !important;
        border: 1px solid rgba(0, 0, 0, .06) !important;
    }

    /* ===============================
       TITULOS
    =============================== */

    .dashboard-chart-card .card-title {
        font-weight: 700;
        letter-spacing: .1px;
    }

    .dashboard-chart-card .card-subtitle {
        font-size: .92rem;
        line-height: 1.35;
    }

    .section-heading-wrap h4 {
        letter-spacing: .2px;
    }

    /* ===============================
       DIVISORES
    =============================== */

    .section-divider {
        padding-top: .35rem;
        border-top: 1px solid rgba(0, 0, 0, .08);
    }

    /* ===============================
       APEXCHARTS
    =============================== */

    .apexcharts-canvas .apexcharts-legend {
        padding-top: 6px !important;
    }

    .apexcharts-tooltip {
        border-radius: 12px !important;
        box-shadow: 0 14px 30px rgba(15, 23, 42, .12) !important;
        border: 1px solid rgba(0, 0, 0, .05) !important;
    }

    /* =====================================
   APEXCHARTS TOOLTIP DARK MODE FIX
===================================== */

    [data-bs-theme="dark"] .apexcharts-tooltip,
    body.dark .apexcharts-tooltip {
        background: #1e1e2d !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, .08) !important;
        box-shadow: 0 14px 30px rgba(0, 0, 0, .45) !important;
    }

    [data-bs-theme="dark"] .apexcharts-tooltip-title {
        background: #1e1e2d !important;
        color: #ffffff !important;
        border-bottom: 1px solid rgba(255, 255, 255, .08) !important;
    }


    .apexcharts-xaxistooltip,
    .apexcharts-yaxistooltip {
        border-radius: 10px !important;
    }

    /* ===============================
       DARK MODE
    =============================== */

    [data-bs-theme="dark"] .dashboard-soft-card,
    body.dark .dashboard-soft-card {
        border: 1px solid rgba(255, 255, 255, .12) !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .25) !important;
    }

    [data-bs-theme="dark"] .dashboard-soft-card:hover,
    body.dark .dashboard-soft-card:hover {
        border-color: rgba(255, 255, 255, .18) !important;
        box-shadow: 0 16px 36px rgba(0, 0, 0, .35) !important;
    }

    [data-bs-theme="dark"] .dashboard-kpi-subtle,
    body.dark .dashboard-kpi-subtle {
        border: 1px solid rgba(255, 255, 255, .12) !important;
    }

    [data-bs-theme="dark"] .section-divider,
    body.dark .section-divider {
        border-top: 1px solid rgba(255, 255, 255, .12);
    }

    [data-bs-theme="dark"] .dashboard-muted-hero,
    body.dark .dashboard-muted-hero {
        border: 1px solid rgba(255, 255, 255, .12) !important;
        background:
            radial-gradient(circle at top right, rgba(13, 202, 240, .10), transparent 28%);
    }

    @media (max-width: 767.98px) {
        .container-fluid {
            padding-left: .4rem !important;
            padding-right: .4rem !important;
        }

        .row {
            --bs-gutter-x: .5rem;
        }

        .dashboard-soft-card .card-body,
        .dashboard-chart-card .card-body {
            padding: .9rem .8rem .85rem !important;
        }

        .dashboard-muted-hero .card-body {
            padding: 1rem .9rem !important;
        }
        .container-fluid {
        padding-top: .4rem !important;
    }

    .page-wrapper .page-body-wrapper .page-body {
        padding-top: .4rem !important;
    }

    }
</style>

<div class="container-fluid">

    <div class="card border-0 bg-light-subtle shadow-none mb-4 dashboard-muted-hero">
        <div class="card-body p-4 p-xl-4">
            <div class="row align-items-center gy-3">
                <div class="col-xl-7">
                    <span class="badge bg-primary mb-3 px-3 py-2">Dashboard ejecutivo</span>
                    <h2 class="mb-2">Inventario y facturación</h2>
                    <p class="text-muted mb-0">
                        Vista consolidada del negocio con foco en ventas, rentabilidad, inventario,
                        clientes, finanzas y operaciones de Mercado Libre.
                    </p>
                </div>

                <div class="col-xl-5">
                    <form method="post" action="admin.php" class="row g-2">
                        <div class="col-5 col-md-5">
                            <label class="form-label small text-muted mb-1">Desde</label>
                            <input type="date" class="form-control" name="start-date" value="<?= $startDate; ?>"
                                required>
                        </div>
                        <div class="col-5 col-md-5">
                            <label class="form-label small text-muted mb-1">Hasta</label>
                            <input type="date" class="form-control" name="end-date" value="<?= $endDate; ?>" required>
                        </div>
                        <div class="col-2 col-md-2 d-grid">
                            <label class="form-label small text-muted mb-1">&nbsp;</label>
                            <button type="submit" name="submit" class="btn btn-primary px-2">Ver</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-1">
        <div class="col-md-4 mb-3">
            <div class="card border-0 bg-light-subtle shadow-none h-100 dashboard-kpi-subtle">
                <div class="card-body">
                    <p class="text-muted mb-2">Saldo financiero total</p>
                    <h4 class="fw-bold mb-0 text-info">$<?= number_format($totalFinancialBalance, 2, ',', '.'); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 bg-light-subtle shadow-none h-100 dashboard-kpi-subtle">
                <div class="card-body">
                    <p class="text-muted mb-2">Inversión en inventario</p>
                    <h4 class="fw-bold mb-0 text-warning">$<?= number_format($totalInventoryInvestment, 2, ',', '.'); ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 bg-light-subtle shadow-none h-100 dashboard-kpi-subtle">
                <div class="card-body">
                    <p class="text-muted mb-2">Productos vendidos</p>
                    <h4 class="fw-bold mb-0 text-success"><?= number_format($totalProductsSold, 0, ',', '.'); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card border-0 h-100 dashboard-soft-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-info-subtle text-info px-3 py-2">Ventas</span>
                        <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center"
                            style="width:46px;height:46px;">
                            <i class="ti ti-currency-dollar fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">$<?= number_format($totalSalesAmount, 2, ',', '.'); ?></h3>
                    <p class="text-muted mb-0">Total facturado</p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card border-0 h-100 dashboard-soft-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-success-subtle text-success px-3 py-2">Utilidad</span>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center"
                            style="width:46px;height:46px;">
                            <i class="ti ti-chart-line fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">$<?= number_format($totalProfit, 2, ',', '.'); ?></h3>
                    <p class="text-muted mb-0">Ganancia estimada</p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card border-0 h-100 dashboard-soft-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-info-subtle text-info px-3 py-2">Ticket</span>
                        <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center"
                            style="width:46px;height:46px;">
                            <i class="ti ti-receipt fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">$<?= number_format($averageTicket, 2, ',', '.'); ?></h3>
                    <p class="text-muted mb-0">Promedio por venta</p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card border-0 h-100 dashboard-soft-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2">Stock</span>
                        <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center"
                            style="width:46px;height:46px;">
                            <i class="ti ti-package fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1"><?= number_format($totalStockUnits, 0, ',', '.'); ?></h3>
                    <p class="text-muted mb-0">Unidades disponibles</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-1 align-items-start">
        <div class="col-xl-6 mb-4">
            <div class="card border-0 dashboard-soft-card dashboard-chart-card h-100">
                <div class="card-body">
                    <h4 class="mb-1">Margen por canal</h4>
                    <p class="card-subtitle text-muted mb-3">
                        Comparación entre facturación, utilidad y margen por tipo de venta
                    </p>
                    <div id="chartMarginByChannel"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 mb-4">
            <div class="card border-0 dashboard-soft-card dashboard-chart-card h-100">
                <div class="card-body">
                    <h5 class="mb-1">Conversión operativa</h5>
                    <p class="card-subtitle text-muted mb-3">Ventas emitidas sobre el total</p>
                    <div id="chartIssuedRate"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 mb-4">
            <div class="card border-0 dashboard-soft-card dashboard-chart-card h-100">
                <div class="card-body">
                    <h5 class="mb-1">Canales de venta</h5>
                    <p class="card-subtitle text-muted mb-3">Participación de facturación</p>
                    <div id="chartSalesType"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3 section-heading-wrap section-divider">
        <div>
            <h4 class="fw-bold mb-1 dashboard-section-title">Ventas</h4>
            <p class="text-muted mb-0">Categorías, estados, clientes, margen y concentración</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Estado de ventas</h5>
                    <p class="card-subtitle text-muted mb-3">Emitidas, en validación y canceladas</p>
                    <div id="chartSalesStatus"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Ventas por categoría</h5>
                    <p class="card-subtitle text-muted mb-3">Facturación agrupada por categoría</p>
                    <div id="chartVentasCategoria"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Utilidad por categoría</h5>
                    <p class="card-subtitle text-muted mb-3">Qué categorías dejan más ganancia</p>
                    <div id="chartProfitByCategory"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Concentración de clientes</h5>
                    <p class="card-subtitle text-muted mb-3">Pareto de ventas por cliente</p>
                    <div id="chartClientsPareto"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-7 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Top clientes</h5>
                    <p class="card-subtitle text-muted mb-3">Clientes con mayor volumen de compra</p>
                    <div id="chartTopClientes"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Top productos vendidos</h5>
                    <p class="card-subtitle text-muted mb-3">Ranking por unidades</p>
                    <div id="chartProductosMasVendidos"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-7 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Top productos rentables</h5>
                    <p class="card-subtitle text-muted mb-3">Ranking por utilidad</p>
                    <div id="chartProductosRentables"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Top margen por producto</h5>
                    <p class="card-subtitle text-muted mb-3">Porcentaje de margen por artículo</p>
                    <div id="chartTopMarginProducts"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3 section-heading-wrap section-divider">
        <div>
            <h4 class="fw-bold mb-1 dashboard-section-title">Inventario</h4>
            <p class="text-muted mb-0">Inversión, rotación, antigüedad y distribución de stock</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Inversión por categoría</h5>
                    <p class="card-subtitle text-muted mb-3">Capital invertido agrupado por categoría</p>
                    <div id="chartInvestmentByCategory"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Inventario valorizado</h5>
                    <p class="card-subtitle text-muted mb-3">Productos con mayor inversión</p>
                    <div id="chartInventarioValorizado"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Distribución de stock por categoría</h5>
                    <p class="card-subtitle text-muted mb-3">Peso del stock actual por familia</p>
                    <div id="chartStockDistributionByCategory"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Stock por ubicación</h5>
                    <p class="card-subtitle text-muted mb-3">Distribución actual por locación</p>
                    <div id="chartStockByLocation"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Relación ventas vs compras</h5>
                    <p class="card-subtitle text-muted mb-3">Unidades vendidas frente a compras por categoría</p>
                    <div id="chartSalesVsPurchases"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Stock vs ventas</h5>
                    <p class="card-subtitle text-muted mb-3">Comparación de stock actual frente a unidades vendidas</p>
                    <div id="chartStockVsSales"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Rotación de inventario</h5>
                    <p class="card-subtitle text-muted mb-3">Productos con mejor salida relativa</p>
                    <div id="chartInventoryRotation"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Antigüedad del inventario</h5>
                    <p class="card-subtitle text-muted mb-3">Qué parte del stock está envejeciendo</p>
                    <div id="chartInventoryAge"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Comparativo de stock</h5>
                    <p class="card-subtitle text-muted mb-3">Stock bajo vs stock saludable</p>
                    <div id="chartStockComparison"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Productos sin movimiento</h5>
                    <p class="card-subtitle text-muted mb-3">Stock actual sin ventas en el período</p>
                    <div id="chartProductsWithoutMovement"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3 section-heading-wrap section-divider">
        <div>
            <h4 class="fw-bold mb-1 dashboard-section-title">Finanzas y Mercado Libre</h4>
            <p class="text-muted mb-0">Distribución financiera y peso del canal marketplace</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Saldo por tipo financiero</h5>
                    <p class="card-subtitle text-muted mb-3">Caja, banco y datáfono</p>
                    <div id="chartFinancialByType"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Cuentas financieras</h5>
                    <p class="card-subtitle text-muted mb-3">Saldo actual por cuenta</p>
                    <div id="chartCuentasSaldo"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="card border-0 h-100 dashboard-soft-card dashboard-chart-card">
                <div class="card-body">
                    <h5 class="mb-1">Ventas ML vs no ML</h5>
                    <p class="card-subtitle text-muted mb-3">Participación del canal marketplace</p>
                    <div id="chartMLVsNonML"></div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

        const colorPositive = 'var(--bs-success)';
        const colorMain = 'var(--bs-info)';
        const colorWarning = 'var(--bs-warning)';
        const colorDanger = 'var(--bs-danger)';

        const chartPalette = [colorMain, colorPositive, colorWarning, colorDanger];

        const moneyFormatter = (value) => {
            return new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(value || 0));
        };


        const numberFormatter = (value) => {
            return new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(Number(value || 0));
        };

        const percentFormatter = (value) => {
            return new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(value || 0)) + '%';
        };

        const baseOptions = {
            chart: {
                fontFamily: 'inherit',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                },
                foreColor: '#6c757d',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 550
                }
            },
            dataLabels: {
                enabled: false
            },
            grid: {
                borderColor: 'rgba(0,0,0,.06)',
                strokeDashArray: 4,
                padding: {
                    top: 4,
                    right: 8,
                    bottom: 0,
                    left: 6
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                fontSize: '13px',
                itemMargin: {
                    horizontal: 10,
                    vertical: 6
                },
                markers: {
                    radius: 12,
                    width: 10,
                    height: 10
                }
            },
            noData: {
                text: 'Sin datos disponibles',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    color: '#6c757d',
                    fontSize: '14px'
                }
            },
            tooltip: {
                theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light',
                x: {
                    show: true
                },
                marker: {
                    show: true
                }
            },
            stroke: {
                lineCap: 'round'
            },
            states: {
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.9
                    }
                },
                active: {
                    filter: {
                        type: 'none'
                    }
                }
            }
        };

        new ApexCharts(document.querySelector("#chartMarginByChannel"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                height: 330,
                type: 'line',
                stacked: false
            },
            series: [
                {
                    name: 'Ventas',
                    type: 'column',
                    data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($marginByChannel, 'total_sales'))); ?>
                },
                {
                    name: 'Utilidad',
                    type: 'column',
                    data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($marginByChannel, 'total_profit'))); ?>
                },
                {
                    name: 'Margen %',
                    type: 'line',
                    data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($marginByChannel, 'margin_percent'))); ?>
                }
            ],
            xaxis: {
                categories: <?= json_encode(array_column($marginByChannel, 'type')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    rotate: 0,
                    trim: true,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: [
                {
                    seriesName: 'Ventas',
                    min: 0,
                    title: {
                        text: 'Monto'
                    },
                    labels: {
                        formatter: function (value) {
                            return '$ ' + moneyFormatter(value);
                        }
                    }
                },
                {
                    seriesName: 'Utilidad',
                    show: false
                },
                {
                    seriesName: 'Margen %',
                    opposite: true,
                    min: 0,
                    max: 100,
                    tickAmount: 5,
                    title: {
                        text: 'Margen %'
                    },
                    labels: {
                        formatter: function (value) {
                            return value.toFixed(0) + '%';
                        }
                    }
                }
            ],
            stroke: {
                width: [0, 0, 3],
                curve: 'smooth'
            },
            markers: {
                size: [0, 0, 4],
                strokeWidth: 0,
                hover: {
                    sizeOffset: 2
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    columnWidth: '52%'
                }
            },
            grid: {
                borderColor: 'rgba(0,0,0,.06)',
                strokeDashArray: 4,
                padding: {
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },
            dataLabels: {
                enabled: false
            },
            colors: [colorMain, colorPositive, colorWarning],
            tooltip: {
                shared: true,
                intersect: false,
                y: [
                    {
                        formatter: function (value) {
                            return '$ ' + moneyFormatter(value);
                        }
                    },
                    {
                        formatter: function (value) {
                            return '$ ' + moneyFormatter(value);
                        }
                    },
                    {
                        formatter: function (value) {
                            return percentFormatter(value);
                        }
                    }
                ]
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            }
        }).render();


        new ApexCharts(document.querySelector("#chartIssuedRate"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'radialBar',
                height: 355
            },
            series: [<?= json_encode($issuedPercentage); ?>],
            labels: ['Emitidas'],
            colors: [colorPositive],
            stroke: {
                lineCap: 'round'
            },
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '62%'
                    },
                    track: {
                        background: 'rgba(0,0,0,.06)',
                        strokeWidth: '100%'
                    },
                    dataLabels: {
                        name: {
                            show: true,
                            fontSize: '14px',
                            offsetY: -8
                        },
                        value: {
                            show: true,
                            fontSize: '28px',
                            fontWeight: 700,
                            offsetY: 6,
                            formatter: function (val) { return val + '%'; }
                        }
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartSalesType"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'donut',
                height: 345
            },
            series: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($salesByType, 'total'))); ?>,
            labels: <?= json_encode(array_column($salesByType, 'type')); ?>,
            colors: chartPalette,
            stroke: { width: 0 },
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%'
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartSalesStatus"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'donut',
                height: 340
            },
            series: <?= json_encode(array_values($statusMap)); ?>,
            labels: <?= json_encode(array_keys($statusMap)); ?>,
            colors: [colorPositive, colorWarning, colorDanger],
            stroke: { width: 0 },
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%'
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return numberFormatter(value) + ' ventas';
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartVentasCategoria"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 340
            },
            series: [{
                name: 'Ventas',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($salesByCategory, 'total'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($salesByCategory, 'category')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    trim: true,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            },
            plotOptions: {
                bar: {
                    distributed: true,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    columnWidth: '46%'
                }
            },
            colors: chartPalette,
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartProfitByCategory"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'line',
                height: 340
            },
            series: [{
                name: 'Utilidad',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($profitByCategory, 'profit'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($profitByCategory, 'category')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    trim: true
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            markers: {
                size: 4,
                hover: {
                    sizeOffset: 2
                }
            },
            colors: [colorPositive],
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartClientsPareto"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'line',
                height: 340
            },
            series: [
                {
                    name: 'Ventas',
                    type: 'column',
                    data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($clientsConcentration, 'total_purchases'))); ?>
                },
                {
                    name: 'Acumulado %',
                    type: 'line',
                    data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($clientsConcentration, 'accum_percent'))); ?>
                }
            ],
            xaxis: {
                categories: <?= json_encode(array_column($clientsConcentration, 'client')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    show: true,
                    trim: true
                },
            },
            yaxis: [
                {
                    seriesName: 'Ventas',
                    min: 0,
                    title: {
                        text: 'Ventas'
                    },
                    labels: {
                        formatter: function (value) {
                            return '$ ' + moneyFormatter(value);
                        }
                    }
                },
                {
                    seriesName: 'Acumulado %',
                    opposite: true,
                    min: 0,
                    max: 100,
                    tickAmount: 5,
                    title: {
                        text: 'Acumulado %'
                    },
                    labels: {
                        formatter: function (value) {
                            return value.toFixed(0) + '%';
                        }
                    }
                }
            ],
            stroke: {
                width: [0, 3],
                curve: 'smooth'
            },
            markers: {
                size: [0, 4]
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    columnWidth: '46%'
                }
            },
            colors: [colorMain, colorWarning],
            tooltip: {
                shared: false,
                intersect: true,
                custom: function ({ dataPointIndex }) {
                    const labels = <?= json_encode(array_column($clientsConcentration, 'client')); ?>;
                    const sales = <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($clientsConcentration, 'total_purchases'))); ?>;
                    const percent = <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($clientsConcentration, 'percent'))); ?>;
                    const accum = <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($clientsConcentration, 'accum_percent'))); ?>;

                    return `
                <div class="apexcharts-tooltip-box p-2">
                    <div class="fw-semibold mb-1">${labels[dataPointIndex] ?? ''}</div>
                    <div>Ventas: <strong>$ ${moneyFormatter(sales[dataPointIndex] ?? 0)}</strong></div>
                    <div>% cliente: <strong>${percentFormatter(percent[dataPointIndex] ?? 0)}</strong></div>
                    <div>% acumulado: <strong>${percentFormatter(accum[dataPointIndex] ?? 0)}</strong></div>
                </div>
            `;
                }
            }
        }).render();


        new ApexCharts(document.querySelector("#chartProductosMasVendidos"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 360
            },
            series: [{
                name: 'Cantidad',
                data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($topSellingProducts, 'quantity'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($topSellingProducts, 'product')); ?>,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    barHeight: '56%'
                }
            },
            colors: [colorMain],
            tooltip: {
                y: {
                    formatter: function (value) {
                        return numberFormatter(value) + ' unidades';
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartProductosRentables"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 360
            },
            series: [{
                name: 'Utilidad',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($topProfitProducts, 'profit'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($topProfitProducts, 'product')); ?>,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    barHeight: '56%'
                }
            },
            colors: [colorPositive],
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartTopMarginProducts"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'radar',
                height: 360
            },
            series: [{
                name: 'Margen %',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($topMarginProducts, 'margin_percent'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($topMarginProducts, 'product')); ?>
            },
            stroke: {
                width: 2.5
            },
            fill: {
                opacity: 0.14
            },
            markers: {
                size: 3
            },
            colors: [colorWarning],
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return value.toFixed(0) + '%';
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return percentFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartTopClientes"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 360
            },
            series: [{
                name: 'Compras',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($topClients, 'total_purchases'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($topClients, 'client')); ?>,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    barHeight: '56%'
                }
            },
            colors: [colorMain],
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartInvestmentByCategory"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 350
            },
            series: [{
                name: 'Inversión',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($investmentByCategory, 'total_investment'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($investmentByCategory, 'category')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    trim: true
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            },
            plotOptions: {
                bar: {
                    distributed: true,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    columnWidth: '46%'
                }
            },
            colors: chartPalette,
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartInventarioValorizado"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 360
            },
            series: [{
                name: 'Inversión',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($inventoryValued, 'total_value'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($inventoryValued, 'product')); ?>,
                labels: {
                    show: true,
                    trim: true
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    columnWidth: '43%'
                }
            },
            colors: [colorWarning],
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartStockDistributionByCategory"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'donut',
                height: 360
            },
            series: <?= json_encode(array_map(fn($v) => (int) $v, array_column($stockDistributionByCategory, 'total_stock'))); ?>,
            labels: <?= json_encode(array_column($stockDistributionByCategory, 'category')); ?>,
            colors: chartPalette,
            stroke: { width: 0 },
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%'
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return numberFormatter(value) + ' unidades';
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartSalesVsPurchases"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'line',
                height: 360
            },
            series: [
                {
                    name: 'Vendidos',
                    type: 'line',
                    data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($salesVsPurchases, 'sold_qty'))); ?>
                },
                {
                    name: 'Comprados',
                    type: 'area',
                    data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($salesVsPurchases, 'purchased_qty'))); ?>
                }
            ],
            xaxis: {
                categories: <?= json_encode(array_column($salesVsPurchases, 'category')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    trim: true
                }
            },
            stroke: {
                curve: 'smooth',
                width: [3, 2]
            },
            fill: {
                type: ['solid', 'gradient'],
                gradient: {
                    shadeIntensity: 0.2,
                    opacityFrom: 0.25,
                    opacityTo: 0.05,
                    stops: [0, 100]
                }
            },
            markers: {
                size: [4, 0]
            },
            colors: [colorMain, colorWarning],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (value) {
                        return numberFormatter(value) + ' unidades';
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartStockByLocation"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 360
            },
            series: [{
                name: 'Stock',
                data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($stockByLocation, 'total_qty'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($stockByLocation, 'location')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    trim: true
                }
            },
            plotOptions: {
                bar: {
                    distributed: true,
                    borderRadius: 10,
                    borderRadiusApplication: 'end',
                    columnWidth: '40%'
                }
            },
            colors: chartPalette,
            tooltip: {
                y: {
                    formatter: function (value) {
                        return numberFormatter(value) + ' unidades';
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartStockVsSales"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'line',
                height: 360
            },
            series: [
                {
                    name: 'Vendidos',
                    type: 'line',
                    data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($stockVsSales, 'sold_qty'))); ?>
                },
                {
                    name: 'Stock actual',
                    type: 'area',
                    data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($stockVsSales, 'current_stock'))); ?>
                }
            ],
            xaxis: {
                categories: <?= json_encode(array_column($stockVsSales, 'product')); ?>,
                labels: {
                    show: true,
                    trim: true
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            stroke: {
                curve: 'smooth',
                width: [3, 2]
            },
            fill: {
                type: ['solid', 'gradient'],
                gradient: {
                    shadeIntensity: 0.2,
                    opacityFrom: 0.25,
                    opacityTo: 0.05,
                    stops: [0, 100]
                }
            },
            markers: {
                size: [4, 0]
            },
            colors: [colorMain, colorWarning],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (value) {
                        return numberFormatter(value) + ' unidades';
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartInventoryRotation"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'line',
                height: 360
            },
            series: [{
                name: 'Rotación',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($inventoryRotation, 'rotation_index'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($inventoryRotation, 'product')); ?>,
                labels: {
                    show: true,
                    trim: true
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            markers: {
                size: 4
            },
            colors: [colorPositive],
            tooltip: {
                y: {
                    formatter: function (value) {
                        return value.toFixed(2);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartInventoryAge"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'area',
                height: 360
            },
            series: [{
                name: 'Stock',
                data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($inventoryAgeBuckets, 'total_stock'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($inventoryAgeBuckets, 'bucket')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.25,
                    opacityFrom: 0.30,
                    opacityTo: 0.05,
                    stops: [0, 100]
                }
            },
            colors: [colorDanger],
            tooltip: {
                y: {
                    formatter: function (value) {
                        return numberFormatter(value) + ' unidades';
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartStockComparison"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                stacked: true,
                height: 380
            },
            series: [
                {
                    name: 'Stock bajo',
                    data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($stockComparison, 'low_stock'))); ?>
                },
                {
                    name: 'Stock saludable',
                    data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($stockComparison, 'high_stock'))); ?>
                }
            ],
            xaxis: {
                categories: <?= json_encode(array_column($stockComparison, 'product')); ?>,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    barHeight: '54%'
                }
            },
            colors: [colorDanger, colorPositive],
            tooltip: {
                y: {
                    formatter: function (value) {
                        return numberFormatter(value) + ' unidades';
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartProductsWithoutMovement"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 380
            },
            series: [{
                name: 'Stock sin movimiento',
                data: <?= json_encode(array_map(fn($v) => (int) $v, array_column($productsWithoutMovement, 'current_stock'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($productsWithoutMovement, 'product')); ?>,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    barHeight: '54%'
                }
            },
            colors: [colorDanger],
            tooltip: {
                y: {
                    formatter: function (value) {
                        return numberFormatter(value) + ' unidades';
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartFinancialByType"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 340
            },
            series: [{
                name: 'Saldo',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($financialByType, 'total_balance'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($financialByType, 'type')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            },
            plotOptions: {
                bar: {
                    distributed: true,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    columnWidth: '48%'
                }
            },
            colors: [colorMain, colorPositive, colorWarning, colorDanger],
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartCuentasSaldo"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'bar',
                height: 360
            },
            series: [{
                name: 'Saldo',
                data: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($financialAccounts, 'balance'))); ?>
            }],
            xaxis: {
                categories: <?= json_encode(array_column($financialAccounts, 'account')); ?>,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    trim: true
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            },
            plotOptions: {
                bar: {
                    distributed: true,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    columnWidth: '40%'
                }
            },
            colors: chartPalette,
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chartMLVsNonML"), {
            ...baseOptions,
            chart: {
                ...baseOptions.chart,
                type: 'donut',
                height: 320
            },
            series: <?= json_encode(array_map(fn($v) => round((float) $v, 2), array_column($mlVsNonMlSales, 'total_sales'))); ?>,
            labels: <?= json_encode(array_column($mlVsNonMlSales, 'channel')); ?>,
            colors: [colorMain, colorWarning],
            stroke: { width: 0 },
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: { size: '70%' }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '$ ' + moneyFormatter(value);
                    }
                }
            }
        }).render();

    });
</script>

<?php include_once('layouts/footer.php'); ?>