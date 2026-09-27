<aside class="left-sidebar">
  <div>
    <div class="brand-logo d-flex align-items-center justify-content-between">
      <a href="./home.php" class="text-nowrap logo-img">
        <img src="assets/images/logos/logo.svg" alt="Logo">      
      </a>
      <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
        <i class="ti ti-x fs-6"></i>
      </div>
    </div>

    <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
      <ul id="sidebarnav">

        <!-- DASHBOARD -->

        <li class="sidebar-item">
          <a class="sidebar-link" href="admin.php">
            <i class="ti ti-dashboard"></i>
            <span class="hide-menu">Inicio</span>
          </a>
        </li>


        <!-- VENDER -->

        <li class="sidebar-item">
          <a class="sidebar-link" href="#" data-bs-toggle="modal" data-bs-target="#addSaleModal">
            <i class="ti ti-cash"></i>
            <span class="hide-menu">Vender</span>
          </a>
        </li>


        <!-- VENTAS -->

        <li class="sidebar-item">
          <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)">
            <div class="d-flex align-items-center gap-3">
              <i class="ti ti-shopping-cart"></i>
              <span class="hide-menu">Ventas</span>
            </div>
          </a>

          <ul class="collapse first-level">

            <li class="sidebar-item">
              <a class="sidebar-link" href="sales.php">
                <i class="ti ti-receipt"></i>
                <span class="hide-menu">Historial</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="client.php">
                <i class="ti ti-user"></i>
                <span class="hide-menu">Clientes</span>
              </a>
            </li>
          </ul>
        </li>


        <!-- CATALOGOS -->

        <li class="sidebar-item">
          <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)">
            <div class="d-flex align-items-center gap-3">
              <i class="ti ti-database"></i>
              <span class="hide-menu">Catalogos</span>
            </div>
          </a>

          <ul class="collapse first-level">

            <li class="sidebar-item">
              <a class="sidebar-link" href="categorie.php">
                <i class="ti ti-tag"></i>
                <span class="hide-menu">Categorias</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="product.php">
                <i class="ti ti-package"></i>
                <span class="hide-menu">Productos</span>
              </a>
            </li>

          </ul>
        </li>


        <!-- INVENTARIO -->

        <li class="sidebar-item">
          <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)">
            <div class="d-flex align-items-center gap-3">
              <i class="ti ti-box"></i>
              <span class="hide-menu">Inventario</span>
            </div>
          </a>

          <ul class="collapse first-level">

            <li class="sidebar-item">
              <a class="sidebar-link" href="inventory.php">
                <i class="ti ti-plus"></i>
                <span class="hide-menu">Entradas</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="product_location.php">
                <i class="ti ti-stack"></i>
                <span class="hide-menu">Stock</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="location.php">
                <i class="ti ti-map-pin"></i>
                <span class="hide-menu">Ubicaciones</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="inventory_transfer.php">
                <i class="ti ti-arrows-transfer-up"></i>
                <span class="hide-menu">Traslados</span>
              </a>  
            </li>

          </ul>
        </li>


        <!-- FINANZAS -->

        <li class="sidebar-item">
          <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)">
            <div class="d-flex align-items-center gap-3">
              <i class="ti ti-wallet"></i>
              <span class="hide-menu">Finanzas</span>
            </div>
          </a>

          <ul class="collapse first-level">

            <li class="sidebar-item">
              <a class="sidebar-link" href="financial_accounts.php">
                <i class="ti ti-credit-card"></i>
                <span class="hide-menu">Cuentas</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="account_movements.php">
                <i class="ti ti-arrows-exchange"></i>
                <span class="hide-menu">Movimientos</span>
              </a>
            </li>

          </ul>
        </li>


        <!-- REPORTES -->

        <li class="sidebar-item">
          <a class="sidebar-link" data-bs-toggle="modal" data-bs-target="#salesReportModal">
            <i class="ti ti-report"></i>
            <span class="hide-menu">Reportes</span>
          </a>
        </li>


        <!-- ACCESOS -->

        <li class="sidebar-item">
          <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)">
            <div class="d-flex align-items-center gap-3">
              <i class="ti ti-shield-lock"></i>
              <span class="hide-menu">Accesos</span>
            </div>
          </a>

          <ul class="collapse first-level">

            <li class="sidebar-item">
              <a class="sidebar-link" href="users.php">
                <i class="ti ti-user"></i>
                <span class="hide-menu">Usuarios</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="group.php">
                <i class="ti ti-users"></i>
                <span class="hide-menu">Roles</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="accounts.php">
                <i class="ti ti-building"></i>
                <span class="hide-menu">Cuentas</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="employee.php">
                <i class="ti ti-id"></i>
                <span class="hide-menu">Empleados</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="attendance.php">
                <i class="ti ti-calendar"></i>
                <span class="hide-menu">Asistencia</span>
              </a>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link" href="#" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                <i class="ti ti-check"></i>
                <span class="hide-menu">Registrar</span>
              </a>
            </li>

          </ul>
        </li>

      </ul>
    </nav>
  </div>
</aside>