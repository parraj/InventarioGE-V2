<?php
$user = current_user();
if (isset($_SESSION['user_id'])) {
    $user_accounts2 = find_user_accounts((int) $_SESSION['user_id']);
    $validate_auth = find_user_accounts_for_add((int) $_SESSION['user_id'], (int) $_SESSION['account']);
    $all_accounts = find_all('accounts');
    if (!$validate_auth) {
        redirect('logout.php', false);
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title><?php
    if (!empty($page_title))
        echo remove_junk($page_title);
    elseif (!empty($user))
        echo ucfirst($user['name']);
    else
        echo "Base";
    ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#ffffff">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="<?php echo $_SESSION['account_image'] ?? 'assets/images/logos/favicon.png' ?>">

    <!-- Google Fonts: todas las fuentes -->
    <link href="https://fonts.googleapis.com/css2?
family=Plus+Jakarta+Sans:wght@400;600;700&
family=Roboto:wght@400;500;700&
family=Montserrat:wght@400;500;700&
family=Lato:wght@400;700&
family=Merriweather:wght@400;700&
family=Oswald:wght@400;500;700&
family=Dancing+Script:wght@400;700&
family=Pacifico&display=swap" rel="stylesheet">

    <!-- Estilos principales -->
    <link rel="stylesheet" href="assets/css/styles.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

    <style>
        .logo {
            max-height: 60px;
            height: auto;
        }

        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .body-wrapper {
            margin-top: 70px;
            margin-left: 250px;
        }

        .notification-btn {
            position: relative;
            display: flex;
            align-items: center;
            margin-right: 20px;
        }

        .notification-btn .notification {
            width: 10px;
            height: 10px;
            background-color: red;
            border-radius: 50%;
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(50%, -50%);
        }

        #loader {
            position: fixed;
            inset: 0;
            background: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999;
        }

        .logo-spin {
            width: 80px;
            height: 80px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        #datatable th,
        #datatable td {
            text-align: center;
            vertical-align: middle;
        }

        .app-header .navbar {
            position: relative;
            min-height: 70px;
        }

        .navbar-right-tools {
            display: flex;
            align-items: center;
            margin-left: auto;
        }

        .account-switcher-wrap {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            z-index: 5;
        }

        .account-switcher-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 9px 18px;
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            color: var(--bs-body-color);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .account-switcher-btn:hover {
            background: var(--bs-tertiary-bg);
            border-color: var(--bs-secondary-color);
        }

        [data-bs-theme="dark"] .account-switcher-btn,
        body.dark .account-switcher-btn {
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
        }


        .account-switcher-btn .account-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(var(--bs-primary-rgb), 0.12);
            color: var(--bs-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .account-switcher-btn .account-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.05;
        }

        .account-switcher-btn .account-text small {
            font-size: 10px;
            color: #8a94a6;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }

        .account-switcher-btn .account-text span {
            font-size: 13px;
            font-weight: 700;
            color: #344054;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .account-switcher-btn .account-chevron {
            color: #98a2b3;
            font-size: 13px;
        }

        .account-switcher-menu {
            min-width: 290px;
            padding: 10px;
            border: 1px solid #1a80e7;
            border-radius: 16px;
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.10);
        }

        .account-switcher-menu .dropdown-header {
            font-size: 11px;
            font-weight: 700;
            color: #98a2b3;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
        }

        .account-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .account-option:hover {
            background: #f8f9fa;
        }

        .account-option .account-option-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(var(--bs-primary-rgb), 0.12);
            color: var(--bs-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .account-option .account-option-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
            min-width: 0;
        }

        .account-option .account-option-text strong {
            font-size: 13px;
            color: #344054;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .account-option .account-option-text span {
            font-size: 11px;
            color: #98a2b3;
        }

        .account-option.active-account {
            background: rgba(var(--bs-primary-rgb), 0.08);
        }

        .config-toggle-edge {
            position: fixed;
            top: 90px;
            right: 0;
            z-index: 1040;
            width: 42px;
            height: 54px;
            border: 0;
            border-radius: 12px 0 0 12px;
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
            box-shadow: -4px 6px 18px rgba(0, 0, 0, 0.10);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .config-toggle-edge:hover {
            background: var(--bs-tertiary-bg);
            color: var(--bs-primary);
            width: 46px;
        }

        .config-toggle-edge i {
            font-size: 18px;
        }

        /* Ajuste de sombra en dark mode */
        [data-bs-theme="dark"] .config-toggle-edge,
        body.dark .config-toggle-edge {
            box-shadow: -4px 6px 18px rgba(0, 0, 0, 0.45);
        }


        @media (max-width: 991.98px) {
            .account-switcher-wrap {
                position: static;
                transform: none;
                margin-left: auto;
                margin-right: auto;
                margin-top: 8px;
            }

            .app-header .navbar {
                flex-wrap: wrap;
                gap: 10px;
            }

            .navbar-right-tools {
                margin-left: 0;
            }

            .account-switcher-btn .account-text span {
                max-width: 160px;
            }
        }

        @media (max-width: 575.98px) {
            .account-switcher-btn {
                padding: 8px 12px;
            }

            .account-switcher-btn .account-text small {
                display: none;
            }

            .account-switcher-btn .account-text span {
                max-width: 120px;
            }

            .config-toggle-edge {
                top: 82px;
                width: 38px;
                height: 48px;
            }
        }
    </style>
</head>

<body>
    <div id="loader">
        <img src="<?php echo $_SESSION['account_image'] ?? 'assets/images/logos/favicon.png' ?>" alt="Logo"
            class="logo-spin">
    </div>

    <?php if ($session->isUserLoggedIn(true)): ?>
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">

            <!-- Sidebar -->
            <?php if ($user['user_level'] === '1')
                include_once('admin_menu.php'); ?>
            <?php if ($user['user_level'] === '2')
                include_once('special_menu.php'); ?>

            <!-- Botón oculto/lateral para configuración -->
            <button class="config-toggle-edge" type="button" data-bs-toggle="offcanvas" data-bs-target="#themeSettings"
                aria-controls="themeSettings" title="Configuración">
                <i class="bi bi-chevron-left"></i>
            </button>

            <!-- Contenido principal -->
            <div class="body-wrapper">
                <header class="app-header">
                    <nav class="navbar navbar-expand-lg navbar-light px-3 justify-content-between">
                        <ul class="navbar-nav">
                            <li class="nav-item d-block d-xl-none">
                                <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                                    <i class="ti ti-menu-2"></i>
                                </a>
                            </li>
                        </ul>

                        <!-- Selector de cuenta centrado -->
                        <div class="dropdown account-switcher-wrap">
                            <a href="javascript:void(0)" class="account-switcher-btn text-decoration-none"
                                id="accountSwitcherDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="account-icon">
                                    <?php if (!empty($_SESSION['account_image']) && file_exists($_SESSION['account_image'])): ?>
                                        <img src="<?php echo $_SESSION['account_image']; ?>"
                                            style="width:30px;height:30px;object-fit:contain;border-radius:50%;">
                                    <?php else: ?>
                                        <i class="bi bi-building"></i>
                                    <?php endif; ?>
                                </span>

                                <span class="account-text">
                                    <small>Cuenta activa</small>
                                    <span><?php echo !empty($_SESSION['account_name']) ? remove_junk($_SESSION['account_name']) : 'Seleccionar cuenta'; ?></span>
                                </span>

                                <i class="bi bi-chevron-down account-chevron"></i>
                            </a>

                            <div class="dropdown-menu dropdown-menu-center dropdown-menu-animate-up account-switcher-menu"
                                aria-labelledby="accountSwitcherDropdown">
                                <div class="dropdown-header">Cambiar de cuenta</div>

                                <?php if (!empty($user_accounts2)): ?>
                                    <?php foreach ($user_accounts2 as $account2): ?>
                                        <a href="change_account.php?id=<?php echo (int) $account2['id_account']; ?>"
                                            class="account-option <?php echo ((int) $account2['id_account'] === (int) $_SESSION['account']) ? 'active-account' : ''; ?>">
                                            <span class="account-option-icon">
                                                <i class="bi bi-shop"></i>
                                            </span>
                                            <span class="account-option-text">
                                                <strong><?php echo remove_junk(ucfirst($account2['name'])); ?></strong>
                                                <span>
                                                    <?php echo ((int) $account2['id_account'] === (int) $_SESSION['account']) ? 'Cuenta actual' : 'Cambiar a esta cuenta'; ?>
                                                </span>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="navbar-right-tools">
                            <ul class="navbar-nav flex-row ms-3 align-items-center justify-content-end">
                                <li class="nav-item dropdown">
                                    <a class="nav-link" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown">
                                        <img src="assets/images/profile/user-1.jpg" alt="Usuario" width="35" height="35"
                                            class="rounded-circle">
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up"
                                        aria-labelledby="drop2">
                                        <div class="message-body">

                                            <a href="edit_account.php"
                                                class="d-flex align-items-center gap-2 dropdown-item">
                                                <i class="ti ti-mail fs-6"></i>
                                                <p class="mb-0 fs-3">Mi Cuenta</p>
                                            </a>

                                            <a href="logout.php" class="btn btn-outline-primary mx-3 mt-2 d-block">
                                                Salir
                                            </a>

                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </header>

                <div class="container-fluid">
                    <div class="card shadow-sm">
                        <div class="card-body">

                            <!-- Modal de iniciar venta -->
                            <div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleLabel"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="add_sale.php">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="addSaleLabel">Iniciar Venta</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Cerrar"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label>Cuenta Emisora</label>
                                                    <select name="account_sender" class="form-select" required>
                                                        <?php foreach ($all_accounts as $acc): ?>
                                                            <option value="<?php echo (int) $acc['id']; ?>">
                                                                <?php echo $acc['name']; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label>DNI del Cliente</label>
                                                    <input type="text" name="dni" class="form-control" placeholder="DNI"
                                                        pattern="[0-9]{4,}" autocomplete="off" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="add_product"
                                                    class="btn btn-primary">Continuar</button>
                                                <button type="button" class="btn btn-danger"
                                                    data-bs-dismiss="modal">Cancelar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal de Filtro de Reporte de Ventas -->
                            <div class="modal fade" id="salesReportModal" tabindex="-1" aria-labelledby="salesReportLabel"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="get" action="sales_report.php" target="_blank">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="salesReportLabel">Reporte de Ventas</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Cerrar"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Fecha Inicio</label>
                                                    <input type="date" name="start_date" class="form-control" required
                                                        value="<?php echo date('Y-m-01'); ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Fecha Fin</label>
                                                    <input type="date" name="end_date" class="form-control" required
                                                        value="<?php echo date('Y-m-t'); ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Tipo de venta</label>
                                                    <select name="sale_type" class="form-select">
                                                        <option value="all">Todos</option>
                                                        <option value="Diaria">Diaria</option>
                                                        <option value="Mayorista">Mayorista</option>
                                                        <option value="Mercado Libre">Mercado Libre</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Tipo de reporte</label>
                                                    <select name="report_type" class="form-select">
                                                        <option value="detailed">Detallado por movimientos</option>
                                                        <option value="consolidated">Consolidado por producto</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">Generar Reporte</button>
                                                <button type="button" class="btn btn-danger"
                                                    data-bs-dismiss="modal">Cancelar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- ============================================================== 
                            Modal de Registrar Llegadas
                        ============================================================== -->

                            <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceLabel"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="register_attendance.php" autocomplete="off">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="attendanceLabel">Registrar Llegada</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Cerrar"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label>DNI del Empleado</label>
                                                    <input type="text" name="dni" id="dni" class="form-control"
                                                        placeholder="DNI" pattern="[0-9]{4,}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label>Fecha y Hora</label>
                                                    <div id="live-datetime" class="form-control bg-light"></div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">Registrar</button>
                                                <button type="button" class="btn btn-danger"
                                                    data-bs-dismiss="modal">Cancelar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <script>
                                function updateDateTime() {
                                    const now = new Date();

                                    const formatted = now.toLocaleString('es-AR', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        second: '2-digit',
                                        hour12: false,
                                        timeZone: 'America/Argentina/Buenos_Aires'
                                    });

                                    document.getElementById("live-datetime").textContent = formatted;
                                }

                                setInterval(updateDateTime, 1000);
                                updateDateTime();
                            </script>

                        <?php endif; ?>