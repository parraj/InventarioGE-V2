<?php
$page_title = 'Clientes';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(2);

// Obtener clientes con resumen de ventas
$clients = find_all_clients_with_sales_summary();
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="table-responsive">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="bi bi-person me-2"></i>Listado de Clientes
            </h5>

            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse"
                data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                <i class="bi bi-funnel me-1"></i>Filtros
            </button>
        </div>

        <div class="collapse mb-3" id="filtersCollapse">
            <div class="card card-body">
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <label class="form-label">DNI</label>
                        <input type="text" id="filterDni" class="form-control" placeholder="Filtrar DNI">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Nombre</label>
                        <input type="text" id="filterName" class="form-control" placeholder="Filtrar nombre">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Teléfono</label>
                        <input type="text" id="filterPhone" class="form-control" placeholder="Filtrar teléfono">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Correo</label>
                        <input type="text" id="filterMail" class="form-control" placeholder="Filtrar correo">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Ventas mín.</label>
                        <input type="number" id="filterSalesMin" class="form-control" placeholder="0">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Ventas máx.</label>
                        <input type="number" id="filterSalesMax" class="form-control" placeholder="999999">
                    </div>

                    <div class="col-md-3 mb-2">
                        <label class="form-label">Total vendido mín.</label>
                        <input type="number" id="filterAmountMin" class="form-control" placeholder="0">
                    </div>

                    <div class="col-md-3 mb-2">
                        <label class="form-label">Total vendido máx.</label>
                        <input type="number" id="filterAmountMax" class="form-control" placeholder="999999">
                    </div>

                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="button" id="clearClientFilters" class="btn btn-primary w-100">
                            Limpiar filtros
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <table id="datatable" data-order='[[1,"desc"]]' class="table text-nowrap mb-0 align-middle">
            <thead>
                <tr>
                    <th class="text-center" data-orderable="false">
                        <input type="checkbox" id="checkAll" class="form-check-input">
                    </th>
                    <th class="text-center" style="width: 10px;">#</th>
                    <th>DNI</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Dirección</th>
                    <th class="text-center">Ventas</th>
                    <th class="text-end">Total Vendido</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input item-check"
                                data-id="<?php echo (int) $client['id']; ?>">
                        </td>
                        <td class="text-center"><?php echo remove_junk($client['id']); ?></td>
                        <td><?php echo remove_junk($client['dni']); ?></td>
                        <td><?php echo remove_junk($client['name']); ?></td>
                        <td><?php echo remove_junk($client['phone']); ?></td>
                        <td><?php echo remove_junk($client['mail']); ?></td>
                        <td><?php echo nl2br(remove_junk($client['address'])); ?></td>
                        <td><?php echo (int) $client['total_sales']; ?></td>
                        <td>$<?php echo number_format((float) $client['total_sales_amount'], 2, ',', '.'); ?></td>
                        <td><?php echo read_date($client['date']); ?></td>

                        <td class="text-center">
                            <div class="btn-group">
                                <a href="sales_by_client.php?id=<?php echo (int) $client['id']; ?>"
                                    class="btn btn-success btn-sm" title="Ver ventas">
                                    <i class="bi bi-cart-check"></i>
                                </a>

                                <a href="edit_client.php?id=<?php echo (int) $client['id']; ?>"
                                    class="btn btn-primary btn-sm" title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <button onclick="delet(<?php echo (int) $client['id']; ?>)" class="btn btn-danger btn-sm"
                                    title="Eliminar">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Botón flotante -->
    <button id="btnFlotante"
        class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 d-flex align-items-center justify-content-center shadow"
        style="width: 60px; height: 60px;" data-bs-toggle="modal" data-bs-target="#addclient">
        <i class="bi bi-plus fs-3"></i>
    </button>

    <button id="btnBulk"
        class="btn btn-success rounded-circle position-fixed align-items-center justify-content-center shadow"
        style="width:60px;height:60px;display:none;bottom:92px;right:16px;z-index:1051;" onclick="openModal();">
        <i class="bi bi-check2-square fs-4"></i>
    </button>
</div>

<?php include_once('layouts/footer.php'); ?>

<!-- Modal Add Client -->
<div class="modal fade" id="addclient" tabindex="-1" aria-labelledby="addClientLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="add_client.php" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClientLabel">
                        <i class="bi bi-person-plus me-2"></i>Crear Cliente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="dni" class="form-label">DNI</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" class="form-control" name="dni" placeholder="DNI">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre Completo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="name" placeholder="Nombre">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" class="form-control" name="phone" placeholder="Teléfono">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="mail" class="form-label">Correo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" name="mail" placeholder="Correo">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <div class="input-group">
                            <span class="input-group-text align-items-start pt-2">
                                <i class="bi bi-geo-alt"></i>
                            </span>
                            <textarea class="form-control" name="address" id="address" rows="4"
                                style="resize: none; white-space: pre-wrap;"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_client" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSend">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="post" action="send_mass_email.php" id="formSend">

                <div class="modal-header">
                    <h5 class="modal-title">Enviar Correos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="type" value="clients">

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

<!-- SweetAlert eliminar -->
<script>
    function delet(id) {
        Swal.fire({
            icon: "warning",
            title: "¿Estás seguro de eliminar este cliente?",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
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
                window.location = 'delete_client.php?id=' + id;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const address = document.getElementById('address');
        if (address && address.value.trim() === '') {
            address.value = "Dirección:\nLocalidad:\nCódigo Postal:";
        }

        let table = $('#datatable').DataTable();

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'datatable') {
                return true;
            }

            const dniFilter = document.getElementById('filterDni').value.trim().toLowerCase();
            const nameFilter = document.getElementById('filterName').value.trim().toLowerCase();
            const phoneFilter = document.getElementById('filterPhone').value.trim().toLowerCase();
            const mailFilter = document.getElementById('filterMail').value.trim().toLowerCase();

            const salesMin = document.getElementById('filterSalesMin').value;
            const salesMax = document.getElementById('filterSalesMax').value;
            const amountMin = document.getElementById('filterAmountMin').value;
            const amountMax = document.getElementById('filterAmountMax').value;

            //  índices corregidos (por el checkbox nuevo)
            const rowDni = (data[2] || '').toLowerCase();
            const rowName = (data[3] || '').toLowerCase();
            const rowPhone = (data[4] || '').toLowerCase();
            const rowMail = (data[5] || '').toLowerCase();

            const rowSales = parseFloat(
                (data[7] || '0')
                    .toString()
                    .replace(/\./g, '')
                    .replace(',', '.')
            ) || 0;

            const rowAmount = parseFloat(
                (data[8] || '0')
                    .toString()
                    .replace('$', '')
                    .replace(/\./g, '')
                    .replace(',', '.')
                    .trim()
            ) || 0;

            if (dniFilter !== '' && !rowDni.includes(dniFilter)) return false;
            if (nameFilter !== '' && !rowName.includes(nameFilter)) return false;
            if (phoneFilter !== '' && !rowPhone.includes(phoneFilter)) return false;
            if (mailFilter !== '' && !rowMail.includes(mailFilter)) return false;

            if (salesMin !== '' && rowSales < parseFloat(salesMin)) return false;
            if (salesMax !== '' && rowSales > parseFloat(salesMax)) return false;

            if (amountMin !== '' && rowAmount < parseFloat(amountMin)) return false;
            if (amountMax !== '' && rowAmount > parseFloat(amountMax)) return false;

            return true;
        });

        const filterIds = [
            'filterDni',
            'filterName',
            'filterPhone',
            'filterMail',
            'filterSalesMin',
            'filterSalesMax',
            'filterAmountMin',
            'filterAmountMax'
        ];

        filterIds.forEach(function (id) {
            const element = document.getElementById(id);

            if (element) {
                element.addEventListener('input', function () {
                    table.draw();
                });

                element.addEventListener('change', function () {
                    table.draw();
                });
            }
        });

        document.getElementById('clearClientFilters').addEventListener('click', function () {
            filterIds.forEach(function (id) {
                const element = document.getElementById(id);
                if (element) {
                    element.value = '';
                }
            });

            table.draw();
        });
    });

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

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('item-check')) {
            const id = e.target.dataset.id;

            if (e.target.checked) selected.add(id);
            else selected.delete(id);

            toggleBulk();
        }
    });

    if (window.jQuery && $.fn.DataTable) {
        $('#datatable').on('draw.dt', function () {
            syncChecks();
            toggleBulk();
        });
    }

    document.getElementById('formSend').addEventListener('submit', function () {
        if (tinymce.get('editor')) {
            tinymce.get('editor').save();
        }
    });

    // SELECT ALL
    document.getElementById('checkAll').addEventListener('change', function () {

        const checked = this.checked;

        document.querySelectorAll('.item-check').forEach(cb => {
            cb.checked = checked;

            const id = cb.dataset.id;

            if (checked) {
                selected.add(id);
            } else {
                selected.delete(id);
            }
        });

        toggleBulk();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('item-check')) {

            const total = document.querySelectorAll('.item-check').length;
            const checked = document.querySelectorAll('.item-check:checked').length;

            document.getElementById('checkAll').checked = (total === checked);
        }
    });
</script>