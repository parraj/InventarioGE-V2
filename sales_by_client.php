<?php
// sales_by_client.php
$page_title = 'Ventas';
require_once('includes/load.php');
page_require_level(2);

if (!isset($_GET['id']) || empty((int)$_GET['id'])) {
  $session->msg("d", "Cliente no válido.");
  redirect('clients.php', false);
}

$client_id = (int)$_GET['id'];
$client = find_by_id('clients', $client_id);

if (!$client || $client['account'] != $_SESSION['account']) {
  $session->msg("d", "El cliente no existe o no pertenece a tu cuenta.");
  redirect('clients.php', false);
}

$sales = find_sales_by_client($client_id);

/**
 * Métricas rápidas
 */
$total_sales_count = 0;
$total_sales_amount = 0;
$retail_count = 0;
$delivery_count = 0;
$ml_count = 0;

foreach ($sales as $s) {

  if (($s['status'] ?? '') === 'Cancelada') {
    continue;
  }

  $total_sales_count++;
  $total_sales_amount += (float) $s['total'];

  if (($s['sale_type'] ?? '') === 'Diaria') {
    $retail_count++;
  } elseif (($s['sale_type'] ?? '') === 'Mayorista') {
    $delivery_count++;
  } elseif (($s['sale_type'] ?? '') === 'Mercado Libre') {
    $ml_count++;
  }
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>

  <!-- CABECERA (AGREGADO) -->
  <div class="col-md-12 mb-3 d-flex justify-content-between align-items-center flex-wrap">
    <h5 class="mb-0">
      <i class="bi bi-person me-2"></i>
      Ventas del cliente: <span class="text-primary"><?php echo remove_junk($client['name']); ?></span>
    </h5>

    <a href="client.php" class="btn btn-primary btn-sm mt-2 mt-md-0">
      <i class="bi bi-arrow-left me-1"></i>Volver a clientes
    </a>
  </div>

  <!-- MÉTRICAS -->
  <div class="col-md-3 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <span class="text-muted small d-block mb-1">Total de ventas</span>
        <h3 class="mt-2 mb-0 fw-semibold"><?php echo (int) $total_sales_count; ?></h3>
      </div>
    </div>
  </div>

  <div class="col-md-3 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <span class="text-muted small d-block mb-1">Monto total</span>
        <h3 class="mt-2 mb-0 fw-semibold">$<?php echo number_format($total_sales_amount, 2, ',', '.'); ?></h3>
      </div>
    </div>
  </div>

  <div class="col-md-2 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <span class="text-muted small d-block mb-1">Diaria</span>
        <h4 class="mt-2 mb-0 fw-semibold"><?php echo (int) $retail_count; ?></h4>
      </div>
    </div>
  </div>

  <div class="col-md-2 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <span class="text-muted small d-block mb-1">Mayorista</span>
        <h4 class="mt-2 mb-0 fw-semibold"><?php echo (int) $delivery_count; ?></h4>
      </div>
    </div>
  </div>

  <div class="col-md-2 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <span class="text-muted small d-block mb-1">Mercado Libre</span>
        <h4 class="mt-2 mb-0 fw-semibold"><?php echo (int) $ml_count; ?></h4>
      </div>
    </div>
  </div>

  <!-- TABLA DE VENTAS -->
  <div class="col-md-12 table-responsive">
    <h5 class="mb-3">
      <i class="bi bi-cart-check"></i> Ventas
    </h5>

    <table id="datatable" class="table text-nowrap align-middle mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Cliente</th>
          <th>Tipo</th>
          <th>Total</th>
          <th>Estado</th>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sales as $s): ?>
          <tr id="sale-row-<?php echo (int) $s['id']; ?>">
            <td><?php echo (int) $s['id']; ?></td>
            <td><?php echo remove_junk($s['client_name'] ?? ''); ?></td>
            <td><?php echo remove_junk($s['sale_type'] ?? ''); ?></td>
            <td>$<?php echo number_format((float) $s['total'], 2, ',', '.'); ?></td>
            <td>
              <?php
              $status = remove_junk($s['status'] ?? '');
              $badge_class = 'bg-danger';

              if ($status === 'En Validación') {
                $badge_class = 'bg-primary';
              } elseif ($status === 'Emitida') {
                $badge_class = 'bg-success';
              }
              ?>
              <span class="badge <?php echo $badge_class; ?>">
                <?php echo $status; ?>
              </span>
            </td>
            <td><?php echo read_date($s['date']); ?></td>
            <td><?php echo remove_junk($s['user'] ?? ''); ?></td>
            <td class="text-center">
              <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary btn-sm"
                  onclick="toggleSaleDetail(<?php echo (int) $s['id']; ?>)" title="Ver detalle">
                  <i class="bi bi-eye-fill"></i>
                </button>
                 <!-- NUEVO BOTÓN FACTURA -->
                <a href="invoice_sale.php?id=<?php echo (int) $s['id']; ?>&download=1" target="_blank" class="btn btn-secondary btn-sm"
                  title="Ver factura">
                  <i class="bi bi-receipt"></i>
                </a>
                <?php if (($s['status'] ?? '') === 'En Validación'): ?>
                  <a href="emit_sale.php?id=<?php echo (int) $s['id']; ?>" class="btn btn-success btn-sm"
                    title="Emitir venta">
                    <i class="bi bi-box-arrow-up-right"></i>
                  </a>
                <?php endif; ?>
                <?php if ($s['status'] !== 'Cancelada'): ?>
                  <button type="button"
                          onclick="cancelSale(<?php echo (int)$s['id']; ?>)"
                          class="btn btn-danger btn-sm">
                      <i class="bi bi-trash-fill"></i>
                  </button>
              <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<script>
function cancelSale(id) {
    Swal.fire({
        icon: "warning",
        title: "¿Estás seguro de cancelar esta venta?",
        showCancelButton: true,
        confirmButtonText: "Sí, cancelar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#0d6efd",
        cancelButtonColor: "#dc3545",
        width: '350px',
        customClass: {
            title: 'swal-title-sm',
            content: 'swal-content-sm',
            confirmButton: 'swal-btn-sm',
            cancelButton: 'swal-btn-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = 'cancel_sale.php?id=' + id;
        }
    });
}
</script>