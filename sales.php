<?php
// sales.php
$page_title = 'Ventas';
require_once('includes/load.php');
page_require_level(2);

/**
 * Fechas por defecto (mes actual)
 */
$start = date("Y-m-01") . " 00:00:00";
$end = date("Y-m-t") . " 23:59:59";
$start_date = date("Y-m-01");
$end_date = date("Y-m-t");

if (isset($_POST['submit'])) {
  $start_date = !empty($_POST['start-date']) ? $_POST['start-date'] : date("Y-m-01");
  $end_date = !empty($_POST['end-date']) ? $_POST['end-date'] : date("Y-m-t");
  $start = $start_date . " 00:00:00";
  $end = $end_date . " 23:59:59";
}

$sales = find_sales($start, $end);

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

  <!-- FILTRO FECHAS -->
  <div class="col-md-12 mb-3">
    <form method="post" action="sales.php" class="row g-2 align-items-center">
      <div class="col-auto">
        <div class="input-group">
          <input type="date" class="form-control" name="start-date" value="<?php echo remove_junk($start_date); ?>"
            required>
        </div>
      </div>

      <div class="col-auto">
        <div class="input-group">
          <input type="date" class="form-control" name="end-date" value="<?php echo remove_junk($end_date); ?>"
            required>
        </div>
      </div>

      <div class="col-auto">
        <button type="submit" name="submit" class="btn btn-primary">
          Consultar
        </button>
      </div>
    </form>
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

    <table id="datatable" data-order='[[1,"desc"]]' class="table text-nowrap align-middle mb-0">
      <thead>
        <tr>
          <th class="text-center" data-orderable="false">
            <input type="checkbox" id="checkAll" class="form-check-input">
          </th>
          <th>#</th>
          <th>Cliente</th>
          <th>Tipo</th>
          <th>Total</th>
          <th>Estado</th>
          <th>Fecha</th>
          <th>Usuario Creador</th>
          <th>Usuario Gestión</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sales as $s): ?>
          <tr id="sale-row-<?php echo (int) $s['id']; ?>">
            <td class="text-center">
              <input type="checkbox" class="form-check-input item-check" data-id="<?php echo (int) $s['id']; ?>">
            </td>
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
            <td><?php echo remove_junk($s['user_status'] ?? ''); ?></td>
            <td class="text-center">
              <div class="btn-group" role="group">

                <button type="button" class="btn btn-primary btn-sm"
                  onclick="toggleSaleDetail(<?php echo (int) $s['id']; ?>)" title="Ver detalle">
                  <i class="bi bi-eye-fill"></i>
                </button>
                <!-- NUEVO BOTÓN FACTURA -->
                <a href="invoice_sale.php?id=<?php echo (int) $s['id']; ?>&download=1" target="_blank"
                  class="btn btn-secondary btn-sm" title="Ver factura">
                  <i class="bi bi-receipt"></i>
                </a>

                <?php if (($s['status'] ?? '') === 'En Validación'): ?>
                  <a href="emit_sale.php?id=<?php echo (int) $s['id']; ?>" class="btn btn-success btn-sm"
                    title="Emitir venta">
                    <i class="bi bi-box-arrow-up-right"></i>
                  </a>
                <?php endif; ?>

                <?php if ($s['status'] !== 'Cancelada'): ?>
                  <button type="button" onclick="cancelSale(<?php echo (int) $s['id']; ?>)" class="btn btn-danger btn-sm">
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

<button id="btnBulk"
  class="btn btn-success rounded-circle position-fixed align-items-center justify-content-center shadow"
  style="width:60px;height:60px;display:none;bottom:20px;right:16px;z-index:1051;" onclick="openModal();">
  <i class="bi bi-check2-square fs-4"></i>
</button>

<div class="modal fade" id="modalSend">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <form method="post" action="send_mass_email.php" id="formSend">

        <div class="modal-header">
          <h5 class="modal-title">Enviar Correos</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <input type="hidden" name="type" value="sales">

          <div class="mb-3">
            <label class="form-label">Asunto</label>
            <input type="text" name="subject" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Mensaje</label>
            <textarea name="message" id="editor"></textarea>
          </div>

          <div id="selected_ids"></div>

        </div>

        <div class="modal-footer">
          <button class="btn btn-primary">Enviar</button>
          <button class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
        </div>

      </form>
    </div>
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

  let editorInitialized = false;

  document.getElementById('modalSend').addEventListener('shown.bs.modal', function () {
    if (!editorInitialized) {
      tinymce.init({
        selector: '#editor',
        height: 300,
        menubar: false,
        plugins: 'lists',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
        branding: false
      });
      editorInitialized = true;
    }
  });

  /* ====== SELECCIÓN ====== */
  const selected = new Set();

  function toggleBulk() {
    document.getElementById('btnBulk').style.display =
      selected.size > 0 ? 'flex' : 'none';
  }

  function syncChecks() {
    document.querySelectorAll('.item-check').forEach(cb => {
      const id = cb.dataset.id;
      cb.checked = selected.has(id);
    });

    const total = document.querySelectorAll('.item-check').length;
    const checked = document.querySelectorAll('.item-check:checked').length;

    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
      checkAll.checked = (total > 0 && total === checked);
    }
  }

  /* CHECK INDIVIDUAL */
  document.addEventListener('change', function (e) {
    if (e.target.classList.contains('item-check')) {
      const id = e.target.dataset.id;

      if (e.target.checked) selected.add(id);
      else selected.delete(id);

      toggleBulk();
    }
  });

  /* SELECT ALL */
  document.getElementById('checkAll').addEventListener('change', function () {

    const checked = this.checked;

    document.querySelectorAll('.item-check').forEach(cb => {
      cb.checked = checked;

      const id = cb.dataset.id;

      if (checked) selected.add(id);
      else selected.delete(id);
    });

    toggleBulk();
  });

  /* EVITA ORDEN */
  document.getElementById('checkAll').addEventListener('click', function (e) {
    e.stopPropagation();
  });

  /* MODAL */
  function openModal() {

    const container = document.getElementById('selected_ids');
    container.innerHTML = '';

    selected.forEach(id => {
      let input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'ids[]';
      input.value = id;
      container.appendChild(input);
    });

    new bootstrap.Modal(document.getElementById('modalSend')).show();
  }

  /* DATATABLE */
  if (window.jQuery && $.fn.DataTable) {
    $('#datatable').on('draw.dt', function () {
      syncChecks();
      toggleBulk();
    });
  }

  /* TINYMCE */
  document.getElementById('formSend').addEventListener('submit', function () {
    if (tinymce.get('editor')) {
      tinymce.get('editor').save();
    }
  });
</script>